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
 * Privacy provider for availability_rvspayment.
 *
 * @package    availability_rvspayment
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_rvspayment\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider class.
 *
 * @package    availability_rvspayment
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Returns metadata about this plugin's privacy data.
     *
     * @param collection $collection The collection to add to.
     * @return collection The updated collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'availability_rvspayment_pay',
            [
                'userid' => 'privacy:metadata:availability_rvspayment_pay:userid',
                'courseid' => 'privacy:metadata:availability_rvspayment_pay:courseid',
                'amount' => 'privacy:metadata:availability_rvspayment_pay:amount',
                'phone' => 'privacy:metadata:availability_rvspayment_pay:phone',
                'status' => 'privacy:metadata:availability_rvspayment_pay:status',
                'timecreated' => 'privacy:metadata:availability_rvspayment_pay:timecreated',
            ],
            'privacy:metadata:availability_rvspayment_pay'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT DISTINCT ctx.id
                  FROM {availability_rvspayment_pay} p
                  JOIN {context} ctx ON ctx.instanceid = p.courseid AND ctx.contextlevel = :contextlevel
                 WHERE p.userid = :userid";

        $params = [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        $sql = "SELECT p.userid
                  FROM {availability_rvspayment_pay} p
                 WHERE p.courseid = :courseid";

        $params = ['courseid' => $context->instanceid];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_COURSE) {
                continue;
            }

            $payments = $DB->get_records('availability_rvspayment_pay', [
                'userid' => $userid,
                'courseid' => $context->instanceid,
            ]);

            if ($payments) {
                $data = [];
                foreach ($payments as $payment) {
                    $data[] = [
                        'itemtype' => $payment->itemtype,
                        'itemid' => $payment->itemid,
                        'amount' => $payment->currency . ' ' . $payment->amount,
                        'phone' => $payment->phone,
                        'status' => $payment->status,
                        'mpesareceiptnumber' => $payment->mpesareceiptnumber,
                        'timecreated' => transform::datetime($payment->timecreated),
                    ];
                }

                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'availability_rvspayment')],
                    (object)['payments' => $data]
                );
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        // Delete payment logs first.
        $payments = $DB->get_records('availability_rvspayment_pay', ['courseid' => $context->instanceid]);
        foreach ($payments as $payment) {
            $DB->delete_records('availability_rvspayment_log', ['paymentid' => $payment->id]);
        }

        // Delete payments.
        $DB->delete_records('availability_rvspayment_pay', ['courseid' => $context->instanceid]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to delete in.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_COURSE) {
                continue;
            }

            // Delete payment logs first.
            $payments = $DB->get_records('availability_rvspayment_pay', [
                'userid' => $userid,
                'courseid' => $context->instanceid,
            ]);

            foreach ($payments as $payment) {
                $DB->delete_records('availability_rvspayment_log', ['paymentid' => $payment->id]);
            }

            // Delete payments.
            $DB->delete_records('availability_rvspayment_pay', [
                'userid' => $userid,
                'courseid' => $context->instanceid,
            ]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved userlist to delete.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        $userids = $userlist->get_userids();

        if (empty($userids)) {
            return;
        }

        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        // Delete payment logs first.
        $sql = "SELECT p.id FROM {availability_rvspayment_pay} p
                 WHERE p.courseid = :courseid AND p.userid {$usersql}";
        $params = array_merge(['courseid' => $context->instanceid], $userparams);
        $paymentids = $DB->get_fieldset_sql($sql, $params);

        if ($paymentids) {
            list($paymentsql, $paymentparams) = $DB->get_in_or_equal($paymentids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('availability_rvspayment_log', "paymentid {$paymentsql}", $paymentparams);
        }

        // Delete payments.
        $DB->delete_records_select(
            'availability_rvspayment_pay',
            "courseid = :courseid AND userid {$usersql}",
            $params
        );
    }
}
