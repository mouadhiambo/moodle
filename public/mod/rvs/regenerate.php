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
 * Regenerate AI content for RVS
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$id = required_param('id', PARAM_INT); // Course module ID.
$module = optional_param('module', 'all', PARAM_ALPHA);

$cm = get_coursemodule_from_id('rvs', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$rvs = $DB->get_record('rvs', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);
require_capability('mod/rvs:generate', $modulecontext);

$returnurl = new moodle_url('/mod/rvs/view.php', array('id' => $cm->id));

// Queue the generation task.
$task = new \mod_rvs\task\generate_content();
$task->set_custom_data(array('rvsid' => $rvs->id));
\core\task\manager::queue_adhoc_task($task);

redirect($returnurl, get_string('generationqueued', 'mod_rvs'), null, \core\output\notification::NOTIFY_SUCCESS);



