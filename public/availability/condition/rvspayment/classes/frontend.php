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
 * Front-end class for RVS Payment condition.
 *
 * @package    availability_rvspayment
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_rvspayment;

defined('MOODLE_INTERNAL') || die();

/**
 * Front-end class for RVS Payment condition.
 *
 * @package    availability_rvspayment
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontend extends \core_availability\frontend {

    /**
     * Gets the strings needed for JavaScript.
     *
     * @return array Array of required string identifiers
     */
    protected function get_javascript_strings() {
        return [
            'label_price',
            'label_currency',
            'label_isfree',
            'label_requireprevious',
            'error_setprice',
            'currency_kes',
        ];
    }

    /**
     * Gets additional parameters for the plugin's initInner function.
     *
     * @param \stdClass $course Course object
     * @param \cm_info $cm Course-module currently being edited (null if none)
     * @param \section_info $section Section currently being edited (null if none)
     * @return array Array of parameters for the JavaScript function
     */
    protected function get_javascript_init_params($course, ?\cm_info $cm = null,
            ?\section_info $section = null) {
        // Get the default price from plugin settings or use 0.
        $defaultprice = get_config('availability_rvspayment', 'default_price') ?: 0;
        
        // Available currencies.
        $currencies = [
            ['code' => 'KES', 'name' => get_string('currency_kes', 'availability_rvspayment')],
        ];

        return [
            $defaultprice,
            $currencies,
            ($section !== null), // Whether we're editing a section.
        ];
    }

    /**
     * Decides whether this plugin should be available in a given course.
     *
     * @param \stdClass $course Course object
     * @param \cm_info $cm Course-module currently being edited (null if none)
     * @param \section_info $section Section currently being edited (null if none)
     * @return bool True if plugin should be available
     */
    protected function allow_add($course, ?\cm_info $cm = null,
            ?\section_info $section = null) {
        // Always allow adding payment restrictions.
        // M-Pesa configuration will be checked at payment time.
        return true;
    }
}
