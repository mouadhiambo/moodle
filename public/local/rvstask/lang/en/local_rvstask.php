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
 * Language strings for the RVS Task plugin.
 *
 * @package    local_rvstask
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'RVS Task';
$string['rvstask:manage'] = 'Manage RVS email tasks';
$string['rvstask:send'] = 'Send emails on demand';

// Task strings.
$string['sendemailstask'] = 'Send scheduled emails';

// Email template strings.
$string['emailtemplate'] = 'Email template';
$string['emailtemplates'] = 'Email templates';
$string['templatename'] = 'Template name';
$string['emailsubject'] = 'Email subject';
$string['emailbody'] = 'Email body';
$string['emailbodyformat'] = 'Email body format';
$string['createtemplate'] = 'Create email template';
$string['edittemplate'] = 'Edit email template';
$string['deletetemplate'] = 'Delete email template';
$string['templatecreated'] = 'Email template created successfully';
$string['templateupdated'] = 'Email template updated successfully';
$string['templatedeleted'] = 'Email template deleted successfully';
$string['confirmdeletetemplate'] = 'Are you sure you want to delete this email template?';

// Recipient strings.
$string['recipients'] = 'Recipients';
$string['selectrecipients'] = 'Select recipients';
$string['allstudents'] = 'All students';
$string['allteachers'] = 'All teachers';
$string['coursestudents'] = 'Students in specific course';
$string['courseteachers'] = 'Teachers in specific course';
$string['specificusers'] = 'Specific users';
$string['coursecompletions'] = 'Users who completed courses';
$string['neveraccessed'] = 'Users who never accessed courses';
$string['customquery'] = 'Custom query';

// Send email strings.
$string['sendemail'] = 'Send email';
$string['sendondemand'] = 'Send on demand';
$string['emailsent'] = 'Email sent successfully to {$a} recipients';
$string['emailsentpartial'] = '({$a} failed)';
$string['emailsenterror'] = 'Error sending emails';
$string['emailqueued'] = '{$a} emails queued successfully';
$string['selecttemplate'] = 'Select a template';
$string['nosendtimespecified'] = 'No send time specified';
$string['schedulesend'] = 'Schedule send';
$string['sendnow'] = 'Send now';

// Course completion strings.
$string['coursecompletionemail'] = 'Course completion email';
$string['enablecoursecompletion'] = 'Enable course completion emails';
$string['coursecompletiontemplate'] = 'Course completion email template';

// Management strings.
$string['managetemplates'] = 'Manage email templates';
$string['managetasks'] = 'Manage email tasks';

// Placeholders.
$string['availableplaceholders'] = 'Available placeholders';
$string['placeholder_firstname'] = '{firstname} - Recipient\'s first name';
$string['placeholder_lastname'] = '{lastname} - Recipient\'s last name';
$string['placeholder_fullname'] = '{fullname} - Recipient\'s full name';
$string['placeholder_email'] = '{email} - Recipient\'s email';
$string['placeholder_coursename'] = '{coursename} - Course name';
$string['placeholder_coursefullname'] = '{coursefullname} - Course full name';
$string['placeholder_sitename'] = '{sitename} - Site name';

// Settings.
$string['settings'] = 'RVS Task settings';
$string['enablescheduledtask'] = 'Enable scheduled email task';
$string['enablescheduledtask_desc'] = 'Enable or disable the scheduled email task';

// Errors.
$string['errornotemplateselected'] = 'Please select an email template';
$string['errornorecipients'] = 'No recipients found';
$string['errortemplatenotfound'] = 'Email template not found';
$string['errortemplateinuse'] = 'Cannot delete template - it has pending emails in the queue';
$string['errorselectcourse'] = 'Please select a course';

// Additional strings.
$string['notemplates'] = 'No email templates found';
$string['queuestats'] = 'Queue Statistics';
$string['enabled'] = 'Enabled';
$string['specificusers_help'] = 'You can enter either email addresses or user IDs. Examples:<br>
• Email: john.doe@example.com<br>
• User ID: 123<br>
• Multiple: john@example.com, 456, jane@example.com<br>
Separate entries with commas, spaces, or new lines. Invalid or inactive users will be skipped.';
