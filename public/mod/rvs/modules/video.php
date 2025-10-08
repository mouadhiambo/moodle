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
    }
    
    // Display script.
    echo html_writer::tag('h4', get_string('videoscript', 'mod_rvs'));
    echo html_writer::div(
        nl2br(format_text($video->script, FORMAT_HTML)),
        'video-script card card-body',
        array('style' => 'max-height: 500px; overflow-y: auto;')
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

echo html_writer::end_div();



