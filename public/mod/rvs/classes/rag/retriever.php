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
 * Content retriever for RAG processing
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_rvs\rag;

defined('MOODLE_INTERNAL') || die();

/**
 * Content retriever class for selecting relevant chunks
 */
class retriever {

    /**
     * Task-specific keywords for relevance scoring
     */
    private static $taskkeywords = [
        'mindmap' => [
            'concept', 'relationship', 'key', 'main', 'topic', 'idea', 'principle',
            'theory', 'definition', 'category', 'type', 'classification', 'structure'
        ],
        'flashcard' => [
            'definition', 'term', 'important', 'remember', 'key', 'concept', 'fact',
            'meaning', 'describe', 'explain', 'what is', 'who is', 'when', 'where'
        ],
        'quiz' => [
            'fact', 'process', 'explain', 'describe', 'how', 'why', 'what', 'which',
            'correct', 'incorrect', 'true', 'false', 'example', 'demonstrate', 'show'
        ],
        'report' => [
            'summary', 'analysis', 'conclusion', 'finding', 'result', 'evidence',
            'data', 'research', 'study', 'investigation', 'overview', 'detail'
        ],
        'podcast' => [
            'story', 'narrative', 'explain', 'discuss', 'explore', 'understand',
            'learn', 'discover', 'journey', 'example', 'case', 'scenario', 'context'
        ],
        'video' => [
            'visual', 'show', 'demonstrate', 'illustrate', 'example', 'diagram',
            'chart', 'graph', 'image', 'picture', 'scene', 'display', 'present'
        ]
    ];

    /**
     * Retrieve relevant chunks for a task
     *
     * @param array $chunks All content chunks
     * @param string $tasktype Type of generation task
     * @param int $maxchunks Maximum chunks to return (default 5)
     * @return array Relevant chunks sorted by relevance
     */
    public static function retrieve_relevant_chunks($chunks, $tasktype, $maxchunks = 5) {
        if (empty($chunks)) {
            mtrace('[DEBUG] Retriever received empty chunks array');
            return [];
        }

        try {
            mtrace('[DEBUG] Retrieving relevant chunks for task type: ' . $tasktype . 
                   ' (from ' . count($chunks) . ' total chunks, max: ' . $maxchunks . ')');
            
            // If we have fewer chunks than max, return all
            if (count($chunks) <= $maxchunks) {
                mtrace('[DEBUG] Chunk count <= max, returning all chunks');
                return $chunks;
            }

            // Calculate relevance score for each chunk
            $scoredchunks = [];
            foreach ($chunks as $index => $chunk) {
                try {
                    $score = self::calculate_relevance($chunk, $tasktype);
                    $scoredchunks[] = [
                        'chunk' => $chunk,
                        'score' => $score,
                        'index' => $index
                    ];
                } catch (\Exception $e) {
                    mtrace('[WARNING] Error scoring chunk ' . $index . ': ' . $e->getMessage() . 
                           ', assigning score 0');
                    $scoredchunks[] = [
                        'chunk' => $chunk,
                        'score' => 0.0,
                        'index' => $index
                    ];
                }
            }

            // Sort by relevance score (descending)
            usort($scoredchunks, function($a, $b) {
                if ($a['score'] === $b['score']) {
                    // If scores are equal, prefer earlier chunks
                    return $a['index'] - $b['index'];
                }
                return $b['score'] - $a['score'];
            });

            // Take top N chunks
            $topchunks = array_slice($scoredchunks, 0, $maxchunks);

            // Re-sort by original index to maintain document order
            usort($topchunks, function($a, $b) {
                return $a['index'] - $b['index'];
            });

            // Extract just the chunk text
            $result = [];
            foreach ($topchunks as $item) {
                $result[] = $item['chunk'];
            }

            mtrace('[INFO] Retrieved ' . count($result) . ' relevant chunks for ' . $tasktype);

            return $result;
            
        } catch (\Exception $e) {
            $error = 'Error retrieving relevant chunks: ' . $e->getMessage();
            mtrace('[ERROR] ' . $error);
            debugging($error, DEBUG_NORMAL);
            
            // Fallback: return first N chunks
            mtrace('[WARNING] Returning first ' . $maxchunks . ' chunks as fallback');
            return array_slice($chunks, 0, $maxchunks);
        }
    }

    /**
     * Calculate relevance score for chunk
     *
     * @param string $chunk Content chunk
     * @param string $tasktype Generation task type
     * @return float Relevance score (0-1)
     */
    private static function calculate_relevance($chunk, $tasktype) {
        if (empty($chunk)) {
            return 0.0;
        }

        try {
            // Normalize task type
            $tasktype = strtolower($tasktype);
            
            // Get keywords for this task type
            $keywords = self::$taskkeywords[$tasktype] ?? [];
            
            if (empty($keywords)) {
                // If no specific keywords, use general scoring
                return self::calculate_general_relevance($chunk);
            }

            // Convert chunk to lowercase for matching
            $chunklow = strtolower($chunk);
            $chunklength = strlen($chunk);
            
            if ($chunklength === 0) {
                return 0.0;
            }
            
            // Count keyword matches
            $matches = 0;
            $totalweight = 0;
            
            foreach ($keywords as $keyword) {
                $keywordcount = substr_count($chunklow, $keyword);
                if ($keywordcount > 0) {
                    $matches += $keywordcount;
                    $totalweight += $keywordcount * strlen($keyword);
                }
            }

            // Calculate base score from keyword density
            $keywordscore = 0.0;
            if ($matches > 0) {
                // Normalize by chunk length to avoid bias toward longer chunks
                $keywordscore = min(1.0, ($totalweight / $chunklength) * 10);
            }

            // Add bonus for chunk position (earlier chunks often more important)
            // This is handled in retrieve_relevant_chunks by preferring earlier chunks on tie

            // Add bonus for chunk length (prefer substantial chunks)
            $lengthscore = min(1.0, $chunklength / 2000); // Normalize to ~2000 chars

            // Combine scores (weighted average)
            $finalscore = ($keywordscore * 0.7) + ($lengthscore * 0.3);

            return $finalscore;
            
        } catch (\Exception $e) {
            mtrace('[WARNING] Error calculating relevance score: ' . $e->getMessage());
            debugging('Error calculating relevance score: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return 0.0;
        }
    }

    /**
     * Calculate general relevance when no task-specific keywords available
     *
     * @param string $chunk Content chunk
     * @return float Relevance score (0-1)
     */
    private static function calculate_general_relevance($chunk) {
        $chunklength = strlen($chunk);
        
        // Prefer chunks with good length (not too short, not too long)
        $ideallength = 1500;
        $lengthdiff = abs($chunklength - $ideallength);
        $lengthscore = max(0, 1.0 - ($lengthdiff / $ideallength));

        // Prefer chunks with more sentences (more complete thoughts)
        $sentencecount = preg_match_all('/[.!?]+\s+/', $chunk);
        $sentencescore = min(1.0, $sentencecount / 10);

        // Combine scores
        return ($lengthscore * 0.6) + ($sentencescore * 0.4);
    }

    /**
     * Combine chunks into context
     *
     * @param array $chunks Selected chunks
     * @return string Combined context
     */
    public static function combine_chunks($chunks) {
        if (empty($chunks)) {
            return '';
        }

        // Join chunks with double line break for clear separation
        return implode("\n\n", $chunks);
    }

    /**
     * Get task-specific keywords for a given task type
     *
     * @param string $tasktype Task type
     * @return array Keywords for the task
     */
    public static function get_task_keywords($tasktype) {
        $tasktype = strtolower($tasktype);
        return self::$taskkeywords[$tasktype] ?? [];
    }
}
