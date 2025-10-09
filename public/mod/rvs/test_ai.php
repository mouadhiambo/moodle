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
 * AI Configuration Test Page
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/rvs/lib.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('rvs', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$rvs = $DB->get_record('rvs', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_url('/mod/rvs/test_ai.php', array('id' => $id));
$PAGE->set_title(get_string('aitest', 'mod_rvs'));
$PAGE->set_heading(get_string('aitest', 'mod_rvs'));

echo $OUTPUT->header();

echo html_writer::tag('h2', get_string('aitest', 'mod_rvs'));

// Display current configuration
echo html_writer::tag('h3', 'Current Configuration');
$provider = get_config('mod_rvs', 'default_provider');
$apikey = get_config('mod_rvs', 'api_key');
$endpoint = get_config('mod_rvs', 'api_endpoint');

echo html_writer::start_tag('ul');
echo html_writer::tag('li', 'Default AI Provider: ' . ($provider ?: 'Not set'));
echo html_writer::tag('li', 'API Key: ' . ($apikey ? 'Set (' . strlen($apikey) . ' characters)' : 'Not set'));
echo html_writer::tag('li', 'API Endpoint: ' . ($endpoint ?: 'Not set'));
echo html_writer::end_tag('ul');

// Test AI configuration
echo html_writer::tag('h3', 'Test Results');
$test_result = \mod_rvs\ai\generator::test_ai_configuration();

if ($test_result['success']) {
    echo $OUTPUT->notification($test_result['message'], \core\output\notification::NOTIFY_SUCCESS);
} else {
    echo $OUTPUT->notification($test_result['message'], \core\output\notification::NOTIFY_ERROR);
}

// Back button
$backurl = new moodle_url('/mod/rvs/view.php', array('id' => $id));
echo html_writer::link($backurl, 'Back to RVS Activity', array('class' => 'btn btn-secondary'));

echo $OUTPUT->footer();
