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
 * Event observers for mod_rvs
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_rvs;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer class
 */
class observer {

    /**
     * Observer for book chapter created event
     *
     * @param \mod_book\event\chapter_created $event
     */
    public static function book_chapter_created(\mod_book\event\chapter_created $event) {
        global $DB;

        $courseid = $event->courseid;
        
        // Find all RVS instances in the course with auto-detect enabled.
        $rvss = $DB->get_records('rvs', array('course' => $courseid, 'auto_detect_books' => 1));
        
        foreach ($rvss as $rvs) {
            self::add_book_content($rvs->id, $event->objectid);
        }
    }

    /**
     * Observer for book chapter updated event
     *
     * @param \mod_book\event\chapter_updated $event
     */
    public static function book_chapter_updated(\mod_book\event\chapter_updated $event) {
        global $DB;

        $courseid = $event->courseid;
        
        // Find all RVS instances in the course with auto-detect enabled.
        $rvss = $DB->get_records('rvs', array('course' => $courseid, 'auto_detect_books' => 1));
        
        foreach ($rvss as $rvs) {
            self::update_book_content($rvs->id, $event->objectid);
        }
    }

    /**
     * Observer for resource viewed event
     *
     * @param \mod_resource\event\course_module_viewed $event
     */
    public static function resource_viewed(\mod_resource\event\course_module_viewed $event) {
        global $DB;

        $courseid = $event->courseid;
        
        // Find all RVS instances in the course with auto-detect enabled.
        $rvss = $DB->get_records('rvs', array('course' => $courseid, 'auto_detect_files' => 1));
        
        foreach ($rvss as $rvs) {
            self::add_file_content($rvs->id, $event->objectid);
        }
    }

    /**
     * Observer for course module created event
     *
     * @param \core\event\course_module_created $event
     */
    public static function course_module_created(\core\event\course_module_created $event) {
        global $DB;

        $courseid = $event->courseid;
        $modname = $event->other['modulename'];
        
        // Only process book and resource modules.
        if (!in_array($modname, array('book', 'resource'))) {
            return;
        }

        $rvss = $DB->get_records('rvs', array('course' => $courseid));
        
        foreach ($rvss as $rvs) {
            if ($modname == 'book' && $rvs->auto_detect_books) {
                self::add_book_content($rvs->id, $event->objectid);
            } else if ($modname == 'resource' && $rvs->auto_detect_files) {
                self::add_file_content($rvs->id, $event->objectid);
            }
        }
    }

    /**
     * Add book content to RVS
     *
     * @param int $rvsid
     * @param int $bookid
     */
    private static function add_book_content($rvsid, $bookid) {
        global $DB;

        // Check if already exists.
        $exists = $DB->record_exists('rvs_content', array(
            'rvsid' => $rvsid,
            'sourcetype' => 'book',
            'sourceid' => $bookid
        ));

        if (!$exists) {
            $book = $DB->get_record('book', array('id' => $bookid));
            $chapters = $DB->get_records('book_chapters', array('bookid' => $bookid), 'pagenum ASC');
            
            $content = '';
            foreach ($chapters as $chapter) {
                $content .= $chapter->title . "\n\n" . $chapter->content . "\n\n";
            }

            $record = new \stdClass();
            $record->rvsid = $rvsid;
            $record->sourcetype = 'book';
            $record->sourceid = $bookid;
            $record->content = $content;
            $record->timecreated = time();

            $DB->insert_record('rvs_content', $record);
            
            // Trigger content generation.
            self::trigger_content_generation($rvsid);
        }
    }

    /**
     * Update book content in RVS
     *
     * @param int $rvsid
     * @param int $bookid
     */
    private static function update_book_content($rvsid, $bookid) {
        global $DB;

        $record = $DB->get_record('rvs_content', array(
            'rvsid' => $rvsid,
            'sourcetype' => 'book',
            'sourceid' => $bookid
        ));

        if ($record) {
            $book = $DB->get_record('book', array('id' => $bookid));
            $chapters = $DB->get_records('book_chapters', array('bookid' => $bookid), 'pagenum ASC');
            
            $content = '';
            foreach ($chapters as $chapter) {
                $content .= $chapter->title . "\n\n" . $chapter->content . "\n\n";
            }

            $record->content = $content;
            $DB->update_record('rvs_content', $record);
            
            // Trigger content regeneration.
            self::trigger_content_generation($rvsid);
        }
    }

    /**
     * Add file content to RVS
     *
     * @param int $rvsid
     * @param int $resourceid
     */
    private static function add_file_content($rvsid, $resourceid) {
        global $DB;

        // Check if already exists.
        $exists = $DB->record_exists('rvs_content', array(
            'rvsid' => $rvsid,
            'sourcetype' => 'file',
            'sourceid' => $resourceid
        ));

        if (!$exists) {
            $resource = $DB->get_record('resource', array('id' => $resourceid));
            
            $record = new \stdClass();
            $record->rvsid = $rvsid;
            $record->sourcetype = 'file';
            $record->sourceid = $resourceid;
            $record->content = ''; // Content extraction would be done separately.
            $record->timecreated = time();

            $DB->insert_record('rvs_content', $record);
            
            // Trigger content generation.
            self::trigger_content_generation($rvsid);
        }
    }

    /**
     * Trigger content generation for all enabled modules
     *
     * @param int $rvsid
     */
    private static function trigger_content_generation($rvsid) {
        // This would be implemented as an adhoc task or scheduled task.
        // For now, we'll create a flag that generation is needed.
        global $DB;
        
        $rvs = $DB->get_record('rvs', array('id' => $rvsid));
        if ($rvs) {
            // Queue generation tasks.
            $task = new \mod_rvs\task\generate_content();
            $task->set_custom_data(array('rvsid' => $rvsid));
            \core\task\manager::queue_adhoc_task($task);
        }
    }
}



