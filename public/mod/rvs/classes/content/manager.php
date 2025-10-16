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
 * Content manager for RVS section-aware logic.
 *
 * @package    mod_rvs
 * @category   content
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_rvs\content;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper for selecting and combining course content for RVS.
 */
class manager {
    /** @var array<int, object|null> Cache of RVS metadata keyed by rvsid. */
    private static $rvscache = [];

    /** @var array<string, int|null> Cache of source sections keyed by courseid:sourcetype:sourceid. */
    private static $sourcecache = [];

    /**
     * Get section-aware metadata for an RVS instance.
     *
     * @param int $rvsid RVS id.
     * @return object|null Object with rvs record and sectionid or null if not accessible.
     */
    public static function get_rvs_meta(int $rvsid): ?object {
        global $DB;

        if (array_key_exists($rvsid, self::$rvscache)) {
            return self::$rvscache[$rvsid];
        }

        $rvs = $DB->get_record('rvs', ['id' => $rvsid], '*', IGNORE_MISSING);
        if (!$rvs) {
            self::$rvscache[$rvsid] = null;
            return null;
        }

        try {
            $cm = get_coursemodule_from_instance('rvs', $rvsid, $rvs->course, false, IGNORE_MISSING);
            $sectionid = $cm ? (int)$cm->section : null;
        } catch (\moodle_exception $e) {
            $sectionid = null;
        }

        self::$rvscache[$rvsid] = (object)[
            'rvs' => $rvs,
            'sectionid' => $sectionid,
        ];

        return self::$rvscache[$rvsid];
    }

    /**
     * Resolve the course section for a source module.
     *
     * @param int $courseid Course id.
     * @param string $sourcetype Source type (book|file|scorm).
     * @param int $sourceid Source instance id.
     * @return int|null Section id or null if not found.
     */
    public static function get_source_section(int $courseid, string $sourcetype, int $sourceid): ?int {
        $cachekey = $courseid . ':' . $sourcetype . ':' . $sourceid;
        if (array_key_exists($cachekey, self::$sourcecache)) {
            return self::$sourcecache[$cachekey];
        }

        $module = $sourcetype === 'book' ? 'book' : ($sourcetype === 'scorm' ? 'scorm' : 'resource');

        try {
            $cm = get_coursemodule_from_instance($module, $sourceid, $courseid, false, IGNORE_MISSING);
            $sectionid = $cm ? (int)$cm->section : null;
        } catch (\moodle_exception $e) {
            $sectionid = null;
        }

        self::$sourcecache[$cachekey] = $sectionid;
        return $sectionid;
    }

    /**
     * Determine whether the source module shares the same section as the RVS activity.
     *
     * @param int $rvsid RVS id.
     * @param string $sourcetype Source type.
     * @param int $sourceid Source id.
     * @return int|null Matching section id or null if it does not match.
     */
    public static function get_matching_source_section(int $rvsid, string $sourcetype, int $sourceid): ?int {
        $meta = self::get_rvs_meta($rvsid);
        if (!$meta || empty($meta->sectionid)) {
            return null;
        }

        $sectionid = self::get_source_section($meta->rvs->course, $sourcetype, $sourceid);
        if ($sectionid !== null && (int)$sectionid === (int)$meta->sectionid) {
            return $sectionid;
        }

        return null;
    }

    /**
     * Filter rvs_content records so only matching section entries remain.
     *
     * @param int $rvsid RVS id.
     * @param array $records Raw rvs_content records.
     * @return array Filtered records keyed by id.
     */
    public static function filter_records(int $rvsid, array $records): array {
        global $DB;

        if (empty($records)) {
            return [];
        }

        $meta = self::get_rvs_meta($rvsid);
        if (!$meta) {
            return $records;
        }

        if (empty($meta->sectionid)) {
            return $records;
        }

        $filtered = [];
        foreach ($records as $record) {
            if (!empty($record->sectionid) && (int)$record->sectionid === (int)$meta->sectionid) {
                $filtered[$record->id] = $record;
                continue;
            }

            $matching = self::get_matching_source_section($rvsid, $record->sourcetype, $record->sourceid);
            if ($matching !== null) {
                $filtered[$record->id] = $record;

                if (empty($record->sectionid)) {
                    $update = new \stdClass();
                    $update->id = $record->id;
                    $update->sectionid = $matching;
                    $DB->update_record('rvs_content', $update);
                }
            }
        }

        return $filtered;
    }

    /**
     * Combine filtered content into a single string for AI generation.
     *
     * @param int $rvsid RVS id.
     * @return string Combined textual content.
     */
    public static function get_combined_content(int $rvsid): string {
        global $DB;

        $records = $DB->get_records('rvs_content', ['rvsid' => $rvsid], 'id ASC');
        $filtered = self::filter_records($rvsid, $records);

        $combined = '';
        foreach ($filtered as $record) {
            if (!empty($record->content)) {
                $combined .= $record->content . "\n\n";
            }
        }

        return trim($combined);
    }

    /**
     * Reset caches (useful for unit tests).
     */
    public static function reset_caches(): void {
        self::$rvscache = [];
        self::$sourcecache = [];
    }
}
