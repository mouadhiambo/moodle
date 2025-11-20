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
 * Email queue manager class.
 *
 * @package    local_rvstask
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rvstask;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for managing email queue.
 */
class queue_manager {

    /**
     * Queue an email to be sent.
     *
     * @param int $templateid Template ID.
     * @param int $userid Recipient user ID.
     * @param int $scheduledtime When to send (unix timestamp).
     * @param int|null $courseid Related course ID (optional).
     * @return int New queue item ID.
     */
    public static function queue_email($templateid, $userid, $scheduledtime = 0, $courseid = null) {
        global $DB;

        // If no scheduled time specified, send immediately.
        if ($scheduledtime == 0) {
            $scheduledtime = time();
        }

        $record = new \stdClass();
        $record->templateid = $templateid;
        $record->userid = $userid;
        $record->courseid = $courseid;
        $record->scheduledtime = $scheduledtime;
        $record->sent = 0;
        $record->attempts = 0;
        $record->timecreated = time();

        return $DB->insert_record('local_rvstask_queue', $record);
    }

    /**
     * Queue emails to multiple users.
     *
     * @param int $templateid Template ID.
     * @param array $userids Array of user IDs.
     * @param int $scheduledtime When to send (unix timestamp).
     * @param int|null $courseid Related course ID (optional).
     * @return int Number of emails queued.
     */
    public static function queue_emails_to_users($templateid, $userids, $scheduledtime = 0, $courseid = null) {
        $count = 0;
        foreach ($userids as $userid) {
            if (self::queue_email($templateid, $userid, $scheduledtime, $courseid)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Get recipients based on criteria.
     *
     * @param string $type Recipient type (allstudents, allteachers, coursecompletions, custom).
     * @param array $params Additional parameters.
     * @return array Array of user IDs.
     */
    public static function get_recipients($type, $params = []) {
        global $DB;

        $userids = [];

        switch ($type) {
            case 'allstudents':
                // Get all users with student role.
                $sql = "SELECT DISTINCT u.id
                        FROM {user} u
                        JOIN {role_assignments} ra ON ra.userid = u.id
                        JOIN {role} r ON r.id = ra.roleid
                        WHERE r.archetype = 'student'
                        AND u.deleted = 0 AND u.suspended = 0";
                $users = $DB->get_records_sql($sql);
                $userids = array_keys($users);
                break;

            case 'allteachers':
                // Get all users with teacher role.
                $sql = "SELECT DISTINCT u.id
                        FROM {user} u
                        JOIN {role_assignments} ra ON ra.userid = u.id
                        JOIN {role} r ON r.id = ra.roleid
                        WHERE r.archetype IN ('teacher', 'editingteacher')
                        AND u.deleted = 0 AND u.suspended = 0";
                $users = $DB->get_records_sql($sql);
                $userids = array_keys($users);
                break;

            case 'coursecompletions':
                // Get users who completed specific course(s).
                if (!empty($params['courseids'])) {
                    list($insql, $inparams) = $DB->get_in_or_equal($params['courseids']);
                    $sql = "SELECT DISTINCT cc.userid
                            FROM {course_completions} cc
                            JOIN {user} u ON u.id = cc.userid
                            WHERE cc.course $insql
                            AND cc.timecompleted IS NOT NULL
                            AND u.deleted = 0 AND u.suspended = 0";
                    $users = $DB->get_records_sql($sql, $inparams);
                    $userids = array_keys($users);
                }
                break;

            case 'coursestudents':
                // Get students enrolled in a specific course.
                if (!empty($params['courseid'])) {
                    $context = \context_course::instance($params['courseid']);
                    $sql = "SELECT DISTINCT u.id
                            FROM {user} u
                            JOIN {role_assignments} ra ON ra.userid = u.id
                            JOIN {role} r ON r.id = ra.roleid
                            WHERE ra.contextid = :contextid
                            AND r.archetype = 'student'
                            AND u.deleted = 0 AND u.suspended = 0";
                    $users = $DB->get_records_sql($sql, ['contextid' => $context->id]);
                    $userids = array_keys($users);
                }
                break;

            case 'courseteachers':
                // Get teachers enrolled in a specific course.
                if (!empty($params['courseid'])) {
                    $context = \context_course::instance($params['courseid']);
                    $sql = "SELECT DISTINCT u.id
                            FROM {user} u
                            JOIN {role_assignments} ra ON ra.userid = u.id
                            JOIN {role} r ON r.id = ra.roleid
                            WHERE ra.contextid = :contextid
                            AND r.archetype IN ('teacher', 'editingteacher')
                            AND u.deleted = 0 AND u.suspended = 0";
                    $users = $DB->get_records_sql($sql, ['contextid' => $context->id]);
                    $userids = array_keys($users);
                }
                break;

            case 'neveraccessed':
                // Get users enrolled in specific course(s) who have never accessed them.
                if (!empty($params['courseids'])) {
                    list($insql, $inparams) = $DB->get_in_or_equal($params['courseids']);
                    $sql = "SELECT DISTINCT u.id
                            FROM {user} u
                            JOIN {user_enrolments} ue ON ue.userid = u.id
                            JOIN {enrol} e ON e.id = ue.enrolid
                            WHERE e.courseid $insql
                            AND u.deleted = 0 AND u.suspended = 0
                            AND NOT EXISTS (
                                SELECT 1
                                FROM {user_lastaccess} ul
                                WHERE ul.userid = u.id
                                AND ul.courseid = e.courseid
                            )";
                    $users = $DB->get_records_sql($sql, $inparams);
                    $userids = array_keys($users);
                }
                break;

            case 'specificusers':
                // Use provided user IDs or emails.
                if (!empty($params['userinputs'])) {
                    foreach ($params['userinputs'] as $input) {
                        $input = trim($input);
                        // Check if it's an email address.
                        if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
                            // Look up user by email.
                            $user = $DB->get_record('user', ['email' => $input, 'deleted' => 0, 'suspended' => 0]);
                            if ($user) {
                                $userids[] = $user->id;
                            }
                        } else if (is_numeric($input)) {
                            // It's a user ID.
                            $userid = intval($input);
                            // Verify user exists and is active.
                            if ($DB->record_exists('user', ['id' => $userid, 'deleted' => 0, 'suspended' => 0])) {
                                $userids[] = $userid;
                            }
                        }
                    }
                    // Remove duplicates.
                    $userids = array_unique($userids);
                }
                break;

            case 'custom':
                // Custom SQL query (use with caution).
                if (!empty($params['sql'])) {
                    $users = $DB->get_records_sql($params['sql']);
                    $userids = array_keys($users);
                }
                break;
        }

        return $userids;
    }

    /**
     * Send emails immediately (on demand).
     *
     * @param int $templateid Template ID.
     * @param array $userids Array of user IDs.
     * @param int|null $courseid Related course ID (optional).
     * @return int Number of emails queued for immediate sending.
     */
    public static function send_now($templateid, $userids, $courseid = null) {
        return self::queue_emails_to_users($templateid, $userids, time(), $courseid);
    }

    /**
     * Get queue statistics.
     *
     * @return object Queue statistics.
     */
    public static function get_queue_stats() {
        global $DB;

        $stats = new \stdClass();
        $stats->pending = $DB->count_records('local_rvstask_queue', ['sent' => 0]);
        $stats->sent = $DB->count_records('local_rvstask_queue', ['sent' => 1]);
        $stats->failed = $DB->count_records_select('local_rvstask_queue', 'sent = 0 AND attempts >= 3');

        return $stats;
    }
}
