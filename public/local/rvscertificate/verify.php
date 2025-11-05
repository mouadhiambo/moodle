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
 * Certificate verification page
 *
 * @package    local_rvscertificate
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$code = optional_param('code', '', PARAM_ALPHANUMEXT);

$PAGE->set_url('/local/rvscertificate/verify.php', ['code' => $code]);
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_rvscertificate') . ' - Verification');
$PAGE->set_heading(get_string('pluginname', 'local_rvscertificate'));
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();

echo html_writer::tag('h2', 'Certificate Verification');

if (empty($code)) {
    // Show verification form
    echo html_writer::tag('p', 'Enter the verification code from your certificate to verify its authenticity.');
    
    echo html_writer::start_tag('form', [
        'method' => 'get',
        'action' => new moodle_url('/local/rvscertificate/verify.php'),
        'class' => 'mform'
    ]);
    
    echo html_writer::start_div('form-group');
    echo html_writer::label('Verification Code', 'code', true, ['class' => 'form-label']);
    echo html_writer::empty_tag('input', [
        'type' => 'text',
        'class' => 'form-control',
        'id' => 'code',
        'name' => 'code',
        'required' => 'required',
        'placeholder' => 'Enter verification code',
        'style' => 'max-width: 400px;'
    ]);
    echo html_writer::end_div();
    
    echo html_writer::start_div('form-group');
    echo html_writer::tag('button', 'Verify Certificate', [
        'type' => 'submit',
        'class' => 'btn btn-primary'
    ]);
    echo html_writer::end_div();
    
    echo html_writer::end_tag('form');
    
} else {
    // Verify the code
    $payment = $DB->get_record('local_rvscertificate_payments', [
        'verificationcode' => $code,
        'status' => 'completed'
    ]);
    
    if ($payment) {
        // Get user and course details
        $user = $DB->get_record('user', ['id' => $payment->userid]);
        $course = $DB->get_record('course', ['id' => $payment->courseid]);
        
        if ($user && $course) {
            echo html_writer::div(
                html_writer::tag('i', '', ['class' => 'fa fa-check-circle', 'style' => 'font-size: 3em; color: green;']),
                'text-center mb-3'
            );
            
            echo html_writer::tag('h3', 'Certificate Verified', ['class' => 'text-center text-success']);
            
            echo html_writer::start_tag('div', ['class' => 'card']);
            echo html_writer::start_tag('div', ['class' => 'card-body']);
            
            echo html_writer::start_tag('dl', ['class' => 'row']);
            
            echo html_writer::tag('dt', 'Verification Code', ['class' => 'col-sm-4']);
            echo html_writer::tag('dd', html_writer::tag('code', $code), ['class' => 'col-sm-8']);
            
            echo html_writer::tag('dt', 'Recipient', ['class' => 'col-sm-4']);
            echo html_writer::tag('dd', fullname($user), ['class' => 'col-sm-8']);
            
            echo html_writer::tag('dt', 'Course', ['class' => 'col-sm-4']);
            echo html_writer::tag('dd', format_string($course->fullname), ['class' => 'col-sm-8']);
            
            echo html_writer::tag('dt', 'Issue Date', ['class' => 'col-sm-4']);
            echo html_writer::tag('dd', userdate($payment->transactiondate), ['class' => 'col-sm-8']);
            
            echo html_writer::tag('dt', 'Status', ['class' => 'col-sm-4']);
            echo html_writer::tag('dd', 
                html_writer::tag('span', 'Valid', ['class' => 'badge badge-success']), 
                ['class' => 'col-sm-8']
            );
            
            echo html_writer::end_tag('dl');
            
            echo html_writer::end_tag('div');
            echo html_writer::end_tag('div');
            
        } else {
            echo $OUTPUT->notification('Certificate data incomplete', 'error');
        }
        
    } else {
        echo html_writer::div(
            html_writer::tag('i', '', ['class' => 'fa fa-times-circle', 'style' => 'font-size: 3em; color: red;']),
            'text-center mb-3'
        );
        
        echo html_writer::tag('h3', 'Certificate Not Found', ['class' => 'text-center text-danger']);
        echo html_writer::tag('p', 'The verification code you entered is invalid or the certificate has not been issued.', 
            ['class' => 'text-center']);
    }
    
    // Show verify another button
    echo html_writer::div(
        html_writer::link(
            new moodle_url('/local/rvscertificate/verify.php'),
            'Verify Another Certificate',
            ['class' => 'btn btn-secondary']
        ),
        'text-center mt-3'
    );
}

echo $OUTPUT->footer();
