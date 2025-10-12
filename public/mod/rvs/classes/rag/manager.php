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
 * RAG manager for coordinating RAG workflow
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_rvs\rag;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/chunker.php');
require_once(__DIR__ . '/retriever.php');

/**
 * RAG manager class for coordinating the RAG workflow
 */
class manager {

    /**
     * Token threshold for using RAG (content larger than this uses RAG)
     */
    const RAG_THRESHOLD_TOKENS = 2000;

    /**
     * Maximum tokens to send to AI after RAG processing
     */
    const MAX_OUTPUT_TOKENS = 3000;

    /**
     * Default chunk size in tokens
     */
    const DEFAULT_CHUNK_SIZE = 1000;

    /**
     * Default overlap size in tokens
     */
    const DEFAULT_OVERLAP = 100;

    /**
     * Process content with RAG for a specific task
     *
     * @param string $content Full content
     * @param string $tasktype Generation task type
     * @return string Processed content ready for AI
     */
    public static function process_for_task($content, $tasktype) {
        if (empty($content)) {
            mtrace('[DEBUG] RAG manager received empty content');
            return '';
        }

        $starttime = microtime(true);

        try {
            // Check if RAG is needed
            if (!self::should_use_rag($content)) {
                // Content is small enough, return as-is
                mtrace('[INFO] Content is small enough (' . chunker::estimate_tokens($content) . 
                       ' tokens), skipping RAG processing');
                return $content;
            }

            mtrace('[INFO] Starting RAG processing for task type: ' . $tasktype);

            // Step 1: Chunk the content
            try {
                $chunks = chunker::chunk_content(
                    $content,
                    self::DEFAULT_CHUNK_SIZE,
                    self::DEFAULT_OVERLAP
                );

                if (empty($chunks)) {
                    mtrace('[WARNING] Chunking produced no chunks, using fallback truncation');
                    return self::fallback_truncate($content);
                }
                
                mtrace('[DEBUG] Content chunked into ' . count($chunks) . ' segments');
                
            } catch (\Exception $e) {
                $error = 'Chunking failed: ' . $e->getMessage();
                mtrace('[ERROR] ' . $error);
                debugging($error, DEBUG_NORMAL);
                return self::fallback_truncate($content);
            }

            // Step 2: Retrieve relevant chunks
            try {
                $maxchunks = self::calculate_max_chunks($tasktype);
                $relevantchunks = retriever::retrieve_relevant_chunks(
                    $chunks,
                    $tasktype,
                    $maxchunks
                );

                if (empty($relevantchunks)) {
                    mtrace('[WARNING] Retrieval produced no chunks, using fallback truncation');
                    return self::fallback_truncate($content);
                }
                
                mtrace('[DEBUG] Retrieved ' . count($relevantchunks) . ' relevant chunks');
                
            } catch (\Exception $e) {
                $error = 'Chunk retrieval failed: ' . $e->getMessage();
                mtrace('[ERROR] ' . $error);
                debugging($error, DEBUG_NORMAL);
                return self::fallback_truncate($content);
            }

            // Step 3: Combine chunks into final context
            try {
                $processedcontent = retriever::combine_chunks($relevantchunks);
                
                if (empty($processedcontent)) {
                    mtrace('[WARNING] Chunk combination produced empty content, using fallback');
                    return self::fallback_truncate($content);
                }
                
            } catch (\Exception $e) {
                $error = 'Chunk combination failed: ' . $e->getMessage();
                mtrace('[ERROR] ' . $error);
                debugging($error, DEBUG_NORMAL);
                return self::fallback_truncate($content);
            }

            // Step 4: Verify output size
            $outputtokens = chunker::estimate_tokens($processedcontent);
            if ($outputtokens > self::MAX_OUTPUT_TOKENS) {
                mtrace('[DEBUG] Processed content still too large (' . $outputtokens . 
                       ' tokens), truncating to ' . self::MAX_OUTPUT_TOKENS . ' tokens');
                $processedcontent = self::truncate_to_tokens(
                    $processedcontent,
                    self::MAX_OUTPUT_TOKENS
                );
            }

            $elapsed = round(microtime(true) - $starttime, 2);
            mtrace('[INFO] RAG processing complete in ' . $elapsed . ' seconds, output: ' . 
                   strlen($processedcontent) . ' characters (' . 
                   chunker::estimate_tokens($processedcontent) . ' tokens)');

            return $processedcontent;

        } catch (\Exception $e) {
            $error = 'RAG processing failed: ' . $e->getMessage() . 
                    ' (File: ' . $e->getFile() . ', Line: ' . $e->getLine() . ')';
            mtrace('[ERROR] ' . $error);
            debugging($error, DEBUG_NORMAL);
            
            // Send admin notification for RAG failure.
            \mod_rvs\notification_helper::notify_rag_failure($tasktype, $e->getMessage());
            
            mtrace('[WARNING] Falling back to content truncation');
            return self::fallback_truncate($content);
        }
    }

    /**
     * Check if RAG processing is needed
     *
     * @param string $content Content to check
     * @return bool True if RAG should be used
     */
    public static function should_use_rag($content) {
        if (empty($content)) {
            return false;
        }

        $tokens = chunker::estimate_tokens($content);
        return $tokens > self::RAG_THRESHOLD_TOKENS;
    }

    /**
     * Calculate maximum chunks to retrieve based on task type
     *
     * @param string $tasktype Task type
     * @return int Maximum number of chunks
     */
    private static function calculate_max_chunks($tasktype) {
        // Different tasks need different amounts of context
        $tasktype = strtolower($tasktype);
        
        switch ($tasktype) {
            case 'report':
                // Reports need comprehensive coverage
                return 8;
            
            case 'mindmap':
                // Mind maps need broad overview
                return 6;
            
            case 'podcast':
            case 'video':
                // Narrative content needs good coverage
                return 6;
            
            case 'flashcard':
            case 'quiz':
                // Q&A content can work with focused chunks
                return 5;
            
            default:
                return 5;
        }
    }

    /**
     * Fallback method: truncate content to fit token limit
     *
     * @param string $content Content to truncate
     * @return string Truncated content with warning
     */
    private static function fallback_truncate($content) {
        try {
            $originallength = strlen($content);
            $truncated = self::truncate_to_tokens($content, self::MAX_OUTPUT_TOKENS);
            
            // Add warning if content was truncated
            if (strlen($truncated) < $originallength) {
                $warning = "\n\n[Note: Content has been truncated due to length. " .
                          "This is a partial view of the full material.]\n\n";
                mtrace('[WARNING] Content truncated from ' . $originallength . ' to ' . 
                       strlen($truncated) . ' characters');
                return $warning . $truncated;
            }
            
            return $truncated;
            
        } catch (\Exception $e) {
            $error = 'Fallback truncation failed: ' . $e->getMessage();
            mtrace('[ERROR] ' . $error);
            debugging($error, DEBUG_NORMAL);
            
            // Last resort: return first 12000 characters (roughly 3000 tokens)
            $emergency = substr($content, 0, 12000);
            mtrace('[WARNING] Using emergency truncation to 12000 characters');
            return $emergency;
        }
    }

    /**
     * Truncate content to specified token count
     *
     * @param string $content Content to truncate
     * @param int $maxtokens Maximum tokens
     * @return string Truncated content
     */
    private static function truncate_to_tokens($content, $maxtokens) {
        $currenttokens = chunker::estimate_tokens($content);
        
        if ($currenttokens <= $maxtokens) {
            return $content;
        }

        // Calculate approximate character limit
        // Using 4 chars per token as rough estimate
        $maxchars = (int)($maxtokens * 4);
        
        // Truncate at character limit
        $truncated = substr($content, 0, $maxchars);
        
        // Try to break at sentence boundary
        $lastperiod = strrpos($truncated, '.');
        $lastquestion = strrpos($truncated, '?');
        $lastexclamation = strrpos($truncated, '!');
        
        $lastsentence = max($lastperiod, $lastquestion, $lastexclamation);
        
        if ($lastsentence !== false && $lastsentence > $maxchars * 0.8) {
            // Found a good sentence boundary
            $truncated = substr($truncated, 0, $lastsentence + 1);
        } else {
            // Try to break at word boundary
            $lastspace = strrpos($truncated, ' ');
            if ($lastspace !== false && $lastspace > $maxchars * 0.9) {
                $truncated = substr($truncated, 0, $lastspace);
            }
        }
        
        return trim($truncated);
    }

    /**
     * Get RAG statistics for content
     *
     * @param string $content Content to analyze
     * @return array Statistics about RAG processing
     */
    public static function get_rag_stats($content) {
        $stats = [
            'total_tokens' => chunker::estimate_tokens($content),
            'needs_rag' => self::should_use_rag($content),
            'total_chars' => strlen($content),
            'word_count' => str_word_count($content)
        ];

        if ($stats['needs_rag']) {
            $chunks = chunker::chunk_content($content);
            $stats['chunk_count'] = count($chunks);
            $stats['avg_chunk_tokens'] = $stats['chunk_count'] > 0 
                ? (int)($stats['total_tokens'] / $stats['chunk_count'])
                : 0;
        }

        return $stats;
    }
}
