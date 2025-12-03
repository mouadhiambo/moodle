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
 * M-Pesa callback handler for RVS Payment availability condition.
 *
 * This endpoint receives payment confirmations from M-Pesa Daraja API
 * and updates the payment status accordingly.
 *
 * @package    availability_rvspayment
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Files.RequireLogin.Missing
// No login required for callback.

require_once(__DIR__ . '/../../../config.php');

// Get the JSON data from M-Pesa.
$mpesaresponse = file_get_contents('php://input');

// Log the raw callback.
$log = new stdClass();
$log->type = 'callback_raw';
$log->response = $mpesaresponse;
$log->timecreated = time();
$DB->insert_record('availability_rvspayment_log', $log);

// Decode the response.
$data = json_decode($mpesaresponse, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid JSON']);
    exit;
}

// Extract the callback data.
if (isset($data['Body']['stkCallback'])) {
    $callback = $data['Body']['stkCallback'];
    
    $merchantrequestid = $callback['MerchantRequestID'] ?? null;
    $checkoutrequestid = $callback['CheckoutRequestID'] ?? null;
    $resultcode = $callback['ResultCode'] ?? null;
    $resultdesc = $callback['ResultDesc'] ?? null;
    
    // Find the payment record.
    $payment = $DB->get_record('availability_rvspayment_pay', [
        'checkoutrequestid' => $checkoutrequestid,
    ]);
    
    if (!$payment) {
        // Log error.
        $log = new stdClass();
        $log->type = 'callback_error';
        $log->response = $mpesaresponse;
        $log->resultcode = $resultcode;
        $log->resultdesc = 'Payment record not found for CheckoutRequestID: ' . $checkoutrequestid;
        $log->timecreated = time();
        $DB->insert_record('availability_rvspayment_log', $log);
        
        http_response_code(200);
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        exit;
    }
    
    // Log the callback with payment ID.
    $log = new stdClass();
    $log->paymentid = $payment->id;
    $log->type = 'callback';
    $log->response = $mpesaresponse;
    $log->resultcode = $resultcode;
    $log->resultdesc = $resultdesc;
    $log->timecreated = time();
    $DB->insert_record('availability_rvspayment_log', $log);
    
    if ($resultcode == 0) {
        // Payment successful.
        $callbackmetadata = $callback['CallbackMetadata']['Item'] ?? [];
        
        $mpesareceiptnumber = null;
        $transactiondate = null;
        $phonenumber = null;
        $amount = null;
        
        foreach ($callbackmetadata as $item) {
            switch ($item['Name']) {
                case 'MpesaReceiptNumber':
                    $mpesareceiptnumber = $item['Value'];
                    break;
                case 'TransactionDate':
                    $transactiondate = $item['Value'];
                    break;
                case 'PhoneNumber':
                    $phonenumber = $item['Value'];
                    break;
                case 'Amount':
                    $amount = $item['Value'];
                    break;
            }
        }
        
        // Update payment record.
        $payment->status = 'completed';
        $payment->mpesareceiptnumber = $mpesareceiptnumber;
        
        if ($transactiondate) {
            // Convert YYYYMMDDHHMMSS to timestamp.
            $dt = DateTime::createFromFormat('YmdHis', $transactiondate);
            if ($dt) {
                $payment->transactiondate = $dt->getTimestamp();
            }
        }
        
        $payment->timemodified = time();
        $DB->update_record('availability_rvspayment_pay', $payment);
        
        // Send notification to user.
        try {
            send_payment_notification($payment);
        } catch (Exception $e) {
            // Log the error but don't fail the callback.
            $errorlog = new stdClass();
            $errorlog->paymentid = $payment->id;
            $errorlog->type = 'notification_error';
            $errorlog->resultdesc = $e->getMessage();
            $errorlog->timecreated = time();
            $DB->insert_record('availability_rvspayment_log', $errorlog);
        }
        
        // Purge availability caches for this course so the unlock takes effect immediately.
        \cache_helper::purge_by_definition('core', 'coursemodinfo');
        
    } else {
        // Payment failed or cancelled.
        $payment->status = 'failed';
        $payment->timemodified = time();
        $DB->update_record('availability_rvspayment_pay', $payment);
    }
}

// Send success response to M-Pesa.
http_response_code(200);
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);

/**
 * Send a notification to the user about their successful payment.
 *
 * @param stdClass $payment The payment record
 */
function send_payment_notification($payment) {
    global $DB;
    
    $user = $DB->get_record('user', ['id' => $payment->userid], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $payment->courseid], '*', MUST_EXIST);
    
    // Get item name.
    $itemname = '';
    if ($payment->itemtype === 'section') {
        $section = $DB->get_record('course_sections', ['id' => $payment->itemid]);
        if ($section) {
            if (!empty($section->name)) {
                $itemname = 'Section ' . $section->section . ': ' . $section->name;
            } else {
                $itemname = 'Section ' . $section->section;
            }
        }
    } else {
        $cm = $DB->get_record('course_modules', ['id' => $payment->itemid]);
        if ($cm) {
            $modinfo = get_fast_modinfo($course);
            if (isset($modinfo->cms[$cm->id])) {
                $itemname = $modinfo->cms[$cm->id]->name;
            }
        }
    }
    
    // Build message.
    $a = new stdClass();
    $a->currency = $payment->currency;
    $a->amount = number_format($payment->amount, 2);
    $a->itemname = $itemname;
    $a->coursename = $course->fullname;
    
    $subject = get_string('notification_subject', 'availability_rvspayment', $itemname);
    $message = get_string('notification_body', 'availability_rvspayment', $a);
    
    // Send using Moodle's messaging system.
    $eventdata = new \core\message\message();
    $eventdata->component = 'availability_rvspayment';
    $eventdata->name = 'payment_success';
    $eventdata->userfrom = \core_user::get_noreply_user();
    $eventdata->userto = $user;
    $eventdata->subject = $subject;
    $eventdata->fullmessage = strip_tags($message);
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml = $message;
    $eventdata->smallmessage = $subject;
    $eventdata->notification = 1;
    $eventdata->contexturl = new moodle_url('/course/view.php', ['id' => $course->id]);
    $eventdata->contexturlname = $course->fullname;
    
    message_send($eventdata);
}
