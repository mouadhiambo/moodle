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
 * Definition of RVS scheduled tasks
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = array(
    // Note: The generate_content task is an adhoc task, not a scheduled task.
    // It is defined here for reference but registered programmatically when needed.
    // Future scheduled tasks can be added here if needed, for example:
    //
    // array(
    //     'classname' => 'mod_rvs\task\cleanup_old_content',
    //     'blocking' => 0,
    //     'minute' => '0',
    //     'hour' => '2',
    //     'day' => '*',
    //     'month' => '*',
    //     'dayofweek' => '*'
    // )
);

