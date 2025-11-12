<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * M-Pesa Daraja API Client
 *
 * @package    local_rvscertificate
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rvscertificate;

defined('MOODLE_INTERNAL') || die();

/**
 * M-Pesa API Client class
 */
class mpesa_client {
    
    /** @var string Environment (sandbox/production) */
    private $environment;
    
    /** @var string Consumer key */
    private $consumerkey;
    
    /** @var string Consumer secret */
    private $consumersecret;
    
    /** @var string Business shortcode */
    private $shortcode;
    
    /** @var string Passkey */
    private $passkey;
    
    /** @var string Callback URL */
    private $callbackurl;
    
    /** @var string Base URL for API */
    private $baseurl;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->environment = get_config('local_rvscertificate', 'mpesa_environment') ?: 'sandbox';
        $this->consumerkey = get_config('local_rvscertificate', 'mpesa_consumer_key');
        $this->consumersecret = get_config('local_rvscertificate', 'mpesa_consumer_secret');
        $this->shortcode = get_config('local_rvscertificate', 'mpesa_shortcode');
        $this->passkey = get_config('local_rvscertificate', 'mpesa_passkey');
        $this->callbackurl = get_config('local_rvscertificate', 'mpesa_callback_url');
        
        if ($this->environment === 'production') {
            $this->baseurl = 'https://api.safaricom.co.ke';
        } else {
            $this->baseurl = 'https://sandbox.safaricom.co.ke';
        }
    }
    
    /**
     * Get OAuth access token
     *
     * @return string|false Access token or false on failure
     */
    private function get_access_token() {
        $url = $this->baseurl . '/oauth/v1/generate?grant_type=client_credentials';
        
        debugging('M-Pesa: Requesting OAuth token from: ' . $url, DEBUG_DEVELOPER);
        
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_USERPWD, $this->consumerkey . ':' . $this->consumersecret);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        
        $result = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($curl);
        curl_close($curl);
        
        debugging('M-Pesa: OAuth response status: ' . $status, DEBUG_DEVELOPER);
        debugging('M-Pesa: OAuth response: ' . $result, DEBUG_DEVELOPER);
        
        if ($curl_error) {
            debugging('M-Pesa: OAuth cURL error: ' . $curl_error, DEBUG_DEVELOPER);
            mtrace('M-Pesa OAuth Error: ' . $curl_error);
            return false;
        }
        
        if ($status == 200) {
            $result = json_decode($result);
            if (isset($result->access_token)) {
                debugging('M-Pesa: OAuth token obtained successfully', DEBUG_DEVELOPER);
                return $result->access_token;
            } else {
                debugging('M-Pesa: OAuth token not found in response', DEBUG_DEVELOPER);
                mtrace('M-Pesa OAuth Error: Token not found in response');
                return false;
            }
        }
        
        debugging('M-Pesa: OAuth failed with status ' . $status, DEBUG_DEVELOPER);
        mtrace('M-Pesa OAuth Error: HTTP status ' . $status . ' - ' . $result);
        return false;
    }
    
    /**
     * Generate password for STK Push
     *
     * @return string Base64 encoded password
     */
    private function generate_password() {
        $timestamp = date('YmdHis');
        return base64_encode($this->shortcode . $this->passkey . $timestamp);
    }
    
    /**
     * Get timestamp
     *
     * @return string Timestamp in YmdHis format
     */
    private function get_timestamp() {
        return date('YmdHis');
    }
    
    /**
     * Format phone number to required format (254XXXXXXXXX)
     *
     * @param string $phone Phone number
     * @return string Formatted phone number
     */
    public function format_phone_number($phone) {
        // Remove any spaces, dashes, or plus signs
        $phone = preg_replace('/[\s\-\+]/', '', $phone);
        
        // If it starts with 0, replace with 254
        if (substr($phone, 0, 1) === '0') {
            $phone = '254' . substr($phone, 1);
        }
        
        // If it doesn't start with 254, prepend it
        if (substr($phone, 0, 3) !== '254') {
            $phone = '254' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Initiate STK Push request
     *
     * @param string $phone Phone number
     * @param float $amount Amount to charge
     * @param string $accountref Account reference
     * @param string $description Transaction description
     * @return object|false Response object or false on failure
     */
    public function stk_push($phone, $amount, $accountref, $description) {
        global $DB;
        
        debugging('M-Pesa: Initiating STK Push for phone: ' . $phone . ', amount: ' . $amount, DEBUG_DEVELOPER);
        
        $accesstoken = $this->get_access_token();
        if (!$accesstoken) {
            debugging('M-Pesa: STK Push failed - could not get access token', DEBUG_DEVELOPER);
            mtrace('M-Pesa STK Push Error: Failed to obtain access token');
            return false;
        }
        
        $phone = $this->format_phone_number($phone);
        $timestamp = $this->get_timestamp();
        $password = $this->generate_password();
        
        $url = $this->baseurl . '/mpesa/stkpush/v1/processrequest';
        
        $curl_post_data = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int)$amount,
            'PartyA' => $phone,
            'PartyB' => $this->shortcode,
            'PhoneNumber' => $phone,
            'CallBackURL' => $this->callbackurl,
            'AccountReference' => $accountref,
            'TransactionDesc' => $description
        ];
        
        $data_string = json_encode($curl_post_data);
        
        debugging('M-Pesa: STK Push request URL: ' . $url, DEBUG_DEVELOPER);
        debugging('M-Pesa: STK Push request data: ' . $data_string, DEBUG_DEVELOPER);
        
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type:application/json',
            'Authorization:Bearer ' . $accesstoken
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        
        $result = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($curl);
        curl_close($curl);
        
        debugging('M-Pesa: STK Push response status: ' . $status, DEBUG_DEVELOPER);
        debugging('M-Pesa: STK Push response: ' . $result, DEBUG_DEVELOPER);
        
        if ($curl_error) {
            debugging('M-Pesa: STK Push cURL error: ' . $curl_error, DEBUG_DEVELOPER);
            mtrace('M-Pesa STK Push Error: ' . $curl_error);
        }
        
        $response = json_decode($result);
        
        // Log the transaction
        $log = new \stdClass();
        $log->type = 'stkpush';
        $log->request = $data_string;
        $log->response = $result;
        $log->resultcode = $response->ResponseCode ?? null;
        $log->resultdesc = $response->ResponseDescription ?? null;
        $log->timecreated = time();
        $DB->insert_record('local_rvscertificate_logs', $log);
        
        if ($status == 200 && isset($response->ResponseCode) && $response->ResponseCode == '0') {
            debugging('M-Pesa: STK Push successful - CheckoutRequestID: ' . ($response->CheckoutRequestID ?? 'N/A'), DEBUG_DEVELOPER);
            mtrace('M-Pesa STK Push: Successfully initiated payment request');
            return $response;
        }
        
        $error_msg = 'M-Pesa STK Push failed - Status: ' . $status;
        if (isset($response->ResponseDescription)) {
            $error_msg .= ', Description: ' . $response->ResponseDescription;
        }
        if (isset($response->errorMessage)) {
            $error_msg .= ', Error: ' . $response->errorMessage;
        }
        
        debugging('M-Pesa: ' . $error_msg, DEBUG_DEVELOPER);
        mtrace($error_msg);
        
        return false;
    }
    
    /**
     * Query STK Push transaction status
     *
     * @param string $checkoutrequestid Checkout Request ID
     * @return object|false Response object or false on failure
     */
    public function query_stk_status($checkoutrequestid) {
        debugging('M-Pesa: Querying STK status for CheckoutRequestID: ' . $checkoutrequestid, DEBUG_DEVELOPER);
        
        $accesstoken = $this->get_access_token();
        if (!$accesstoken) {
            debugging('M-Pesa: STK Query failed - could not get access token', DEBUG_DEVELOPER);
            mtrace('M-Pesa STK Query Error: Failed to obtain access token');
            return false;
        }
        
        $timestamp = $this->get_timestamp();
        $password = $this->generate_password();
        
        $url = $this->baseurl . '/mpesa/stkpushquery/v1/query';
        
        $curl_post_data = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutrequestid
        ];
        
        $data_string = json_encode($curl_post_data);
        
        debugging('M-Pesa: STK Query request URL: ' . $url, DEBUG_DEVELOPER);
        debugging('M-Pesa: STK Query request data: ' . $data_string, DEBUG_DEVELOPER);
        
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type:application/json',
            'Authorization:Bearer ' . $accesstoken
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        
        $result = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($curl);
        
        debugging('M-Pesa: STK Query response status: ' . $status, DEBUG_DEVELOPER);
        debugging('M-Pesa: STK Query response: ' . $result, DEBUG_DEVELOPER);
        
        if ($curl_error) {
            debugging('M-Pesa: STK Query cURL error: ' . $curl_error, DEBUG_DEVELOPER);
            mtrace('M-Pesa STK Query Error: ' . $curl_error);
        }
        
        $result = curl_exec($curl);
        curl_close($curl);
        
        return json_decode($result);
    }
    
    /**
     * Validate configuration
     *
     * @return array Array of validation errors (empty if valid)
     */
    public function validate_config() {
        $errors = [];
        
        debugging('M-Pesa: Validating configuration', DEBUG_DEVELOPER);
        
        if (empty($this->consumerkey)) {
            $error = get_string('error_missing_consumerkey', 'local_rvscertificate');
            $errors[] = $error;
            debugging('M-Pesa Config Error: ' . $error, DEBUG_DEVELOPER);
        }
        
        if (empty($this->consumersecret)) {
            $error = get_string('error_missing_consumersecret', 'local_rvscertificate');
            $errors[] = $error;
            debugging('M-Pesa Config Error: ' . $error, DEBUG_DEVELOPER);
        }
        
        if (empty($this->shortcode)) {
            $error = get_string('error_missing_shortcode', 'local_rvscertificate');
            $errors[] = $error;
            debugging('M-Pesa Config Error: ' . $error, DEBUG_DEVELOPER);
        }
        
        if (empty($this->passkey)) {
            $error = get_string('error_missing_passkey', 'local_rvscertificate');
            $errors[] = $error;
            debugging('M-Pesa Config Error: ' . $error, DEBUG_DEVELOPER);
        }
        
        if (empty($this->callbackurl)) {
            $error = get_string('error_missing_callbackurl', 'local_rvscertificate');
            $errors[] = $error;
            debugging('M-Pesa Config Error: ' . $error, DEBUG_DEVELOPER);
        }
        
        if (empty($errors)) {
            debugging('M-Pesa: Configuration is valid', DEBUG_DEVELOPER);
            debugging('M-Pesa: Environment - ' . $this->environment, DEBUG_DEVELOPER);
            debugging('M-Pesa: Base URL - ' . $this->baseurl, DEBUG_DEVELOPER);
            debugging('M-Pesa: Shortcode - ' . $this->shortcode, DEBUG_DEVELOPER);
            debugging('M-Pesa: Callback URL - ' . $this->callbackurl, DEBUG_DEVELOPER);
        } else {
            mtrace('M-Pesa Configuration Errors: ' . implode(', ', $errors));
        }
        
        return $errors;
    }
}
