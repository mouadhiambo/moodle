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
 * Adhoc task for generating RVS content
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_rvs\task;

defined('MOODLE_INTERNAL') || die();

use mod_rvs\local\error_tracker;

/**
 * Adhoc task to generate AI content for RVS
 */
class generate_content extends \core\task\adhoc_task {

    /**
     * Execute the task
     */
    public function execute() {
        global $DB;

        $data = $this->get_custom_data();
        $rvsid = $data->rvsid;
        $taskstarttime = microtime(true);

        mtrace("Starting content generation for RVS ID {$rvsid}...");

        $rvs = $DB->get_record('rvs', array('id' => $rvsid));
        if (!$rvs) {
            mtrace("ERROR: RVS activity with ID {$rvsid} not found.");
            return;
        }

        // Get all content for this RVS instance.
        $content = \mod_rvs\ai\generator::get_content($rvsid);

        if (!\mod_rvs\ai\generator::is_ai_configured()) {
            mtrace("WARNING: AI provider not configured. Skipping content generation for RVS ID {$rvsid}.");
            $message = \get_string('ainotconfigured', 'mod_rvs') . '. ' . \get_string('ainotconfigured_help', 'mod_rvs');
            $this->store_error_for_enabled_modules($rvs, $message);
            return;
        }

        if (empty($content)) {
            mtrace("WARNING: No source content found for RVS ID {$rvsid}. Add books or files to generate AI content.");
            $this->store_error_for_enabled_modules($rvs, \get_string('usererror_no_content', 'mod_rvs'));
            return;
        }

        mtrace("Found " . strlen($content) . " characters of source content.");

        $successcount = 0;
        $failurecount = 0;

        try {
            // Generate mind map if enabled.
            if ($rvs->enable_mindmap) {
                $starttime = microtime(true);
                mtrace("Generating mind map for RVS ID {$rvsid}...");
                try {
                    $this->generate_mindmap($rvsid, $content);
                    $elapsed = round(microtime(true) - $starttime, 2);
                    mtrace("Mind map generated successfully in {$elapsed} seconds.");
                    $successcount++;
                } catch (\Exception $e) {
                    $elapsed = round(microtime(true) - $starttime, 2);
                    mtrace("ERROR: Mind map generation failed after {$elapsed} seconds: " . $e->getMessage());
                    $failurecount++;
                }
            }

            // Generate podcast if enabled.
            if ($rvs->enable_podcast) {
                $starttime = microtime(true);
                mtrace("Generating podcast for RVS ID {$rvsid}...");
                try {
                    $this->generate_podcast($rvsid, $content);
                    $elapsed = round(microtime(true) - $starttime, 2);
                    mtrace("Podcast generated successfully in {$elapsed} seconds.");
                    $successcount++;
                } catch (\Exception $e) {
                    $elapsed = round(microtime(true) - $starttime, 2);
                    mtrace("ERROR: Podcast generation failed after {$elapsed} seconds: " . $e->getMessage());
                    $failurecount++;
                }
            }

            // Generate video if enabled.
            if ($rvs->enable_video) {
                $starttime = microtime(true);
                mtrace("Generating video script for RVS ID {$rvsid}...");
                try {
                    $this->generate_video($rvsid, $content);
                    $elapsed = round(microtime(true) - $starttime, 2);
                    mtrace("Video script generated successfully in {$elapsed} seconds.");
                    $successcount++;
                } catch (\Exception $e) {
                    $elapsed = round(microtime(true) - $starttime, 2);
                    mtrace("ERROR: Video script generation failed after {$elapsed} seconds: " . $e->getMessage());
                    $failurecount++;
                }
            }

            // Generate report if enabled.
            if ($rvs->enable_report) {
                $starttime = microtime(true);
                mtrace("Generating report for RVS ID {$rvsid}...");
                try {
                    $this->generate_report($rvsid, $content);
                    $elapsed = round(microtime(true) - $starttime, 2);
                    mtrace("Report generated successfully in {$elapsed} seconds.");
                    $successcount++;
                } catch (\Exception $e) {
                    $elapsed = round(microtime(true) - $starttime, 2);
                    mtrace("ERROR: Report generation failed after {$elapsed} seconds: " . $e->getMessage());
                    $failurecount++;
                }
            }

            // Generate flashcards if enabled.
            if ($rvs->enable_flashcard) {
                $starttime = microtime(true);
                mtrace("Generating flashcards for RVS ID {$rvsid}...");
                try {
                    $this->generate_flashcards($rvsid, $content);
                    $elapsed = round(microtime(true) - $starttime, 2);
                    mtrace("Flashcards generated successfully in {$elapsed} seconds.");
                    $successcount++;
                } catch (\Exception $e) {
                    $elapsed = round(microtime(true) - $starttime, 2);
                    mtrace("ERROR: Flashcard generation failed after {$elapsed} seconds: " . $e->getMessage());
                    $failurecount++;
                }
            }

            // Generate quiz if enabled.
            if ($rvs->enable_quiz) {
                $starttime = microtime(true);
                mtrace("Generating quiz for RVS ID {$rvsid}...");
                try {
                    $this->generate_quiz($rvsid, $content);
                    $elapsed = round(microtime(true) - $starttime, 2);
                    mtrace("Quiz generated successfully in {$elapsed} seconds.");
                    $successcount++;
                } catch (\Exception $e) {
                    $elapsed = round(microtime(true) - $starttime, 2);
                    mtrace("ERROR: Quiz generation failed after {$elapsed} seconds: " . $e->getMessage());
                    $failurecount++;
                }
            }

            $totalelapsed = round(microtime(true) - $taskstarttime, 2);
            mtrace("Content generation completed for RVS ID {$rvsid} in {$totalelapsed} seconds.");
            mtrace("Summary: {$successcount} succeeded, {$failurecount} failed.");

            // Only throw exception if all generations failed.
            if ($failurecount > 0 && $successcount === 0) {
                throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 
                    'All content generation attempts failed for RVS ID ' . $rvsid);
            }
        } catch (\moodle_exception $e) {
            $totalelapsed = round(microtime(true) - $taskstarttime, 2);
            mtrace("CRITICAL ERROR: Content generation failed for RVS ID {$rvsid} after {$totalelapsed} seconds: " . $e->getMessage());
            throw $e; // Re-throw to mark task as failed
        }
    }

    /**
     * Generate mind map
     *
     * @param int $rvsid
     * @param string $content
     * @throws \moodle_exception If generation fails
     */
    private function generate_mindmap($rvsid, $content) {
        global $DB;

        // Pass rvsid to generator for RAG processing.
        $mindmapdata = \mod_rvs\ai\generator::generate_mindmap($content, $rvsid);

        if (empty($mindmapdata)) {
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 'Mind map data is empty');
        }

        // Validate mind map structure.
        if (!is_array($mindmapdata)) {
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 
                'Mind map data is not in expected array format');
        }

        // Check for required fields.
        if (!isset($mindmapdata['central']) || empty($mindmapdata['central'])) {
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 
                'Mind map missing central topic');
        }

        if (!isset($mindmapdata['branches']) || !is_array($mindmapdata['branches'])) {
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 
                'Mind map missing or invalid branches');
        }

        $record = new \stdClass();
        $record->rvsid = $rvsid;
        $record->title = 'AI Generated Mind Map';
        $record->data = json_encode($mindmapdata);
        $record->timecreated = time();

        // Delete old mind map if exists.
        $DB->delete_records('rvs_mindmap', array('rvsid' => $rvsid));
        $DB->insert_record('rvs_mindmap', $record);

        mtrace("Mind map stored with central topic: " . $mindmapdata['central']);
    }

    /**
     * Generate podcast
     *
     * @param int $rvsid
     * @param string $content
     * @throws \moodle_exception If generation fails
     */
    private function generate_podcast($rvsid, $content) {
        global $DB;

        // Pass rvsid to generator for RAG processing.
        $script = \mod_rvs\ai\generator::generate_podcast($content, $rvsid);

        if (empty($script)) {
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 
                'Podcast script is empty');
        }

        // Validate script has reasonable content.
        if (strlen(trim($script)) < 50) {
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 
                'Podcast script is too short (less than 50 characters)');
        }

        $record = new \stdClass();
        $record->rvsid = $rvsid;
        $record->title = 'AI Generated Podcast';
        $record->script = $script;
        $record->audiourl = '';
        $record->duration = 0;
        $record->timecreated = time();

        // Delete old podcast if exists.
        $DB->delete_records('rvs_podcast', array('rvsid' => $rvsid));
        $podcastid = $DB->insert_record('rvs_podcast', $record);

        $wordcount = str_word_count($script);
        mtrace("Podcast script stored with {$wordcount} words.");

        // Optionally synthesize audio if enabled and configured.
        $audioenabled = (bool)\get_config('mod_rvs', 'enable_audio_generation');
        if ($audioenabled) {
            try {
                $format = \get_config('mod_rvs', 'tts_format') ?: 'mp3';
                $result = \mod_rvs\tts\tts_client::synthesize($script, $format);

                // Store audio in Moodle file API under context of the cm.
                $cm = get_coursemodule_from_instance('rvs', $rvsid, 0, false, MUST_EXIST);
                $context = \context_module::instance($cm->id);
                $fs = get_file_storage();

                $filename = 'podcast_' . $rvsid . '.' . $result['extension'];
                // Clean previous files for this itemid.
                $existing = $fs->get_area_files($context->id, 'mod_rvs', 'podcastaudio', $podcastid, 'id', false);
                foreach ($existing as $file) { $file->delete(); }

                $fileinfo = array(
                    'contextid' => $context->id,
                    'component' => 'mod_rvs',
                    'filearea'  => 'podcastaudio',
                    'itemid'    => $podcastid,
                    'filepath'  => '/',
                    'filename'  => $filename
                );

                $fs->create_file_from_string($fileinfo, $result['binary']);

                // Build pluginfile URL.
                global $CFG;
                $base = $CFG->wwwroot . '/pluginfile.php';
                $record->audiourl = $base . '/' . $context->id . '/mod_rvs/podcastaudio/' . $podcastid . '/' . rawurlencode($filename);
                $DB->update_record('rvs_podcast', (object)['id' => $podcastid, 'audiourl' => $record->audiourl]);
                mtrace('Podcast audio synthesized and stored.');
            } catch (\Exception $e) {
                mtrace('WARNING: Audio synthesis failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Generate video
     *
     * @param int $rvsid
     * @param string $content
     * @throws \moodle_exception If generation fails
     */
    private function generate_video($rvsid, $content) {
        global $DB;

        // Pass rvsid to generator for RAG processing.
        $script = \mod_rvs\ai\generator::generate_video_script($content, $rvsid);

        if (empty($script)) {
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 
                'Video script is empty');
        }

        // Validate script has reasonable content.
        if (strlen(trim($script)) < 50) {
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 
                'Video script is too short (less than 50 characters)');
        }

        $record = new \stdClass();
        $record->rvsid = $rvsid;
        $record->title = 'AI Generated Video';
        $record->script = $script;
        $record->videourl = ''; // Would be generated using video generation API.
        $record->duration = 0;
        $record->timecreated = time();

        // Delete old video if exists.
        $DB->delete_records('rvs_video', array('rvsid' => $rvsid));
        $DB->insert_record('rvs_video', $record);

        $wordcount = str_word_count($script);
        mtrace("Video script stored with {$wordcount} words.");
    }

    /**
     * Generate report
     *
     * @param int $rvsid
     * @param string $content
     * @throws \moodle_exception If generation fails
     */
    private function generate_report($rvsid, $content) {
        global $DB;

        // Pass rvsid to generator for RAG processing.
        $reportcontent = \mod_rvs\ai\generator::generate_report($content, $rvsid);

        if (empty($reportcontent)) {
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 
                'Report content is empty');
        }

        // Validate report has reasonable content.
        if (strlen(trim($reportcontent)) < 100) {
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 
                'Report content is too short (less than 100 characters)');
        }

        $record = new \stdClass();
        $record->rvsid = $rvsid;
        $record->title = 'AI Generated Report';
        $record->content = $reportcontent;
        $record->format = 'html';
        $record->timecreated = time();

        // Delete old report if exists.
        $DB->delete_records('rvs_report', array('rvsid' => $rvsid));
        $DB->insert_record('rvs_report', $record);

        $wordcount = str_word_count(strip_tags($reportcontent));
        mtrace("Report stored with {$wordcount} words.");
    }

    /**
     * Generate flashcards
     *
     * @param int $rvsid
     * @param string $content
     * @throws \moodle_exception If generation fails
     */
    private function generate_flashcards($rvsid, $content) {
        global $DB;

        // Pass rvsid to generator for RAG processing.
        $flashcards = \mod_rvs\ai\generator::generate_flashcards($content, 15, $rvsid);

        if (empty($flashcards)) {
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 
                'Flashcards data is empty');
        }

        // Validate flashcards is an array.
        if (!is_array($flashcards)) {
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 
                'Flashcards data is not in expected array format');
        }

        // Validate we have at least some flashcards.
        if (count($flashcards) === 0) {
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 
                'No flashcards were generated');
        }

        // Delete old flashcards.
        $DB->delete_records('rvs_flashcard', array('rvsid' => $rvsid));

        $validcount = 0;
        foreach ($flashcards as $index => $flashcard) {
            // Validate each flashcard has required fields.
            if (!is_array($flashcard)) {
                mtrace("WARNING: Flashcard at index {$index} is not an array, skipping.");
                continue;
            }

            if (empty($flashcard['question']) || empty($flashcard['answer'])) {
                mtrace("WARNING: Flashcard at index {$index} missing question or answer, skipping.");
                continue;
            }

            $record = new \stdClass();
            $record->rvsid = $rvsid;
            $record->question = $flashcard['question'];
            $record->answer = $flashcard['answer'];
            $record->difficulty = $flashcard['difficulty'] ?? 'medium';
            $record->timecreated = time();

            $DB->insert_record('rvs_flashcard', $record);
            $validcount++;
        }

        if ($validcount === 0) {
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 
                'No valid flashcards could be stored');
        }

        mtrace("Stored {$validcount} flashcards.");
    }

    /**
     * Generate quiz
     *
     * @param int $rvsid
     * @param string $content
     * @throws \moodle_exception If generation fails
     */
    private function generate_quiz($rvsid, $content) {
        global $DB;

        // Pass rvsid to generator for RAG processing.
        $questions = \mod_rvs\ai\generator::generate_quiz($content, 15, $rvsid);

        if (empty($questions)) {
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 
                'Quiz questions data is empty');
        }

        // Validate questions is an array.
        if (!is_array($questions)) {
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 
                'Quiz questions data is not in expected array format');
        }

        // Validate we have at least some questions.
        if (count($questions) === 0) {
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 
                'No quiz questions were generated');
        }

        // Delete old quiz questions.
        $DB->delete_records('rvs_quiz', array('rvsid' => $rvsid));

        $validcount = 0;
        foreach ($questions as $index => $question) {
            // Validate each question has required fields.
            if (!is_array($question)) {
                mtrace("WARNING: Question at index {$index} is not an array, skipping.");
                continue;
            }

            if (empty($question['question'])) {
                mtrace("WARNING: Question at index {$index} missing question text, skipping.");
                continue;
            }

            if (!isset($question['options']) || !is_array($question['options'])) {
                mtrace("WARNING: Question at index {$index} missing or invalid options, skipping.");
                continue;
            }

            if (count($question['options']) < 2) {
                mtrace("WARNING: Question at index {$index} has less than 2 options, skipping.");
                continue;
            }

            if (!isset($question['correctanswer'])) {
                mtrace("WARNING: Question at index {$index} missing correct answer, skipping.");
                continue;
            }

            $record = new \stdClass();
            $record->rvsid = $rvsid;
            $record->question = $question['question'];
            $record->options = json_encode($question['options']);
            $record->correctanswer = $question['correctanswer'];
            $record->explanation = $question['explanation'] ?? '';
            $record->difficulty = $question['difficulty'] ?? 'medium';
            $record->timecreated = time();

            $DB->insert_record('rvs_quiz', $record);
            $validcount++;
        }

        if ($validcount === 0) {
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 
                'No valid quiz questions could be stored');
        }

        mtrace("Stored {$validcount} quiz questions.");
    }

    /**
     * Store an error message for every enabled module on the activity.
     */
    private function store_error_for_enabled_modules(\stdClass $rvs, string $message): void {
        $flags = [
            'mindmap' => $rvs->enable_mindmap ?? 0,
            'podcast' => $rvs->enable_podcast ?? 0,
            'video' => $rvs->enable_video ?? 0,
            'report' => $rvs->enable_report ?? 0,
            'flashcard' => $rvs->enable_flashcard ?? 0,
            'quiz' => $rvs->enable_quiz ?? 0,
        ];

        foreach ($flags as $module => $enabled) {
            if (!empty($enabled)) {
                error_tracker::store($rvs->id, $module, $message);
            }
        }
    }
}



