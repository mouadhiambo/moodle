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
 * Certificate request page
 *
 * @package    local_rvscertificate
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/completionlib.php');

$courseid = required_param('courseid', PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

require_login($course);
$context = context_course::instance($courseid);
require_capability('local/rvscertificate:request', $context);

$PAGE->set_url('/local/rvscertificate/index.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('mycertificate', 'local_rvscertificate'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('course');

// Check if user has completed the course
if (!local_rvscertificate_is_course_completed($USER->id, $courseid)) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('coursenotcompleted', 'local_rvscertificate'), 'error');
    echo $OUTPUT->footer();
    exit;
}

// Check if customcert module is available
if (!local_rvscertificate_customcert_available()) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('customcertnotavailable', 'local_rvscertificate'), 'error');
    echo $OUTPUT->footer();
    exit;
}

// Get certificate for this course
$certmodule = local_rvscertificate_get_course_certificate($courseid);
if (!$certmodule) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('nocertificateincourse', 'local_rvscertificate'), 'warning');
    echo $OUTPUT->footer();
    exit;
}

// Check payment status
$payment = $DB->get_record('local_rvscertificate_payments', [
    'userid' => $USER->id,
    'courseid' => $courseid,
    'status' => 'completed'
]);

echo $OUTPUT->header();

if ($payment) {
    // Payment completed - show download link
    echo html_writer::tag('h3', get_string('certificateavailable', 'local_rvscertificate'));
    echo html_writer::tag('p', get_string('certificateavailable_desc', 'local_rvscertificate'));
    
    // Show verification code
    if ($payment->verificationcode) {
        echo html_writer::div(
            html_writer::tag('strong', get_string('verificationcode', 'local_rvscertificate') . ': ') . 
            html_writer::tag('code', $payment->verificationcode, ['style' => 'font-size: 1.2em']),
            'alert alert-info'
        );
    }
    
    // Download button
    $downloadurl = new moodle_url('/mod/customcert/view.php', [
        'id' => $certmodule->id,
        'downloadissue' => $payment->id
    ]);
    
    echo html_writer::link(
        $downloadurl,
        get_string('downloadcertificate', 'local_rvscertificate'),
        ['class' => 'btn btn-primary btn-lg mb-3']
    );
    
    // Show payment details
    echo html_writer::start_tag('div', ['class' => 'card']);
    echo html_writer::start_tag('div', ['class' => 'card-body']);
    echo html_writer::tag('h5', get_string('paymentdetails', 'local_rvscertificate'), ['class' => 'card-title']);
    
    echo html_writer::start_tag('dl', ['class' => 'row']);
    
    echo html_writer::tag('dt', get_string('amount', 'local_rvscertificate'), ['class' => 'col-sm-3']);
    echo html_writer::tag('dd', 'KES ' . number_format($payment->amount, 2), ['class' => 'col-sm-9']);
    
    if ($payment->mpesareceiptnumber) {
        echo html_writer::tag('dt', get_string('mpesareceipt', 'local_rvscertificate'), ['class' => 'col-sm-3']);
        echo html_writer::tag('dd', $payment->mpesareceiptnumber, ['class' => 'col-sm-9']);
    }
    
    echo html_writer::tag('dt', get_string('paymentdate', 'local_rvscertificate'), ['class' => 'col-sm-3']);
    echo html_writer::tag('dd', userdate($payment->transactiondate), ['class' => 'col-sm-9']);
    
    echo html_writer::end_tag('dl');
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
    
} else {
    // Check for pending payment
    $pendingpayment = $DB->get_record('local_rvscertificate_payments', [
        'userid' => $USER->id,
        'courseid' => $courseid,
        'status' => 'pending'
    ]);
    
    if ($pendingpayment) {
        echo $OUTPUT->notification(get_string('paymentpending', 'local_rvscertificate'), 'info');
        echo html_writer::tag('p', get_string('paymentpending_desc', 'local_rvscertificate'));
        
        // Check status button
        $checkurl = new moodle_url('/local/rvscertificate/check_status.php', [
            'courseid' => $courseid,
            'paymentid' => $pendingpayment->id
        ]);
        echo html_writer::link($checkurl, get_string('checkstatus', 'local_rvscertificate'), 
            ['class' => 'btn btn-secondary']);
    } else {
        // No payment - show payment form
        $price = local_rvscertificate_get_price();
        
        echo html_writer::tag('h3', get_string('requestcertificate', 'local_rvscertificate'));
        echo html_writer::tag('p', get_string('requestcertificate_desc', 'local_rvscertificate'));
        
        echo html_writer::div(
            get_string('certificateprice', 'local_rvscertificate') . ': ' . 
            html_writer::tag('strong', 'KES ' . number_format($price, 2)),
            'alert alert-info'
        );
        
        // Payment form
        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => new moodle_url('/local/rvscertificate/request.php'),
            'class' => 'mform'
        ]);
        
        echo html_writer::input_hidden_params(new moodle_url('', ['courseid' => $courseid]));
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        
        echo html_writer::start_div('form-group');
        echo html_writer::label(get_string('phonenumber', 'local_rvscertificate'), 'phone', true, 
            ['class' => 'form-label']);
        echo html_writer::empty_tag('input', [
            'type' => 'tel',
            'class' => 'form-control',
            'id' => 'phone',
            'name' => 'phone',
            'required' => 'required',
            'placeholder' => '0712345678 or 254712345678',
            'pattern' => '[0-9]{9,12}'
        ]);
        echo html_writer::tag('small', get_string('phonenumber_help', 'local_rvscertificate'), 
            ['class' => 'form-text text-muted']);
        echo html_writer::end_div();
        
        echo html_writer::start_div('form-group');
        echo html_writer::tag('button', get_string('paynow', 'local_rvscertificate'), [
            'type' => 'submit',
            'class' => 'btn btn-primary btn-lg'
        ]);
        echo html_writer::end_div();
        
        echo html_writer::end_tag('form');
    }
}

echo $OUTPUT->footer();
