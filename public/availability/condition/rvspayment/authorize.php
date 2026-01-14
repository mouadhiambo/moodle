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
 * Manual authorization page for RVS Payment availability condition.
 *
 * This page allows teachers/admins to manually authorize enrolled students
 * to access locked sections or activities without payment.
 *
 * @package    availability_rvspayment
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/tablelib.php');

$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$userid = optional_param('userid', 0, PARAM_INT);
$itemtype = optional_param('itemtype', '', PARAM_ALPHA);
$itemid = optional_param('itemid', 0, PARAM_INT);
$overrideid = optional_param('overrideid', 0, PARAM_INT);

// Get the course.
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

// Require login and course access.
require_login($course);
$context = context_course::instance($course->id);

// Require capability to manage course (teacher or admin).
require_capability('moodle/course:update', $context);

// Set up the page.
$PAGE->set_url(new moodle_url('/availability/condition/rvspayment/authorize.php', [
    'courseid' => $courseid,
]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('authorize_title', 'availability_rvspayment'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

// Get course modinfo for section/module names.
$modinfo = get_fast_modinfo($course);

// Handle actions.
if ($action && confirm_sesskey()) {
    switch ($action) {
        case 'add':
            // Add a new authorization.
            if ($userid && $itemtype) {
                $reason = optional_param('reason', '', PARAM_TEXT);
                $expiry = optional_param('expiry', '', PARAM_TEXT);
                $allsections = optional_param('allsections', 0, PARAM_INT);

                // Validate user is enrolled.
                if (!is_enrolled($context, $userid)) {
                    redirect($PAGE->url, get_string('authorize_error_notenrolled', 'availability_rvspayment'), 
                        null, \core\output\notification::NOTIFY_ERROR);
                }

                $timeexpires = null;
                if (!empty($expiry)) {
                    $timeexpires = strtotime($expiry);
                    if ($timeexpires === false || $timeexpires < time()) {
                        $timeexpires = null;
                    }
                }

                if ($allsections) {
                    // Authorize all sections in the course.
                    $result = \availability_rvspayment\helper::authorize_all_sections($userid, $courseid, $reason, $USER->id, $timeexpires);
                    if ($result) {
                        redirect($PAGE->url, get_string('authorize_success_all', 'availability_rvspayment'), 
                            null, \core\output\notification::NOTIFY_SUCCESS);
                    }
                } else {
                    // Authorize specific item.
                    $result = \availability_rvspayment\helper::add_authorization($userid, $courseid, $itemtype, $itemid, $reason, $USER->id, $timeexpires);
                    if ($result) {
                        redirect($PAGE->url, get_string('authorize_success', 'availability_rvspayment'), 
                            null, \core\output\notification::NOTIFY_SUCCESS);
                    } else {
                        redirect($PAGE->url, get_string('authorize_error_exists', 'availability_rvspayment'), 
                            null, \core\output\notification::NOTIFY_WARNING);
                    }
                }
            }
            break;

        case 'delete':
            // Remove an authorization.
            if ($overrideid) {
                $override = $DB->get_record('availability_rvspayment_override', ['id' => $overrideid, 'courseid' => $courseid]);
                if ($override) {
                    $DB->delete_records('availability_rvspayment_override', ['id' => $overrideid]);
                    redirect($PAGE->url, get_string('authorize_removed', 'availability_rvspayment'), 
                        null, \core\output\notification::NOTIFY_SUCCESS);
                }
            }
            break;

        case 'deleteuser':
            // Remove all authorizations for a user in this course.
            if ($userid) {
                $DB->delete_records('availability_rvspayment_override', ['userid' => $userid, 'courseid' => $courseid]);
                redirect($PAGE->url, get_string('authorize_removed_all', 'availability_rvspayment'), 
                    null, \core\output\notification::NOTIFY_SUCCESS);
            }
            break;
    }
}

// Start output.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('authorize_title', 'availability_rvspayment'));
echo html_writer::tag('p', get_string('authorize_description', 'availability_rvspayment'));

// Get enrolled users.
$enrolledusers = get_enrolled_users($context, '', 0, 'u.id, u.firstname, u.lastname, u.email', 'u.lastname, u.firstname');

// Get sections and modules that have payment restrictions.
$paidsections = \availability_rvspayment\helper::get_paid_sections($courseid);
$paidmodules = \availability_rvspayment\helper::get_paid_modules($courseid);

// Build options for sections and modules.
$itemoptions = [];
$itemoptions[''] = get_string('authorize_select_item', 'availability_rvspayment');

if (!empty($paidsections)) {
    $sectiongroup = [];
    foreach ($paidsections as $section) {
        if (empty($section->priceinfo) || !is_array($section->priceinfo)) {
            continue;
        }
        $sectionname = !empty($section->name) ? format_string($section->name) : get_string('section') . ' ' . $section->section;
        $price = number_format((float)$section->priceinfo['price'], 2);
        $currency = $section->priceinfo['currency'] ?? 'KES';
        $sectiongroup["section-{$section->id}"] = $sectionname . " ({$currency} {$price})";
    }
    if (!empty($sectiongroup)) {
        $itemoptions[get_string('sections', 'availability_rvspayment')] = $sectiongroup;
    }
}

if (!empty($paidmodules)) {
    $modulegroup = [];
    foreach ($paidmodules as $module) {
        if (empty($module->priceinfo) || !is_array($module->priceinfo)) {
            continue;
        }
        $price = number_format((float)$module->priceinfo['price'], 2);
        $currency = $module->priceinfo['currency'] ?? 'KES';
        $modulegroup["module-{$module->id}"] = format_string($module->name) . " ({$currency} {$price})";
    }
    if (!empty($modulegroup)) {
        $itemoptions[get_string('activities', 'availability_rvspayment')] = $modulegroup;
    }
}

// User options.
$useroptions = ['' => get_string('authorize_select_user', 'availability_rvspayment')];
foreach ($enrolledusers as $user) {
    $useroptions[$user->id] = fullname($user) . ' (' . $user->email . ')';
}

// Authorization form.
echo html_writer::start_tag('div', ['class' => 'card mb-4']);
echo html_writer::start_tag('div', ['class' => 'card-header']);
echo html_writer::tag('h4', get_string('authorize_add', 'availability_rvspayment'), ['class' => 'mb-0']);
echo html_writer::end_tag('div');
echo html_writer::start_tag('div', ['class' => 'card-body']);

echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => $PAGE->url->out(false),
    'class' => 'form-inline',
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'add']);

echo html_writer::start_tag('div', ['class' => 'row']);

// User selector.
echo html_writer::start_tag('div', ['class' => 'col-md-3 mb-2']);
echo html_writer::tag('label', get_string('authorize_user', 'availability_rvspayment'), ['for' => 'userid', 'class' => 'sr-only']);
echo html_writer::select($useroptions, 'userid', '', null, ['class' => 'form-control w-100', 'id' => 'userid', 'required' => 'required']);
echo html_writer::end_tag('div');

// Item selector.
echo html_writer::start_tag('div', ['class' => 'col-md-3 mb-2']);
echo html_writer::tag('label', get_string('authorize_item', 'availability_rvspayment'), ['for' => 'item', 'class' => 'sr-only']);
echo html_writer::select($itemoptions, 'item', '', null, ['class' => 'form-control w-100', 'id' => 'item']);
echo html_writer::end_tag('div');

// Reason field.
echo html_writer::start_tag('div', ['class' => 'col-md-2 mb-2']);
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'reason',
    'class' => 'form-control w-100',
    'placeholder' => get_string('authorize_reason', 'availability_rvspayment'),
]);
echo html_writer::end_tag('div');

// Expiry date field.
echo html_writer::start_tag('div', ['class' => 'col-md-2 mb-2']);
echo html_writer::empty_tag('input', [
    'type' => 'date',
    'name' => 'expiry',
    'class' => 'form-control w-100',
    'placeholder' => get_string('authorize_expiry', 'availability_rvspayment'),
    'title' => get_string('authorize_expiry_help', 'availability_rvspayment'),
]);
echo html_writer::end_tag('div');

// Submit button.
echo html_writer::start_tag('div', ['class' => 'col-md-2 mb-2']);
echo html_writer::tag('button', get_string('authorize_add_btn', 'availability_rvspayment'), [
    'type' => 'submit',
    'class' => 'btn btn-primary w-100',
]);
echo html_writer::end_tag('div');

echo html_writer::end_tag('div');

// All sections checkbox.
echo html_writer::start_tag('div', ['class' => 'row mt-2']);
echo html_writer::start_tag('div', ['class' => 'col-md-12']);
echo html_writer::start_tag('div', ['class' => 'form-check']);
echo html_writer::empty_tag('input', [
    'type' => 'checkbox',
    'name' => 'allsections',
    'value' => '1',
    'class' => 'form-check-input',
    'id' => 'allsections',
]);
echo html_writer::tag('label', get_string('authorize_all_sections', 'availability_rvspayment'), 
    ['class' => 'form-check-label', 'for' => 'allsections']);
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

echo html_writer::end_tag('form');
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// JavaScript to parse item selection.
$PAGE->requires->js_amd_inline("
    require(['jquery'], function($) {
        $('#item').on('change', function() {
            var val = $(this).val();
            var parts = val.split('-');
            if (parts.length === 2) {
                $('input[name=\"itemtype\"]').remove();
                $('input[name=\"itemid\"]').remove();
                $(this).closest('form').append('<input type=\"hidden\" name=\"itemtype\" value=\"' + parts[0] + '\">');
                $(this).closest('form').append('<input type=\"hidden\" name=\"itemid\" value=\"' + parts[1] + '\">');
            }
        });
        
        $('#allsections').on('change', function() {
            if ($(this).is(':checked')) {
                $('#item').prop('disabled', true).val('');
            } else {
                $('#item').prop('disabled', false);
            }
        });
    });
");

// Current authorizations table.
echo html_writer::tag('h4', get_string('authorize_current', 'availability_rvspayment'), ['class' => 'mt-4 mb-3']);

// Get current authorizations for this course.
$sql = "SELECT o.*, u.firstname, u.lastname, u.email, a.firstname AS authfirst, a.lastname AS authlast
        FROM {availability_rvspayment_override} o
        JOIN {user} u ON u.id = o.userid
        JOIN {user} a ON a.id = o.authorizedby
        WHERE o.courseid = :courseid
        ORDER BY u.lastname, u.firstname, o.timecreated DESC";
$overrides = $DB->get_records_sql($sql, ['courseid' => $courseid]);

if (empty($overrides)) {
    echo $OUTPUT->notification(get_string('authorize_none', 'availability_rvspayment'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('authorize_col_user', 'availability_rvspayment'),
        get_string('authorize_col_item', 'availability_rvspayment'),
        get_string('authorize_col_reason', 'availability_rvspayment'),
        get_string('authorize_col_authorizedby', 'availability_rvspayment'),
        get_string('authorize_col_date', 'availability_rvspayment'),
        get_string('authorize_col_expires', 'availability_rvspayment'),
        get_string('authorize_col_actions', 'availability_rvspayment'),
    ];
    $table->attributes['class'] = 'table table-striped';
    $table->data = [];

    foreach ($overrides as $override) {
        // Get item name.
        $itemname = '';
        if ($override->itemtype === 'course') {
            $itemname = html_writer::tag('em', get_string('authorize_all_course', 'availability_rvspayment'));
        } else if ($override->itemtype === 'section') {
            $section = $DB->get_record('course_sections', ['id' => $override->itemid]);
            if ($section) {
                $itemname = !empty($section->name) ? format_string($section->name) : get_string('section') . ' ' . $section->section;
            } else {
                $itemname = get_string('section') . ' (ID: ' . $override->itemid . ')';
            }
        } else if ($override->itemtype === 'module') {
            if (isset($modinfo->cms[$override->itemid])) {
                $itemname = format_string($modinfo->cms[$override->itemid]->name);
            } else {
                $itemname = get_string('activity') . ' (ID: ' . $override->itemid . ')';
            }
        }

        // Check if expired.
        $expired = false;
        $expirytext = get_string('authorize_never', 'availability_rvspayment');
        if ($override->timeexpires) {
            $expirytext = userdate($override->timeexpires, get_string('strftimedatetime', 'langconfig'));
            if ($override->timeexpires < time()) {
                $expired = true;
                $expirytext = html_writer::tag('span', $expirytext . ' ' . get_string('authorize_expired', 'availability_rvspayment'), 
                    ['class' => 'text-danger']);
            }
        }

        // Delete link.
        $deleteurl = new moodle_url($PAGE->url, [
            'action' => 'delete',
            'overrideid' => $override->id,
            'sesskey' => sesskey(),
        ]);
        $deletelink = html_writer::link($deleteurl, get_string('delete'), [
            'class' => 'btn btn-sm btn-danger',
            'onclick' => 'return confirm("' . get_string('authorize_confirm_delete', 'availability_rvspayment') . '");',
        ]);

        $row = [
            fullname($override) . html_writer::tag('small', ' (' . $override->email . ')', ['class' => 'text-muted']),
            $itemname,
            format_text($override->reason, FORMAT_PLAIN),
            $override->authfirst . ' ' . $override->authlast,
            userdate($override->timecreated, get_string('strftimedatetime', 'langconfig')),
            $expirytext,
            $deletelink,
        ];

        $table->data[] = $row;
    }

    echo html_writer::table($table);
}

// Back to course link.
echo html_writer::start_tag('div', ['class' => 'mt-4']);
echo html_writer::link(
    new moodle_url('/course/view.php', ['id' => $courseid]),
    get_string('backtocourse', 'availability_rvspayment'),
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_tag('div');

echo $OUTPUT->footer();
