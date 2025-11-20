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
 * Event observer for the RVS Task plugin.
 *
 * @package    local_rvstask
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rvstask;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer class.
 */
class observer {

    /**
     * Observer for course completion event.
     *
     * @param \core\event\course_completed $event The event.
     */
    public static function course_completed(\core\event\course_completed $event) {
        global $DB;

        // Check if course completion emails are enabled.
        $config = get_config('local_rvstask');
        if (empty($config->enablecoursecompletion)) {
            return;
        }

        // Get the course completion template.
        $templateid = $config->coursecompletiontemplateid ?? 0;
        if (!$templateid) {
            return;
        }

        // Verify template exists and is enabled.
        $template = $DB->get_record('local_rvstask_templates', ['id' => $templateid, 'enabled' => 1]);
        if (!$template) {
            return;
        }

        // Get event data.
        $userid = $event->relateduserid;
        $courseid = $event->courseid;

        // Queue the email to be sent immediately.
        queue_manager::queue_email($templateid, $userid, time(), $courseid);

        // Log the action.
        $context = $event->get_context();
        $logparams = [
            'context' => $context,
            'objectid' => $templateid,
            'relateduserid' => $userid,
            'other' => ['courseid' => $courseid]
        ];
        
        // Create a custom event for logging (optional).
        // \local_rvstask\event\email_queued::create($logparams)->trigger();
    }
}
