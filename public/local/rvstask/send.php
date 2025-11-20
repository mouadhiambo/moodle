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
            'coursestudents' => get_string('coursestudents', 'local_rvstask'),
            'courseteachers' => get_string('courseteachers', 'local_rvstask'),
            'coursecompletions' => get_string('coursecompletions', 'local_rvstask'),
            'neveraccessed' => get_string('neveraccessed', 'local_rvstask'),
            'specificusers' => get_string('specificusers', 'local_rvstask')
        ];
        $mform->addElement('select', 'recipienttype', get_string('selectrecipients', 'local_rvstask'), $recipientoptions);
        $mform->setDefault('recipienttype', 'allstudents');

        // Course selector (for course-based recipient types).
        $courses = $DB->get_records_menu('course', null, 'fullname', 'id,fullname');
        // Remove site course from list.
        if (isset($courses[SITEID])) {
            unset($courses[SITEID]);
        }
        $mform->addElement('autocomplete', 'courseids', get_string('courses'), $courses, ['multiple' => true]);
        $mform->hideIf('courseids', 'recipienttype', 'neq', 'coursecompletions');
        $mform->hideIf('courseids', 'recipienttype', 'neq', 'coursestudents');
        $mform->hideIf('courseids', 'recipienttype', 'neq', 'courseteachers');
        $mform->hideIf('courseids', 'recipienttype', 'neq', 'neveraccessed');
        
        // Single course selector (for coursestudents and courseteachers).
        $mform->addElement('autocomplete', 'courseid', get_string('course'), $courses);
        $mform->hideIf('courseid', 'recipienttype', 'neq', 'coursestudents');
        $mform->hideIf('courseid', 'recipienttype', 'neq', 'courseteachers');

        // User selector (for specificusers).
        $mform->addElement('textarea', 'userids', get_string('specificusers', 'local_rvstask'),
            ['rows' => 5, 'cols' => 50, 'placeholder' => 'Enter email addresses or user IDs (one per line or comma-separated)']);
        $mform->setType('userids', PARAM_TEXT);
        $mform->addElement('static', 'userids_help', '', get_string('specificusers_help', 'local_rvstask'));
        $mform->hideIf('userids', 'recipienttype', 'neq', 'specificusers');
        $mform->hideIf('userids_help', 'recipienttype', 'neq', 'specificusers');

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

        if (in_array($data['recipienttype'], ['coursecompletions', 'neveraccessed']) && empty($data['courseids'])) {
            $errors['courseids'] = get_string('errornorecipients', 'local_rvstask');
        }

        if (in_array($data['recipienttype'], ['coursestudents', 'courseteachers']) && empty($data['courseid'])) {
            $errors['courseid'] = get_string('errorselectcourse', 'local_rvstask');
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
    if (in_array($data->recipienttype, ['coursecompletions', 'neveraccessed']) && !empty($data->courseids)) {
        $params['courseids'] = $data->courseids;
    } else if (in_array($data->recipienttype, ['coursestudents', 'courseteachers']) && !empty($data->courseid)) {
        $params['courseid'] = $data->courseid;
    } else if ($data->recipienttype === 'specificusers' && !empty($data->userids)) {
        // Parse user IDs or emails from textarea.
        $userinputs = preg_split('/[\s,]+/', $data->userids, -1, PREG_SPLIT_NO_EMPTY);
        $params['userinputs'] = $userinputs;
    }

    $userids = \local_rvstask\queue_manager::get_recipients($data->recipienttype, $params);

    if (empty($userids)) {
        $message = get_string('errornorecipients', 'local_rvstask');
        redirect($PAGE->url, $message, null, \core\output\notification::NOTIFY_ERROR);
    }

    if ($data->sendingtype === 'now') {
        // Send immediately without queuing.
        $courseid = !empty($params['courseid']) ? $params['courseid'] : null;
        $result = \local_rvstask\queue_manager::send_immediately($data->templateid, $userids, $courseid);
        
        if ($result['sent'] > 0) {
            $message = get_string('emailsent', 'local_rvstask', $result['sent']);
            if ($result['failed'] > 0) {
                $message .= ' ' . get_string('emailsentpartial', 'local_rvstask', $result['failed']);
            }
            redirect($PAGE->url, $message, null, \core\output\notification::NOTIFY_SUCCESS);
        } else {
            $message = get_string('emailsenterror', 'local_rvstask');
            redirect($PAGE->url, $message, null, \core\output\notification::NOTIFY_ERROR);
        }
    } else {
        // Queue for scheduled sending.
        $scheduledtime = $data->scheduledtime;
        $count = \local_rvstask\queue_manager::queue_emails_to_users($data->templateid, $userids, $scheduledtime);
        $message = get_string('emailqueued', 'local_rvstask', $count);
        redirect($PAGE->url, $message, null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

echo $OUTPUT->header();

// Display queue statistics.
$stats = \local_rvstask\queue_manager::get_queue_stats();
echo html_writer::start_div('alert alert-info', ['id' => 'queue-stats-container']);
echo html_writer::tag('h5', get_string('queuestats', 'local_rvstask'));
echo html_writer::tag('p', "Pending: <span id='stat-pending'>{$stats->pending}</span> | " .
    "Sent: <span id='stat-sent'>{$stats->sent}</span> | " .
    "Failed: <span id='stat-failed'>{$stats->failed}</span>", ['id' => 'queue-stats']);
echo html_writer::tag('small', 'Auto-refreshing every 10 seconds', ['class' => 'text-muted d-block mt-2']);
echo html_writer::end_div();

// Add JavaScript for real-time updates.
echo html_writer::start_tag('script');
?>
(function() {
    var updateStats = function() {
        fetch('<?php echo new moodle_url('/local/rvstask/ajax_stats.php'); ?>')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('stat-pending').textContent = data.pending;
                    document.getElementById('stat-sent').textContent = data.sent;
                    document.getElementById('stat-failed').textContent = data.failed;
                    
                    // Highlight changes with animation
                    var container = document.getElementById('queue-stats-container');
                    container.style.transition = 'background-color 0.3s';
                    container.style.backgroundColor = '#d1ecf1';
                    setTimeout(function() {
                        container.style.backgroundColor = '';
                    }, 300);
                }
            })
            .catch(error => console.error('Error updating stats:', error));
    };
    
    // Update every 10 seconds
    setInterval(updateStats, 10000);
})();
<?php
echo html_writer::end_tag('script');

$mform->display();
echo $OUTPUT->footer();
