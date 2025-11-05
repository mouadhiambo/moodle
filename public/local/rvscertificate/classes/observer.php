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
        if (!local_rvscertificate_customcert_available()) {
            return;
        }
        
        $certmodule = local_rvscertificate_get_course_certificate($courseid);
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
        
        $price = local_rvscertificate_get_price();
        
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
}
