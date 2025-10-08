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
 * Define all the backup steps that will be used by the backup_rvs_activity_task
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define the complete rvs structure for backup, with file and id annotations
 */
class backup_rvs_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // Define each element separated.
        $rvs = new backup_nested_element('rvs', array('id'), array(
            'course', 'name', 'intro', 'introformat', 
            'enable_mindmap', 'enable_podcast', 'enable_video',
            'enable_report', 'enable_flashcard', 'enable_quiz',
            'auto_detect_books', 'auto_detect_files',
            'timecreated', 'timemodified'
        ));

        $contents = new backup_nested_element('contents');
        $content = new backup_nested_element('content', array('id'), array(
            'sourcetype', 'sourceid', 'content', 'timecreated'
        ));

        $mindmaps = new backup_nested_element('mindmaps');
        $mindmap = new backup_nested_element('mindmap', array('id'), array(
            'title', 'data', 'timecreated'
        ));

        $podcasts = new backup_nested_element('podcasts');
        $podcast = new backup_nested_element('podcast', array('id'), array(
            'title', 'script', 'audiourl', 'duration', 'timecreated'
        ));

        $videos = new backup_nested_element('videos');
        $video = new backup_nested_element('video', array('id'), array(
            'title', 'script', 'videourl', 'duration', 'timecreated'
        ));

        $reports = new backup_nested_element('reports');
        $report = new backup_nested_element('report', array('id'), array(
            'title', 'content', 'format', 'timecreated'
        ));

        $flashcards = new backup_nested_element('flashcards');
        $flashcard = new backup_nested_element('flashcard', array('id'), array(
            'question', 'answer', 'difficulty', 'timecreated'
        ));

        $quizzes = new backup_nested_element('quizzes');
        $quiz = new backup_nested_element('quiz', array('id'), array(
            'question', 'options', 'correctanswer', 'explanation', 
            'difficulty', 'timecreated'
        ));

        // Build the tree.
        $rvs->add_child($contents);
        $contents->add_child($content);

        $rvs->add_child($mindmaps);
        $mindmaps->add_child($mindmap);

        $rvs->add_child($podcasts);
        $podcasts->add_child($podcast);

        $rvs->add_child($videos);
        $videos->add_child($video);

        $rvs->add_child($reports);
        $reports->add_child($report);

        $rvs->add_child($flashcards);
        $flashcards->add_child($flashcard);

        $rvs->add_child($quizzes);
        $quizzes->add_child($quiz);

        // Define sources.
        $rvs->set_source_table('rvs', array('id' => backup::VAR_ACTIVITYID));
        $content->set_source_table('rvs_content', array('rvsid' => backup::VAR_PARENTID));
        $mindmap->set_source_table('rvs_mindmap', array('rvsid' => backup::VAR_PARENTID));
        $podcast->set_source_table('rvs_podcast', array('rvsid' => backup::VAR_PARENTID));
        $video->set_source_table('rvs_video', array('rvsid' => backup::VAR_PARENTID));
        $report->set_source_table('rvs_report', array('rvsid' => backup::VAR_PARENTID));
        $flashcard->set_source_table('rvs_flashcard', array('rvsid' => backup::VAR_PARENTID));
        $quiz->set_source_table('rvs_quiz', array('rvsid' => backup::VAR_PARENTID));

        // Define id annotations.
        $content->annotate_ids('course_module', 'sourceid');

        // Define file annotations.
        $rvs->annotate_files('mod_rvs', 'intro', null);

        // Return the root element (rvs), wrapped into standard activity structure.
        return $this->prepare_activity_structure($rvs);
    }
}

