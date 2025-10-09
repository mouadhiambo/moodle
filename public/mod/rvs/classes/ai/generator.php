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

        $response = self::call_ai_api($prompt, 'mindmap');
        
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

        $response = self::call_ai_api($prompt, 'flashcards');
        
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

        $response = self::call_ai_api($prompt, 'quiz');
        
        return json_decode($response, true);
    }

    /**
     * Check if AI is properly configured
     *
     * @return bool True if AI is configured and available
     */
    public static function is_ai_configured() {
        // Check if we have the basic configuration
        $provider = get_config('mod_rvs', 'default_provider');
        $apikey = get_config('mod_rvs', 'api_key');
        $endpoint = get_config('mod_rvs', 'api_endpoint');
        
        if (empty($provider) || empty($apikey) || empty($endpoint)) {
            return false;
        }
        
        // If Moodle AI subsystem is available, try to use it
        if (class_exists('\core_ai\ai')) {
            try {
                $ai = \core_ai\ai::get_provider($provider);
                return !empty($ai);
            } catch (\Exception $e) {
                // Fall through to direct API check
            }
        }
        
        // For direct API usage, just check if we have the required settings
        return !empty($provider) && !empty($apikey) && !empty($endpoint);
    }

    /**
     * Call the AI API
     *
     * @param string $prompt The prompt to send to AI
     * @param string $type Content type for error messaging
     * @return string AI response
     * @throws \moodle_exception If AI is not configured or generation fails
     */
    private static function call_ai_api($prompt, $type = 'text') {
        global $CFG;
        
        // Get configuration
        $provider = get_config('mod_rvs', 'default_provider');
        $apikey = get_config('mod_rvs', 'api_key');
        $endpoint = get_config('mod_rvs', 'api_endpoint');
        
        if (empty($provider) || empty($apikey) || empty($endpoint)) {
            throw new \moodle_exception('ainotconfigured', 'mod_rvs');
        }
        
        // Try Moodle AI subsystem first if available
        if (class_exists('\core_ai\ai')) {
            try {
                $ai = \core_ai\ai::get_provider($provider);
                if (!empty($ai)) {
                    $response = $ai->generate_text([
                        'prompt' => $prompt,
                        'temperature' => 0.7,
                        'max_tokens' => 2000,
                    ]);
                    return $response;
                }
            } catch (\Exception $e) {
                // Fall through to direct API call
                debugging('Moodle AI subsystem failed, falling back to direct API: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
        
        // Fall back to direct API call
        return self::call_direct_api($prompt, $provider, $apikey, $endpoint);
    }
    
    /**
     * Make direct API call to AI provider
     *
     * @param string $prompt The prompt to send
     * @param string $provider Provider name
     * @param string $apikey API key
     * @param string $endpoint API endpoint
     * @return string AI response
     * @throws \moodle_exception If API call fails
     */
    private static function call_direct_api($prompt, $provider, $apikey, $endpoint) {
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 2000
        ];
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apikey
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint . '/chat/completions');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 'cURL error: ' . $error);
        }
        
        if ($httpcode !== 200) {
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 'HTTP ' . $httpcode . ': ' . $response);
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 'Invalid JSON response: ' . $response);
        }
        
        if (!isset($decoded['choices'][0]['message']['content'])) {
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 'Unexpected API response format: ' . $response);
        }
        
        return $decoded['choices'][0]['message']['content'];
    }

    /**
     * Test AI configuration
     *
     * @return array Test results with success status and message
     */
    public static function test_ai_configuration() {
        $provider = get_config('mod_rvs', 'default_provider');
        $apikey = get_config('mod_rvs', 'api_key');
        $endpoint = get_config('mod_rvs', 'api_endpoint');
        
        $missing = [];
        if (empty($provider)) {
            $missing[] = 'Default AI Provider';
        }
        if (empty($apikey)) {
            $missing[] = 'API Key';
        }
        if (empty($endpoint)) {
            $missing[] = 'API Endpoint';
        }
        
        if (!empty($missing)) {
            return [
                'success' => false,
                'message' => get_string('aitest_missing_config', 'mod_rvs', implode(', ', $missing))
            ];
        }
        
        // Try a simple test call
        try {
            $test_prompt = "Say 'Hello, this is a test.'";
            $response = self::call_ai_api($test_prompt, 'test');
            
            if (!empty($response)) {
                return [
                    'success' => true,
                    'message' => get_string('aitest_success', 'mod_rvs')
                ];
            } else {
                return [
                    'success' => false,
                    'message' => get_string('aitest_failed', 'mod_rvs', 'Empty response from AI provider')
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => get_string('aitest_failed', 'mod_rvs', $e->getMessage())
            ];
        }
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



