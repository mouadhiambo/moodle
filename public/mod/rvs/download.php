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
 * Download RVS generated content
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$id = required_param('id', PARAM_INT); // Course module ID.
$type = required_param('type', PARAM_ALPHAEXT);
$format = optional_param('format', 'txt', PARAM_ALPHA);

$cm = get_coursemodule_from_id('rvs', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$rvs = $DB->get_record('rvs', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);
require_capability('mod/rvs:view', $modulecontext);

switch ($type) {
    case 'mindmap':
        $mindmap = $DB->get_record('rvs_mindmap', array('rvsid' => $rvs->id), '*', MUST_EXIST);
        $filename = clean_filename($rvs->name . '_mindmap.json');
        $content = $mindmap->data;
        $mimetype = 'application/json';
        break;
        
    case 'podcast_script':
        $podcast = $DB->get_record('rvs_podcast', array('rvsid' => $rvs->id), '*', MUST_EXIST);
        $filename = clean_filename($rvs->name . '_podcast_script.txt');
        $content = $podcast->script;
        $mimetype = 'text/plain';
        break;
        
    case 'video_script':
        $video = $DB->get_record('rvs_video', array('rvsid' => $rvs->id), '*', MUST_EXIST);
        $filename = clean_filename($rvs->name . '_video_script.txt');
        $content = $video->script;
        $mimetype = 'text/plain';
        break;
        
    case 'report':
        $report = $DB->get_record('rvs_report', array('rvsid' => $rvs->id), '*', MUST_EXIST);
        
        if ($format == 'html') {
            $filename = clean_filename($rvs->name . '_report.html');
            $content = '<html><head><meta charset="UTF-8"><title>' . $report->title . '</title></head><body>' . 
                       $report->content . '</body></html>';
            $mimetype = 'text/html';
        } else {
            $filename = clean_filename($rvs->name . '_report.txt');
            $content = strip_tags($report->content);
            $mimetype = 'text/plain';
        }
        break;
        
    case 'flashcards':
        $flashcards = $DB->get_records('rvs_flashcard', array('rvsid' => $rvs->id));
        $filename = clean_filename($rvs->name . '_flashcards.json');
        
        $data = array();
        foreach ($flashcards as $flashcard) {
            $data[] = array(
                'question' => strip_tags($flashcard->question),
                'answer' => strip_tags($flashcard->answer),
                'difficulty' => $flashcard->difficulty
            );
        }
        $content = json_encode($data, JSON_PRETTY_PRINT);
        $mimetype = 'application/json';
        break;
        
    case 'quiz':
        $questions = $DB->get_records('rvs_quiz', array('rvsid' => $rvs->id));
        $filename = clean_filename($rvs->name . '_quiz.json');
        
        $data = array();
        foreach ($questions as $question) {
            $data[] = array(
                'question' => strip_tags($question->question),
                'options' => json_decode($question->options),
                'correctanswer' => $question->correctanswer,
                'explanation' => strip_tags($question->explanation),
                'difficulty' => $question->difficulty
            );
        }
        $content = json_encode($data, JSON_PRETTY_PRINT);
        $mimetype = 'application/json';
        break;
        
    default:
        throw new moodle_exception('invalidtype', 'mod_rvs');
}

// Send the file.
send_file($content, $filename, 0, 0, true, true, $mimetype);



