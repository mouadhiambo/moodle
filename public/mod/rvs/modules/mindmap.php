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

$amdready = rvs_require_amd('mindmap', 'init');

echo html_writer::start_div('rvs-mindmap');

if (!$amdready) {
    echo $OUTPUT->notification(
        get_string('missingamdmodule', 'mod_rvs', 'mindmap'),
        \core\output\notification::NOTIFY_ERROR
    );
}

$mindmap = $DB->get_record('rvs_mindmap', array('rvsid' => $rvs->id));
$mindmaperror = \mod_rvs\local\error_tracker::get($rvs->id, 'mindmap');

if (!$mindmap) {
    if ($mindmaperror) {
        echo $OUTPUT->notification($mindmaperror, \core\output\notification::NOTIFY_ERROR);
    }
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
    // Validate mind map data before rendering.
    $mindmapdata = null;
    $datavalid = false;
    
    if (!empty($mindmap->data)) {
        $mindmapdata = json_decode($mindmap->data, true);
        
        // Check if JSON is valid and has required structure.
        if (json_last_error() === JSON_ERROR_NONE && is_array($mindmapdata)) {
            // Validate basic structure (should have central topic or branches).
            if (isset($mindmapdata['central']) || isset($mindmapdata['branches'])) {
                $datavalid = true;
            }
        }
    }
    
    if (!$datavalid) {
        // Display error message for invalid or empty data.
        echo $OUTPUT->notification(
            get_string('mindmapdatainvalid', 'mod_rvs'),
            \core\output\notification::NOTIFY_ERROR
        );

        if ($mindmaperror) {
            echo $OUTPUT->notification($mindmaperror, \core\output\notification::NOTIFY_ERROR);
        }
        
        echo html_writer::div(
            get_string('mindmapdatainvalid_help', 'mod_rvs'),
            'alert alert-warning mt-3'
        );
        
        // Offer regeneration option.
        if (has_capability('mod/rvs:generate', $modulecontext)) {
            $regenerateurl = new moodle_url('/mod/rvs/regenerate.php', array('id' => $cm->id, 'module' => 'mindmap'));
            echo html_writer::div(
                html_writer::link(
                    $regenerateurl,
                    get_string('regeneratemindmap', 'mod_rvs'),
                    array('class' => 'btn btn-warning')
                ),
                'mt-3'
            );
        }
    } else {
        // Display valid mind map.
        if ($mindmaperror) {
            echo $OUTPUT->notification($mindmaperror, \core\output\notification::NOTIFY_ERROR);
        }

        echo html_writer::tag('h3', format_string($mindmap->title));
        
        // Display mind map visualization.
        $encodeddata = base64_encode($mindmap->data ?? '');

        if ($amdready) {
            echo html_writer::div('', 'mindmap-container', array(
                'id' => 'mindmap-visualization',
                'data-mindmap' => $mindmap->data,
                'data-mindmap-b64' => $encodeddata,
                'style' => 'width: 100%; height: 600px; border: 1px solid #ddd; border-radius: 5px;'
            ));
        } else {
            // Provide a readable fallback when JavaScript cannot render the mind map.
            $decoded = json_decode($mindmap->data ?? '', true);
            if (json_last_error() === JSON_ERROR_NONE && !empty($decoded['branches'])) {
                echo html_writer::start_tag('ul', array('class' => 'list-group mb-3'));
                foreach ($decoded['branches'] as $branch) {
                    $topic = $branch['topic'] ?? get_string('mindmap', 'mod_rvs');
                    echo html_writer::start_tag('li', array('class' => 'list-group-item'));
                    echo html_writer::tag('strong', s($topic));
                    if (!empty($branch['subtopics']) && is_array($branch['subtopics'])) {
                        echo html_writer::start_tag('ul', array('class' => 'mt-2 mb-0'));
                        foreach ($branch['subtopics'] as $subtopic) {
                            echo html_writer::tag('li', s($subtopic));
                        }
                        echo html_writer::end_tag('ul');
                    }
                    echo html_writer::end_tag('li');
                }
                echo html_writer::end_tag('ul');
            } else {
                echo html_writer::div(
                    get_string('mindmapdatainvalid', 'mod_rvs'),
                    'alert alert-warning'
                );
            }
        }
        
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
}

echo html_writer::end_div();



