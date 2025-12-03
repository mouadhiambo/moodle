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
 * RVS Payment availability condition.
 *
 * @package    availability_rvspayment
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_rvspayment;

use core_availability\info;
use core_availability\info_module;
use core_availability\info_section;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * RVS Payment condition class.
 *
 * This condition allows teachers to require payment to access sections or activities.
 * Students can pay via M-Pesa STK push to unlock the content.
 *
 * @package    availability_rvspayment
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {

    /** @var float Price required to unlock this item */
    protected $price;

    /** @var string Currency code */
    protected $currency;

    /** @var bool Whether this item is free (no payment required) */
    protected $isfree;

    /** @var bool Require previous section to be paid before this one */
    protected $requireprevious;

    /**
     * Constructor.
     *
     * @param stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct($structure) {
        // Get price (default to 0 if not set).
        if (isset($structure->price) && is_numeric($structure->price)) {
            $this->price = (float)$structure->price;
        } else {
            $this->price = 0;
        }

        // Get currency (default to KES).
        if (isset($structure->currency) && is_string($structure->currency)) {
            $this->currency = $structure->currency;
        } else {
            $this->currency = 'KES';
        }

        // Check if this is marked as free.
        if (isset($structure->isfree)) {
            $this->isfree = (bool)$structure->isfree;
        } else {
            $this->isfree = ($this->price <= 0);
        }

        // Check if previous section payment is required.
        if (isset($structure->requireprevious)) {
            $this->requireprevious = (bool)$structure->requireprevious;
        } else {
            $this->requireprevious = false;
        }
    }

    /**
     * Saves tree data back to a structure object.
     *
     * @return stdClass Structure object (ready to be made into JSON format)
     */
    public function save(): stdClass {
        return (object)[
            'type' => 'rvspayment',
            'price' => $this->price,
            'currency' => $this->currency,
            'isfree' => $this->isfree,
            'requireprevious' => $this->requireprevious,
        ];
    }

    /**
     * Returns a JSON object which corresponds to a condition of this type.
     *
     * @param float $price Price amount
     * @param string $currency Currency code
     * @param bool $isfree Whether this is free
     * @param bool $requireprevious Whether previous section payment is required
     * @return stdClass Object representing condition
     */
    public static function get_json(float $price, string $currency = 'KES', 
            bool $isfree = false, bool $requireprevious = false): stdClass {
        return (object)[
            'type' => 'rvspayment',
            'price' => $price,
            'currency' => $currency,
            'isfree' => $isfree,
            'requireprevious' => $requireprevious,
        ];
    }

    /**
     * Determines whether a particular item is currently available
     * according to this availability condition.
     *
     * @param bool $not Set true if we are inverting the condition
     * @param info $info Item we're checking
     * @param bool $grabthelot Performance hint
     * @param int $userid User ID to check availability for
     * @return bool True if available
     */
    public function is_available($not, info $info, $grabthelot, $userid): bool {
        global $DB;

        // If marked as free, always available.
        if ($this->isfree || $this->price <= 0) {
            return $not ? false : true;
        }

        // Get the item details.
        $course = $info->get_course();
        $itemtype = $this->get_item_type($info);
        $itemid = $this->get_item_id($info);

        // Check if user has paid for this item.
        $haspaid = $this->user_has_paid($userid, $course->id, $itemtype, $itemid);

        // If requireprevious is set, check that all previous sections are paid.
        if ($this->requireprevious && !$haspaid) {
            $previouspaid = $this->check_previous_sections_paid($userid, $info);
            if (!$previouspaid) {
                return $not ? true : false;
            }
        }

        $allow = $haspaid;

        if ($not) {
            $allow = !$allow;
        }

        return $allow;
    }

    /**
     * Get the type of item (section or module).
     *
     * @param info $info The availability info object
     * @return string 'section' or 'module'
     */
    protected function get_item_type(info $info): string {
        if ($info instanceof info_section) {
            return 'section';
        }
        return 'module';
    }

    /**
     * Get the ID of the item.
     *
     * @param info $info The availability info object
     * @return int The item ID
     */
    protected function get_item_id(info $info): int {
        if ($info instanceof info_section) {
            return $info->get_section()->id;
        }
        if ($info instanceof info_module) {
            return $info->get_course_module()->id;
        }
        return 0;
    }

    /**
     * Check if a user has paid for this specific item.
     *
     * @param int $userid User ID
     * @param int $courseid Course ID
     * @param string $itemtype 'section' or 'module'
     * @param int $itemid The item ID
     * @return bool True if user has paid
     */
    protected function user_has_paid(int $userid, int $courseid, string $itemtype, int $itemid): bool {
        global $DB;

        $payment = $DB->get_record('availability_rvspayment_pay', [
            'userid' => $userid,
            'courseid' => $courseid,
            'itemtype' => $itemtype,
            'itemid' => $itemid,
            'status' => 'completed',
        ]);

        return !empty($payment);
    }

    /**
     * Check if all previous sections have been paid for.
     *
     * @param int $userid User ID
     * @param info $info The availability info object
     * @return bool True if all previous sections are paid
     */
    protected function check_previous_sections_paid(int $userid, info $info): bool {
        global $DB;

        if (!($info instanceof info_section)) {
            return true;
        }

        $section = $info->get_section();
        $course = $info->get_course();
        $modinfo = get_fast_modinfo($course);

        // Get all sections before this one.
        foreach ($modinfo->get_section_info_all() as $othersection) {
            if ($othersection->section >= $section->section) {
                continue;
            }

            // Skip section 0 (general section).
            if ($othersection->section == 0) {
                continue;
            }

            // Check if this section has an RVS payment restriction.
            if (!empty($othersection->availability)) {
                $tree = json_decode($othersection->availability);
                if ($this->section_requires_payment($tree)) {
                    // Check if user has paid for this section.
                    if (!$this->user_has_paid($userid, $course->id, 'section', $othersection->id)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Check if an availability tree contains an RVS payment condition.
     *
     * @param object $tree The availability tree
     * @return bool True if payment is required
     */
    protected function section_requires_payment($tree): bool {
        if (!$tree) {
            return false;
        }

        if (isset($tree->type) && $tree->type === 'rvspayment') {
            if (!isset($tree->isfree) || !$tree->isfree) {
                if (isset($tree->price) && $tree->price > 0) {
                    return true;
                }
            }
        }

        if (isset($tree->c) && is_array($tree->c)) {
            foreach ($tree->c as $condition) {
                if ($this->section_requires_payment($condition)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Gets the description for this condition.
     *
     * @param bool $full Set true for full info, false for student summary
     * @param bool $not Set true if condition is inverted
     * @param info $info The availability info object
     * @return string Description text
     */
    public function get_description($full, $not, info $info): string {
        if ($this->isfree || $this->price <= 0) {
            return get_string('description_free', 'availability_rvspayment');
        }

        $price = number_format($this->price, 2);

        if ($not) {
            return get_string('description_not', 'availability_rvspayment', 
                ['price' => $price, 'currency' => $this->currency]);
        }

        // Build description with payment link.
        $course = $info->get_course();
        $itemtype = $this->get_item_type($info);
        $itemid = $this->get_item_id($info);

        // Use callback to generate the payment link at display time.
        return $this->description_callback([
            $price,
            $this->currency,
            $course->id,
            $itemtype,
            $itemid,
        ]);
    }

    /**
     * Gets the callback value for the description.
     * This is called when the description is actually displayed.
     *
     * @param \course_modinfo $modinfo The course modinfo
     * @param \context $context The context
     * @param array $params Parameters from description_callback
     * @return string The formatted description with payment link
     */
    public static function get_description_callback_value(\course_modinfo $modinfo, \context $context, array $params): string {
        global $USER, $DB;

        list($price, $currency, $courseid, $itemtype, $itemid) = $params;

        // Check if the current user has already paid for this item.
        if (isloggedin() && !isguestuser()) {
            $payment = $DB->get_record('availability_rvspayment_pay', [
                'userid' => $USER->id,
                'courseid' => $courseid,
                'itemtype' => $itemtype,
                'itemid' => $itemid,
                'status' => 'completed',
            ]);

            if ($payment) {
                // User has paid - show confirmation message instead of payment link.
                return get_string('description_paid', 'availability_rvspayment');
            }
        }

        // User has not paid - show payment link.
        $paymenturl = new \moodle_url('/availability/condition/rvspayment/pay.php', [
            'courseid' => $courseid,
            'itemtype' => $itemtype,
            'itemid' => $itemid,
        ]);

        $a = new stdClass();
        $a->price = $price;
        $a->currency = $currency;
        $a->payurl = $paymenturl->out(false);

        return get_string('description_withpayment', 'availability_rvspayment', $a);
    }

    /**
     * Returns a string for debugging.
     *
     * @return string Debug string
     */
    public function get_debug_string(): string {
        if ($this->isfree) {
            return 'FREE';
        }
        return $this->currency . ' ' . number_format($this->price, 2);
    }

    /**
     * Get the price for this condition.
     *
     * @return float The price
     */
    public function get_price(): float {
        return $this->price;
    }

    /**
     * Get the currency for this condition.
     *
     * @return string The currency code
     */
    public function get_currency(): string {
        return $this->currency;
    }

    /**
     * Check if this condition is free.
     *
     * @return bool True if free
     */
    public function is_free(): bool {
        return $this->isfree || $this->price <= 0;
    }
}
