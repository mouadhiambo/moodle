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
 * Scheduled task to sync payment records for enrolled students in paid courses
 *
 * @package    local_rvscertificate
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rvscertificate\task;

/**
 * Sync payment records for enrolled students in paid courses
 */
class sync_enrolled_students extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_sync_enrolled_students', 'local_rvscertificate');
    }

    /**
     * Execute the task
     */
    public function execute() {
        global $DB;

        mtrace('Starting sync of enrolled students in paid courses...');

        // Get all courses that require payment for certificates
        $courses = $this->get_paid_courses();
        
        if (empty($courses)) {
            mtrace('No paid courses found.');
            return;
        }

        $created_count = 0;
        $skipped_count = 0;

        foreach ($courses as $course) {
            mtrace('Processing course: ' . $course->courseid . ' (price: ' . $course->price . ')');
            
            // Get all enrolled students in this course
            $enrolled_users = $this->get_enrolled_students($course->courseid);
            
            foreach ($enrolled_users as $user) {
                // Check if payment record already exists
                $existing = $DB->get_record('local_rvscertificate_payments', [
                    'userid' => $user->id,
                    'courseid' => $course->courseid
                ]);

                if ($existing) {
                    $skipped_count++;
                    continue;
                }

                // Create pending payment record
                $payment = new \stdClass();
                $payment->userid = $user->id;
                $payment->courseid = $course->courseid;
                $payment->amount = $course->price;
                $payment->phone = '';
                $payment->status = 'pending';
                $payment->certificateissued = 0;
                $payment->emailsent = 0;
                $payment->timecreated = time();
                $payment->timemodified = time();

                try {
                    $DB->insert_record('local_rvscertificate_payments', $payment);
                    $created_count++;
                    mtrace('  Created payment record for user: ' . $user->id);
                } catch (\Exception $e) {
                    mtrace('  ERROR: Failed to create payment record for user ' . $user->id . ': ' . $e->getMessage());
                }
            }
        }

        mtrace('Sync complete. Created: ' . $created_count . ', Skipped: ' . $skipped_count);
    }

    /**
     * Get all courses that require payment
     *
     * @return array Array of course objects with courseid and price
     */
    private function get_paid_courses() {
        global $DB;

        $courses = [];

        // Get courses with specific prices set and enabled
        $course_prices = $DB->get_records_select('local_rvscertificate_course_prices', 
            'enabled = 1 AND price > 0', 
            null, 
            '', 
            'courseid, price'
        );

        foreach ($course_prices as $cp) {
            $courses[] = (object)[
                'courseid' => $cp->courseid,
                'price' => $cp->price
            ];
        }

        // Get global default price
        $global_price = get_config('local_rvscertificate', 'certificate_price');
        
        if ($global_price > 0) {
            // Find courses without specific pricing
            $sql = "SELECT c.id as courseid
                      FROM {course} c
                     WHERE c.id != 1
                       AND c.visible = 1
                       AND NOT EXISTS (
                           SELECT 1
                             FROM {local_rvscertificate_course_prices} cp
                            WHERE cp.courseid = c.id
                       )";
            
            $unprice_courses = $DB->get_records_sql($sql);
            
            foreach ($unprice_courses as $course) {
                $courses[] = (object)[
                    'courseid' => $course->courseid,
                    'price' => $global_price
                ];
            }
        }

        return $courses;
    }

    /**
     * Get all students enrolled in a course
     *
     * @param int $courseid Course ID
     * @return array Array of user objects
     */
    private function get_enrolled_students($courseid) {
        global $DB;

        // Get context
        $context = \context_course::instance($courseid);

        // Get enrolled users with student role (typically roleid 5, but let's be flexible)
        $sql = "SELECT DISTINCT u.id, u.username, u.firstname, u.lastname, u.email
                  FROM {user} u
                  JOIN {user_enrolments} ue ON ue.userid = u.id
                  JOIN {enrol} e ON e.id = ue.enrolid
                  JOIN {role_assignments} ra ON ra.userid = u.id
                  JOIN {context} ctx ON ctx.id = ra.contextid
                 WHERE e.courseid = :courseid
                   AND ctx.contextlevel = :contextlevel
                   AND ctx.instanceid = :instanceid
                   AND u.deleted = 0
                   AND u.suspended = 0
                   AND ue.status = :active
                   AND e.status = :enrolstatus
                   AND (ue.timeend = 0 OR ue.timeend > :now)
                   AND ue.timestart < :now2";

        $params = [
            'courseid' => $courseid,
            'contextlevel' => CONTEXT_COURSE,
            'instanceid' => $courseid,
            'active' => 0, // ENROL_USER_ACTIVE
            'enrolstatus' => 0, // ENROL_INSTANCE_ENABLED
            'now' => time(),
            'now2' => time()
        ];

        return $DB->get_records_sql($sql, $params);
    }
}
