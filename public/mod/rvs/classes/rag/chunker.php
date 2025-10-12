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
 * Content chunker for RAG processing
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_rvs\rag;

defined('MOODLE_INTERNAL') || die();

/**
 * Content chunker class for splitting content into semantic segments
 */
class chunker {

    /**
     * Chunk content into semantic segments
     *
     * @param string $content Full content text
     * @param int $maxtokens Maximum tokens per chunk (default 1000)
     * @param int $overlap Overlap tokens between chunks (default 100)
     * @return array Array of content chunks
     */
    public static function chunk_content($content, $maxtokens = 1000, $overlap = 100) {
        if (empty($content)) {
            mtrace('[DEBUG] Chunker received empty content');
            return [];
        }

        try {
            // Estimate total tokens
            $totaltokens = self::estimate_tokens($content);
            
            mtrace('[DEBUG] Chunking content: ' . $totaltokens . ' tokens, max chunk size: ' . 
                   $maxtokens . ' tokens');
            
            // If content is small enough, return as single chunk
            if ($totaltokens <= $maxtokens) {
                mtrace('[DEBUG] Content fits in single chunk, no chunking needed');
                return [$content];
            }

            $chunks = [];
            $boundaries = self::find_semantic_boundaries($content);
            
            if (empty($boundaries)) {
                mtrace('[WARNING] No semantic boundaries found, using fallback chunking');
                $maxchars = (int)($maxtokens * 4);
                $overlapchars = (int)($overlap * 4);
                return self::fallback_chunk($content, $maxchars, $overlapchars);
            }
            
            mtrace('[DEBUG] Found ' . count($boundaries) . ' semantic boundaries');
            
            // Convert token counts to approximate character positions
            $maxchars = (int)($maxtokens * 4); // Rough estimate: 1 token ≈ 4 characters
            $overlapchars = (int)($overlap * 4);
            
            $currentchunk = '';
            $currentlength = 0;
            $lastboundary = 0;
            
            foreach ($boundaries as $boundary) {
                $segment = substr($content, $lastboundary, $boundary - $lastboundary);
                $segmentlength = strlen($segment);
                
                // If adding this segment would exceed max, save current chunk
                if ($currentlength + $segmentlength > $maxchars && !empty($currentchunk)) {
                    $chunks[] = trim($currentchunk);
                    
                    // Start new chunk with overlap from previous chunk
                    if ($overlapchars > 0 && strlen($currentchunk) > $overlapchars) {
                        $currentchunk = substr($currentchunk, -$overlapchars) . $segment;
                        $currentlength = $overlapchars + $segmentlength;
                    } else {
                        $currentchunk = $segment;
                        $currentlength = $segmentlength;
                    }
                } else {
                    $currentchunk .= $segment;
                    $currentlength += $segmentlength;
                }
                
                $lastboundary = $boundary;
            }
            
            // Add final chunk if not empty
            if (!empty(trim($currentchunk))) {
                $chunks[] = trim($currentchunk);
            }
            
            // If no chunks were created (edge case), split by max chars
            if (empty($chunks)) {
                mtrace('[WARNING] Semantic chunking produced no chunks, using fallback');
                $chunks = self::fallback_chunk($content, $maxchars, $overlapchars);
            }
            
            mtrace('[INFO] Content chunked into ' . count($chunks) . ' segments');
            
            return $chunks;
            
        } catch (\Exception $e) {
            $error = 'Error during content chunking: ' . $e->getMessage();
            mtrace('[ERROR] ' . $error);
            debugging($error, DEBUG_NORMAL);
            
            // Fallback: return content as single chunk
            mtrace('[WARNING] Returning content as single chunk due to chunking error');
            return [$content];
        }
    }

    /**
     * Estimate token count for text
     *
     * @param string $text Text to estimate
     * @return int Estimated token count
     */
    public static function estimate_tokens($text) {
        if (empty($text)) {
            return 0;
        }
        
        // Simple estimation: ~4 characters per token on average
        // This is a rough approximation for English text
        $charlength = strlen($text);
        $wordcount = str_word_count($text);
        
        // Use word count as primary metric (more accurate)
        // Average: 1.3 tokens per word
        $tokenestimate = (int)($wordcount * 1.3);
        
        // Fallback to character-based if word count is unreliable
        if ($wordcount === 0) {
            $tokenestimate = (int)($charlength / 4);
        }
        
        return max(1, $tokenestimate);
    }

    /**
     * Find semantic boundaries in text
     *
     * @param string $text Text to analyze
     * @return array Boundary positions (character offsets)
     */
    private static function find_semantic_boundaries($text) {
        try {
            $boundaries = [];
            $length = strlen($text);
            
            if ($length === 0) {
                return [0];
            }
            
            // Priority 1: Double line breaks (paragraph boundaries)
            $paragraphs = preg_split('/\n\s*\n/', $text, -1, PREG_SPLIT_OFFSET_CAPTURE);
            if ($paragraphs !== false) {
                foreach ($paragraphs as $para) {
                    if (isset($para[1]) && $para[1] > 0) {
                        $boundaries[] = $para[1];
                    }
                }
            }
            
            // Priority 2: Single line breaks (if not enough paragraph breaks)
            if (count($boundaries) < 10) {
                $lines = preg_split('/\n/', $text, -1, PREG_SPLIT_OFFSET_CAPTURE);
                if ($lines !== false) {
                    foreach ($lines as $line) {
                        if (isset($line[1]) && $line[1] > 0) {
                            $boundaries[] = $line[1];
                        }
                    }
                }
            }
            
            // Priority 3: Sentence boundaries (if still not enough)
            if (count($boundaries) < 20) {
                $matchcount = preg_match_all('/[.!?]+\s+/', $text, $matches, PREG_OFFSET_CAPTURE);
                if ($matchcount !== false && $matchcount > 0) {
                    foreach ($matches[0] as $match) {
                        $boundaries[] = $match[1] + strlen($match[0]);
                    }
                }
            }
            
            // Remove duplicates and sort
            $boundaries = array_unique($boundaries);
            sort($boundaries);
            
            // Ensure we have the start and end
            if (!in_array(0, $boundaries)) {
                array_unshift($boundaries, 0);
            }
            if (!in_array($length, $boundaries)) {
                $boundaries[] = $length;
            }
            
            return $boundaries;
            
        } catch (\Exception $e) {
            mtrace('[WARNING] Error finding semantic boundaries: ' . $e->getMessage() . 
                   ', returning basic boundaries');
            debugging('Error finding semantic boundaries: ' . $e->getMessage(), DEBUG_DEVELOPER);
            
            // Return basic boundaries (start and end)
            return [0, strlen($text)];
        }
    }

    /**
     * Fallback chunking method using simple character splitting
     *
     * @param string $content Content to chunk
     * @param int $maxchars Maximum characters per chunk
     * @param int $overlapchars Overlap characters between chunks
     * @return array Array of chunks
     */
    private static function fallback_chunk($content, $maxchars, $overlapchars) {
        $chunks = [];
        $length = strlen($content);
        $position = 0;
        
        while ($position < $length) {
            $chunksize = min($maxchars, $length - $position);
            $chunk = substr($content, $position, $chunksize);
            
            // Try to break at word boundary
            if ($position + $chunksize < $length) {
                $lastspace = strrpos($chunk, ' ');
                if ($lastspace !== false && $lastspace > $chunksize * 0.8) {
                    $chunk = substr($chunk, 0, $lastspace);
                    $chunksize = $lastspace;
                }
            }
            
            $chunks[] = trim($chunk);
            $position += $chunksize - $overlapchars;
            
            // Prevent infinite loop
            if ($chunksize <= $overlapchars) {
                $position += $overlapchars + 1;
            }
        }
        
        return $chunks;
    }
}
