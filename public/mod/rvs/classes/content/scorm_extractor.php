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
 * SCORM content extractor for mod_rvs
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_rvs\content;

defined('MOODLE_INTERNAL') || die();

/**
 * SCORM content extractor class
 */
class scorm_extractor {

	/**
	 * Extract textual content from a SCORM activity's extracted content area.
	 *
	 * @param int $scormid SCORM instance ID
	 * @return string Extracted text content (combined)
	 */
	public static function extract_content(int $scormid): string {
		global $DB;

		$starttime = microtime(true);

		try {
			$scorm = $DB->get_record('scorm', ['id' => $scormid], '*', MUST_EXIST);
			$cm = get_coursemodule_from_instance('scorm', $scormid, $scorm->course, false, MUST_EXIST);
			$context = \context_module::instance($cm->id);

			$fs = get_file_storage();
			$files = $fs->get_area_files($context->id, 'mod_scorm', 'content', 0, 'filepath, filename', false);

			if (empty($files)) {
				mtrace('[WARNING] No extracted files found for SCORM ' . $scormid . ' in mod_scorm/content');
				return '';
			}

			$combined = '';
			$processed = 0;
			foreach ($files as $file) {
				$mimetype = $file->get_mimetype();
				$filename = $file->get_filename();
				// Consider HTML and plain text-like assets.
				if (in_array($mimetype, ['text/html', 'text/plain', 'text/markdown'])) {
					$content = $file->get_content();
					if ($content === false || $content === null) {
						continue;
					}
					if ($mimetype === 'text/html') {
						$combined .= self::html_to_text($content) . "\n\n";
					} else {
						$combined .= trim(self::normalize_encoding($content)) . "\n\n";
					}
					$processed++;
				}
			}

			$elapsed = round(microtime(true) - $starttime, 2);
			mtrace('[INFO] SCORM extraction complete for ' . $scormid . ': files processed=' . $processed . ', chars=' . strlen($combined) . ' in ' . $elapsed . 's');

			return trim($combined);
		} catch (\dml_missing_record_exception $e) {
			$error = 'SCORM record not found for ID ' . $scormid;
			mtrace('[ERROR] ' . $error);
			debugging($error, DEBUG_NORMAL);
			return '';
		} catch (\Exception $e) {
			$error = 'Unexpected error extracting content from SCORM ' . $scormid . ': ' . $e->getMessage();
			mtrace('[ERROR] ' . $error);
			debugging($error, DEBUG_NORMAL);
			return '';
		}
	}

	/**
	 * Normalize encoding to UTF-8 if needed.
	 * @param string $content
	 * @return string
	 */
	private static function normalize_encoding(string $content): string {
		$encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'ASCII', 'Windows-1252'], true);
		if ($encoding && $encoding !== 'UTF-8') {
			$converted = mb_convert_encoding($content, 'UTF-8', $encoding);
			if ($converted !== false) {
				return $converted;
			}
		}
		return $content;
	}

	/**
	 * Strip HTML while roughly preserving structure.
	 * @param string $html
	 * @return string
	 */
	private static function html_to_text(string $html): string {
		try {
			$text = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
			$text = preg_replace('/<\/(p|div|br|h[1-6]|li|tr)[^>]*>/i', "\n", $text);
			$text = preg_replace('/<li[^>]*>/i', "\n• ", $text);
			$text = preg_replace('/<\/(h[1-6])>/i', "\n\n", $text);
			$text = preg_replace('/<\/?(td|th)[^>]*>/i', "\t", $text);
			$text = strip_tags($text);
			$text = preg_replace('/[ \t]+/', ' ', $text);
			$text = preg_replace('/\n\s*\n\s*\n+/', "\n\n", $text);
			$lines = array_map('trim', explode("\n", $text));
			return trim(implode("\n", $lines));
		} catch (\Exception $e) {
			return trim(strip_tags($html));
		}
	}
}


