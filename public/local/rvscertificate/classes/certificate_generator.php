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
 * Certificate Generator
 *
 * @package    local_rvscertificate
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rvscertificate;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/completionlib.php');

/**
 * Certificate Generator class
 */
class certificate_generator {
    
    /**
     * Process payment and generate certificate
     *
     * @param int $paymentid Payment ID
     * @return bool Success
     * @throws \moodle_exception
     */
    public function process_payment($paymentid) {
        global $DB;
        
        $payment = $DB->get_record('local_rvscertificate_payments', ['id' => $paymentid], '*', MUST_EXIST);
        
        // Check if already processed
        if ($payment->certificateissued) {
            return true;
        }
        
        // Verify payment is completed
        if ($payment->status !== 'completed') {
            throw new \moodle_exception('paymentnotcompleted', 'local_rvscertificate');
        }
        
        // Generate verification code if not exists
        if (!$payment->verificationcode) {
            $payment->verificationcode = $this->generate_unique_verification_code();
            $DB->update_record('local_rvscertificate_payments', $payment);
        }
        
        // Get course and user
        $course = $DB->get_record('course', ['id' => $payment->courseid], '*', MUST_EXIST);
        $user = $DB->get_record('user', ['id' => $payment->userid], '*', MUST_EXIST);
        
        // Get customcert instance
        $certmodule = local_rvscertificate_get_course_certificate($payment->courseid);
        
        if (!$certmodule) {
            throw new \moodle_exception('nocertificateincourse', 'local_rvscertificate');
        }
        
        // Get the customcert instance
        $customcert = $DB->get_record('customcert', ['id' => $certmodule->instance], '*', MUST_EXIST);
        
        // Issue the certificate if not already issued
        $issue = $DB->get_record('customcert_issues', [
            'customcertid' => $customcert->id,
            'userid' => $user->id
        ]);
        
        if (!$issue) {
            // Create certificate issue
            $issue = new \stdClass();
            $issue->customcertid = $customcert->id;
            $issue->userid = $user->id;
            $issue->code = $payment->verificationcode; // Use our verification code
            $issue->emailed = 0;
            $issue->timecreated = time();
            
            try {
                $issue->id = $DB->insert_record('customcert_issues', $issue);
            } catch (\Exception $e) {
                // Certificate might already be issued, try to get it
                $issue = $DB->get_record('customcert_issues', [
                    'customcertid' => $customcert->id,
                    'userid' => $user->id
                ]);
                
                if (!$issue) {
                    throw $e;
                }
            }
        }
        
        // Update payment record
        $payment->certificateissued = 1;
        $payment->timemodified = time();
        $DB->update_record('local_rvscertificate_payments', $payment);
        
        // Send email notification
        if (get_config('local_rvscertificate', 'send_email')) {
            $this->send_certificate_email($payment, $user, $course, $certmodule);
        }
        
        return true;
    }
    
    /**
     * Generate a unique verification code
     *
     * @return string Verification code
     */
    private function generate_unique_verification_code() {
        global $DB;
        
        $maxattempts = 10;
        $attempts = 0;
        
        do {
            $code = local_rvscertificate_generate_verification_code();
            $exists = $DB->record_exists('local_rvscertificate_payments', ['verificationcode' => $code]);
            $attempts++;
        } while ($exists && $attempts < $maxattempts);
        
        if ($exists) {
            throw new \moodle_exception('couldnotgenerateverificationcode', 'local_rvscertificate');
        }
        
        return $code;
    }
    
    /**
     * Send certificate email to user
     *
     * @param \stdClass $payment Payment record
     * @param \stdClass $user User record
     * @param \stdClass $course Course record
     * @param \stdClass $certmodule Certificate module record
     * @return bool Success
     */
    private function send_certificate_email($payment, $user, $course, $certmodule) {
        global $DB;
        
        // Check if already sent
        if ($payment->emailsent) {
            return true;
        }
        
        $sitename = format_string($GLOBALS['SITE']->fullname);
        $coursename = format_string($course->fullname);
        
        // Prepare email content
        $subject = get_string('certificateemailsubject', 'local_rvscertificate', [
            'course' => $coursename
        ]);
        
        $downloadurl = new \moodle_url('/mod/customcert/view.php', [
            'id' => $certmodule->id
        ]);
        
        $messagehtml = get_string('certificateemailbody', 'local_rvscertificate', [
            'fullname' => fullname($user),
            'course' => $coursename,
            'verificationcode' => $payment->verificationcode,
            'downloadurl' => $downloadurl->out(false),
            'sitename' => $sitename
        ]);
        
        $messagetext = html_to_text($messagehtml);
        
        // Send email
        $eventdata = new \core\message\message();
        $eventdata->component = 'local_rvscertificate';
        $eventdata->name = 'certificateissued';
        $eventdata->userfrom = \core_user::get_noreply_user();
        $eventdata->userto = $user;
        $eventdata->subject = $subject;
        $eventdata->fullmessage = $messagetext;
        $eventdata->fullmessageformat = FORMAT_HTML;
        $eventdata->fullmessagehtml = $messagehtml;
        $eventdata->smallmessage = $subject;
        $eventdata->notification = 1;
        $eventdata->contexturl = $downloadurl->out(false);
        $eventdata->contexturlname = get_string('downloadcertificate', 'local_rvscertificate');
        
        $result = message_send($eventdata);
        
        if ($result) {
            // Update payment record
            $payment->emailsent = 1;
            $payment->timemodified = time();
            $DB->update_record('local_rvscertificate_payments', $payment);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get certificate PDF content
     *
     * @param int $paymentid Payment ID
     * @return string PDF content
     * @throws \moodle_exception
     */
    public function get_certificate_pdf($paymentid) {
        global $DB, $CFG;
        
        $payment = $DB->get_record('local_rvscertificate_payments', ['id' => $paymentid], '*', MUST_EXIST);
        
        if (!$payment->certificateissued || $payment->status !== 'completed') {
            throw new \moodle_exception('certificatenotissued', 'local_rvscertificate');
        }
        
        // Get customcert instance
        $certmodule = local_rvscertificate_get_course_certificate($payment->courseid);
        
        if (!$certmodule) {
            throw new \moodle_exception('nocertificateincourse', 'local_rvscertificate');
        }
        
        // Include customcert library if available
        if (file_exists($CFG->dirroot . '/mod/customcert/locallib.php')) {
            require_once($CFG->dirroot . '/mod/customcert/locallib.php');
            
            $customcert = $DB->get_record('customcert', ['id' => $certmodule->instance], '*', MUST_EXIST);
            $issue = $DB->get_record('customcert_issues', [
                'customcertid' => $customcert->id,
                'userid' => $payment->userid
            ]);
            
            if ($issue) {
                // Generate PDF using customcert
                $template = $DB->get_record('customcert_templates', ['id' => $customcert->templateid]);
                
                if ($template) {
                    // Use customcert's PDF generation
                    return \mod_customcert\certificate::generate_pdf_from_issue($issue->id);
                }
            }
        }
        
        throw new \moodle_exception('couldnotgeneratepdf', 'local_rvscertificate');
    }
}
