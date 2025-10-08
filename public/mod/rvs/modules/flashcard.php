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
 * Flashcard module for RVS
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$PAGE->requires->js_call_amd('mod_rvs/flashcard', 'init');

echo html_writer::start_div('rvs-flashcard');

$flashcards = $DB->get_records('rvs_flashcard', array('rvsid' => $rvs->id));

if (empty($flashcards)) {
    echo html_writer::tag('div', get_string('noflashcards', 'mod_rvs'), array('class' => 'alert alert-info'));
    
    if (has_capability('mod/rvs:generate', $modulecontext)) {
        $regenerateurl = new moodle_url('/mod/rvs/regenerate.php', array('id' => $cm->id, 'module' => 'flashcard'));
        echo html_writer::link(
            $regenerateurl,
            get_string('generateflashcards', 'mod_rvs'),
            array('class' => 'btn btn-primary')
        );
    }
} else {
    echo html_writer::tag('h3', get_string('flashcards', 'mod_rvs'));
    
    // Filter options.
    echo html_writer::start_div('flashcard-filters mb-3');
    echo html_writer::tag('label', get_string('filterbydifficulty', 'mod_rvs') . ': ');
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
        array('id' => 'flashcard-difficulty-filter', 'class' => 'form-control d-inline-block w-auto')
    );
    echo html_writer::end_div();
    
    // Flashcard deck.
    echo html_writer::div('', 'flashcard-deck-container', array('id' => 'flashcard-deck'));
    
    // Pass flashcard data to JavaScript.
    $flashcarddata = array();
    foreach ($flashcards as $flashcard) {
        $flashcarddata[] = array(
            'id' => $flashcard->id,
            'question' => format_text($flashcard->question, FORMAT_HTML),
            'answer' => format_text($flashcard->answer, FORMAT_HTML),
            'difficulty' => $flashcard->difficulty
        );
    }
    
    echo html_writer::script('
        var flashcardData = ' . json_encode($flashcarddata) . ';
    ');
    
    // Navigation buttons.
    echo html_writer::start_div('flashcard-navigation mt-4 text-center');
    echo html_writer::tag('button', get_string('previous', 'mod_rvs'), array(
        'id' => 'flashcard-prev',
        'class' => 'btn btn-secondary mr-2'
    ));
    echo html_writer::tag('span', '1 / ' . count($flashcards), array(
        'id' => 'flashcard-counter',
        'class' => 'mx-3'
    ));
    echo html_writer::tag('button', get_string('next', 'mod_rvs'), array(
        'id' => 'flashcard-next',
        'class' => 'btn btn-secondary ml-2'
    ));
    echo html_writer::tag('button', get_string('flip', 'mod_rvs'), array(
        'id' => 'flashcard-flip',
        'class' => 'btn btn-primary ml-4'
    ));
    echo html_writer::end_div();
    
    // Download button.
    echo html_writer::start_div('mt-3 text-center');
    $downloadurl = new moodle_url('/mod/rvs/download.php', array('id' => $cm->id, 'type' => 'flashcards'));
    echo html_writer::link(
        $downloadurl,
        get_string('downloadflashcards', 'mod_rvs'),
        array('class' => 'btn btn-secondary')
    );
    echo html_writer::end_div();
}

echo html_writer::end_div();



