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
 * Mind map module for RVS
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$PAGE->requires->js_call_amd('mod_rvs/mindmap', 'init');

echo html_writer::start_div('rvs-mindmap');

$mindmap = $DB->get_record('rvs_mindmap', array('rvsid' => $rvs->id));

if (!$mindmap) {
    echo html_writer::tag('div', get_string('nocontentgenerated', 'mod_rvs'), array('class' => 'alert alert-info'));
    
    // Check if AI is configured
    if (!\mod_rvs\ai\generator::is_ai_configured()) {
        $message = html_writer::tag('strong', get_string('ainotconfigured', 'mod_rvs')) . '<br>';
        $message .= get_string('ainotconfigured_help', 'mod_rvs') . '<br><br>';
        
        if (has_capability('moodle/site:config', context_system::instance())) {
            $configurl = new moodle_url('/admin/settings.php', array('section' => 'modsettingrvs'));
            $message .= html_writer::link($configurl, get_string('configurenow', 'mod_rvs'), 
                array('class' => 'btn btn-warning btn-sm'));
        }
        
        echo $OUTPUT->notification($message, \core\output\notification::NOTIFY_WARNING);
    } else if (has_capability('mod/rvs:generate', $modulecontext)) {
        $regenerateurl = new moodle_url('/mod/rvs/regenerate.php', array('id' => $cm->id, 'module' => 'mindmap'));
        echo html_writer::link(
            $regenerateurl,
            get_string('generatemindmap', 'mod_rvs'),
            array('class' => 'btn btn-primary')
        );
    }
} else {
    echo html_writer::tag('h3', format_string($mindmap->title));
    
    // Display mind map visualization.
    echo html_writer::div('', 'mindmap-container', array(
        'id' => 'mindmap-visualization',
        'data-mindmap' => $mindmap->data,
        'style' => 'width: 100%; height: 600px; border: 1px solid #ddd; border-radius: 5px;'
    ));
    
    // Download button.
    echo html_writer::start_div('mt-3');
    $downloadurl = new moodle_url('/mod/rvs/download.php', array('id' => $cm->id, 'type' => 'mindmap'));
    echo html_writer::link(
        $downloadurl,
        get_string('downloadmindmap', 'mod_rvs'),
        array('class' => 'btn btn-secondary')
    );
    echo html_writer::end_div();
}

echo html_writer::end_div();



