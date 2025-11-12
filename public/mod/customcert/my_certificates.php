<?php
// This file is part of the customcert module for Moodle - http://moodle.org/
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
 * Handles viewing the certificates for a certain user.
 *
 * @package    mod_customcert
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$userid = optional_param('userid', $USER->id, PARAM_INT);
$download = optional_param('download', null, PARAM_ALPHA);
$courseid = optional_param('course', null, PARAM_INT);
$downloadcert = optional_param('downloadcert', '', PARAM_BOOL);
if ($downloadcert) {
    $certificateid = required_param('certificateid', PARAM_INT);
    $customcert = $DB->get_record('customcert', ['id' => $certificateid], '*', MUST_EXIST);

    // Check there exists an issued certificate for this user.
    if (!$issue = $DB->get_record('customcert_issues', ['userid' => $userid, 'customcertid' => $customcert->id])) {
        throw new moodle_exception('You have not been issued a certificate');
    }
}
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', \mod_customcert\certificate::CUSTOMCERT_PER_PAGE, PARAM_INT);
$pageurl = $url = new moodle_url('/mod/customcert/my_certificates.php', ['userid' => $userid,
    'page' => $page, 'perpage' => $perpage]);

// Requires a login.
if ($courseid) {
    require_login($courseid);
} else {
    require_login();
}

// Check that we have a valid user.
$user = \core_user::get_user($userid, '*', MUST_EXIST);

// If we are viewing certificates that are not for the currently logged in user then do a capability check.
if (($userid != $USER->id) && !has_capability('mod/customcert:viewallcertificates', context_system::instance())) {
    throw new moodle_exception('You are not allowed to view these certificates');
}

$PAGE->set_url($pageurl);
$PAGE->set_context(context_user::instance($userid));
$PAGE->set_title(get_string('mycertificates', 'customcert'));
$PAGE->set_pagelayout('standard');
$PAGE->navigation->extend_for_user($user);

// Check if we requested to download a certificate.
if ($downloadcert) {
    // Check for payment requirement via rvscertificate plugin (before PDF generation)
    // Only check for students downloading their own certificate, not for admins viewing others
    if ($userid == $USER->id) {
        // Get course ID from the customcert instance
        $cm = $DB->get_record_sql(
            "SELECT cm.course 
             FROM {course_modules} cm
             JOIN {modules} m ON m.id = cm.module
             WHERE cm.instance = :instanceid AND m.name = 'customcert'
             LIMIT 1",
            ['instanceid' => $customcert->id]
        );
        
        if ($cm) {
            $certcourseid = $cm->course;
            
            // Check if rvscertificate plugin is available
            $rvscertificate_available = file_exists($CFG->dirroot . '/local/rvscertificate/lib.php');
            if ($rvscertificate_available) {
                require_once($CFG->dirroot . '/local/rvscertificate/lib.php');
                
                // Get course context
                $coursecontext = context_course::instance($certcourseid);
                
                // Allow teachers and admins to access without payment
                $is_teacher_or_admin = has_capability('moodle/course:update', $coursecontext) || 
                                        has_capability('local/rvscertificate:manage', $coursecontext) ||
                                        is_siteadmin();
                
                if (!$is_teacher_or_admin) {
                    // Check if user has completed the course
                    if (function_exists('local_rvscertificate_is_course_completed')) {
                        $course_completed = local_rvscertificate_is_course_completed($USER->id, $certcourseid);
                        
                        if ($course_completed) {
                            // Check if user has already paid
                            if (function_exists('local_rvscertificate_has_paid')) {
                                $has_paid = local_rvscertificate_has_paid($USER->id, $certcourseid);
                                
                                if (!$has_paid) {
                                    // User hasn't paid - redirect to payment page
                                    $redirecturl = new moodle_url('/local/rvscertificate/index.php', ['courseid' => $certcourseid]);
                                    redirect(
                                        $redirecturl,
                                        get_string('paymentrequired', 'local_rvscertificate'),
                                        null,
                                        \core\output\notification::NOTIFY_WARNING
                                    );
                                    exit();
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    $template = $DB->get_record('customcert_templates', ['id' => $customcert->templateid], '*', MUST_EXIST);
    $template = new \mod_customcert\template($template);
    $template->generate_pdf(false, $userid);
    exit();
}

$table = new \mod_customcert\my_certificates_table($userid, $download);
$table->define_baseurl($pageurl);

if ($table->is_downloading()) {
    $table->download();
    exit();
}

// Additional page setup.
$PAGE->navbar->add(get_string('profile'), new moodle_url('/user/profile.php', ['id' => $userid]));
$PAGE->navbar->add(get_string('mycertificates', 'customcert'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('mycertificates', 'customcert'));
echo html_writer::div(get_string('mycertificatesdescription', 'customcert'));
$table->out($perpage, false);
echo $OUTPUT->footer();
