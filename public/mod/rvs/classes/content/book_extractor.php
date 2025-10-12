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
 * Book content extractor for mod_rvs
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_rvs\content;

defined('MOODLE_INTERNAL') || die();

/**
 * Book content extractor class
 */
class book_extractor {

    /**
     * Extract content from a book module
     *
     * @param int $bookid Book module ID
     * @return string Formatted book content
     */
    public static function extract_content($bookid) {
        global $DB;

        $starttime = microtime(true);

        try {
            // Get the book record.
            $book = $DB->get_record('book', ['id' => $bookid], '*', MUST_EXIST);
            
            mtrace('[INFO] Extracting content from book: "' . $book->name . '" (ID: ' . $bookid . ')');
            
            // Get all chapters ordered by pagenum.
            $chapters = $DB->get_records('book_chapters', ['bookid' => $bookid], 'pagenum ASC');

            if (empty($chapters)) {
                $warning = 'No chapters found for book "' . $book->name . '" (ID: ' . $bookid . ')';
                mtrace('[WARNING] ' . $warning);
                debugging($warning, DEBUG_DEVELOPER);
                return '';
            }

            mtrace('[INFO] Processing ' . count($chapters) . ' chapters from book "' . $book->name . '"');

            $content = '';
            $successfulchapters = 0;
            $failedchapters = 0;
            
            // Process each chapter.
            foreach ($chapters as $chapter) {
                try {
                    // Add chapter title.
                    $content .= "# " . $chapter->title . "\n\n";
                    
                    // Convert HTML content to text.
                    $chaptertext = self::html_to_text($chapter->content);
                    $content .= $chaptertext . "\n\n";
                    
                    // Extract and add image descriptions.
                    $imagedescriptions = self::extract_image_descriptions($chapter->content);
                    if (!empty($imagedescriptions)) {
                        $content .= "[Images in this chapter: " . implode('; ', $imagedescriptions) . "]\n\n";
                        mtrace('[DEBUG] Extracted ' . count($imagedescriptions) . 
                               ' image descriptions from chapter "' . $chapter->title . '"');
                    }
                    
                    $successfulchapters++;
                    
                } catch (\Exception $e) {
                    $failedchapters++;
                    $error = 'Failed to process chapter "' . $chapter->title . '" (ID: ' . 
                            $chapter->id . ') in book ' . $bookid . ': ' . $e->getMessage();
                    mtrace('[ERROR] ' . $error);
                    debugging($error, DEBUG_NORMAL);
                    // Continue processing other chapters
                }
            }

            $elapsed = round(microtime(true) - $starttime, 2);
            $contentlength = strlen($content);
            
            mtrace('[INFO] Book extraction complete: ' . $successfulchapters . ' chapters processed, ' . 
                   $failedchapters . ' failed, ' . $contentlength . ' characters extracted in ' . 
                   $elapsed . ' seconds');

            return trim($content);

        } catch (\dml_missing_record_exception $e) {
            $error = 'Book record not found for ID ' . $bookid;
            mtrace('[ERROR] ' . $error);
            debugging($error, DEBUG_NORMAL);
            return '';
        } catch (\Exception $e) {
            $error = 'Unexpected error extracting content from book ' . $bookid . 
                    ': ' . $e->getMessage() . ' (File: ' . $e->getFile() . ', Line: ' . $e->getLine() . ')';
            mtrace('[ERROR] ' . $error);
            debugging($error, DEBUG_NORMAL);
            return '';
        }
    }

    /**
     * Strip HTML while preserving text structure
     *
     * @param string $html HTML content
     * @return string Plain text with structure
     */
    private static function html_to_text($html) {
        if (empty($html)) {
            return '';
        }

        try {
            // Convert common HTML entities to text.
            $text = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            if ($text === false) {
                throw new \Exception('html_entity_decode failed');
            }
            
            // Replace block-level elements with line breaks to preserve structure.
            $text = preg_replace('/<\/?(p|div|br|h[1-6]|li|tr)[^>]*>/i', "\n", $text);
            
            // Replace list items with bullet points.
            $text = preg_replace('/<li[^>]*>/i', "\n• ", $text);
            
            // Add spacing after headings.
            $text = preg_replace('/<\/h[1-6]>/i', "\n\n", $text);
            
            // Replace table cells with tabs.
            $text = preg_replace('/<\/?(td|th)[^>]*>/i', "\t", $text);
            
            // Strip remaining HTML tags.
            $text = strip_tags($text);
            
            // Clean up excessive whitespace while preserving paragraph breaks.
            $text = preg_replace('/[ \t]+/', ' ', $text);
            $text = preg_replace('/\n\s*\n\s*\n+/', "\n\n", $text);
            
            // Trim each line.
            $lines = explode("\n", $text);
            $lines = array_map('trim', $lines);
            $text = implode("\n", $lines);
            
            return trim($text);

        } catch (\Exception $e) {
            $error = 'Error converting HTML to text: ' . $e->getMessage();
            mtrace('[WARNING] ' . $error . ', using fallback method');
            debugging($error, DEBUG_DEVELOPER);
            
            // Fallback to simple strip_tags.
            try {
                return strip_tags($html);
            } catch (\Exception $fallbacke) {
                mtrace('[ERROR] Fallback HTML stripping also failed: ' . $fallbacke->getMessage());
                return '';
            }
        }
    }

    /**
     * Extract image alt text and captions
     *
     * @param string $html HTML content
     * @return array Image descriptions
     */
    private static function extract_image_descriptions($html) {
        if (empty($html)) {
            return [];
        }

        $descriptions = [];

        try {
            // Extract alt text from img tags.
            $altmatches = preg_match_all('/<img[^>]+alt=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);
            if ($altmatches !== false && $altmatches > 0) {
                foreach ($matches[1] as $alt) {
                    if (!empty(trim($alt))) {
                        $descriptions[] = trim($alt);
                    }
                }
            }

            // Extract figure captions.
            $captionmatches = preg_match_all('/<figcaption[^>]*>(.*?)<\/figcaption>/is', $html, $matches);
            if ($captionmatches !== false && $captionmatches > 0) {
                foreach ($matches[1] as $caption) {
                    $caption = strip_tags($caption);
                    if (!empty(trim($caption))) {
                        $descriptions[] = trim($caption);
                    }
                }
            }

            // Extract title attributes from images as fallback.
            $titlematches = preg_match_all('/<img[^>]+title=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);
            if ($titlematches !== false && $titlematches > 0) {
                foreach ($matches[1] as $title) {
                    if (!empty(trim($title)) && !in_array(trim($title), $descriptions)) {
                        $descriptions[] = trim($title);
                    }
                }
            }

            // Remove duplicates.
            $descriptions = array_unique($descriptions);

        } catch (\Exception $e) {
            $error = 'Error extracting image descriptions: ' . $e->getMessage();
            mtrace('[WARNING] ' . $error);
            debugging($error, DEBUG_DEVELOPER);
        }

        return $descriptions;
    }
}
