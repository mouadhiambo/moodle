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
 * Overview module for RVS
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

echo html_writer::start_div('rvs-overview');

// Show warning if AI is not configured.
if (!\mod_rvs\ai\generator::is_ai_configured()) {
    $message = html_writer::tag('strong', get_string('ainotconfigured', 'mod_rvs')) . '<br>';
    $message .= get_string('ainotconfigured_help', 'mod_rvs') . '<br><br>';
    
    if (has_capability('moodle/site:config', context_system::instance())) {
        $configurl = new moodle_url('/admin/settings.php', array('section' => 'modsettingrvs'));
        $message .= html_writer::link($configurl, get_string('configurenow', 'mod_rvs'), 
            array('class' => 'btn btn-warning btn-sm'));
    }
    
    echo $OUTPUT->notification($message, \core\output\notification::NOTIFY_WARNING);
}

// Always show test button for administrators
if (has_capability('moodle/site:config', context_system::instance())) {
    $testurl = new moodle_url('/mod/rvs/test_ai.php', array('id' => $cm->id));
    echo html_writer::start_div('mb-3');
    echo html_writer::link($testurl, get_string('aitest', 'mod_rvs'), 
        array('class' => 'btn btn-info btn-sm'));
    echo html_writer::end_div();
}

// Display content sources.
echo html_writer::tag('h3', get_string('contentsources', 'mod_rvs'));

$rawcontents = $DB->get_records('rvs_content', array('rvsid' => $rvs->id));
$contents = \mod_rvs\content\manager::filter_records($rvs->id, $rawcontents);

if (empty($contents)) {
    echo html_writer::tag('p', get_string('nocontentsources', 'mod_rvs'), array('class' => 'alert alert-info'));
} else {
    echo html_writer::start_tag('ul', array('class' => 'list-group'));
    
    foreach ($contents as $content) {
        $icon = '';
        $name = '';
        
        if ($content->sourcetype == 'book') {
            $book = $DB->get_record('book', array('id' => $content->sourceid));
            if ($book) {
                $icon = html_writer::tag('i', '', array('class' => 'fa fa-book'));
                $name = $book->name;
            }
        } else if ($content->sourcetype == 'file') {
            $resource = $DB->get_record('resource', array('id' => $content->sourceid));
            if ($resource) {
                $icon = html_writer::tag('i', '', array('class' => 'fa fa-file'));
                $name = $resource->name;
            }
        }
        
        echo html_writer::tag('li', $icon . ' ' . $name, array('class' => 'list-group-item'));
    }
    
    echo html_writer::end_tag('ul');
}

// Display available modules.
echo html_writer::tag('h3', get_string('availablemodules', 'mod_rvs'), array('style' => 'margin-top: 30px;'));

echo html_writer::start_div('row');

$modules = array(
    'mindmap' => array('icon' => 'fa-sitemap', 'enabled' => $rvs->enable_mindmap),
    'podcast' => array('icon' => 'fa-microphone', 'enabled' => $rvs->enable_podcast),
    'video' => array('icon' => 'fa-video', 'enabled' => $rvs->enable_video),
    'report' => array('icon' => 'fa-file-text', 'enabled' => $rvs->enable_report),
    'flashcard' => array('icon' => 'fa-layer-group', 'enabled' => $rvs->enable_flashcard),
    'quiz' => array('icon' => 'fa-question-circle', 'enabled' => $rvs->enable_quiz),
);

foreach ($modules as $modname => $modinfo) {
    if ($modinfo['enabled']) {
        echo html_writer::start_div('col-md-4 mb-3');
        
        $card = html_writer::start_div('card text-center');
        $card .= html_writer::start_div('card-body');
        $card .= html_writer::tag('i', '', array('class' => 'fa ' . $modinfo['icon'] . ' fa-3x mb-3'));
        $card .= html_writer::tag('h5', get_string($modname, 'mod_rvs'), array('class' => 'card-title'));
        $card .= html_writer::link(
            new moodle_url('/mod/rvs/view.php', array('id' => $cm->id, 'module' => $modname)),
            get_string('view'),
            array('class' => 'btn btn-primary')
        );
        $card .= html_writer::end_div();
        $card .= html_writer::end_div();
        
        echo $card;
        echo html_writer::end_div();
    }
}

echo html_writer::end_div();

// Show video preview/player if a generated video exists.
$video = $DB->get_record('rvs_video', array('rvsid' => $rvs->id));
if ($video && !empty($video->videourl)) {
    echo html_writer::start_div('mt-4');
    echo html_writer::tag('h4', get_string('videopreview', 'mod_rvs'));
    echo html_writer::start_div('video-player mb-3');
    echo html_writer::tag('video', '', array(
        'src' => $video->videourl,
        'controls' => 'controls',
        'class' => 'w-100',
        'style' => 'max-width: 800px;'
    ));
    echo html_writer::end_div();
    echo html_writer::end_div();
}

// Regenerate button.
if (has_capability('mod/rvs:generate', $modulecontext)) {
    echo html_writer::start_div('mt-4');
    
    $regenerateurl = new moodle_url('/mod/rvs/regenerate.php', array('id' => $cm->id));
    echo html_writer::link(
        $regenerateurl,
        get_string('regenerateall', 'mod_rvs'),
        array('class' => 'btn btn-success')
    );
    
    echo html_writer::end_div();
}

echo html_writer::end_div();



