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
 * Payment page for RVS Payment availability condition.
 *
 * This page allows students to pay via M-Pesa STK push to unlock
 * restricted sections or activities.
 *
 * @package    availability_rvspayment
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/rvscertificate/classes/mpesa_client.php');

$courseid = required_param('courseid', PARAM_INT);
$itemtype = required_param('itemtype', PARAM_ALPHA);
$itemid = required_param('itemid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

// Validate item type.
if (!in_array($itemtype, ['section', 'module'])) {
    throw new moodle_exception('invaliditem', 'availability_rvspayment');
}

// Get the course.
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

// Require login and course enrollment.
require_login($course);

$context = context_course::instance($course->id);

// Check if user is enrolled.
if (!is_enrolled($context, $USER)) {
    throw new moodle_exception('notenrolled', 'availability_rvspayment');
}

// Get the item details.
$modinfo = get_fast_modinfo($course);

$itemname = '';
$price = 0;
$currency = 'KES';
$restrictiondata = null;

if ($itemtype === 'section') {
    $section = $DB->get_record('course_sections', ['id' => $itemid, 'course' => $courseid], '*', MUST_EXIST);
    $sectioninfo = $modinfo->get_section_info($section->section);
    
    if (!$sectioninfo) {
        throw new moodle_exception('invaliditem', 'availability_rvspayment');
    }
    
    // Get section name.
    if (!empty($section->name)) {
        $itemname = get_string('sectionname', 'availability_rvspayment', 
            ['num' => $section->section, 'name' => format_string($section->name)]);
    } else {
        $itemname = get_string('sectionname_noname', 'availability_rvspayment', 
            ['num' => $section->section]);
    }
    
    // Get the payment condition from availability.
    if (!empty($sectioninfo->availability)) {
        $restrictiondata = json_decode($sectioninfo->availability);
    }
    
} else {
    // Module.
    if (!isset($modinfo->cms[$itemid])) {
        throw new moodle_exception('invaliditem', 'availability_rvspayment');
    }
    
    $cm = $modinfo->cms[$itemid];
    $itemname = format_string($cm->name);
    
    // Get the payment condition from availability.
    if (!empty($cm->availability)) {
        $restrictiondata = json_decode($cm->availability);
    }
}

// Find the RVS payment condition and get price.
$paymentcondition = find_payment_condition($restrictiondata);
if ($paymentcondition) {
    $price = isset($paymentcondition->price) ? (float)$paymentcondition->price : 0;
    $currency = isset($paymentcondition->currency) ? $paymentcondition->currency : 'KES';
}

if ($price <= 0) {
    // No payment required, redirect to course.
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}

// Check if already paid.
$existingpayment = $DB->get_record('availability_rvspayment_pay', [
    'userid' => $USER->id,
    'courseid' => $courseid,
    'itemtype' => $itemtype,
    'itemid' => $itemid,
    'status' => 'completed',
]);

if ($existingpayment) {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]),
        get_string('alreadypaid', 'availability_rvspayment'));
}

// Check for pending payment.
$pendingpayment = $DB->get_record('availability_rvspayment_pay', [
    'userid' => $USER->id,
    'courseid' => $courseid,
    'itemtype' => $itemtype,
    'itemid' => $itemid,
    'status' => 'pending',
]);

// Set up the page.
$PAGE->set_url(new moodle_url('/availability/condition/rvspayment/pay.php', [
    'courseid' => $courseid,
    'itemtype' => $itemtype,
    'itemid' => $itemid,
]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('pagetitle', 'availability_rvspayment'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('standard');

// Handle payment submission.
if ($action === 'pay' && confirm_sesskey()) {
    $phone = required_param('phone', PARAM_TEXT);
    
    // Validate phone number.
    $phone = preg_replace('/[\s\-\+]/', '', $phone);
    if (strlen($phone) < 9 || strlen($phone) > 15) {
        redirect($PAGE->url, get_string('invalidphonenumber', 'availability_rvspayment'), null, 
            \core\output\notification::NOTIFY_ERROR);
    }
    
    // Create payment record.
    $payment = new stdClass();
    $payment->userid = $USER->id;
    $payment->courseid = $courseid;
    $payment->itemtype = $itemtype;
    $payment->itemid = $itemid;
    $payment->amount = $price;
    $payment->currency = $currency;
    $payment->phone = $phone;
    $payment->status = 'pending';
    $payment->timecreated = time();
    $payment->timemodified = time();
    
    $paymentid = $DB->insert_record('availability_rvspayment_pay', $payment);
    $payment->id = $paymentid;
    
    // Initiate STK push.
    $mpesa = new \local_rvscertificate\mpesa_client();
    
    $accountref = 'UNLOCK-' . $courseid . '-' . $itemtype[0] . $itemid;
    $description = get_string('stkpush_description', 'availability_rvspayment', $itemname);
    
    $response = $mpesa->stk_push($phone, $price, $accountref, $description);
    
    if ($response && isset($response->CheckoutRequestID)) {
        // Update payment with request IDs.
        $payment->merchantrequestid = $response->MerchantRequestID;
        $payment->checkoutrequestid = $response->CheckoutRequestID;
        $payment->timemodified = time();
        $DB->update_record('availability_rvspayment_pay', $payment);
        
        // Log the STK push.
        $log = new stdClass();
        $log->paymentid = $paymentid;
        $log->type = 'stkpush';
        $log->request = json_encode([
            'phone' => $phone,
            'amount' => $price,
            'accountref' => $accountref,
        ]);
        $log->response = json_encode($response);
        $log->resultcode = $response->ResponseCode ?? null;
        $log->resultdesc = $response->ResponseDescription ?? null;
        $log->timecreated = time();
        $DB->insert_record('availability_rvspayment_log', $log);
        
        // Redirect to status page.
        redirect(new moodle_url('/availability/condition/rvspayment/status.php', [
            'paymentid' => $paymentid,
        ]), get_string('stkpush_sent', 'availability_rvspayment'));
        
    } else {
        // STK push failed.
        $payment->status = 'failed';
        $payment->timemodified = time();
        $DB->update_record('availability_rvspayment_pay', $payment);
        
        // Log the error.
        $log = new stdClass();
        $log->paymentid = $paymentid;
        $log->type = 'stkpush_error';
        $log->response = json_encode($response);
        $log->timecreated = time();
        $DB->insert_record('availability_rvspayment_log', $log);
        
        redirect($PAGE->url, get_string('stkpush_failed', 'availability_rvspayment'), null,
            \core\output\notification::NOTIFY_ERROR);
    }
}

// Output the page.
echo $OUTPUT->header();

echo html_writer::start_div('rvspayment-container');

// Title.
if ($itemtype === 'section') {
    echo $OUTPUT->heading(get_string('paymentfor_section', 'availability_rvspayment', $itemname));
} else {
    echo $OUTPUT->heading(get_string('paymentfor_module', 'availability_rvspayment', $itemname));
}

// If there's a pending payment, show status.
if ($pendingpayment) {
    echo $OUTPUT->notification(get_string('paymentpending', 'availability_rvspayment'), 'warning');
    echo html_writer::tag('p', get_string('paymentpending_desc', 'availability_rvspayment'));
    
    $statusurl = new moodle_url('/availability/condition/rvspayment/status.php', [
        'paymentid' => $pendingpayment->id,
    ]);
    echo html_writer::link($statusurl, get_string('checkstatus', 'availability_rvspayment'), 
        ['class' => 'btn btn-primary']);
    
    echo html_writer::tag('hr', '');
    echo html_writer::tag('h4', get_string('tryagain', 'availability_rvspayment'));
}

// Payment form.
echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => $PAGE->url->out(false),
    'class' => 'rvspayment-form',
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'pay']);

// Amount display.
echo html_writer::start_div('form-group row mb-3');
echo html_writer::tag('label', get_string('amount_to_pay', 'availability_rvspayment'), 
    ['class' => 'col-sm-3 col-form-label']);
echo html_writer::start_div('col-sm-9');
echo html_writer::tag('p', html_writer::tag('strong', $currency . ' ' . number_format($price, 2)),
    ['class' => 'form-control-plaintext']);
echo html_writer::end_div();
echo html_writer::end_div();

// Phone number input.
echo html_writer::start_div('form-group row mb-3');
echo html_writer::tag('label', get_string('phonenumber', 'availability_rvspayment'), [
    'class' => 'col-sm-3 col-form-label',
    'for' => 'phone',
]);
echo html_writer::start_div('col-sm-9');
echo html_writer::empty_tag('input', [
    'type' => 'tel',
    'name' => 'phone',
    'id' => 'phone',
    'class' => 'form-control',
    'placeholder' => '0712345678',
    'required' => 'required',
    'pattern' => '[0-9+\s\-]{9,15}',
]);
echo html_writer::tag('small', get_string('phonenumber_help', 'availability_rvspayment'), 
    ['class' => 'form-text text-muted']);
echo html_writer::end_div();
echo html_writer::end_div();

// Submit button.
echo html_writer::start_div('form-group row mb-3');
echo html_writer::start_div('col-sm-9 offset-sm-3');
echo html_writer::tag('button', get_string('paynow', 'availability_rvspayment'), [
    'type' => 'submit',
    'class' => 'btn btn-primary btn-lg',
]);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_tag('form');

// Back to course link.
echo html_writer::tag('p', html_writer::link(
    new moodle_url('/course/view.php', ['id' => $courseid]),
    get_string('backtocourse', 'availability_rvspayment')
), ['class' => 'mt-4']);

echo html_writer::end_div();

echo $OUTPUT->footer();

/**
 * Recursively find the RVS payment condition in an availability tree.
 *
 * @param object|null $tree The availability tree
 * @return object|null The payment condition or null
 */
function find_payment_condition($tree) {
    if (!$tree) {
        return null;
    }
    
    if (isset($tree->type) && $tree->type === 'rvspayment') {
        return $tree;
    }
    
    if (isset($tree->c) && is_array($tree->c)) {
        foreach ($tree->c as $condition) {
            $found = find_payment_condition($condition);
            if ($found) {
                return $found;
            }
        }
    }
    
    return null;
}
