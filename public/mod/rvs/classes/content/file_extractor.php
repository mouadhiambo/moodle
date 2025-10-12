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
 * File content extractor for mod_rvs
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_rvs\content;

defined('MOODLE_INTERNAL') || die();

use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory;

/**
 * File content extractor class
 */
class file_extractor {

    /**
     * Supported MIME types for content extraction
     */
    const SUPPORTED_TYPES = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
        'application/msword', // .doc
        'text/plain',
        'text/markdown',
        'text/html',
    ];

    /**
     * Extract content from a resource module file
     *
     * @param int $resourceid Resource module ID
     * @return string Extracted text content
     */
    public static function extract_content($resourceid) {
        global $DB;

        // Check if composer dependencies are installed.
        if (!class_exists('Smalot\PdfParser\Parser') || !class_exists('PhpOffice\PhpWord\IOFactory')) {
            $error = 'RVS plugin: Required composer dependencies not installed. ' .
                    'Please run "composer install" in the public/mod/rvs directory to install ' .
                    'smalot/pdfparser and phpoffice/phpword libraries.';
            mtrace('[ERROR] ' . $error);
            debugging($error, DEBUG_DEVELOPER);
            return '';
        }

        $starttime = microtime(true);

        try {
            // Get the resource record.
            $resource = $DB->get_record('resource', ['id' => $resourceid], '*', MUST_EXIST);
            
            // Get the course module.
            $cm = get_coursemodule_from_instance('resource', $resourceid);
            if (!$cm) {
                $error = 'Could not find course module for resource ' . $resourceid;
                mtrace('[ERROR] File extraction failed: ' . $error);
                debugging($error, DEBUG_NORMAL);
                return '';
            }

            // Get the context.
            $context = \context_module::instance($cm->id);

            // Get the file from the resource.
            $fs = get_file_storage();
            $files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);

            if (empty($files)) {
                $warning = 'No files found for resource ' . $resourceid;
                mtrace('[WARNING] ' . $warning);
                debugging($warning, DEBUG_DEVELOPER);
                return '';
            }

            // Get the first file (main file).
            $file = reset($files);
            $filename = $file->get_filename();
            $filesize = $file->get_filesize();
            $mimetype = $file->get_mimetype();

            mtrace('[INFO] Extracting content from file: ' . $filename . 
                   ' (type: ' . $mimetype . ', size: ' . round($filesize / 1024, 2) . ' KB)');

            // Check if file type is supported.
            if (!self::is_supported_type($mimetype)) {
                $warning = 'Unsupported file type: ' . $mimetype . ' for file "' . $filename . 
                          '" in resource ' . $resourceid;
                mtrace('[WARNING] ' . $warning);
                debugging($warning, DEBUG_DEVELOPER);
                return '';
            }

            // Extract content based on MIME type.
            $content = '';
            try {
                if ($mimetype === 'application/pdf') {
                    $content = self::extract_from_pdf($file);
                } else if (in_array($mimetype, [
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/msword'
                ])) {
                    $content = self::extract_from_docx($file);
                } else if (in_array($mimetype, ['text/plain', 'text/markdown', 'text/html'])) {
                    $content = self::extract_from_text($file);
                }

                $elapsed = round(microtime(true) - $starttime, 2);
                $contentlength = strlen($content);
                
                if ($contentlength > 0) {
                    mtrace('[INFO] Successfully extracted ' . $contentlength . ' characters from "' . 
                           $filename . '" in ' . $elapsed . ' seconds');
                } else {
                    mtrace('[WARNING] No content extracted from "' . $filename . 
                           '" (file may be empty or unreadable)');
                }

            } catch (\Exception $e) {
                $error = 'Failed to extract content from "' . $filename . '" (resource ' . 
                        $resourceid . '): ' . $e->getMessage();
                mtrace('[ERROR] ' . $error);
                debugging($error, DEBUG_NORMAL);
                return '';
            }

            return $content;

        } catch (\dml_missing_record_exception $e) {
            $error = 'Resource record not found for ID ' . $resourceid;
            mtrace('[ERROR] ' . $error);
            debugging($error, DEBUG_NORMAL);
            return '';
        } catch (\Exception $e) {
            $error = 'Unexpected error extracting content from resource ' . $resourceid . 
                    ': ' . $e->getMessage() . ' (File: ' . $e->getFile() . ', Line: ' . $e->getLine() . ')';
            mtrace('[ERROR] ' . $error);
            debugging($error, DEBUG_NORMAL);
            return '';
        }
    }

    /**
     * Check if file type is supported for extraction
     *
     * @param string $mimetype File MIME type
     * @return bool True if supported
     */
    public static function is_supported_type($mimetype) {
        return in_array($mimetype, self::SUPPORTED_TYPES);
    }

    /**
     * Extract text from PDF file
     *
     * @param \stored_file $file Moodle file object
     * @return string Extracted text
     */
    private static function extract_from_pdf($file) {
        $tempfile = null;
        
        try {
            // Create a temporary file for the PDF.
            $tempfile = tempnam(sys_get_temp_dir(), 'rvs_pdf_');
            if ($tempfile === false) {
                throw new \Exception('Failed to create temporary file for PDF extraction');
            }

            $file->copy_content_to($tempfile);

            // Verify file was written
            if (!file_exists($tempfile) || filesize($tempfile) === 0) {
                throw new \Exception('Failed to write PDF content to temporary file');
            }

            // Parse the PDF.
            $parser = new PdfParser();
            $pdf = $parser->parseFile($tempfile);
            
            if (!$pdf) {
                throw new \Exception('PDF parser returned null');
            }

            // Extract text from all pages.
            $text = $pdf->getText();

            // Clean up temporary file.
            @unlink($tempfile);

            return trim($text);

        } catch (\Exception $e) {
            $filename = $file->get_filename();
            $error = 'PDF extraction failed for "' . $filename . '": ' . $e->getMessage();
            
            // Check for common PDF issues
            if (strpos($e->getMessage(), 'encrypted') !== false) {
                $error .= ' (PDF may be password-protected)';
            } else if (strpos($e->getMessage(), 'corrupted') !== false || 
                      strpos($e->getMessage(), 'invalid') !== false) {
                $error .= ' (PDF file may be corrupted)';
            }
            
            mtrace('[ERROR] ' . $error);
            debugging($error, DEBUG_NORMAL);
            
            // Clean up temporary file if it exists.
            if ($tempfile && file_exists($tempfile)) {
                @unlink($tempfile);
            }
            
            return '';
        }
    }

    /**
     * Extract text from Word document
     *
     * @param \stored_file $file Moodle file object
     * @return string Extracted text
     */
    private static function extract_from_docx($file) {
        $tempfile = null;
        
        try {
            // Create a temporary file for the document.
            $tempfile = tempnam(sys_get_temp_dir(), 'rvs_doc_');
            if ($tempfile === false) {
                throw new \Exception('Failed to create temporary file for Word document extraction');
            }

            $file->copy_content_to($tempfile);

            // Verify file was written
            if (!file_exists($tempfile) || filesize($tempfile) === 0) {
                throw new \Exception('Failed to write Word document content to temporary file');
            }

            // Load the document.
            $phpWord = IOFactory::load($tempfile);
            
            if (!$phpWord) {
                throw new \Exception('PhpWord returned null when loading document');
            }
            
            // Extract text from all sections.
            $text = '';
            $sections = $phpWord->getSections();
            
            if (empty($sections)) {
                mtrace('[WARNING] Word document has no sections');
            }
            
            foreach ($sections as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . "\n";
                    } else if (method_exists($element, 'getElements')) {
                        // Handle nested elements (like tables, lists).
                        $text .= self::extract_text_from_elements($element->getElements()) . "\n";
                    }
                }
                $text .= "\n";
            }

            // Clean up temporary file.
            @unlink($tempfile);

            return trim($text);

        } catch (\Exception $e) {
            $filename = $file->get_filename();
            $error = 'Word document extraction failed for "' . $filename . '": ' . $e->getMessage();
            
            // Check for common Word document issues
            if (strpos($e->getMessage(), 'not supported') !== false) {
                $error .= ' (Document format may not be supported)';
            } else if (strpos($e->getMessage(), 'corrupted') !== false || 
                      strpos($e->getMessage(), 'invalid') !== false) {
                $error .= ' (Document file may be corrupted)';
            }
            
            mtrace('[ERROR] ' . $error);
            debugging($error, DEBUG_NORMAL);
            
            // Clean up temporary file if it exists.
            if ($tempfile && file_exists($tempfile)) {
                @unlink($tempfile);
            }
            
            return '';
        }
    }

    /**
     * Extract text from nested Word document elements
     *
     * @param array $elements Array of document elements
     * @return string Extracted text
     */
    private static function extract_text_from_elements($elements) {
        $text = '';
        foreach ($elements as $element) {
            if (method_exists($element, 'getText')) {
                $text .= $element->getText() . ' ';
            } else if (method_exists($element, 'getElements')) {
                $text .= self::extract_text_from_elements($element->getElements());
            }
        }
        return $text;
    }

    /**
     * Extract text from plain text file
     *
     * @param \stored_file $file Moodle file object
     * @return string File content
     */
    private static function extract_from_text($file) {
        try {
            // Get file content.
            $content = $file->get_content();

            if ($content === false || $content === null) {
                throw new \Exception('Failed to read file content');
            }

            // Detect and convert encoding if needed.
            $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'ASCII', 'Windows-1252'], true);
            
            if ($encoding && $encoding !== 'UTF-8') {
                mtrace('[DEBUG] Converting text file encoding from ' . $encoding . ' to UTF-8');
                $converted = mb_convert_encoding($content, 'UTF-8', $encoding);
                if ($converted !== false) {
                    $content = $converted;
                } else {
                    mtrace('[WARNING] Encoding conversion failed, using original content');
                }
            }

            // Strip HTML tags if it's an HTML file.
            if ($file->get_mimetype() === 'text/html') {
                $content = strip_tags($content);
            }

            return trim($content);

        } catch (\Exception $e) {
            $filename = $file->get_filename();
            $error = 'Text file extraction failed for "' . $filename . '": ' . $e->getMessage();
            mtrace('[ERROR] ' . $error);
            debugging($error, DEBUG_NORMAL);
            return '';
        }
    }
}
