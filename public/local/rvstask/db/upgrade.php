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
 * Upgrade script for RVS Task plugin.
 *
 * @package    local_rvstask
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade this plugin.
 *
 * @param int $oldversion The old version of the plugin.
 * @return bool Always true.
 */
function xmldb_local_rvstask_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Add future upgrade steps here.
    // Example:
    // if ($oldversion < 2025112001) {
    //     // Upgrade steps.
    //     upgrade_plugin_savepoint(true, 2025112001, 'local', 'rvstask');
    // }

    return true;
}
