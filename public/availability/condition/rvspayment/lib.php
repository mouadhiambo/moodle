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
 * Library functions for availability_rvspayment.
 *
 * @package    availability_rvspayment
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extend course navigation to add payment report and authorization links.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course object
 * @param context $context The context
 */
function availability_rvspayment_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('moodle/course:update', $context)) {
        // Add payment report link.
        $url = new moodle_url('/availability/condition/rvspayment/report.php', ['courseid' => $course->id]);
        $navigation->add(
            get_string('report_title', 'availability_rvspayment'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'rvspaymentreport',
            new pix_icon('i/report', '')
        );

        // Add manual authorization link.
        $authorizeurl = new moodle_url('/availability/condition/rvspayment/authorize.php', ['courseid' => $course->id]);
        $navigation->add(
            get_string('authorize_title', 'availability_rvspayment'),
            $authorizeurl,
            navigation_node::TYPE_SETTING,
            null,
            'rvspaymentauthorize',
            new pix_icon('i/permissions', '')
        );
    }
}
