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
 * Intercept customcert access to enforce payment
 * This file should be included early in the request to check for payment
 *
 * @package    local_rvscertificate
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Intercept customcert module access
 * Call this function after config.php is loaded but before any output
 */
function local_rvscertificate_intercept_customcert() {
    global $DB, $USER, $PAGE, $COURSE, $CFG;
    
    // Only process if user is logged in
    if (!isloggedin() || isguestuser()) {
        return;
    }
    
    // Check if we're in the mod/customcert directory OR accessing view.php
    $scriptname = basename($_SERVER['SCRIPT_FILENAME']);
    $scriptpath = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
    $modpath = str_replace('\\', '/', $CFG->dirroot . '/mod/customcert/');
    
    // Only intercept if we're accessing customcert module view.php
    if (strpos($scriptpath, $modpath) !== 0 || $scriptname !== 'view.php') {
        return;
    }
    
    // Get course module ID from URL
    $cmid = optional_param('id', 0, PARAM_INT);
    if (!$cmid) {
        return;
    }
    
    // CRITICAL: Check if this is a view/download action BEFORE any processing
    // We need to block BEFORE the PDF generation starts
    $downloadown = optional_param('downloadown', false, PARAM_BOOL);
    $downloadissue = optional_param('downloadissue', 0, PARAM_INT);
    $deleteissue = optional_param('deleteissue', 0, PARAM_INT);
    $action = optional_param('action', '', PARAM_ALPHA);
    
    // Don't block delete operations (for managers)
    if ($deleteissue) {
        return;
    }
    
    // Only block if user is trying to view or download the certificate
    // Allow other actions like editing (for teachers)
    $isViewingOrDownloading = ($downloadown || $downloadissue || !$action);
    
    if (!$isViewingOrDownloading) {
        return;
    }
    
    // If headers have already been sent, we can't redirect - fail open
    if (headers_sent()) {
        return;
    }
    
    try {
        // Get course module
        $cm = $DB->get_record('course_modules', ['id' => $cmid], '*', IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        
        // Check if it's a customcert module
        $module = $DB->get_record('modules', ['id' => $cm->module], '*', IGNORE_MISSING);
        if (!$module || $module->name !== 'customcert') {
            return;
        }
        
        $courseid = $cm->course;
        $userid = $USER->id;
        
        // Get course
        $course = $DB->get_record('course', ['id' => $courseid], '*', IGNORE_MISSING);
        if (!$course) {
            return;
        }
        
        // Get context
        $context = context_course::instance($courseid);
        
        // Allow teachers and admins to access without payment
        if (has_capability('moodle/course:update', $context) || 
            has_capability('local/rvscertificate:manage', $context) ||
            is_siteadmin()) {
            return; // Allow access for teachers and admins
        }
        
        // Check if user has completed the course
        require_once($CFG->libdir . '/completionlib.php');
        $completion = new completion_info($course);
        
        if (!$completion->is_course_complete($userid)) {
            return; // Let Moodle handle non-completed users normally
        }
        
        // Check if user has already paid
        $haspaid = $DB->record_exists('local_rvscertificate_payments', [
            'userid' => $userid,
            'courseid' => $courseid,
            'status' => 'completed'
        ]);
        
        if ($haspaid) {
            return; // Allow access - payment completed
        }
        
        // User hasn't paid - redirect to payment page
        // Use header redirect to avoid any output
        $redirecturl = new moodle_url('/local/rvscertificate/index.php', ['courseid' => $courseid]);
        
        // Clean any output buffers before redirect to prevent PDF errors
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Perform redirect
        redirect(
            $redirecturl,
            get_string('paymentrequired', 'local_rvscertificate'),
            null,
            \core\output\notification::NOTIFY_WARNING
        );
        
        // Ensure script stops here
        exit;
        
    } catch (Exception $e) {
        // If any error occurs, allow access (fail open)
        // Don't use debugging() here as it can break PDF output
        return;
    }
}
