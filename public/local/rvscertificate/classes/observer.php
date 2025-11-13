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
 * Event observers
 *
 * @package    local_rvscertificate
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rvscertificate;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/rvscertificate/lib.php');

/**
 * Event observer class
 */
class observer {
    
    /**
     * Observer for course_completed event
     *
     * @param \core\event\course_completed $event
     */
    public static function course_completed(\core\event\course_completed $event) {
        global $DB;
        
        $userid = $event->relateduserid;
        $courseid = $event->courseid;
        
        // Check if customcert exists in the course
        if (!\local_rvscertificate_customcert_available()) {
            return;
        }
        
        $certmodule = \local_rvscertificate_get_course_certificate($courseid);
        if (!$certmodule) {
            return;
        }
        
        // Send notification to user about certificate availability
        $user = $DB->get_record('user', ['id' => $userid]);
        $course = $DB->get_record('course', ['id' => $courseid]);
        
        if ($user && $course) {
            self::send_certificate_available_notification($user, $course);
        }
    }
    
    /**
     * Send notification about certificate availability
     *
     * @param \stdClass $user User object
     * @param \stdClass $course Course object
     */
    private static function send_certificate_available_notification($user, $course) {
        global $SITE;
        
        $sitename = format_string($SITE->fullname);
        $coursename = format_string($course->fullname);
        
        $subject = get_string('certificateavailablesubject', 'local_rvscertificate', [
            'course' => $coursename
        ]);
        
        $certificateurl = new \moodle_url('/local/rvscertificate/index.php', [
            'courseid' => $course->id
        ]);
        
        $price = \local_rvscertificate_get_price();
        
        $messagehtml = get_string('certificateavailablebody', 'local_rvscertificate', [
            'fullname' => fullname($user),
            'course' => $coursename,
            'price' => 'KES ' . number_format($price, 2),
            'url' => $certificateurl->out(false),
            'sitename' => $sitename
        ]);
        
        $messagetext = html_to_text($messagehtml);
        
        // Send message
        $eventdata = new \core\message\message();
        $eventdata->component = 'local_rvscertificate';
        $eventdata->name = 'certificateavailable';
        $eventdata->userfrom = \core_user::get_noreply_user();
        $eventdata->userto = $user;
        $eventdata->subject = $subject;
        $eventdata->fullmessage = $messagetext;
        $eventdata->fullmessageformat = FORMAT_HTML;
        $eventdata->fullmessagehtml = $messagehtml;
        $eventdata->smallmessage = $subject;
        $eventdata->notification = 1;
        $eventdata->contexturl = $certificateurl->out(false);
        $eventdata->contexturlname = get_string('requestcertificate', 'local_rvscertificate');
        
        message_send($eventdata);
    }
    
    /**
     * Observer for customcert course_module_viewed event
     * Intercepts customcert module views to enforce payment
     *
     * @param \mod_customcert\event\course_module_viewed $event
     */
    public static function course_module_viewed(\mod_customcert\event\course_module_viewed $event) {
        global $DB, $USER, $PAGE;
        
        // Check if this is a download action - if so, exit immediately to avoid breaking PDF generation
        $downloadown = optional_param('downloadown', false, PARAM_BOOL);
        $downloadissue = optional_param('downloadissue', 0, PARAM_INT);
        $downloadall = optional_param('downloadall', 0, PARAM_INT);
        
        if ($downloadown || $downloadissue || $downloadall) {
            // This is a download action - do not intercept or output anything
            return;
        }
        
        // Don't run if headers already sent (too late for redirect)
        if (headers_sent()) {
            return;
        }
        
        // Get the course module ID from the event
        $cmid = $event->contextinstanceid;
        
        debugging('RVS Certificate: Intercepting customcert view for CM ID: ' . $cmid, DEBUG_DEVELOPER);
        
        $courseid = $event->courseid;
        $userid = $USER->id;
        
        // Get context and check if user is a teacher/admin
        try {
            $context = \context_course::instance($courseid);
            if (has_capability('moodle/course:update', $context) || 
                has_capability('local/rvscertificate:manage', $context) ||
                is_siteadmin()) {
                debugging('RVS Certificate: User is teacher/admin - allowing access', DEBUG_DEVELOPER);
                return; // Allow access for teachers and admins
            }
        } catch (\Exception $e) {
            debugging('RVS Certificate: Error getting context - failing open', DEBUG_DEVELOPER);
            return; // Fail open if context can't be determined
        }
        
        // Check if user has completed the course
        $courseCompleted = \local_rvscertificate_is_course_completed($userid, $courseid);
        
        if (!$courseCompleted) {
            debugging('RVS Certificate: User ' . $userid . ' has not completed course ' . $courseid, DEBUG_DEVELOPER);
        } else {
            debugging('RVS Certificate: User ' . $userid . ' has completed course ' . $courseid, DEBUG_DEVELOPER);
        }
        
        // Check if user has already paid
        if (\local_rvscertificate_has_paid($userid, $courseid)) {
            debugging('RVS Certificate: User ' . $userid . ' has already paid for course ' . $courseid, DEBUG_DEVELOPER);
            return; // Allow access - payment completed
        }
        
        // User hasn't paid - redirect to payment page (whether completed or not)
        debugging('RVS Certificate: User ' . $userid . ' has NOT paid - redirecting to payment page', DEBUG_DEVELOPER);
        
        // Clean output buffers before redirect
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Redirect to payment page
        redirect(
            new \moodle_url('/local/rvscertificate/index.php', ['courseid' => $courseid]),
            get_string('paymentrequired', 'local_rvscertificate'),
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    /**
     * Observer for user_enrolment_created event
     * Creates pending payment record when user is enrolled in paid course
     *
     * @param \core\event\user_enrolment_created $event
     */
    public static function user_enrolment_created(\core\event\user_enrolment_created $event) {
        global $DB;
        
        $userid = $event->relateduserid;
        $courseid = $event->courseid;
        
        // Check if payment is required for this course
        if (!\local_rvscertificate_payment_required($courseid)) {
            return;
        }
        
        // Check if user already has a payment record for this course
        $existing = $DB->get_record('local_rvscertificate_payments', [
            'userid' => $userid,
            'courseid' => $courseid
        ]);
        
        if ($existing) {
            return; // Payment record already exists
        }
        
        // Get the course price
        $amount = \local_rvscertificate_get_price($courseid);
        
        // Create payment record with pending status
        $payment = new \stdClass();
        $payment->userid = $userid;
        $payment->courseid = $courseid;
        $payment->amount = $amount;
        $payment->phone = ''; // Will be filled when user initiates payment
        $payment->status = 'pending';
        $payment->certificateissued = 0;
        $payment->emailsent = 0;
        $payment->timecreated = time();
        $payment->timemodified = time();
        
        try {
            $DB->insert_record('local_rvscertificate_payments', $payment);
        } catch (\Exception $e) {
            // Log error but don't break enrolment process
            debugging('Failed to create payment record for user ' . $userid . ' in course ' . $courseid . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
