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
 * Upgrade script for availability_rvspayment.
 *
 * @package    availability_rvspayment
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the availability_rvspayment plugin.
 *
 * @param int $oldversion The old version of the plugin
 * @return bool
 */
function xmldb_availability_rvspayment_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026011400) {
        // Define table availability_rvspayment_override to be created.
        $table = new xmldb_table('availability_rvspayment_override');

        // Adding fields to table availability_rvspayment_override.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('itemtype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('itemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('reason', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('authorizedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timeexpires', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table availability_rvspayment_override.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('authorizedby', XMLDB_KEY_FOREIGN, ['authorizedby'], 'user', ['id']);

        // Adding indexes to table availability_rvspayment_override.
        $table->add_index('userid_courseid_item', XMLDB_INDEX_UNIQUE, ['userid', 'courseid', 'itemtype', 'itemid']);
        $table->add_index('itemtype_itemid', XMLDB_INDEX_NOTUNIQUE, ['itemtype', 'itemid']);
        $table->add_index('timeexpires', XMLDB_INDEX_NOTUNIQUE, ['timeexpires']);

        // Conditionally launch create table for availability_rvspayment_override.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Rvspayment savepoint reached.
        upgrade_plugin_savepoint(true, 2026011400, 'availability', 'rvspayment');
    }

    return true;
}
