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
    global $DB, $CFG;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025101300) {
        require_once($CFG->dirroot . '/course/lib.php');

        $table = new xmldb_table('rvs_content');
        $field = new xmldb_field('sectionid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'content');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Attempt to backfill section ids for existing content records.
        $records = $DB->get_records('rvs_content');
        foreach ($records as $record) {
            if (!empty($record->sectionid)) {
                continue;
            }

            $module = ($record->sourcetype === 'book') ? 'book' : 'resource';

            try {
                $cm = get_coursemodule_from_instance($module, $record->sourceid, 0, false, IGNORE_MISSING);
            } catch (\moodle_exception $e) {
                $cm = null;
            }

            if ($cm && !empty($cm->section)) {
                $update = new \stdClass();
                $update->id = $record->id;
                $update->sectionid = (int)$cm->section;
                $DB->update_record('rvs_content', $update);
            }
        }

        upgrade_mod_savepoint(true, 2025101300, 'rvs');
    }

    return true;
}

