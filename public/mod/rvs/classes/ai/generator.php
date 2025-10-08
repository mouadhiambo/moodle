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
 * AI content generator for RVS
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_rvs\ai;

defined('MOODLE_INTERNAL') || die();

/**
 * AI Generator class for RVS content generation
 */
class generator {

    /**
     * Generate mind map from content
     *
     * @param string $content Source content
     * @return array Mind map data structure
     */
    public static function generate_mindmap($content) {
        $prompt = "Generate a comprehensive mind map structure in JSON format based on the following content. " .
                  "The mind map should have a central topic and multiple branches with sub-topics. " .
                  "Format: {\"central\": \"main topic\", \"branches\": [{\"topic\": \"branch1\", \"subtopics\": [\"sub1\", \"sub2\"]}, ...]}\n\n" .
                  "Content:\n" . $content;

        $response = self::call_ai_api($prompt);
        
        return json_decode($response, true);
    }

    /**
     * Generate podcast script from content
     *
     * @param string $content Source content
     * @return string Podcast script
     */
    public static function generate_podcast($content) {
        $prompt = "Create an engaging podcast script based on the following educational content. " .
                  "The script should be conversational, informative, and suitable for audio narration. " .
                  "Include an introduction, main points discussion, and conclusion. " .
                  "Format it with speaker labels (HOST:) where appropriate.\n\n" .
                  "Content:\n" . $content;

        return self::call_ai_api($prompt);
    }

    /**
     * Generate video script from content
     *
     * @param string $content Source content
     * @return string Video script
     */
    public static function generate_video_script($content) {
        $prompt = "Create a detailed video script for an educational explainer video based on the following content. " .
                  "Include visual descriptions in [VISUAL: ...] tags and narration. " .
                  "Make it engaging and educational.\n\n" .
                  "Content:\n" . $content;

        return self::call_ai_api($prompt);
    }

    /**
     * Generate report from content
     *
     * @param string $content Source content
     * @return string Report content
     */
    public static function generate_report($content) {
        $prompt = "Generate a comprehensive educational report based on the following content. " .
                  "Include: Executive Summary, Key Topics, Detailed Analysis, and Conclusions. " .
                  "Use proper headings and formatting.\n\n" .
                  "Content:\n" . $content;

        return self::call_ai_api($prompt);
    }

    /**
     * Generate flashcards from content
     *
     * @param string $content Source content
     * @param int $count Number of flashcards to generate
     * @return array Array of flashcard objects
     */
    public static function generate_flashcards($content, $count = 10) {
        $prompt = "Generate {$count} educational flashcards based on the following content. " .
                  "Each flashcard should have a question and answer. " .
                  "Format as JSON: [{\"question\": \"...\", \"answer\": \"...\", \"difficulty\": \"easy|medium|hard\"}, ...]\n\n" .
                  "Content:\n" . $content;

        $response = self::call_ai_api($prompt);
        
        return json_decode($response, true);
    }

    /**
     * Generate quiz questions from content
     *
     * @param string $content Source content
     * @param int $count Number of questions to generate
     * @return array Array of quiz question objects
     */
    public static function generate_quiz($content, $count = 10) {
        $prompt = "Generate {$count} multiple-choice quiz questions based on the following content. " .
                  "Each question should have 4 options with one correct answer. " .
                  "Format as JSON: [{\"question\": \"...\", \"options\": [\"opt1\", \"opt2\", \"opt3\", \"opt4\"], " .
                  "\"correctanswer\": 0-3, \"explanation\": \"...\", \"difficulty\": \"easy|medium|hard\"}, ...]\n\n" .
                  "Content:\n" . $content;

        $response = self::call_ai_api($prompt);
        
        return json_decode($response, true);
    }

    /**
     * Call the Moodle AI API
     *
     * @param string $prompt The prompt to send to AI
     * @return string AI response
     */
    private static function call_ai_api($prompt) {
        global $CFG;
        
        // Use Moodle's AI subsystem if available.
        if (class_exists('\core_ai\ai')) {
            try {
                $ai = \core_ai\ai::get_provider('openai'); // Or get the configured provider.
                
                $response = $ai->generate_text([
                    'prompt' => $prompt,
                    'temperature' => 0.7,
                    'max_tokens' => 2000,
                ]);
                
                return $response;
            } catch (\Exception $e) {
                debugging('AI API error: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
        
        // Fallback: return a placeholder response if AI is not configured.
        return json_encode([
            'error' => 'AI provider not configured',
            'message' => 'Please configure an AI provider in Moodle settings to use this feature.'
        ]);
    }

    /**
     * Get all content for an RVS instance
     *
     * @param int $rvsid RVS instance ID
     * @return string Combined content from all sources
     */
    public static function get_content($rvsid) {
        global $DB;
        
        $contents = $DB->get_records('rvs_content', array('rvsid' => $rvsid));
        
        $combined = '';
        foreach ($contents as $content) {
            $combined .= $content->content . "\n\n";
        }
        
        return $combined;
    }
}



