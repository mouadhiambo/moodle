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
 * Video module for RVS
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

echo html_writer::start_div('rvs-video');

$video = $DB->get_record('rvs_video', array('rvsid' => $rvs->id));

if (!$video) {
    echo html_writer::tag('div', get_string('novideo', 'mod_rvs'), array('class' => 'alert alert-info'));
    
    if (has_capability('mod/rvs:generate', $modulecontext)) {
        $regenerateurl = new moodle_url('/mod/rvs/regenerate.php', array('id' => $cm->id, 'module' => 'video'));
        echo html_writer::link(
            $regenerateurl,
            get_string('generatevideo', 'mod_rvs'),
            array('class' => 'btn btn-primary')
        );
    }
} else {
    // Validate video data.
    if (empty($video->script)) {
        // Display error for missing script.
        echo $OUTPUT->notification(
            get_string('videodatamissing', 'mod_rvs'),
            \core\output\notification::NOTIFY_ERROR
        );
        
        if (has_capability('mod/rvs:generate', $modulecontext)) {
            $regenerateurl = new moodle_url('/mod/rvs/regenerate.php', array('id' => $cm->id, 'module' => 'video'));
            echo html_writer::div(
                html_writer::link(
                    $regenerateurl,
                    get_string('regeneratevideo', 'mod_rvs'),
                    array('class' => 'btn btn-warning')
                ),
                'mt-3'
            );
        }
    } else {
        echo html_writer::tag('h3', format_string($video->title));
        
        // Display video player if available.
        if (!empty($video->videourl)) {
            echo html_writer::start_div('video-player mb-4');
            echo html_writer::tag('video', '', array(
                'src' => $video->videourl,
                'controls' => 'controls',
                'class' => 'w-100',
                'style' => 'max-width: 800px;'
            ));
            echo html_writer::end_div();
        } else {
            // Show message when video generation is not enabled.
            echo html_writer::div(
                get_string('videonotgenerated', 'mod_rvs'),
                'alert alert-info mb-3'
            );
        }
        
        // Display formatted script with visual cues highlighted.
        echo html_writer::tag('h4', get_string('videoscript', 'mod_rvs'));
        
        // Format script to highlight visual cues and scene markers.
        $formattedscript = $video->script;
        
        // Highlight scene markers like [SCENE 1], [SCENE 2].
        $formattedscript = preg_replace(
            '/\[(SCENE \d+)\]/i',
            '<strong class="text-primary">[$1]</strong>',
            $formattedscript
        );
        
        // Highlight visual cues like [VISUAL: ...].
        $formattedscript = preg_replace(
            '/\[VISUAL: ([^\]]+)\]/i',
            '<span class="badge badge-info">[VISUAL: $1]</span>',
            $formattedscript
        );
        
        // Highlight narration labels.
        $formattedscript = preg_replace(
            '/^(NARRATION|NARRATOR):/m',
            '<strong class="text-success">$1:</strong>',
            $formattedscript
        );
        
        echo html_writer::div(
            nl2br(format_text($formattedscript, FORMAT_HTML)),
            'video-script card card-body',
            array('style' => 'max-height: 500px; overflow-y: auto; white-space: pre-wrap; font-family: monospace;')
        );
        
        // Download buttons.
        echo html_writer::start_div('mt-3');
        
        $downloadscripturl = new moodle_url('/mod/rvs/download.php', array('id' => $cm->id, 'type' => 'video_script'));
        echo html_writer::link(
            $downloadscripturl,
            get_string('downloadscript', 'mod_rvs'),
            array('class' => 'btn btn-secondary mr-2')
        );
        
        if (!empty($video->videourl)) {
            echo html_writer::link(
                $video->videourl,
                get_string('downloadvideo', 'mod_rvs'),
                array('class' => 'btn btn-secondary', 'download' => '')
            );
        }
        
        echo html_writer::end_div();
    }
}

echo html_writer::end_div();



