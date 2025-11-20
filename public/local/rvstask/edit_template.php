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
 * Edit email template.
 *
 * @package    local_rvstask
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');

require_login();
require_capability('local/rvstask:manage', context_system::instance());

$id = optional_param('id', 0, PARAM_INT);

$PAGE->set_url(new moodle_url('/local/rvstask/edit_template.php', ['id' => $id]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');

if ($id) {
    $template = \local_rvstask\template_manager::get_template($id);
    if (!$template) {
        throw new moodle_exception('errortemplatenotfound', 'local_rvstask');
    }
    $PAGE->set_title(get_string('edittemplate', 'local_rvstask'));
    $PAGE->set_heading(get_string('edittemplate', 'local_rvstask'));
} else {
    $template = null;
    $PAGE->set_title(get_string('createtemplate', 'local_rvstask'));
    $PAGE->set_heading(get_string('createtemplate', 'local_rvstask'));
}

/**
 * Template form class.
 */
class template_form extends moodleform {
    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;

        // Template name.
        $mform->addElement('text', 'name', get_string('templatename', 'local_rvstask'), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        // Email subject.
        $mform->addElement('text', 'subject', get_string('emailsubject', 'local_rvstask'), ['size' => 50]);
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', null, 'required', null, 'client');

        // Email body.
        $mform->addElement('editor', 'body_editor', get_string('emailbody', 'local_rvstask'), ['rows' => 15]);
        $mform->setType('body_editor', PARAM_RAW);
        $mform->addRule('body_editor', null, 'required', null, 'client');

        // Enabled.
        $mform->addElement('advcheckbox', 'enabled', get_string('enabled', 'local_rvstask'));
        $mform->setDefault('enabled', 1);

        // Placeholders help.
        $placeholders = html_writer::tag('h5', get_string('availableplaceholders', 'local_rvstask'));
        $placeholders .= html_writer::tag('ul', 
            html_writer::tag('li', get_string('placeholder_firstname', 'local_rvstask')) .
            html_writer::tag('li', get_string('placeholder_lastname', 'local_rvstask')) .
            html_writer::tag('li', get_string('placeholder_fullname', 'local_rvstask')) .
            html_writer::tag('li', get_string('placeholder_email', 'local_rvstask')) .
            html_writer::tag('li', get_string('placeholder_coursename', 'local_rvstask')) .
            html_writer::tag('li', get_string('placeholder_coursefullname', 'local_rvstask')) .
            html_writer::tag('li', get_string('placeholder_sitename', 'local_rvstask'))
        );
        $mform->addElement('html', html_writer::div($placeholders, 'alert alert-info'));

        // Hidden fields.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons();
    }
}

$mform = new template_form();

if ($template) {
    // Prepare data for editing.
    $template->body_editor = [
        'text' => $template->body,
        'format' => $template->bodyformat
    ];
    $mform->set_data($template);
}

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/rvstask/templates.php'));
} else if ($data = $mform->get_data()) {
    // Prepare data.
    $data->body = $data->body_editor['text'];
    $data->bodyformat = $data->body_editor['format'];

    if ($data->id) {
        // Update existing template.
        \local_rvstask\template_manager::update_template($data->id, $data);
        redirect(
            new moodle_url('/local/rvstask/templates.php'),
            get_string('templateupdated', 'local_rvstask'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        // Create new template.
        \local_rvstask\template_manager::create_template($data);
        redirect(
            new moodle_url('/local/rvstask/templates.php'),
            get_string('templatecreated', 'local_rvstask'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
