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
 * Send emails on demand.
 *
 * @package    local_rvstask
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');

admin_externalpage_setup('local_rvstask_send');

require_login();
require_capability('local/rvstask:send', context_system::instance());

$PAGE->set_url(new moodle_url('/local/rvstask/send.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('sendemail', 'local_rvstask'));
$PAGE->set_heading(get_string('sendemail', 'local_rvstask'));

/**
 * Send email form class.
 */
class send_email_form extends moodleform {
    /**
     * Form definition.
     */
    protected function definition() {
        global $DB;
        
        $mform = $this->_form;

        // Select template.
        $templates = \local_rvstask\template_manager::get_enabled_templates();
        $templateoptions = ['' => get_string('selecttemplate', 'local_rvstask')];
        foreach ($templates as $template) {
            $templateoptions[$template->id] = $template->name;
        }
        $mform->addElement('select', 'templateid', get_string('emailtemplate', 'local_rvstask'), $templateoptions);
        $mform->addRule('templateid', null, 'required', null, 'client');

        // Recipient type.
        $recipientoptions = [
            'allstudents' => get_string('allstudents', 'local_rvstask'),
            'allteachers' => get_string('allteachers', 'local_rvstask'),
            'coursecompletions' => get_string('coursecompletions', 'local_rvstask'),
            'specificusers' => get_string('specificusers', 'local_rvstask')
        ];
        $mform->addElement('select', 'recipienttype', get_string('selectrecipients', 'local_rvstask'), $recipientoptions);
        $mform->setDefault('recipienttype', 'allstudents');

        // Course selector (for coursecompletions).
        $courses = $DB->get_records_menu('course', ['id' => ['!=', SITEID]], '', 'id,fullname');
        $mform->addElement('autocomplete', 'courseids', get_string('courses'), $courses, ['multiple' => true]);
        $mform->hideIf('courseids', 'recipienttype', 'neq', 'coursecompletions');

        // User selector (for specificusers).
        $mform->addElement('textarea', 'userids', get_string('specificusers', 'local_rvstask'),
            ['rows' => 5, 'cols' => 50, 'placeholder' => 'Enter user IDs (one per line or comma-separated)']);
        $mform->setType('userids', PARAM_TEXT);
        $mform->hideIf('userids', 'recipienttype', 'neq', 'specificusers');

        // Schedule or send now.
        $sendingoptions = [
            'now' => get_string('sendnow', 'local_rvstask'),
            'scheduled' => get_string('schedulesend', 'local_rvstask')
        ];
        $mform->addElement('select', 'sendingtype', get_string('sendemail', 'local_rvstask'), $sendingoptions);
        $mform->setDefault('sendingtype', 'now');

        // Scheduled time.
        $mform->addElement('date_time_selector', 'scheduledtime', get_string('schedulesend', 'local_rvstask'));
        $mform->setDefault('scheduledtime', time() + 3600);
        $mform->hideIf('scheduledtime', 'sendingtype', 'neq', 'scheduled');

        $this->add_action_buttons(true, get_string('sendemail', 'local_rvstask'));
    }

    /**
     * Form validation.
     *
     * @param array $data Form data.
     * @param array $files Form files.
     * @return array Errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['recipienttype'] === 'specificusers' && empty($data['userids'])) {
            $errors['userids'] = get_string('errornorecipients', 'local_rvstask');
        }

        if ($data['recipienttype'] === 'coursecompletions' && empty($data['courseids'])) {
            $errors['courseids'] = get_string('errornorecipients', 'local_rvstask');
        }

        return $errors;
    }
}

$mform = new send_email_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/admin/index.php'));
} else if ($data = $mform->get_data()) {
    // Get recipients.
    $params = [];
    if ($data->recipienttype === 'coursecompletions' && !empty($data->courseids)) {
        $params['courseids'] = $data->courseids;
    } else if ($data->recipienttype === 'specificusers' && !empty($data->userids)) {
        // Parse user IDs from textarea.
        $userids = preg_split('/[\s,]+/', $data->userids, -1, PREG_SPLIT_NO_EMPTY);
        $params['userids'] = array_map('intval', $userids);
    }

    $userids = \local_rvstask\queue_manager::get_recipients($data->recipienttype, $params);

    if (empty($userids)) {
        $message = get_string('errornorecipients', 'local_rvstask');
        redirect($PAGE->url, $message, null, \core\output\notification::NOTIFY_ERROR);
    }

    // Determine scheduled time.
    $scheduledtime = ($data->sendingtype === 'scheduled') ? $data->scheduledtime : time();

    // Queue emails.
    $count = \local_rvstask\queue_manager::queue_emails_to_users($data->templateid, $userids, $scheduledtime);

    $message = get_string('emailsent', 'local_rvstask', $count);
    redirect($PAGE->url, $message, null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

// Display queue statistics.
$stats = \local_rvstask\queue_manager::get_queue_stats();
echo html_writer::start_div('alert alert-info');
echo html_writer::tag('h5', get_string('queuestats', 'local_rvstask'));
echo html_writer::tag('p', "Pending: {$stats->pending} | Sent: {$stats->sent} | Failed: {$stats->failed}");
echo html_writer::end_div();

$mform->display();
echo $OUTPUT->footer();
