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
 * Simple status endpoint for RVS media readiness (podcast/video)
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once('../../config.php');

$id = required_param('id', PARAM_INT); // Course module id.
$type = required_param('type', PARAM_ALPHANUMEXT); // 'podcast' | 'video'

$cm = get_coursemodule_from_id('rvs', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$rvs = $DB->get_record('rvs', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);
require_capability('mod/rvs:view', context_module::instance($cm->id));

@header('Content-Type: application/json');

$result = array(
	'ready' => false,
	'url' => '',
	'error' => ''
);

try {
	if ($type === 'podcast') {
		$rec = $DB->get_record('rvs_podcast', array('rvsid' => $rvs->id));
		if ($rec && !empty($rec->audiourl)) {
			$result['ready'] = true;
			$result['url'] = $rec->audiourl;
		}
	} else if ($type === 'video') {
		$rec = $DB->get_record('rvs_video', array('rvsid' => $rvs->id));
		if ($rec && !empty($rec->videourl)) {
			$result['ready'] = true;
			$result['url'] = $rec->videourl;
		}
	} else {
		throw new moodle_exception('invalidparameter', 'error', '', null, 'Unknown type: ' . $type);
	}
} catch (Exception $e) {
	$result['error'] = $e->getMessage();
}

echo json_encode($result);
exit;


