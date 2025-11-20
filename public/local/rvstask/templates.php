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
 * Manage email templates.
 *
 * @package    local_rvstask
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_rvstask_templates');

require_login();
require_capability('local/rvstask:manage', context_system::instance());

$action = optional_param('action', 'list', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);

$PAGE->set_url(new moodle_url('/local/rvstask/templates.php', ['action' => $action, 'id' => $id]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('managetemplates', 'local_rvstask'));
$PAGE->set_heading(get_string('managetemplates', 'local_rvstask'));

// Handle actions.
if ($action === 'delete' && $id) {
    require_sesskey();
    if (\local_rvstask\template_manager::delete_template($id)) {
        redirect($PAGE->url, get_string('templatedeleted', 'local_rvstask'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect($PAGE->url, get_string('errortemplateinuse', 'local_rvstask'), null, \core\output\notification::NOTIFY_ERROR);
    }
}

echo $OUTPUT->header();

if ($action === 'list') {
    // Display list of templates.
    $templates = \local_rvstask\template_manager::get_all_templates();

    echo html_writer::start_div('mb-3');
    $createurl = new moodle_url('/local/rvstask/edit_template.php');
    echo $OUTPUT->single_button($createurl, get_string('createtemplate', 'local_rvstask'), 'get');
    echo html_writer::end_div();

    if (empty($templates)) {
        echo $OUTPUT->notification(get_string('notemplates', 'local_rvstask'), \core\output\notification::NOTIFY_INFO);
    } else {
        $table = new html_table();
        $table->head = [
            get_string('templatename', 'local_rvstask'),
            get_string('emailsubject', 'local_rvstask'),
            get_string('enabled', 'local_rvstask'),
            get_string('actions', 'core')
        ];

        foreach ($templates as $template) {
            $editurl = new moodle_url('/local/rvstask/edit_template.php', ['id' => $template->id]);
            $deleteurl = new moodle_url('/local/rvstask/templates.php', [
                'action' => 'delete',
                'id' => $template->id,
                'sesskey' => sesskey()
            ]);

            $actions = [];
            $actions[] = html_writer::link($editurl, get_string('edit'));
            $actions[] = html_writer::link($deleteurl, get_string('delete'), [
                'class' => 'text-danger',
                'onclick' => 'return confirm("' . get_string('confirmdeletetemplate', 'local_rvstask') . '");'
            ]);

            $table->data[] = [
                format_string($template->name),
                format_string($template->subject),
                $template->enabled ? get_string('yes') : get_string('no'),
                implode(' | ', $actions)
            ];
        }

        echo html_writer::table($table);
    }
}

echo $OUTPUT->footer();
