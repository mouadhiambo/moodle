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

        $rvs = $DB->get_record('rvs', array('id' => $rvsid));
        if (!$rvs) {
            mtrace("RVS activity with ID {$rvsid} not found.");
            return;
        }

        // Check if AI is configured before attempting generation.
        if (!\mod_rvs\ai\generator::is_ai_configured()) {
            mtrace("AI provider not configured. Skipping content generation for RVS ID {$rvsid}.");
            mtrace("Please configure AI provider in Site Administration → Plugins → Activity modules → RVS AI Learning Suite");
            return;
        }

        // Get all content for this RVS instance.
        $content = \mod_rvs\ai\generator::get_content($rvsid);

        if (empty($content)) {
            mtrace("No source content found for RVS ID {$rvsid}. Add books or files to generate AI content.");
            return;
        }

        try {
            // Generate mind map if enabled.
            if ($rvs->enable_mindmap) {
                mtrace("Generating mind map for RVS ID {$rvsid}...");
                $this->generate_mindmap($rvsid, $content);
            }

            // Generate podcast if enabled.
            if ($rvs->enable_podcast) {
                mtrace("Generating podcast for RVS ID {$rvsid}...");
                $this->generate_podcast($rvsid, $content);
            }

            // Generate video if enabled.
            if ($rvs->enable_video) {
                mtrace("Generating video script for RVS ID {$rvsid}...");
                $this->generate_video($rvsid, $content);
            }

            // Generate report if enabled.
            if ($rvs->enable_report) {
                mtrace("Generating report for RVS ID {$rvsid}...");
                $this->generate_report($rvsid, $content);
            }

            // Generate flashcards if enabled.
            if ($rvs->enable_flashcard) {
                mtrace("Generating flashcards for RVS ID {$rvsid}...");
                $this->generate_flashcards($rvsid, $content);
            }

            // Generate quiz if enabled.
            if ($rvs->enable_quiz) {
                mtrace("Generating quiz for RVS ID {$rvsid}...");
                $this->generate_quiz($rvsid, $content);
            }

            mtrace("Content generation completed successfully for RVS ID {$rvsid}.");
        } catch (\moodle_exception $e) {
            mtrace("Error generating content for RVS ID {$rvsid}: " . $e->getMessage());
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

        $mindmapdata = \mod_rvs\ai\generator::generate_mindmap($content);

        if (empty($mindmapdata)) {
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 'Mind map data is empty');
        }

        $record = new \stdClass();
        $record->rvsid = $rvsid;
        $record->title = 'AI Generated Mind Map';
        $record->data = json_encode($mindmapdata);
        $record->timecreated = time();

        // Delete old mind map if exists.
        $DB->delete_records('rvs_mindmap', array('rvsid' => $rvsid));
        $DB->insert_record('rvs_mindmap', $record);
    }

    /**
     * Generate podcast
     *
     * @param int $rvsid
     * @param string $content
     */
    private function generate_podcast($rvsid, $content) {
        global $DB;

        $script = \mod_rvs\ai\generator::generate_podcast($content);

        $record = new \stdClass();
        $record->rvsid = $rvsid;
        $record->title = 'AI Generated Podcast';
        $record->script = $script;
        $record->audiourl = ''; // Would be generated using text-to-speech API.
        $record->duration = 0;
        $record->timecreated = time();

        // Delete old podcast if exists.
        $DB->delete_records('rvs_podcast', array('rvsid' => $rvsid));
        $DB->insert_record('rvs_podcast', $record);
    }

    /**
     * Generate video
     *
     * @param int $rvsid
     * @param string $content
     */
    private function generate_video($rvsid, $content) {
        global $DB;

        $script = \mod_rvs\ai\generator::generate_video_script($content);

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
    }

    /**
     * Generate report
     *
     * @param int $rvsid
     * @param string $content
     */
    private function generate_report($rvsid, $content) {
        global $DB;

        $reportcontent = \mod_rvs\ai\generator::generate_report($content);

        $record = new \stdClass();
        $record->rvsid = $rvsid;
        $record->title = 'AI Generated Report';
        $record->content = $reportcontent;
        $record->format = 'html';
        $record->timecreated = time();

        // Delete old report if exists.
        $DB->delete_records('rvs_report', array('rvsid' => $rvsid));
        $DB->insert_record('rvs_report', $record);
    }

    /**
     * Generate flashcards
     *
     * @param int $rvsid
     * @param string $content
     */
    private function generate_flashcards($rvsid, $content) {
        global $DB;

        $flashcards = \mod_rvs\ai\generator::generate_flashcards($content, 15);

        // Delete old flashcards.
        $DB->delete_records('rvs_flashcard', array('rvsid' => $rvsid));

        if (is_array($flashcards)) {
            foreach ($flashcards as $flashcard) {
                $record = new \stdClass();
                $record->rvsid = $rvsid;
                $record->question = $flashcard['question'] ?? '';
                $record->answer = $flashcard['answer'] ?? '';
                $record->difficulty = $flashcard['difficulty'] ?? 'medium';
                $record->timecreated = time();

                $DB->insert_record('rvs_flashcard', $record);
            }
        }
    }

    /**
     * Generate quiz
     *
     * @param int $rvsid
     * @param string $content
     */
    private function generate_quiz($rvsid, $content) {
        global $DB;

        $questions = \mod_rvs\ai\generator::generate_quiz($content, 15);

        // Delete old quiz questions.
        $DB->delete_records('rvs_quiz', array('rvsid' => $rvsid));

        if (is_array($questions)) {
            foreach ($questions as $question) {
                $record = new \stdClass();
                $record->rvsid = $rvsid;
                $record->question = $question['question'] ?? '';
                $record->options = json_encode($question['options'] ?? []);
                $record->correctanswer = $question['correctanswer'] ?? 0;
                $record->explanation = $question['explanation'] ?? '';
                $record->difficulty = $question['difficulty'] ?? 'medium';
                $record->timecreated = time();

                $DB->insert_record('rvs_quiz', $record);
            }
        }
    }
}



