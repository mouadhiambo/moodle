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
 * Helper class for RVS Payment availability condition.
 *
 * @package    availability_rvspayment
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_rvspayment;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class with utility methods.
 *
 * @package    availability_rvspayment
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * Check if a user has access to an item (either free or paid).
     *
     * @param int $userid User ID
     * @param int $courseid Course ID
     * @param string $itemtype 'section' or 'module'
     * @param int $itemid The item ID
     * @return bool True if user has access
     */
    public static function user_has_access(int $userid, int $courseid, string $itemtype, int $itemid): bool {
        global $DB;

        // Check for completed payment.
        return $DB->record_exists('availability_rvspayment_pay', [
            'userid' => $userid,
            'courseid' => $courseid,
            'itemtype' => $itemtype,
            'itemid' => $itemid,
            'status' => 'completed',
        ]);
    }

    /**
     * Check if a course module requires payment and if the user can access it.
     * This is used by activity modules (like SCORM) to determine if launch buttons should be shown.
     *
     * @param \cm_info|int $cmorid Course module info object or course module ID
     * @param int|null $userid User ID (defaults to current user)
     * @return array ['requires_payment' => bool, 'has_paid' => bool, 'can_access' => bool, 'price' => float, 'currency' => string, 'payurl' => string]
     */
    public static function check_module_payment_status($cmorid, ?int $userid = null): array {
        global $DB, $USER;

        $userid = $userid ?? $USER->id;

        // Get the cm_info object.
        if (is_numeric($cmorid)) {
            list($course, $cm) = get_course_and_cm_from_cmid($cmorid);
        } else {
            $cm = $cmorid;
            $course = get_course($cm->course);
        }

        $result = [
            'requires_payment' => false,
            'has_paid' => false,
            'can_access' => true,
            'price' => 0,
            'currency' => 'KES',
            'payurl' => '',
            'is_free' => false,
        ];

        // Check if the module has availability restrictions.
        if (empty($cm->availability)) {
            return $result;
        }

        // Find the payment condition in the availability tree.
        $tree = json_decode($cm->availability);
        $paymentcondition = self::find_payment_condition($tree);

        if (!$paymentcondition) {
            return $result;
        }

        // Check if it's marked as free.
        if (!empty($paymentcondition->isfree) || (isset($paymentcondition->price) && $paymentcondition->price <= 0)) {
            $result['is_free'] = true;
            $result['can_access'] = true;
            return $result;
        }

        // It requires payment.
        $result['requires_payment'] = true;
        $result['price'] = (float)($paymentcondition->price ?? 0);
        $result['currency'] = $paymentcondition->currency ?? 'KES';

        // Generate payment URL.
        $result['payurl'] = (new \moodle_url('/availability/condition/rvspayment/pay.php', [
            'courseid' => $course->id,
            'itemtype' => 'module',
            'itemid' => $cm->id,
        ]))->out(false);

        // Check if user has paid.
        $result['has_paid'] = self::user_has_access($userid, $course->id, 'module', $cm->id);
        $result['can_access'] = $result['has_paid'];

        return $result;
    }

    /**
     * Check if a user can launch/enter a SCORM activity.
     * Returns false if payment is required but not made.
     *
     * @param \cm_info|int $cmorid Course module info object or ID
     * @param int|null $userid User ID (defaults to current user)
     * @return bool True if user can launch the activity
     */
    public static function can_launch_activity($cmorid, ?int $userid = null): bool {
        $status = self::check_module_payment_status($cmorid, $userid);
        return $status['can_access'];
    }

    /**
     * Get the payment message HTML to display when a user cannot access an activity.
     *
     * @param \cm_info|int $cmorid Course module info object or ID
     * @param int|null $userid User ID (defaults to current user)
     * @return string HTML message with payment button, or empty string if no payment required
     */
    public static function get_payment_required_message($cmorid, ?int $userid = null): string {
        global $OUTPUT;

        $status = self::check_module_payment_status($cmorid, $userid);

        if (!$status['requires_payment'] || $status['has_paid']) {
            return '';
        }

        $price = number_format($status['price'], 2);
        $message = get_string('description_withpayment', 'availability_rvspayment', (object)[
            'price' => $price,
            'currency' => $status['currency'],
            'payurl' => $status['payurl'],
        ]);

        return \html_writer::div($message, 'alert alert-warning rvspayment-required');
    }

    /**
     * Get all payments for a user in a course.
     *
     * @param int $userid User ID
     * @param int $courseid Course ID
     * @return array Array of payment records
     */
    public static function get_user_payments(int $userid, int $courseid): array {
        global $DB;

        return $DB->get_records('availability_rvspayment_pay', [
            'userid' => $userid,
            'courseid' => $courseid,
        ], 'timecreated DESC');
    }

    /**
     * Get all completed payments for a user in a course.
     *
     * @param int $userid User ID
     * @param int $courseid Course ID
     * @return array Array of payment records
     */
    public static function get_user_completed_payments(int $userid, int $courseid): array {
        global $DB;

        return $DB->get_records('availability_rvspayment_pay', [
            'userid' => $userid,
            'courseid' => $courseid,
            'status' => 'completed',
        ], 'timecreated DESC');
    }

    /**
     * Get the price for a section or module from its availability settings.
     *
     * @param string $availability The availability JSON string
     * @return array|null Array with 'price' and 'currency', or null if no payment required
     */
    public static function get_price_from_availability(string $availability): ?array {
        if (empty($availability)) {
            return null;
        }

        $tree = json_decode($availability);
        $condition = self::find_payment_condition($tree);

        if ($condition && isset($condition->price) && $condition->price > 0) {
            return [
                'price' => (float)$condition->price,
                'currency' => $condition->currency ?? 'KES',
                'isfree' => !empty($condition->isfree),
            ];
        }

        return null;
    }

    /**
     * Recursively find the RVS payment condition in an availability tree.
     *
     * @param object|null $tree The availability tree
     * @return object|null The payment condition or null
     */
    public static function find_payment_condition($tree): ?object {
        if (!$tree) {
            return null;
        }

        if (isset($tree->type) && $tree->type === 'rvspayment') {
            return $tree;
        }

        if (isset($tree->c) && is_array($tree->c)) {
            foreach ($tree->c as $condition) {
                $found = self::find_payment_condition($condition);
                if ($found) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Get all sections in a course that require payment.
     *
     * @param int $courseid Course ID
     * @return array Array of section objects with payment info
     */
    public static function get_paid_sections(int $courseid): array {
        global $DB;

        $sections = $DB->get_records('course_sections', ['course' => $courseid], 'section ASC');
        $paidsections = [];

        foreach ($sections as $section) {
            if (empty($section->availability)) {
                continue;
            }

            $priceinfo = self::get_price_from_availability($section->availability);
            if ($priceinfo && !$priceinfo['isfree']) {
                $section->priceinfo = $priceinfo;
                $paidsections[$section->id] = $section;
            }
        }

        return $paidsections;
    }

    /**
     * Calculate the total amount a user needs to pay to unlock all sections in a course.
     *
     * @param int $userid User ID
     * @param int $courseid Course ID
     * @return array Array with 'total', 'paid', 'remaining' amounts
     */
    public static function calculate_user_payment_totals(int $userid, int $courseid): array {
        $paidsections = self::get_paid_sections($courseid);
        $userpayments = self::get_user_completed_payments($userid, $courseid);

        $total = 0;
        $paid = 0;

        // Calculate totals.
        foreach ($paidsections as $section) {
            $total += $section->priceinfo['price'];
        }

        // Calculate paid amount.
        foreach ($userpayments as $payment) {
            if ($payment->itemtype === 'section') {
                $paid += $payment->amount;
            }
        }

        return [
            'total' => $total,
            'paid' => $paid,
            'remaining' => max(0, $total - $paid),
            'currency' => 'KES',
        ];
    }

    /**
     * Get the next unpaid section for a user.
     *
     * @param int $userid User ID
     * @param int $courseid Course ID
     * @return object|null The next section to pay for, or null if all paid
     */
    public static function get_next_unpaid_section(int $userid, int $courseid): ?object {
        $paidsections = self::get_paid_sections($courseid);

        foreach ($paidsections as $section) {
            if (!self::user_has_access($userid, $courseid, 'section', $section->id)) {
                return $section;
            }
        }

        return null;
    }

    /**
     * Format a phone number to the M-Pesa required format (254XXXXXXXXX).
     *
     * @param string $phone Phone number
     * @return string Formatted phone number
     */
    public static function format_phone_number(string $phone): string {
        // Remove any spaces, dashes, or plus signs.
        $phone = preg_replace('/[\s\-\+]/', '', $phone);

        // If it starts with 0, replace with 254.
        if (substr($phone, 0, 1) === '0') {
            $phone = '254' . substr($phone, 1);
        }

        // If it doesn't start with 254, prepend it.
        if (substr($phone, 0, 3) !== '254') {
            $phone = '254' . $phone;
        }

        return $phone;
    }

    /**
     * Validate a phone number.
     *
     * @param string $phone Phone number
     * @return bool True if valid
     */
    public static function validate_phone_number(string $phone): bool {
        $phone = self::format_phone_number($phone);

        // Should be 12 digits starting with 254.
        return preg_match('/^254[0-9]{9}$/', $phone) === 1;
    }
}
