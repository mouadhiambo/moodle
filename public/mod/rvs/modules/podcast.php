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
 * Podcast module for RVS
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

echo html_writer::start_div('rvs-podcast');

$podcast = $DB->get_record('rvs_podcast', array('rvsid' => $rvs->id));

if (!$podcast) {
    echo html_writer::tag('div', get_string('nopodcast', 'mod_rvs'), array('class' => 'alert alert-info'));
    
    if (has_capability('mod/rvs:generate', $modulecontext)) {
        $regenerateurl = new moodle_url('/mod/rvs/regenerate.php', array('id' => $cm->id, 'module' => 'podcast'));
        echo html_writer::link(
            $regenerateurl,
            get_string('generatepodcast', 'mod_rvs'),
            array('class' => 'btn btn-primary')
        );
    }
} else {
    // Validate podcast data.
    if (empty($podcast->script)) {
        // Display error for missing script.
        echo $OUTPUT->notification(
            get_string('podcastdatamissing', 'mod_rvs'),
            \core\output\notification::NOTIFY_ERROR
        );
        
        if (has_capability('mod/rvs:generate', $modulecontext)) {
            $regenerateurl = new moodle_url('/mod/rvs/regenerate.php', array('id' => $cm->id, 'module' => 'podcast'));
            echo html_writer::div(
                html_writer::link(
                    $regenerateurl,
                    get_string('regeneratepodcast', 'mod_rvs'),
                    array('class' => 'btn btn-warning')
                ),
                'mt-3'
            );
        }
    } else {
        echo html_writer::tag('h3', format_string($podcast->title));
        
        // Display audio player if available.
        if (!empty($podcast->audiourl)) {
            echo html_writer::start_div('podcast-player mb-4');
            echo html_writer::tag('audio', '', array(
                'src' => $podcast->audiourl,
                'controls' => 'controls',
                'class' => 'w-100'
            ));
            echo html_writer::end_div();
        } else {
            // Show message when audio generation is not enabled.
            echo html_writer::div(
                get_string('audionotgenerated', 'mod_rvs'),
                'alert alert-info mb-3'
            );
        }
        
        // Display formatted script with proper structure.
        echo html_writer::tag('h4', get_string('podcastscript', 'mod_rvs'));
        
        // Format script to highlight sections and speaker labels.
        $formattedscript = $podcast->script;
        
        // Highlight section markers like [INTRO], [MAIN CONTENT], [CONCLUSION].
        $formattedscript = preg_replace(
            '/\[(INTRO|MAIN CONTENT|CONCLUSION|SCENE \d+)\]/i',
            '<strong class="text-primary">[$1]</strong>',
            $formattedscript
        );
        
        // Highlight speaker labels like HOST:.
        $formattedscript = preg_replace(
            '/^(HOST|SPEAKER \d+):/m',
            '<strong class="text-success">$1:</strong>',
            $formattedscript
        );
        
        echo html_writer::div(
            nl2br(format_text($formattedscript, FORMAT_HTML)),
            'podcast-script card card-body',
            array('style' => 'max-height: 500px; overflow-y: auto; white-space: pre-wrap; font-family: monospace;')
        );
        
        // Download buttons.
        echo html_writer::start_div('mt-3');
        
        $downloadscripturl = new moodle_url('/mod/rvs/download.php', array('id' => $cm->id, 'type' => 'podcast_script'));
        echo html_writer::link(
            $downloadscripturl,
            get_string('downloadscript', 'mod_rvs'),
            array('class' => 'btn btn-secondary mr-2')
        );
        
        if (!empty($podcast->audiourl)) {
            echo html_writer::link(
                $podcast->audiourl,
                get_string('downloadaudio', 'mod_rvs'),
                array('class' => 'btn btn-secondary', 'download' => '')
            );
        }
        
        echo html_writer::end_div();
    }
}

echo html_writer::end_div();



