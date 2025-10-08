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
 * Upgrade script for mod_rvs
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute mod_rvs upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_rvs_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Upgrades will be added here as needed in future versions.
    // Example structure:
    //
    // if ($oldversion < 2025100801) {
    //     // Upgrade code here
    //     upgrade_mod_savepoint(true, 2025100801, 'rvs');
    // }

    return true;
}

