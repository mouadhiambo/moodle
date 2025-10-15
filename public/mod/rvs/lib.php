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
 * Library of interface functions and constants for module rvs
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Load composer autoloader for external dependencies.
$autoloader = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once($autoloader);
} else {
    // Log error if dependencies are not installed.
    if (function_exists('debugging')) {
        debugging('RVS plugin: Composer dependencies not installed. Please run "composer install" in the mod/rvs directory.', DEBUG_DEVELOPER);
    }
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $rvs An object from the form in mod_form.php
 * @return int The id of the newly inserted rvs record
 */
function rvs_add_instance($rvs) {
    global $DB;

    $rvs->timecreated = time();
    $rvs->timemodified = time();

    $rvs->id = $DB->insert_record('rvs', $rvs);

    return $rvs->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $rvs An object from the form in mod_form.php
 * @return boolean Success/Fail
 */
function rvs_update_instance($rvs) {
    global $DB;

    $rvs->timemodified = time();
    $rvs->id = $rvs->instance;

    return $DB->update_record('rvs', $rvs);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function rvs_delete_instance($id) {
    global $DB;

    if (!$rvs = $DB->get_record('rvs', array('id' => $id))) {
        return false;
    }

    // Delete any dependent records here.
    $DB->delete_records('rvs_content', array('rvsid' => $id));
    $DB->delete_records('rvs_mindmap', array('rvsid' => $id));
    $DB->delete_records('rvs_podcast', array('rvsid' => $id));
    $DB->delete_records('rvs_video', array('rvsid' => $id));
    $DB->delete_records('rvs_report', array('rvsid' => $id));
    $DB->delete_records('rvs_flashcard', array('rvsid' => $id));
    $DB->delete_records('rvs_quiz', array('rvsid' => $id));

    $DB->delete_records('rvs', array('id' => $rvs->id));

    return true;
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $rvs
 * @return object|null
 */
function rvs_user_outline($course, $user, $mod, $rvs) {
    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $rvs
 * @return boolean
 */
function rvs_user_complete($course, $user, $mod, $rvs) {
    return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in rvs activities and print it out.
 *
 * @param object $course
 * @param bool $viewfullnames
 * @param int $timestart
 * @return boolean
 */
function rvs_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Returns all activity in courses since a given time
 *
 * @param array $activities
 * @param int $index
 * @param int $timestart
 * @param int $courseid
 * @param int $cmid
 * @param int $userid
 * @param int $groupid
 * @return void
 */
function rvs_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@see rvs_get_recent_mod_activity()}
 *
 * @param object $activity
 * @param int $courseid
 * @param bool $detail
 * @param array $modnames
 * @param bool $viewfullnames
 * @return void
 */
function rvs_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Returns whether the module supports a feature or not
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return bool|null True if module supports feature, false if not, null if doesn't know
 */
function rvs_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        default:
            return null;
    }
}

/**
 * Serve the files from the rvs file areas.
 *
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just sends the file
 */
function mod_rvs_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);

    if (!has_capability('mod/rvs:view', $context)) {
        return false;
    }

    // Supported filearea: podcastaudio
    if ($filearea !== 'podcastaudio') {
        return false;
    }

    $fs = get_file_storage();
    $itemid = (int)array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? ('/' . implode('/', $args) . '/') : '/';

    $file = $fs->get_file($context->id, 'mod_rvs', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Check if composer dependencies are installed
 *
 * @return bool True if dependencies are installed, false otherwise
 */
function rvs_check_dependencies() {
    $autoloader = __DIR__ . '/vendor/autoload.php';
    return file_exists($autoloader);
}

/**
 * Get error message for missing dependencies
 *
 * @return string Error message with installation instructions
 */
function rvs_get_dependency_error_message() {
    return get_string('dependencymissing', 'mod_rvs');
}

/**
 * Require a mod_rvs AMD module when the compiled asset is available.
 *
 * @param string $module Module name without the mod_rvs prefix.
 * @param string $method Method to invoke.
 * @param array $arguments Arguments passed to the AMD method.
 * @return bool True when the module was enqueued, false when the asset is missing.
 */
function rvs_require_amd(string $module, string $method, array $arguments = []): bool {
    global $CFG, $PAGE;

    $filepath = $CFG->dirroot . '/mod/rvs/amd/build/' . $module . '.min.js';
    if (!file_exists($filepath)) {
        debugging('Missing AMD build for mod_rvs/' . $module . '. Run "grunt amd" in mod/rvs to rebuild assets.', DEBUG_DEVELOPER);
        return false;
    }

    $PAGE->requires->js_call_amd('mod_rvs/' . $module, $method, $arguments);
    return true;
}

/**
 * Print a single fallback notice when AI is not configured.
 */
function rvs_print_fallback_notice(): void {
    // Fallback notices removed; function retained for backward compatibility.
}

/**
 * Generate fallback content once per request when AI is disabled and records are missing.
 *
 * @param \stdClass $rvs RVS activity record.
 */
function rvs_generate_fallback_content_if_needed(\stdClass $rvs): void {
    // Fallback generation removed; function retained for backward compatibility.
}



