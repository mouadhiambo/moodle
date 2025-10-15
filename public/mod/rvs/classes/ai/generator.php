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

require_once(__DIR__ . '/../rag/manager.php');

use mod_rvs\content\manager as content_manager;
use mod_rvs\local\error_tracker;
use mod_rvs\rag\manager as rag_manager;

/**
 * AI Generator class for RVS content generation
 */
class generator {

    /**
     * Maximum retry attempts for API failures
     */
    const MAX_RETRIES = 3;

    /**
     * Initial retry delay in seconds
     */
    const INITIAL_RETRY_DELAY = 1;

    /**
     * Build task-specific prompt
     *
     * @param string $content Processed content
     * @param string $tasktype Task type (mindmap, podcast, video, report, flashcard, quiz)
     * @param array $options Additional options (e.g., count for flashcards/quiz)
     * @return string Complete prompt for AI
     */
    private static function build_prompt($content, $tasktype, $options = []) {
        $prompts = [
            'mindmap' => "Generate a comprehensive mind map structure in JSON format based on the following content. " .
                        "The mind map should have a central topic and multiple branches with sub-topics. " .
                        "Create a hierarchical structure that shows relationships between concepts. " .
                        "Format: {\"central\": \"main topic\", \"branches\": [{\"topic\": \"branch1\", \"subtopics\": [\"sub1\", \"sub2\"], \"relationships\": [\"branch2\"]}, ...]}\n\n" .
                        "Content:\n" . $content,
            
            'podcast' => "[INTRO]\nCreate an engaging podcast script based on the following educational content. " .
                        "The script should be conversational, informative, and suitable for audio narration. " .
                        "Structure the script with:\n" .
                        "- [INTRO] section with a welcoming introduction\n" .
                        "- [MAIN CONTENT] section discussing key points\n" .
                        "- [CONCLUSION] section summarizing takeaways\n" .
                        "Use speaker labels (HOST:) for all narration. Make it engaging and conversational.\n\n" .
                        "Content:\n" . $content,
            
            'video' => "Create a detailed video script for an educational explainer video based on the following content. " .
                      "Structure the script by scenes with:\n" .
                      "- [SCENE X] markers for each scene\n" .
                      "- [VISUAL: ...] tags describing what should be shown on screen\n" .
                      "- NARRATION: for the voice-over text\n" .
                      "Make it engaging, educational, and visually descriptive.\n\n" .
                      "Content:\n" . $content,
            
            'report' => "Generate a comprehensive educational report based on the following content. " .
                       "Structure the report with these sections:\n" .
                       "- <h1>Executive Summary</h1>: Brief overview of key points\n" .
                       "- <h1>Key Topics</h1>: Main topics with <h2> subheadings\n" .
                       "- <h1>Detailed Analysis</h1>: In-depth discussion\n" .
                       "- <h1>Conclusions</h1>: Summary and takeaways\n" .
                       "Use proper HTML headings (<h1>, <h2>) and paragraphs (<p>).\n\n" .
                       "Content:\n" . $content,
            
            'flashcard' => "Generate " . ($options['count'] ?? 15) . " educational flashcards based on the following content. " .
                          "Each flashcard should test understanding of key concepts. " .
                          "Create a mix of difficulty levels (easy, medium, hard). " .
                          "Format as JSON array: [{\"question\": \"...\", \"answer\": \"...\", \"difficulty\": \"easy|medium|hard\"}, ...]\n" .
                          "Ensure questions are clear and answers are concise but complete.\n\n" .
                          "Content:\n" . $content,
            
            'quiz' => "Generate " . ($options['count'] ?? 15) . " multiple-choice quiz questions based on the following content. " .
                     "Each question must have:\n" .
                     "- A clear question\n" .
                     "- Exactly 4 options\n" .
                     "- One correct answer (index 0-3)\n" .
                     "- An explanation for why the answer is correct\n" .
                     "- A difficulty level (easy, medium, hard)\n" .
                     "Create quality distractors that are plausible but incorrect. " .
                     "Format as JSON array: [{\"question\": \"...\", \"options\": [\"opt1\", \"opt2\", \"opt3\", \"opt4\"], " .
                     "\"correctanswer\": 0-3, \"explanation\": \"...\", \"difficulty\": \"easy|medium|hard\"}, ...]\n\n" .
                     "Content:\n" . $content,
        ];

        return $prompts[$tasktype] ?? $content;
    }

    /**
     * Validate AI response based on task type
     *
     * @param string $response AI response
     * @param string $tasktype Task type
     * @return bool True if response is valid
     */
    private static function validate_response($response, $tasktype) {
        if (empty($response)) {
            return false;
        }

        // For JSON responses, validate structure
        if (in_array($tasktype, ['mindmap', 'flashcard', 'quiz'])) {
            $decoded = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return false;
            }

            // Validate specific structures
            switch ($tasktype) {
                case 'mindmap':
                    return isset($decoded['central']) && isset($decoded['branches']) && is_array($decoded['branches']);
                
                case 'flashcard':
                    if (!is_array($decoded) || empty($decoded)) {
                        return false;
                    }
                    // Check first flashcard has required fields
                    $first = $decoded[0];
                    return isset($first['question']) && isset($first['answer']) && isset($first['difficulty']);
                
                case 'quiz':
                    // Be permissive at this stage: accept any non-empty array; strict checks
                    // are performed later during normalization and storage.
                    return is_array($decoded) && !empty($decoded);
            }
        }

        // For text responses, just check they're not empty
        return !empty(trim($response));
    }

    /**
     * Normalize a quiz question item to the expected schema.
     * Accepts common field variants from different AI providers.
     *
     * Expected output keys: question, options[4], correctanswer (0-3), explanation, difficulty
     *
     * @param array $item
     * @return array Normalized item (may still be invalid if insufficient data)
     */
    public static function normalize_quiz_item(array $item): array {
        // Question text
        $question = $item['question'] ?? ($item['prompt'] ?? ($item['text'] ?? null));

        // Options may be named options/choices/answers
        $options = $item['options'] ?? ($item['choices'] ?? ($item['answers'] ?? null));
        if (is_array($options)) {
            // Trim to exactly 4 if there are more; pad with empty strings if fewer
            $options = array_values($options);
            if (count($options) > 4) { $options = array_slice($options, 0, 4); }
            if (count($options) < 4) { $options = array_pad($options, 4, ''); }
        }

        // Correct answer may be index or label
        $correct = $item['correctanswer'] ?? ($item['correctAnswer'] ?? ($item['answerIndex'] ?? ($item['correct_index'] ?? ($item['correct_option'] ?? ($item['answer'] ?? null)))));
        if (is_string($correct)) {
            // Accept letters A-D or the exact option string
            $map = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3];
            $u = strtoupper(trim($correct));
            if (isset($map[$u])) {
                $correct = $map[$u];
            } elseif (is_array($options)) {
                $idx = array_search($correct, $options, true);
                if ($idx !== false) { $correct = $idx; }
            }
        }

        // Explanation may be missing; provide default
        $explanation = $item['explanation'] ?? ($item['why'] ?? ($item['rationale'] ?? 'No explanation provided.'));

        // Difficulty default to medium
        $difficulty = strtolower($item['difficulty'] ?? ($item['level'] ?? 'medium'));
        if (!in_array($difficulty, ['easy','medium','hard'])) { $difficulty = 'medium'; }

        return [
            'question' => $question,
            'options' => $options,
            'correctanswer' => $correct,
            'explanation' => $explanation,
            'difficulty' => $difficulty,
        ];
    }

    /**
     * Call AI API with retry logic
     *
     * @param string $prompt The prompt to send to AI
     * @param string $tasktype Task type for validation
     * @return string AI response
     * @throws \moodle_exception If all retries fail
     */
    private static function call_ai_with_retry($prompt, $tasktype = 'text') {
        $attempt = 0;
        $lastexception = null;
        $starttime = microtime(true);

        mtrace('[INFO] Starting AI generation for task type: ' . $tasktype);

        while ($attempt < self::MAX_RETRIES) {
            $attempt++;
            
            try {
                mtrace('[DEBUG] AI API call attempt ' . $attempt . ' of ' . self::MAX_RETRIES);
                
                $response = self::call_ai_api($prompt, $tasktype);
                
                // Validate response
                if (self::validate_response($response, $tasktype)) {
                    $elapsed = round(microtime(true) - $starttime, 2);
                    mtrace('[INFO] AI generation successful for ' . $tasktype . ' in ' . 
                           $elapsed . ' seconds (attempt ' . $attempt . ')');
                    return $response;
                }
                
                // Invalid response, treat as failure
                $error = 'Invalid response structure for ' . $tasktype;
                mtrace('[WARNING] ' . $error);
                $lastexception = new \moodle_exception(
                    'aigenerationfailed',
                    'mod_rvs',
                    '',
                    null,
                    $error
                );
                
            } catch (\Exception $e) {
                $error = 'AI API call failed (attempt ' . $attempt . '): ' . $e->getMessage();
                mtrace('[ERROR] ' . $error);
                debugging($error, DEBUG_NORMAL);
                $lastexception = $e;
            }
            
            if ($attempt < self::MAX_RETRIES) {
                // Exponential backoff: 1s, 2s, 4s
                $delay = self::INITIAL_RETRY_DELAY * pow(2, $attempt - 1);
                mtrace('[INFO] Retrying in ' . $delay . ' seconds...');
                sleep($delay);
            }
        }

        // All retries failed
        $totalduration = round(microtime(true) - $starttime, 2);
        $error = 'AI generation failed for ' . $tasktype . ' after ' . self::MAX_RETRIES . 
                ' attempts in ' . $totalduration . ' seconds: ' . 
                ($lastexception ? $lastexception->getMessage() : 'Unknown error');
        mtrace('[ERROR] ' . $error);
        
        throw new \moodle_exception(
            'aigenerationfailed',
            'mod_rvs',
            '',
            null,
            $error
        );
    }

    /**
     * Generate mind map from content
     *
     * @param string $content Source content
     * @param int $rvsid RVS instance ID (optional, for RAG processing)
     * @return array Mind map data structure
     */
    public static function generate_mindmap($content, $rvsid = null) {
        $starttime = microtime(true);
        
        try {
            mtrace('[INFO] Generating mind map...');
            
            // Process content with RAG if needed
            $processedcontent = rag_manager::process_for_task($content, 'mindmap');
            
            // Build task-specific prompt
            $prompt = self::build_prompt($processedcontent, 'mindmap');
            
            // Call AI with retry logic
            $response = self::call_ai_with_retry($prompt, 'mindmap');
            
            // Parse and validate JSON response
            $mindmap = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $error = 'Invalid JSON in mind map response: ' . json_last_error_msg();
                mtrace('[ERROR] ' . $error);
                throw new \moodle_exception(
                    'aigenerationfailed',
                    'mod_rvs',
                    '',
                    null,
                    $error
                );
            }
            
            // Validate structure
            if (!isset($mindmap['central']) || !isset($mindmap['branches'])) {
                $error = 'Mind map missing required fields (central, branches)';
                mtrace('[ERROR] ' . $error);
                throw new \moodle_exception(
                    'aigenerationfailed',
                    'mod_rvs',
                    '',
                    null,
                    $error
                );
            }
            
            if (!is_array($mindmap['branches'])) {
                $error = 'Mind map branches must be an array';
                mtrace('[ERROR] ' . $error);
                throw new \moodle_exception(
                    'aigenerationfailed',
                    'mod_rvs',
                    '',
                    null,
                    $error
                );
            }
            
         $elapsed = round(microtime(true) - $starttime, 2);
         mtrace('[INFO] Mind map generated successfully with ' . count($mindmap['branches']) . 
             ' branches in ' . $elapsed . ' seconds');

         if (!empty($rvsid)) {
          error_tracker::clear($rvsid, 'mindmap');
         }

         return $mindmap;
            
        } catch (\Exception $e) {
            $error = 'Mind map generation failed: ' . $e->getMessage();
            mtrace('[ERROR] ' . $error);
            debugging($error, DEBUG_NORMAL);

            if (!empty($rvsid)) {
                error_tracker::store($rvsid, 'mindmap', $error);
            }
            
            // Send admin notification for generation failure.
            \mod_rvs\notification_helper::notify_generation_failure('mindmap', $e->getMessage(), $rvsid);

            throw new \moodle_exception(
                'aigenerationfailed',
                'mod_rvs',
                '',
                null,
                $e->getMessage()
            );
        }
    }

    /**
     * Generate podcast script from content
     *
     * @param string $content Source content
     * @param int $rvsid RVS instance ID (optional, for RAG processing)
     * @return string Podcast script
     */
    public static function generate_podcast($content, $rvsid = null) {
        $starttime = microtime(true);
        
        try {
            mtrace('[INFO] Generating podcast script...');
            
            // Process content with RAG for narrative flow
            $processedcontent = rag_manager::process_for_task($content, 'podcast');
            
            // Build task-specific prompt
            $prompt = self::build_prompt($processedcontent, 'podcast');
            
            // Call AI with retry logic
            $response = self::call_ai_with_retry($prompt, 'podcast');
            
            // Ensure proper structure with sections
            if (stripos($response, '[INTRO]') === false) {
                mtrace('[WARNING] Podcast script missing [INTRO] section, adding structure');
                $response = "[INTRO]\nHOST: " . $response;
            }
            
         $elapsed = round(microtime(true) - $starttime, 2);
         mtrace('[INFO] Podcast script generated successfully (' . strlen($response) . 
             ' characters) in ' . $elapsed . ' seconds');

         if (!empty($rvsid)) {
          error_tracker::clear($rvsid, 'podcast');
         }

         return $response;
            
        } catch (\Exception $e) {
            $error = 'Podcast generation failed: ' . $e->getMessage();
            mtrace('[ERROR] ' . $error);
            debugging($error, DEBUG_NORMAL);

            if (!empty($rvsid)) {
                error_tracker::store($rvsid, 'podcast', $error);
            }
            
            // Send admin notification for generation failure.
            \mod_rvs\notification_helper::notify_generation_failure('podcast', $e->getMessage(), $rvsid);

            throw new \moodle_exception(
                'aigenerationfailed',
                'mod_rvs',
                '',
                null,
                $e->getMessage()
            );
        }
    }

    /**
     * Generate video script from content
     *
     * @param string $content Source content
     * @param int $rvsid RVS instance ID (optional, for RAG processing)
     * @return string Video script
     */
    public static function generate_video_script($content, $rvsid = null) {
        $starttime = microtime(true);
        
        try {
            mtrace('[INFO] Generating video script...');
            
            // Process content with RAG for visual content
            $processedcontent = rag_manager::process_for_task($content, 'video');
            
            // Build task-specific prompt
            $prompt = self::build_prompt($processedcontent, 'video');
            
            // Call AI with retry logic
            $response = self::call_ai_with_retry($prompt, 'video');
            
            // Ensure proper structure with scenes
            if (stripos($response, '[SCENE') === false) {
                mtrace('[WARNING] Video script missing [SCENE] markers, adding structure');
                $response = "[SCENE 1]\n" . $response;
            }
            
            // Ensure visual cues are present
            if (stripos($response, '[VISUAL:') === false) {
                mtrace('[WARNING] Video script missing [VISUAL:] cues, adding placeholder');
                $response = "[VISUAL: Title card with main topic]\n" . $response;
            }
            
         $elapsed = round(microtime(true) - $starttime, 2);
         mtrace('[INFO] Video script generated successfully (' . strlen($response) . 
             ' characters) in ' . $elapsed . ' seconds');

         if (!empty($rvsid)) {
          error_tracker::clear($rvsid, 'video');
         }

         return $response;
            
        } catch (\Exception $e) {
            $error = 'Video script generation failed: ' . $e->getMessage();
            mtrace('[ERROR] ' . $error);
            debugging($error, DEBUG_NORMAL);

            if (!empty($rvsid)) {
                error_tracker::store($rvsid, 'video', $error);
            }
            
            // Send admin notification for generation failure.
            \mod_rvs\notification_helper::notify_generation_failure('video', $e->getMessage(), $rvsid);

            throw new \moodle_exception(
                'aigenerationfailed',
                'mod_rvs',
                '',
                null,
                $e->getMessage()
            );
        }
    }

    /**
     * Generate report from content
     *
     * @param string $content Source content
     * @param int $rvsid RVS instance ID (optional, for RAG processing)
     * @return string Report content
     */
    public static function generate_report($content, $rvsid = null) {
        $starttime = microtime(true);
        
        try {
            mtrace('[INFO] Generating report...');
            
            // Process content with RAG for comprehensive coverage
            $processedcontent = rag_manager::process_for_task($content, 'report');
            
            // Build task-specific prompt
            $prompt = self::build_prompt($processedcontent, 'report');
            
            // Call AI with retry logic
            $response = self::call_ai_with_retry($prompt, 'report');
            
            // Ensure proper HTML structure
            if (stripos($response, '<h1>') === false) {
                mtrace('[WARNING] Report missing HTML headings, adding structure');
                $response = "<h1>Report</h1>\n" . $response;
            }
            
            // Ensure required sections exist
            $requiredsections = ['Executive Summary', 'Key Topics', 'Analysis', 'Conclusions'];
            $missingsections = [];
            
            foreach ($requiredsections as $section) {
                if (stripos($response, $section) === false) {
                    $missingsections[] = $section;
                }
            }
            
            if (!empty($missingsections)) {
                $warning = 'Report missing sections: ' . implode(', ', $missingsections);
                mtrace('[WARNING] ' . $warning);
                debugging($warning, DEBUG_DEVELOPER);
            }
            
         $elapsed = round(microtime(true) - $starttime, 2);
         mtrace('[INFO] Report generated successfully (' . strlen($response) . 
             ' characters) in ' . $elapsed . ' seconds');

         if (!empty($rvsid)) {
          error_tracker::clear($rvsid, 'report');
         }

         return $response;
            
        } catch (\Exception $e) {
            $error = 'Report generation failed: ' . $e->getMessage();
            mtrace('[ERROR] ' . $error);
            debugging($error, DEBUG_NORMAL);

            if (!empty($rvsid)) {
                error_tracker::store($rvsid, 'report', $error);
            }
            
            // Send admin notification for generation failure.
            \mod_rvs\notification_helper::notify_generation_failure('report', $e->getMessage(), $rvsid);

            throw new \moodle_exception(
                'aigenerationfailed',
                'mod_rvs',
                '',
                null,
                $e->getMessage()
            );
        }
    }

    /**
     * Generate flashcards from content
     *
     * @param string $content Source content
     * @param int $count Number of flashcards to generate
     * @param int $rvsid RVS instance ID (optional, for RAG processing)
     * @return array Array of flashcard objects
     */
    public static function generate_flashcards($content, $count = 15, $rvsid = null) {
        $starttime = microtime(true);
        
        try {
            mtrace('[INFO] Generating ' . $count . ' flashcards...');
            
            // Process content with RAG for key concepts
            $processedcontent = rag_manager::process_for_task($content, 'flashcard');
            
            // Build task-specific prompt
            $prompt = self::build_prompt($processedcontent, 'flashcard', ['count' => $count]);
            
            // Call AI with retry logic
            $response = self::call_ai_with_retry($prompt, 'flashcard');
            
            // Parse and validate JSON response
            $flashcards = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $error = 'Invalid JSON in flashcards response: ' . json_last_error_msg();
                mtrace('[ERROR] ' . $error);
                throw new \moodle_exception(
                    'aigenerationfailed',
                    'mod_rvs',
                    '',
                    null,
                    $error
                );
            }
            
            if (!is_array($flashcards) || empty($flashcards)) {
                $error = 'Flashcards response is not a valid array';
                mtrace('[ERROR] ' . $error);
                throw new \moodle_exception(
                    'aigenerationfailed',
                    'mod_rvs',
                    '',
                    null,
                    $error
                );
            }
            
            // Validate each flashcard structure
            $validflashcards = [];
            $invalidcount = 0;
            
            foreach ($flashcards as $index => $card) {
                if (isset($card['question']) && isset($card['answer']) && isset($card['difficulty'])) {
                    $validflashcards[] = $card;
                } else {
                    $invalidcount++;
                    mtrace('[WARNING] Invalid flashcard structure at index ' . $index . ', skipping');
                    debugging('Invalid flashcard structure, skipping: ' . json_encode($card), DEBUG_DEVELOPER);
                }
            }
            
            if (empty($validflashcards)) {
                $error = 'No valid flashcards in response (all ' . $invalidcount . ' cards were invalid)';
                mtrace('[ERROR] ' . $error);
                throw new \moodle_exception(
                    'aigenerationfailed',
                    'mod_rvs',
                    '',
                    null,
                    $error
                );
            }
            
            // Ensure we have the requested count (or close to it)
            if (count($validflashcards) < $count * 0.5) {
                $warning = 'Generated only ' . count($validflashcards) . ' flashcards, expected ' . $count;
                mtrace('[WARNING] ' . $warning);
                debugging($warning, DEBUG_DEVELOPER);
            }
            
         $elapsed = round(microtime(true) - $starttime, 2);
         mtrace('[INFO] Generated ' . count($validflashcards) . ' valid flashcards in ' . 
             $elapsed . ' seconds');

         if (!empty($rvsid)) {
          error_tracker::clear($rvsid, 'flashcard');
         }

         return $validflashcards;
            
        } catch (\Exception $e) {
            $error = 'Flashcard generation failed: ' . $e->getMessage();
            mtrace('[ERROR] ' . $error);
            debugging($error, DEBUG_NORMAL);

            if (!empty($rvsid)) {
                error_tracker::store($rvsid, 'flashcard', $error);
            }
            
            // Send admin notification for generation failure.
            \mod_rvs\notification_helper::notify_generation_failure('flashcard', $e->getMessage(), $rvsid);

            throw new \moodle_exception(
                'aigenerationfailed',
                'mod_rvs',
                '',
                null,
                $e->getMessage()
            );
        }
    }

    /**
     * Generate quiz questions from content
     *
     * @param string $content Source content
     * @param int $count Number of questions to generate
     * @param int $rvsid RVS instance ID (optional, for RAG processing)
     * @return array Array of quiz question objects
     */
    public static function generate_quiz($content, $count = 15, $rvsid = null) {
        $starttime = microtime(true);
        
        try {
            mtrace('[INFO] Generating ' . $count . ' quiz questions...');
            
            // Process content with RAG for factual content
            $processedcontent = rag_manager::process_for_task($content, 'quiz');
            
            // Build task-specific prompt
            $prompt = self::build_prompt($processedcontent, 'quiz', ['count' => $count]);
            
            // Call AI with retry logic
            $response = self::call_ai_with_retry($prompt, 'quiz');
            
            // Parse and validate JSON response
            $questions = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $error = 'Invalid JSON in quiz response: ' . json_last_error_msg();
                mtrace('[ERROR] ' . $error);
                throw new \moodle_exception(
                    'aigenerationfailed',
                    'mod_rvs',
                    '',
                    null,
                    $error
                );
            }
            
            if (!is_array($questions) || empty($questions)) {
                $error = 'Quiz response is not a valid array';
                mtrace('[ERROR] ' . $error);
                throw new \moodle_exception(
                    'aigenerationfailed',
                    'mod_rvs',
                    '',
                    null,
                    $error
                );
            }
            
            // Normalize and validate each question structure
            $validquestions = [];
            $invalidcount = 0;
            
            foreach ($questions as $index => $question) {
                $question = is_array($question) ? self::normalize_quiz_item($question) : [];

                // Check required fields post-normalization
                if (!isset($question['question']) || !isset($question['options']) || 
                    !isset($question['correctanswer']) || !isset($question['explanation'])) {
                    $invalidcount++;
                    mtrace('[WARNING] Quiz question at index ' . $index . ' missing required fields, skipping');
                    debugging('Invalid quiz question structure, skipping: ' . json_encode($question), DEBUG_DEVELOPER);
                    continue;
                }
                
                // Validate options array
                if (!is_array($question['options']) || count($question['options']) !== 4) {
                    $invalidcount++;
                    mtrace('[WARNING] Quiz question at index ' . $index . ' must have exactly 4 options, skipping');
                    debugging('Quiz question must have exactly 4 options, skipping: ' . json_encode($question), DEBUG_DEVELOPER);
                    continue;
                }
                
                // Validate correct answer index
                $correctanswer = $question['correctanswer'];
                if (!is_numeric($correctanswer) || $correctanswer < 0 || $correctanswer > 3) {
                    $invalidcount++;
                    mtrace('[WARNING] Quiz question at index ' . $index . ' has invalid correct answer index, skipping');
                    debugging('Quiz question correct answer must be 0-3, skipping: ' . json_encode($question), DEBUG_DEVELOPER);
                    continue;
                }
                
                $validquestions[] = $question;
            }
            
            if (empty($validquestions)) {
                $error = 'No valid quiz questions in response (all ' . $invalidcount . ' questions were invalid)';
                mtrace('[ERROR] ' . $error);
                throw new \moodle_exception(
                    'aigenerationfailed',
                    'mod_rvs',
                    '',
                    null,
                    $error
                );
            }
            
            // Ensure we have the requested count (or close to it)
            if (count($validquestions) < $count * 0.5) {
                $warning = 'Generated only ' . count($validquestions) . ' questions, expected ' . $count;
                mtrace('[WARNING] ' . $warning);
                debugging($warning, DEBUG_DEVELOPER);
            }
            
         $elapsed = round(microtime(true) - $starttime, 2);
         mtrace('[INFO] Generated ' . count($validquestions) . ' valid quiz questions in ' . 
             $elapsed . ' seconds');

         if (!empty($rvsid)) {
          error_tracker::clear($rvsid, 'quiz');
         }

         return $validquestions;
            
        } catch (\Exception $e) {
            $error = 'Quiz generation failed: ' . $e->getMessage();
            mtrace('[ERROR] ' . $error);
            debugging($error, DEBUG_NORMAL);

            if (!empty($rvsid)) {
                error_tracker::store($rvsid, 'quiz', $error);
            }
            
            // Send admin notification for generation failure.
            \mod_rvs\notification_helper::notify_generation_failure('quiz', $e->getMessage(), $rvsid);

            throw new \moodle_exception(
                'aigenerationfailed',
                'mod_rvs',
                '',
                null,
                $e->getMessage()
            );
        }
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
        return content_manager::get_combined_content($rvsid);
    }
}



