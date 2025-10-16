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

use mod_rvs\content\manager as content_manager;

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
        
        // Only process book, resource and scorm modules.
        if (!in_array($modname, array('book', 'resource', 'scorm'))) {
            return;
        }

        $rvss = $DB->get_records('rvs', array('course' => $courseid));
        foreach ($rvss as $rvs) {
            if ($modname == 'book' && !empty($rvs->auto_detect_books)) {
                self::add_book_content($rvs->id, $event->objectid);
            } else if ($modname == 'resource' && !empty($rvs->auto_detect_files)) {
                self::add_file_content($rvs->id, $event->objectid);
            } else if ($modname == 'scorm' && !empty($rvs->auto_detect_scorm)) {
                self::add_scorm_content($rvs->id, $event->objectid);
            }
        }
    }

    /**
     * Observer for SCORM viewed event (used as a proxy for availability after parse)
     *
     * @param \mod_scorm\event\course_module_viewed $event
     */
    public static function scorm_viewed(\mod_scorm\event\course_module_viewed $event) {
        global $DB;

        $courseid = $event->courseid;
        $scormid = $event->objectid;

        $rvss = $DB->get_records('rvs', array('course' => $courseid, 'auto_detect_scorm' => 1));
        foreach ($rvss as $rvs) {
            self::add_scorm_content($rvs->id, $scormid);
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

        try {
            if (!content_manager::get_rvs_meta($rvsid)) {
                return;
            }

            $sectionid = content_manager::get_matching_source_section($rvsid, 'book', $bookid);
            if ($sectionid === null) {
                mtrace('[DEBUG] Skipping book ' . $bookid . ' for RVS ' . $rvsid . ' because it is not in the same section.');
                return;
            }

            // Check if already exists.
            $exists = $DB->record_exists('rvs_content', array(
                'rvsid' => $rvsid,
                'sourcetype' => 'book',
                'sourceid' => $bookid
            ));

            if (!$exists) {
                mtrace('[INFO] Adding book content (ID: ' . $bookid . ') to RVS (ID: ' . $rvsid . ')');
                
                // Extract content from the book using book_extractor.
                $content = \mod_rvs\content\book_extractor::extract_content($bookid);
                
                if (empty($content)) {
                    $warning = 'No content extracted from book ' . $bookid . 
                              '. The book may be empty or contain unsupported content.';
                    mtrace('[WARNING] ' . $warning);
                    debugging($warning, DEBUG_DEVELOPER);
                } else {
                    mtrace('[INFO] Successfully extracted ' . strlen($content) . 
                           ' characters from book ' . $bookid);
                }

                $record = new \stdClass();
                $record->rvsid = $rvsid;
                $record->sourcetype = 'book';
                $record->sourceid = $bookid;
                $record->content = $content;
                $record->sectionid = $sectionid;
                $record->timecreated = time();

                $DB->insert_record('rvs_content', $record);
                
                // Trigger content generation.
                self::trigger_content_generation($rvsid);
            }
        } catch (\Exception $e) {
            $error = 'Failed to add book content (ID: ' . $bookid . ') to RVS (ID: ' . 
                    $rvsid . '): ' . $e->getMessage();
            mtrace('[ERROR] ' . $error);
            debugging($error, DEBUG_NORMAL);
            
            // Send admin notification for critical extraction failure.
            notification_helper::notify_extraction_failure('book', $bookid, $e->getMessage(), $rvsid);
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

        try {
            if (!content_manager::get_rvs_meta($rvsid)) {
                return;
            }

            $sectionid = content_manager::get_matching_source_section($rvsid, 'book', $bookid);

            $record = $DB->get_record('rvs_content', array(
                'rvsid' => $rvsid,
                'sourcetype' => 'book',
                'sourceid' => $bookid
            ));

            if ($record) {
                if ($sectionid === null) {
                    mtrace('[INFO] Removing book ' . $bookid . ' from RVS ' . $rvsid . ' because it moved to a different section.');
                    $DB->delete_records('rvs_content', array('id' => $record->id));
                    self::trigger_content_generation($rvsid);
                    return;
                }

                mtrace('[INFO] Updating book content (ID: ' . $bookid . ') in RVS (ID: ' . $rvsid . ')');
                
                // Extract content from the book using book_extractor.
                $content = \mod_rvs\content\book_extractor::extract_content($bookid);
                
                if (empty($content)) {
                    $warning = 'No content extracted from book ' . $bookid . ' during update. ' .
                              'The book may be empty or contain unsupported content.';
                    mtrace('[WARNING] ' . $warning);
                    debugging($warning, DEBUG_DEVELOPER);
                } else {
                    mtrace('[INFO] Successfully updated content from book ' . $bookid . 
                           ' (' . strlen($content) . ' characters)');
                }

                $record->content = $content;
                    $record->sectionid = $sectionid;
                $DB->update_record('rvs_content', $record);
                
                // Trigger content regeneration.
                self::trigger_content_generation($rvsid);
            }
        } catch (\Exception $e) {
            $error = 'Failed to update book content (ID: ' . $bookid . ') in RVS (ID: ' . 
                    $rvsid . '): ' . $e->getMessage();
            mtrace('[ERROR] ' . $error);
            debugging($error, DEBUG_NORMAL);
            
            // Send admin notification for critical extraction failure.
            notification_helper::notify_extraction_failure('book', $bookid, $e->getMessage(), $rvsid);
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

        try {
            if (!content_manager::get_rvs_meta($rvsid)) {
                return;
            }

            $sectionid = content_manager::get_matching_source_section($rvsid, 'file', $resourceid);
            if ($sectionid === null) {
                mtrace('[DEBUG] Skipping file resource ' . $resourceid . ' for RVS ' . $rvsid . ' because it is not in the same section.');
                return;
            }

            // Check if already exists.
            $exists = $DB->record_exists('rvs_content', array(
                'rvsid' => $rvsid,
                'sourcetype' => 'file',
                'sourceid' => $resourceid
            ));

            if (!$exists) {
                mtrace('[INFO] Adding file content (resource ID: ' . $resourceid . ') to RVS (ID: ' . $rvsid . ')');
                // Extract content from the file.
                $content = \mod_rvs\content\file_extractor::extract_content($resourceid);
                
                if (empty($content)) {
                    $warning = 'No content extracted from resource ' . $resourceid . 
                              '. The file may be empty, unsupported, or unreadable.';
                    mtrace('[WARNING] ' . $warning);
                    debugging($warning, DEBUG_DEVELOPER);
                } else {
                    mtrace('[INFO] Successfully extracted ' . strlen($content) . 
                           ' characters from resource ' . $resourceid);
                }
                
                $record = new \stdClass();
                $record->rvsid = $rvsid;
                $record->sourcetype = 'file';
                $record->sourceid = $resourceid;
                $record->content = $content;
                $record->sectionid = $sectionid;
                $record->timecreated = time();

                $DB->insert_record('rvs_content', $record);
                
                // Trigger content generation.
                self::trigger_content_generation($rvsid);
            } else {
                // Ensure existing records stay in sync with section changes.
                $record = $DB->get_record('rvs_content', array(
                    'rvsid' => $rvsid,
                    'sourcetype' => 'file',
                    'sourceid' => $resourceid
                ));

                if ($record && (int)$record->sectionid !== (int)$sectionid) {
                    mtrace('[INFO] Removing file resource ' . $resourceid . ' from RVS ' . $rvsid . ' because it moved to a different section.');
                    $DB->delete_records('rvs_content', array('id' => $record->id));
                    self::trigger_content_generation($rvsid);
                }
            }
        } catch (\Exception $e) {
            $error = 'Failed to add file content (resource ID: ' . $resourceid . ') to RVS (ID: ' . 
                    $rvsid . '): ' . $e->getMessage();
            mtrace('[ERROR] ' . $error);
            debugging($error, DEBUG_NORMAL);
            
            // Send admin notification for critical extraction failure.
            notification_helper::notify_extraction_failure('file', $resourceid, $e->getMessage(), $rvsid);
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

    /**
     * Add SCORM content to RVS
     *
     * @param int $rvsid
     * @param int $scormid
     */
    private static function add_scorm_content($rvsid, $scormid) {
        global $DB;

        try {
            if (!content_manager::get_rvs_meta($rvsid)) {
                return;
            }

            $sectionid = content_manager::get_matching_source_section($rvsid, 'scorm', $scormid);
            if ($sectionid === null) {
                mtrace('[DEBUG] Skipping SCORM ' . $scormid . ' for RVS ' . $rvsid . ' because it is not in the same section.');
                return;
            }

            $exists = $DB->record_exists('rvs_content', array(
                'rvsid' => $rvsid,
                'sourcetype' => 'scorm',
                'sourceid' => $scormid
            ));

            if (!$exists) {
                mtrace('[INFO] Adding SCORM content (ID: ' . $scormid . ') to RVS (ID: ' . $rvsid . ')');

                $content = \mod_rvs\content\scorm_extractor::extract_content($scormid);

                if (empty($content)) {
                    $warning = 'No content extracted from SCORM ' . $scormid . ' (may contain non-text assets only).';
                    mtrace('[WARNING] ' . $warning);
                    debugging($warning, DEBUG_DEVELOPER);
                } else {
                    mtrace('[INFO] Successfully extracted ' . strlen($content) . ' characters from SCORM ' . $scormid);
                }

                $record = new \stdClass();
                $record->rvsid = $rvsid;
                $record->sourcetype = 'scorm';
                $record->sourceid = $scormid;
                $record->content = $content;
                $record->sectionid = $sectionid;
                $record->timecreated = time();

                $DB->insert_record('rvs_content', $record);

                self::trigger_content_generation($rvsid);
            }
        } catch (\Exception $e) {
            $error = 'Failed to add SCORM content (ID: ' . $scormid . ') to RVS (ID: ' . $rvsid . '): ' . $e->getMessage();
            mtrace('[ERROR] ' . $error);
            debugging($error, DEBUG_NORMAL);
            notification_helper::notify_extraction_failure('scorm', $scormid, $e->getMessage(), $rvsid);
        }
    }
}



