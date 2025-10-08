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
 * The main rvs configuration form
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_rvs_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('rvsname', 'mod_rvs'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        // Add AI module selection.
        $mform->addElement('header', 'aimodules', get_string('aimodules', 'mod_rvs'));

        $mform->addElement('advcheckbox', 'enable_mindmap', get_string('enable_mindmap', 'mod_rvs'));
        $mform->setDefault('enable_mindmap', 1);

        $mform->addElement('advcheckbox', 'enable_podcast', get_string('enable_podcast', 'mod_rvs'));
        $mform->setDefault('enable_podcast', 1);

        $mform->addElement('advcheckbox', 'enable_video', get_string('enable_video', 'mod_rvs'));
        $mform->setDefault('enable_video', 1);

        $mform->addElement('advcheckbox', 'enable_report', get_string('enable_report', 'mod_rvs'));
        $mform->setDefault('enable_report', 1);

        $mform->addElement('advcheckbox', 'enable_flashcard', get_string('enable_flashcard', 'mod_rvs'));
        $mform->setDefault('enable_flashcard', 1);

        $mform->addElement('advcheckbox', 'enable_quiz', get_string('enable_quiz', 'mod_rvs'));
        $mform->setDefault('enable_quiz', 1);

        // Add auto-detection settings.
        $mform->addElement('header', 'autodetection', get_string('autodetection', 'mod_rvs'));

        $mform->addElement('advcheckbox', 'auto_detect_books', get_string('auto_detect_books', 'mod_rvs'));
        $mform->setDefault('auto_detect_books', 1);

        $mform->addElement('advcheckbox', 'auto_detect_files', get_string('auto_detect_files', 'mod_rvs'));
        $mform->setDefault('auto_detect_files', 1);

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }
}



