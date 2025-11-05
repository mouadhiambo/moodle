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
        
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_USERPWD, $this->consumerkey . ':' . $this->consumersecret);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        
        $result = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($status == 200) {
            $result = json_decode($result);
            return $result->access_token ?? false;
        }
        
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
        
        $accesstoken = $this->get_access_token();
        if (!$accesstoken) {
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
        curl_close($curl);
        
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
            return $response;
        }
        
        return false;
    }
    
    /**
     * Query STK Push transaction status
     *
     * @param string $checkoutrequestid Checkout Request ID
     * @return object|false Response object or false on failure
     */
    public function query_stk_status($checkoutrequestid) {
        $accesstoken = $this->get_access_token();
        if (!$accesstoken) {
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
        
        if (empty($this->consumerkey)) {
            $errors[] = get_string('error_missing_consumerkey', 'local_rvscertificate');
        }
        
        if (empty($this->consumersecret)) {
            $errors[] = get_string('error_missing_consumersecret', 'local_rvscertificate');
        }
        
        if (empty($this->shortcode)) {
            $errors[] = get_string('error_missing_shortcode', 'local_rvscertificate');
        }
        
        if (empty($this->passkey)) {
            $errors[] = get_string('error_missing_passkey', 'local_rvscertificate');
        }
        
        if (empty($this->callbackurl)) {
            $errors[] = get_string('error_missing_callbackurl', 'local_rvscertificate');
        }
        
        return $errors;
    }
}
