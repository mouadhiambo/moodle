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
 * Prints a particular instance of rvs
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course module ID.
$r = optional_param('r', 0, PARAM_INT);  // RVS instance ID.

if ($id) {
    $cm = get_coursemodule_from_id('rvs', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $rvs = $DB->get_record('rvs', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($r) {
    $rvs = $DB->get_record('rvs', array('id' => $r), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $rvs->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('rvs', $rvs->id, $course->id, false, MUST_EXIST);
} else {
    throw new moodle_exception('missingidandcmid', 'mod_rvs');
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

$event = \mod_rvs\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $rvs);
$event->trigger();

// Update completion state.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/rvs/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($rvs->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

// Display the activity name and description.
echo $OUTPUT->heading(format_string($rvs->name));

if ($rvs->intro) {
    echo $OUTPUT->box(format_module_intro('rvs', $rvs, $cm->id), 'generalbox mod_introbox', 'rvsintro');
}

// Display tabs for each module.
$tabs = array();
$moduleactive = optional_param('module', 'overview', PARAM_ALPHA);

$tabs[] = new tabobject('overview', 
    new moodle_url('/mod/rvs/view.php', array('id' => $cm->id, 'module' => 'overview')),
    get_string('overview', 'mod_rvs'));

if ($rvs->enable_mindmap) {
    $tabs[] = new tabobject('mindmap', 
        new moodle_url('/mod/rvs/view.php', array('id' => $cm->id, 'module' => 'mindmap')),
        get_string('mindmap', 'mod_rvs'));
}

if ($rvs->enable_podcast) {
    $tabs[] = new tabobject('podcast', 
        new moodle_url('/mod/rvs/view.php', array('id' => $cm->id, 'module' => 'podcast')),
        get_string('podcast', 'mod_rvs'));
}

if ($rvs->enable_video) {
    $tabs[] = new tabobject('video', 
        new moodle_url('/mod/rvs/view.php', array('id' => $cm->id, 'module' => 'video')),
        get_string('video', 'mod_rvs'));
}

if ($rvs->enable_report) {
    $tabs[] = new tabobject('report', 
        new moodle_url('/mod/rvs/view.php', array('id' => $cm->id, 'module' => 'report')),
        get_string('report', 'mod_rvs'));
}

if ($rvs->enable_flashcard) {
    $tabs[] = new tabobject('flashcard', 
        new moodle_url('/mod/rvs/view.php', array('id' => $cm->id, 'module' => 'flashcard')),
        get_string('flashcard', 'mod_rvs'));
}

if ($rvs->enable_quiz) {
    $tabs[] = new tabobject('quiz', 
        new moodle_url('/mod/rvs/view.php', array('id' => $cm->id, 'module' => 'quiz')),
        get_string('quiz', 'mod_rvs'));
}

print_tabs(array($tabs), $moduleactive);

// Include the appropriate module view.
$modulefile = $CFG->dirroot . '/mod/rvs/modules/' . $moduleactive . '.php';
if (file_exists($modulefile)) {
    include($modulefile);
} else {
    echo $OUTPUT->notification(get_string('modulenotfound', 'mod_rvs'), 'error');
}

echo $OUTPUT->footer();



