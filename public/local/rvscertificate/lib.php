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
 * Plugin library functions
 *
 * @package    local_rvscertificate
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Check if user has completed a course
 *
 * @param int $userid User ID
 * @param int $courseid Course ID
 * @return bool True if completed
 */
function local_rvscertificate_is_course_completed($userid, $courseid) {
    global $DB;
    
    $completion = new completion_info(get_course($courseid));
    return $completion->is_course_complete($userid);
}

/**
 * Check if user has already paid for certificate
 *
 * @param int $userid User ID
 * @param int $courseid Course ID
 * @return bool True if paid
 */
function local_rvscertificate_has_paid($userid, $courseid) {
    global $DB;
    
    return $DB->record_exists('local_rvscertificate_payments', [
        'userid' => $userid,
        'courseid' => $courseid,
        'status' => 'completed'
    ]);
}

/**
 * Generate a unique verification code
 *
 * @return string Verification code
 */
function local_rvscertificate_generate_verification_code() {
    return strtoupper(substr(md5(uniqid(rand(), true)), 0, 12));
}

/**
 * Get certificate price from settings
 *
 * @return float Certificate price
 */
function local_rvscertificate_get_price() {
    return (float)get_config('local_rvscertificate', 'certificate_price');
}

/**
 * Check if customcert module exists and is enabled
 *
 * @return bool True if customcert is available
 */
function local_rvscertificate_customcert_available() {
    global $DB;
    
    return $DB->record_exists('modules', ['name' => 'customcert', 'visible' => 1]);
}

/**
 * Get customcert instance for a course
 *
 * @param int $courseid Course ID
 * @return object|null Customcert instance or null
 */
function local_rvscertificate_get_course_certificate($courseid) {
    global $DB;
    
    // Get the first customcert activity in the course
    return $DB->get_record_sql(
        "SELECT cm.id, cm.instance, cm.course
         FROM {course_modules} cm
         JOIN {modules} m ON m.id = cm.module
         WHERE cm.course = :courseid AND m.name = 'customcert'
         ORDER BY cm.id ASC
         LIMIT 1",
        ['courseid' => $courseid]
    );
}

/**
 * Add navigation to certificate page
 *
 * @param navigation_node $parentnode The parent navigation node
 * @param stdClass $course The course object
 * @param context_course $context The course context
 */
function local_rvscertificate_extend_navigation_course($parentnode, $course, $context) {
    global $USER, $PAGE;
    
    if (!isloggedin() || isguestuser()) {
        return;
    }
    
    // Check if user has completed the course
    if (local_rvscertificate_is_course_completed($USER->id, $course->id)) {
        $url = new moodle_url('/local/rvscertificate/index.php', ['courseid' => $course->id]);
        $node = navigation_node::create(
            get_string('mycertificate', 'local_rvscertificate'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'rvscertificate',
            new pix_icon('i/certificate', '')
        );
        $parentnode->add_node($node);
    }
}

/**
 * Check if user can access certificate - used for permission checks
 * 
 * @param int $courseid Course ID
 * @param int $userid User ID
 * @return bool True if user can access certificate
 */
function local_rvscertificate_can_access_certificate($courseid, $userid) {
    // Check if user has completed the course
    if (!local_rvscertificate_is_course_completed($userid, $courseid)) {
        return false;
    }
    
    // Check if user has already paid
    return local_rvscertificate_has_paid($userid, $courseid);
}
