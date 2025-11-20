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
 * Email template manager class.
 *
 * @package    local_rvstask
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rvstask;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for managing email templates.
 */
class template_manager {

    /**
     * Get all email templates.
     *
     * @return array Array of template objects.
     */
    public static function get_all_templates() {
        global $DB;
        return $DB->get_records('local_rvstask_templates', null, 'name ASC');
    }

    /**
     * Get a single template by ID.
     *
     * @param int $id Template ID.
     * @return object|false Template object or false.
     */
    public static function get_template($id) {
        global $DB;
        return $DB->get_record('local_rvstask_templates', ['id' => $id]);
    }

    /**
     * Create a new email template.
     *
     * @param object $data Template data.
     * @return int New template ID.
     */
    public static function create_template($data) {
        global $DB, $USER;

        $record = new \stdClass();
        $record->name = $data->name;
        $record->subject = $data->subject;
        $record->body = $data->body;
        $record->bodyformat = $data->bodyformat ?? FORMAT_HTML;
        $record->enabled = $data->enabled ?? 1;
        $record->timecreated = time();
        $record->timemodified = time();
        $record->usermodified = $USER->id;

        return $DB->insert_record('local_rvstask_templates', $record);
    }

    /**
     * Update an existing email template.
     *
     * @param int $id Template ID.
     * @param object $data Template data.
     * @return bool Success status.
     */
    public static function update_template($id, $data) {
        global $DB, $USER;

        $record = $DB->get_record('local_rvstask_templates', ['id' => $id]);
        if (!$record) {
            return false;
        }

        $record->name = $data->name;
        $record->subject = $data->subject;
        $record->body = $data->body;
        $record->bodyformat = $data->bodyformat ?? $record->bodyformat;
        $record->enabled = $data->enabled ?? $record->enabled;
        $record->timemodified = time();
        $record->usermodified = $USER->id;

        return $DB->update_record('local_rvstask_templates', $record);
    }

    /**
     * Delete an email template.
     *
     * @param int $id Template ID.
     * @return bool Success status.
     */
    public static function delete_template($id) {
        global $DB;

        // Check if template is being used in queue.
        $inuse = $DB->record_exists('local_rvstask_queue', ['templateid' => $id, 'sent' => 0]);
        if ($inuse) {
            return false; // Cannot delete template that has pending emails.
        }

        return $DB->delete_records('local_rvstask_templates', ['id' => $id]);
    }

    /**
     * Get enabled templates.
     *
     * @return array Array of enabled template objects.
     */
    public static function get_enabled_templates() {
        global $DB;
        return $DB->get_records('local_rvstask_templates', ['enabled' => 1], 'name ASC');
    }
}
