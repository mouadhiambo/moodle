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
 * Process certificate payment request
 *
 * @package    local_rvscertificate
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/completionlib.php');

$courseid = required_param('courseid', PARAM_INT);
$phone = required_param('phone', PARAM_TEXT);

require_sesskey();

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

require_login($course);
$context = context_course::instance($courseid);
require_capability('local/rvscertificate:request', $context);

$PAGE->set_url('/local/rvscertificate/request.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('mycertificate', 'local_rvscertificate'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('course');

// Validate course completion
if (!local_rvscertificate_is_course_completed($USER->id, $courseid)) {
    redirect(
        new moodle_url('/course/view.php', ['id' => $courseid]),
        get_string('coursenotcompleted', 'local_rvscertificate'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

// Check if already paid
if (local_rvscertificate_has_paid($USER->id, $courseid)) {
    redirect(
        new moodle_url('/local/rvscertificate/index.php', ['courseid' => $courseid]),
        get_string('alreadypaid', 'local_rvscertificate'),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

// Check for pending payment
$pendingpayment = $DB->get_record('local_rvscertificate_payments', [
    'userid' => $USER->id,
    'courseid' => $courseid,
    'status' => 'pending'
]);

if ($pendingpayment) {
    redirect(
        new moodle_url('/local/rvscertificate/index.php', ['courseid' => $courseid]),
        get_string('paymentpending', 'local_rvscertificate'),
        null,
        \core\output\notification::NOTIFY_INFO
    );
}

// Initialize M-Pesa client
$mpesa = new \local_rvscertificate\mpesa_client();

// Validate M-Pesa configuration
$errors = $mpesa->validate_config();
if (!empty($errors)) {
    echo $OUTPUT->header();
    foreach ($errors as $error) {
        echo $OUTPUT->notification($error, 'error');
    }
    echo html_writer::link(
        new moodle_url('/local/rvscertificate/index.php', ['courseid' => $courseid]),
        get_string('back')
    );
    echo $OUTPUT->footer();
    exit;
}

// Get price
$price = local_rvscertificate_get_price();

// Create payment record
$payment = new stdClass();
$payment->userid = $USER->id;
$payment->courseid = $courseid;
$payment->amount = $price;
$payment->phone = $mpesa->format_phone_number($phone);
$payment->status = 'pending';
$payment->certificateissued = 0;
$payment->emailsent = 0;
$payment->timecreated = time();
$payment->timemodified = time();

$paymentid = $DB->insert_record('local_rvscertificate_payments', $payment);

// Initiate STK Push
$accountref = 'CERT-' . $courseid . '-' . $USER->id;
$description = get_string('paymentdescription', 'local_rvscertificate', $course->shortname);

$response = $mpesa->stk_push(
    $payment->phone,
    $price,
    $accountref,
    $description
);

if ($response && isset($response->MerchantRequestID)) {
    // Update payment record with M-Pesa details
    $payment->id = $paymentid;
    $payment->merchantrequestid = $response->MerchantRequestID;
    $payment->checkoutrequestid = $response->CheckoutRequestID;
    $payment->timemodified = time();
    
    $DB->update_record('local_rvscertificate_payments', $payment);
    
    // Redirect to index page
    redirect(
        new moodle_url('/local/rvscertificate/index.php', ['courseid' => $courseid]),
        get_string('stkpush_sent', 'local_rvscertificate'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
} else {
    // STK Push failed
    $payment->id = $paymentid;
    $payment->status = 'failed';
    $payment->timemodified = time();
    $DB->update_record('local_rvscertificate_payments', $payment);
    
    redirect(
        new moodle_url('/local/rvscertificate/index.php', ['courseid' => $courseid]),
        get_string('stkpush_failed', 'local_rvscertificate'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}
