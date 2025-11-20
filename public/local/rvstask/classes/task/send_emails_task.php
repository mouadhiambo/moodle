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
 * Scheduled task to send emails.
 *
 * @package    local_rvstask
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rvstask\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task class for sending emails.
 */
class send_emails_task extends \core\task\scheduled_task {

    /**
     * Get the name of the task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('sendemailstask', 'local_rvstask');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        mtrace('Starting RVS email task...');

        // Get all unsent emails that are due to be sent.
        $now = time();
        $emails = $DB->get_records_select(
            'local_rvstask_queue',
            'sent = 0 AND scheduledtime <= ? AND attempts < 3',
            [$now],
            'scheduledtime ASC',
            '*',
            0,
            100 // Process up to 100 emails per run.
        );

        if (empty($emails)) {
            mtrace('No emails to send.');
            return;
        }

        mtrace('Found ' . count($emails) . ' emails to send.');

        $sent = 0;
        $failed = 0;

        foreach ($emails as $email) {
            try {
                if ($this->send_queued_email($email)) {
                    $sent++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                mtrace('Error processing email ID ' . $email->id . ': ' . $e->getMessage());
                $this->update_email_failure($email->id, $e->getMessage());
                $failed++;
            }
        }

        mtrace("Email task completed. Sent: $sent, Failed: $failed");
    }

    /**
     * Send a queued email.
     *
     * @param object $queueitem Queue item from database.
     * @return bool True if sent successfully.
     */
    protected function send_queued_email($queueitem) {
        global $DB;

        // Get the template.
        $template = $DB->get_record('local_rvstask_templates', ['id' => $queueitem->templateid]);
        if (!$template || !$template->enabled) {
            mtrace('Template ' . $queueitem->templateid . ' not found or disabled.');
            $this->update_email_failure($queueitem->id, 'Template not found or disabled');
            return false;
        }

        // Get the recipient.
        $user = $DB->get_record('user', ['id' => $queueitem->userid]);
        if (!$user || $user->deleted || $user->suspended) {
            mtrace('User ' . $queueitem->userid . ' not found, deleted, or suspended.');
            $this->update_email_failure($queueitem->id, 'User not found, deleted, or suspended');
            return false;
        }

        // Get course if specified.
        $course = null;
        if ($queueitem->courseid) {
            $course = $DB->get_record('course', ['id' => $queueitem->courseid]);
        }

        // Replace placeholders in subject and body.
        $subject = $this->replace_placeholders($template->subject, $user, $course);
        $body = $this->replace_placeholders($template->body, $user, $course);

        // Get support user as sender.
        $from = \core_user::get_support_user();

        // Send the email using Moodle's email_to_user function.
        $success = email_to_user($user, $from, $subject, html_to_text($body), $body);

        if ($success) {
            // Mark as sent.
            $DB->update_record('local_rvstask_queue', [
                'id' => $queueitem->id,
                'sent' => 1,
                'timesent' => time(),
            ]);
            mtrace('Email sent to user ' . $user->email);
            return true;
        } else {
            mtrace('Failed to send email to user ' . $user->email);
            $this->update_email_failure($queueitem->id, 'email_to_user returned false');
            return false;
        }
    }

    /**
     * Update email record on failure.
     *
     * @param int $queueid Queue item ID.
     * @param string $error Error message.
     */
    protected function update_email_failure($queueid, $error) {
        global $DB;

        $record = $DB->get_record('local_rvstask_queue', ['id' => $queueid]);
        if ($record) {
            $record->attempts++;
            $record->lasterror = $error;
            $DB->update_record('local_rvstask_queue', $record);
        }
    }

    /**
     * Replace placeholders in text.
     *
     * @param string $text Text with placeholders.
     * @param object $user User object.
     * @param object|null $course Course object (optional).
     * @return string Text with placeholders replaced.
     */
    protected function replace_placeholders($text, $user, $course = null) {
        global $SITE;

        $replacements = [
            '{firstname}' => $user->firstname,
            '{lastname}' => $user->lastname,
            '{fullname}' => fullname($user),
            '{email}' => $user->email,
            '{sitename}' => $SITE->fullname,
        ];

        if ($course) {
            $replacements['{coursename}'] = $course->shortname;
            $replacements['{coursefullname}'] = $course->fullname;
        } else {
            $replacements['{coursename}'] = '';
            $replacements['{coursefullname}'] = '';
        }

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
}
