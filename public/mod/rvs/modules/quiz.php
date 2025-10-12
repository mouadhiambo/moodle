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
 * Quiz module for RVS
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$PAGE->requires->js_call_amd('mod_rvs/quiz', 'init');

echo html_writer::start_div('rvs-quiz');

$questions = $DB->get_records('rvs_quiz', array('rvsid' => $rvs->id));

if (empty($questions)) {
    echo html_writer::tag('div', get_string('noquiz', 'mod_rvs'), array('class' => 'alert alert-info'));
    
    if (has_capability('mod/rvs:generate', $modulecontext)) {
        $regenerateurl = new moodle_url('/mod/rvs/regenerate.php', array('id' => $cm->id, 'module' => 'quiz'));
        echo html_writer::link(
            $regenerateurl,
            get_string('generatequiz', 'mod_rvs'),
            array('class' => 'btn btn-primary')
        );
    }
} else {
    // Validate quiz question data.
    $validquestions = array();
    $invalidcount = 0;
    
    foreach ($questions as $question) {
        // Check if question has required fields.
        if (!empty($question->question) && !empty($question->options) && isset($question->correctanswer)) {
            // Validate options JSON.
            $options = json_decode($question->options, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($options) && count($options) >= 2) {
                // Validate correct answer index.
                if ($question->correctanswer >= 0 && $question->correctanswer < count($options)) {
                    // Validate difficulty level.
                    if (!isset($question->difficulty) || !in_array($question->difficulty, array('easy', 'medium', 'hard'))) {
                        $question->difficulty = 'medium'; // Default to medium if invalid.
                    }
                    
                    // Ensure explanation exists.
                    if (empty($question->explanation)) {
                        $question->explanation = get_string('noexplanation', 'mod_rvs');
                    }
                    
                    $validquestions[] = $question;
                } else {
                    $invalidcount++;
                }
            } else {
                $invalidcount++;
            }
        } else {
            $invalidcount++;
        }
    }
    
    if (empty($validquestions)) {
        // All questions are invalid.
        echo $OUTPUT->notification(
            get_string('quizdatainvalid', 'mod_rvs'),
            \core\output\notification::NOTIFY_ERROR
        );
        
        if (has_capability('mod/rvs:generate', $modulecontext)) {
            $regenerateurl = new moodle_url('/mod/rvs/regenerate.php', array('id' => $cm->id, 'module' => 'quiz'));
            echo html_writer::div(
                html_writer::link(
                    $regenerateurl,
                    get_string('regeneratequiz', 'mod_rvs'),
                    array('class' => 'btn btn-warning')
                ),
                'mt-3'
            );
        }
    } else {
        // Display warning if some questions are invalid.
        if ($invalidcount > 0) {
            echo $OUTPUT->notification(
                get_string('somequestionsinvalid', 'mod_rvs', $invalidcount),
                \core\output\notification::NOTIFY_WARNING
            );
        }
        
        echo html_writer::tag('h3', get_string('interactivequiz', 'mod_rvs'));
        
        // Filter options with difficulty filtering interface.
        echo html_writer::start_div('quiz-filters mb-3 card card-body bg-light');
        echo html_writer::tag('label', get_string('filterbydifficulty', 'mod_rvs') . ': ', array('class' => 'mr-2'));
        echo html_writer::select(
            array(
                'all' => get_string('all'),
                'easy' => get_string('easy', 'mod_rvs'),
                'medium' => get_string('medium', 'mod_rvs'),
                'hard' => get_string('hard', 'mod_rvs')
            ),
            'difficulty',
            'all',
            false,
            array('id' => 'quiz-difficulty-filter', 'class' => 'form-control d-inline-block w-auto')
        );
        echo html_writer::tag('span', 
            get_string('totalquestions', 'mod_rvs', count($validquestions)), 
            array('class' => 'ml-3 text-muted')
        );
        echo html_writer::end_div();
        
        // Quiz container - ensure interactive answer checking works correctly.
        echo html_writer::div('', 'quiz-container', array('id' => 'quiz-questions'));
        
        // Pass validated quiz data to JavaScript.
        $quizdata = array();
        $questionnum = 1;
        foreach ($validquestions as $question) {
            $quizdata[] = array(
                'id' => $question->id,
                'number' => $questionnum++,
                'question' => format_text($question->question, FORMAT_HTML),
                'options' => json_decode($question->options),
                'correctanswer' => $question->correctanswer,
                'explanation' => format_text($question->explanation, FORMAT_HTML),
                'difficulty' => $question->difficulty
            );
        }
        
        echo html_writer::script('
            var quizData = ' . json_encode($quizdata) . ';
        ');
        
        // Quiz controls - ensure interactive answer checking and final score calculation.
        echo html_writer::start_div('quiz-controls mt-4 text-center');
        
        echo html_writer::tag('button', get_string('checkanswers', 'mod_rvs'), array(
            'id' => 'check-answers',
            'class' => 'btn btn-primary btn-lg mr-2'
        ));
        
        echo html_writer::tag('button', get_string('resetquiz', 'mod_rvs'), array(
            'id' => 'reset-quiz',
            'class' => 'btn btn-secondary'
        ));
        
        echo html_writer::end_div();
        
        // Results container - display explanations after answer submission and show final score.
        echo html_writer::div('', 'quiz-results mt-4 card card-body', array(
            'id' => 'quiz-results',
            'style' => 'display: none;'
        ));
        
        // Download button.
        echo html_writer::start_div('mt-3 text-center');
        $downloadurl = new moodle_url('/mod/rvs/download.php', array('id' => $cm->id, 'type' => 'quiz'));
        echo html_writer::link(
            $downloadurl,
            get_string('downloadquiz', 'mod_rvs'),
            array('class' => 'btn btn-secondary')
        );
        echo html_writer::end_div();
    }
}

echo html_writer::end_div();



