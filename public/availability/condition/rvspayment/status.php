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
 * Payment status page for RVS Payment availability condition.
 *
 * This page shows the status of a pending payment and auto-refreshes
 * until the payment is confirmed or fails.
 *
 * @package    availability_rvspayment
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

$paymentid = required_param('paymentid', PARAM_INT);
$ajax = optional_param('ajax', 0, PARAM_INT);

// Get the payment record.
$payment = $DB->get_record('availability_rvspayment_pay', ['id' => $paymentid], '*', MUST_EXIST);

// Security check - only the payment owner can view this.
require_login();
if ($payment->userid != $USER->id && !is_siteadmin()) {
    throw new moodle_exception('paymentnotfound', 'availability_rvspayment');
}

// Get course.
$course = $DB->get_record('course', ['id' => $payment->courseid], '*', MUST_EXIST);
$context = context_course::instance($course->id);

// Handle AJAX request for status check.
if ($ajax) {
    header('Content-Type: application/json');
    
    // Re-fetch payment to get latest status.
    $payment = $DB->get_record('availability_rvspayment_pay', ['id' => $paymentid], '*', MUST_EXIST);
    
    echo json_encode([
        'status' => $payment->status,
        'receipt' => $payment->mpesareceiptnumber,
    ]);
    exit;
}

// Get item details.
$modinfo = get_fast_modinfo($course);
$itemname = '';

if ($payment->itemtype === 'section') {
    $section = $DB->get_record('course_sections', ['id' => $payment->itemid]);
    if ($section) {
        if (!empty($section->name)) {
            $itemname = get_string('sectionname', 'availability_rvspayment', 
                ['num' => $section->section, 'name' => format_string($section->name)]);
        } else {
            $itemname = get_string('sectionname_noname', 'availability_rvspayment', 
                ['num' => $section->section]);
        }
    }
} else {
    if (isset($modinfo->cms[$payment->itemid])) {
        $cm = $modinfo->cms[$payment->itemid];
        $itemname = format_string($cm->name);
    }
}

// Set up the page.
$PAGE->set_url(new moodle_url('/availability/condition/rvspayment/status.php', [
    'paymentid' => $paymentid,
]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('pagetitle', 'availability_rvspayment'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('standard');

// Add JavaScript for auto-refresh on pending status.
if ($payment->status === 'pending') {
    $PAGE->requires->js_init_code('
        (function() {
            var checkInterval = setInterval(function() {
                fetch("' . $PAGE->url->out(false) . '&ajax=1")
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        if (data.status === "completed") {
                            clearInterval(checkInterval);
                            window.location.reload();
                        } else if (data.status === "failed") {
                            clearInterval(checkInterval);
                            window.location.reload();
                        }
                    })
                    .catch(function(err) {
                        console.error("Status check failed:", err);
                    });
            }, 3000); // Check every 3 seconds
            
            // Stop checking after 5 minutes
            setTimeout(function() {
                clearInterval(checkInterval);
            }, 300000);
        })();
    ', true);
}

// Output the page.
echo $OUTPUT->header();

echo html_writer::start_div('rvspayment-status-container text-center');

if ($payment->status === 'pending') {
    // Show pending status with spinner.
    echo html_writer::tag('div', 
        html_writer::tag('i', '', ['class' => 'fa fa-spinner fa-spin fa-3x']),
        ['class' => 'mb-4']);
    echo $OUTPUT->heading(get_string('paymentpending', 'availability_rvspayment'), 3);
    echo html_writer::tag('p', get_string('paymentpending_desc', 'availability_rvspayment'));
    echo html_writer::tag('p', html_writer::tag('strong', $itemname));
    echo html_writer::tag('p', 
        $payment->currency . ' ' . number_format($payment->amount, 2),
        ['class' => 'lead']);
    
    // Manual refresh button.
    echo html_writer::tag('p',
        html_writer::link($PAGE->url, get_string('checkstatus', 'availability_rvspayment'),
            ['class' => 'btn btn-secondary']),
        ['class' => 'mt-4']);
    
    // Try again link.
    $payurl = new moodle_url('/availability/condition/rvspayment/pay.php', [
        'courseid' => $payment->courseid,
        'itemtype' => $payment->itemtype,
        'itemid' => $payment->itemid,
    ]);
    echo html_writer::tag('p',
        html_writer::link($payurl, get_string('tryagain', 'availability_rvspayment'),
            ['class' => 'btn btn-outline-primary']),
        ['class' => 'mt-2']);
        
} else if ($payment->status === 'completed') {
    // Show success.
    echo html_writer::tag('div',
        html_writer::tag('i', '', ['class' => 'fa fa-check-circle fa-3x text-success']),
        ['class' => 'mb-4']);
    echo $OUTPUT->heading(get_string('paymentsuccess', 'availability_rvspayment'), 3);
    echo html_writer::tag('p', get_string('paymentsuccess_desc', 'availability_rvspayment'));
    
    // Payment details.
    echo html_writer::start_div('card mt-4 mx-auto', ['style' => 'max-width: 400px;']);
    echo html_writer::start_div('card-body');
    echo html_writer::tag('p', html_writer::tag('strong', 'Item: ') . $itemname);
    echo html_writer::tag('p', html_writer::tag('strong', 'Amount: ') . 
        $payment->currency . ' ' . number_format($payment->amount, 2));
    if ($payment->mpesareceiptnumber) {
        echo html_writer::tag('p', html_writer::tag('strong', 'M-Pesa Receipt: ') . 
            $payment->mpesareceiptnumber);
    }
    echo html_writer::end_div();
    echo html_writer::end_div();
    
    // Go to content button.
    $courseurl = new moodle_url('/course/view.php', ['id' => $payment->courseid]);
    if ($payment->itemtype === 'section') {
        $section = $DB->get_record('course_sections', ['id' => $payment->itemid]);
        if ($section) {
            $courseurl->param('section', $section->section);
        }
    }
    echo html_writer::tag('p',
        html_writer::link($courseurl, get_string('gotocontent', 'availability_rvspayment'),
            ['class' => 'btn btn-primary btn-lg']),
        ['class' => 'mt-4']);
        
} else {
    // Show failed status.
    echo html_writer::tag('div',
        html_writer::tag('i', '', ['class' => 'fa fa-times-circle fa-3x text-danger']),
        ['class' => 'mb-4']);
    echo $OUTPUT->heading(get_string('paymentfailed', 'availability_rvspayment'), 3);
    echo html_writer::tag('p', get_string('paymentfailed_desc', 'availability_rvspayment'));
    
    // Try again button.
    $payurl = new moodle_url('/availability/condition/rvspayment/pay.php', [
        'courseid' => $payment->courseid,
        'itemtype' => $payment->itemtype,
        'itemid' => $payment->itemid,
    ]);
    echo html_writer::tag('p',
        html_writer::link($payurl, get_string('tryagain', 'availability_rvspayment'),
            ['class' => 'btn btn-primary btn-lg']),
        ['class' => 'mt-4']);
}

// Back to course link.
echo html_writer::tag('p', html_writer::link(
    new moodle_url('/course/view.php', ['id' => $payment->courseid]),
    get_string('backtocourse', 'availability_rvspayment')
), ['class' => 'mt-4']);

echo html_writer::end_div();

echo $OUTPUT->footer();
