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
 * Define all the restore steps that will be used by the restore_rvs_activity_task
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore one rvs activity
 */
class restore_rvs_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $paths[] = new restore_path_element('rvs', '/activity/rvs');
        $paths[] = new restore_path_element('rvs_content', '/activity/rvs/contents/content');
        $paths[] = new restore_path_element('rvs_mindmap', '/activity/rvs/mindmaps/mindmap');
        $paths[] = new restore_path_element('rvs_podcast', '/activity/rvs/podcasts/podcast');
        $paths[] = new restore_path_element('rvs_video', '/activity/rvs/videos/video');
        $paths[] = new restore_path_element('rvs_report', '/activity/rvs/reports/report');
        $paths[] = new restore_path_element('rvs_flashcard', '/activity/rvs/flashcards/flashcard');
        $paths[] = new restore_path_element('rvs_quiz', '/activity/rvs/quizzes/quiz');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_rvs($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // Insert the rvs record.
        $newitemid = $DB->insert_record('rvs', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    protected function process_rvs_content($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->rvsid = $this->get_new_parentid('rvs');
        $data->timecreated = $this->apply_date_offset($data->timecreated);

        $newitemid = $DB->insert_record('rvs_content', $data);
        $this->set_mapping('rvs_content', $oldid, $newitemid);
    }

    protected function process_rvs_mindmap($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->rvsid = $this->get_new_parentid('rvs');
        $data->timecreated = $this->apply_date_offset($data->timecreated);

        $newitemid = $DB->insert_record('rvs_mindmap', $data);
        $this->set_mapping('rvs_mindmap', $oldid, $newitemid);
    }

    protected function process_rvs_podcast($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->rvsid = $this->get_new_parentid('rvs');
        $data->timecreated = $this->apply_date_offset($data->timecreated);

        $newitemid = $DB->insert_record('rvs_podcast', $data);
        $this->set_mapping('rvs_podcast', $oldid, $newitemid);
    }

    protected function process_rvs_video($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->rvsid = $this->get_new_parentid('rvs');
        $data->timecreated = $this->apply_date_offset($data->timecreated);

        $newitemid = $DB->insert_record('rvs_video', $data);
        $this->set_mapping('rvs_video', $oldid, $newitemid);
    }

    protected function process_rvs_report($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->rvsid = $this->get_new_parentid('rvs');
        $data->timecreated = $this->apply_date_offset($data->timecreated);

        $newitemid = $DB->insert_record('rvs_report', $data);
        $this->set_mapping('rvs_report', $oldid, $newitemid);
    }

    protected function process_rvs_flashcard($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->rvsid = $this->get_new_parentid('rvs');
        $data->timecreated = $this->apply_date_offset($data->timecreated);

        $newitemid = $DB->insert_record('rvs_flashcard', $data);
        $this->set_mapping('rvs_flashcard', $oldid, $newitemid);
    }

    protected function process_rvs_quiz($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->rvsid = $this->get_new_parentid('rvs');
        $data->timecreated = $this->apply_date_offset($data->timecreated);

        $newitemid = $DB->insert_record('rvs_quiz', $data);
        $this->set_mapping('rvs_quiz', $oldid, $newitemid);
    }

    protected function after_execute() {
        // Add rvs related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_rvs', 'intro', null);
    }
}

