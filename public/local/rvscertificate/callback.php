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
 * M-Pesa Daraja API Callback Handler
 *
 * @package    local_rvscertificate
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Files.RequireLogin.Missing
// No login required for callback.

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/rvscertificate/classes/certificate_generator.php');

// Get the JSON data from M-Pesa
$mpesaresponse = file_get_contents('php://input');

// Log the raw callback
$log = new stdClass();
$log->type = 'callback_raw';
$log->response = $mpesaresponse;
$log->timecreated = time();
$DB->insert_record('local_rvscertificate_logs', $log);

// Decode the response
$data = json_decode($mpesaresponse, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid JSON']);
    exit;
}

// Extract the callback data
if (isset($data['Body']['stkCallback'])) {
    $callback = $data['Body']['stkCallback'];
    
    $merchantrequestid = $callback['MerchantRequestID'] ?? null;
    $checkoutrequestid = $callback['CheckoutRequestID'] ?? null;
    $resultcode = $callback['ResultCode'] ?? null;
    $resultdesc = $callback['ResultDesc'] ?? null;
    
    // Find the payment record
    $payment = $DB->get_record('local_rvscertificate_payments', [
        'checkoutrequestid' => $checkoutrequestid
    ]);
    
    if (!$payment) {
        // Log error
        $log = new stdClass();
        $log->type = 'callback_error';
        $log->response = $mpesaresponse;
        $log->resultcode = $resultcode;
        $log->resultdesc = 'Payment record not found';
        $log->timecreated = time();
        $DB->insert_record('local_rvscertificate_logs', $log);
        
        http_response_code(200);
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        exit;
    }
    
    // Update payment record with callback info
    $log = new stdClass();
    $log->paymentid = $payment->id;
    $log->type = 'callback';
    $log->response = $mpesaresponse;
    $log->resultcode = $resultcode;
    $log->resultdesc = $resultdesc;
    $log->timecreated = time();
    $DB->insert_record('local_rvscertificate_logs', $log);
    
    if ($resultcode == 0) {
        // Payment successful
        $callbackmetadata = $callback['CallbackMetadata']['Item'] ?? [];
        
        $mpesareceiptnumber = null;
        $transactiondate = null;
        $phonenumber = null;
        
        foreach ($callbackmetadata as $item) {
            if ($item['Name'] == 'MpesaReceiptNumber') {
                $mpesareceiptnumber = $item['Value'];
            }
            if ($item['Name'] == 'TransactionDate') {
                $transactiondate = $item['Value'];
            }
            if ($item['Name'] == 'PhoneNumber') {
                $phonenumber = $item['Value'];
            }
        }
        
        // Update payment record
        $payment->status = 'completed';
        $payment->mpesareceiptnumber = $mpesareceiptnumber;
        
        if ($transactiondate) {
            // Convert YYYYMMDDHHMMSS to timestamp
            $dt = DateTime::createFromFormat('YmdHis', $transactiondate);
            if ($dt) {
                $payment->transactiondate = $dt->getTimestamp();
            }
        }
        
        $payment->timemodified = time();
        $DB->update_record('local_rvscertificate_payments', $payment);
        
        // Generate certificate and send email
        try {
            $generator = new \local_rvscertificate\certificate_generator();
            $generator->process_payment($payment->id);
        } catch (Exception $e) {
            // Log the error but don't fail the callback
            $errorlog = new stdClass();
            $errorlog->paymentid = $payment->id;
            $errorlog->type = 'certificate_generation_error';
            $errorlog->resultdesc = $e->getMessage();
            $errorlog->timecreated = time();
            $DB->insert_record('local_rvscertificate_logs', $errorlog);
        }
        
    } else {
        // Payment failed or cancelled
        $payment->status = 'failed';
        $payment->timemodified = time();
        $DB->update_record('local_rvscertificate_payments', $payment);
    }
}

// Send success response to M-Pesa
http_response_code(200);
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
