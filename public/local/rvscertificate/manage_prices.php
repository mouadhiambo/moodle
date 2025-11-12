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
 * Course prices management page
 *
 * @package    local_rvscertificate
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_url('/local/rvscertificate/manage_prices.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('courseprices', 'local_rvscertificate'));
$PAGE->set_heading(get_string('courseprices', 'local_rvscertificate'));
$PAGE->set_pagelayout('admin');

// Handle form submissions
$action = optional_param('action', '', PARAM_ALPHA);
$courseid = optional_param('courseid', 0, PARAM_INT);
$priceid = optional_param('priceid', 0, PARAM_INT);

if ($action === 'save' && $courseid && confirm_sesskey()) {
    $price = optional_param('price', 0, PARAM_FLOAT);
    $enabled = optional_param('enabled', 0, PARAM_INT);
    
    // Validate price
    if ($price < 0) {
        $price = 0;
    }
    
    // Check if record exists
    $existing = $DB->get_record('local_rvscertificate_course_prices', ['courseid' => $courseid]);
    
    if ($existing) {
        // Update existing record
        $existing->price = $price;
        $existing->enabled = $enabled;
        $existing->timemodified = time();
        $DB->update_record('local_rvscertificate_course_prices', $existing);
        $message = get_string('priceupdated', 'local_rvscertificate');
    } else {
        // Create new record
        $record = new stdClass();
        $record->courseid = $courseid;
        $record->price = $price;
        $record->enabled = $enabled;
        $record->timecreated = time();
        $record->timemodified = time();
        $DB->insert_record('local_rvscertificate_course_prices', $record);
        $message = get_string('priceadded', 'local_rvscertificate');
    }
    
    redirect(new moodle_url('/local/rvscertificate/manage_prices.php'), $message, null, \core\output\notification::NOTIFY_SUCCESS);
}

if ($action === 'delete' && $priceid && confirm_sesskey()) {
    $DB->delete_records('local_rvscertificate_course_prices', ['id' => $priceid]);
    redirect(new moodle_url('/local/rvscertificate/manage_prices.php'), get_string('pricedeleted', 'local_rvscertificate'), null, \core\output\notification::NOTIFY_SUCCESS);
}

if ($action === 'toggle' && $priceid && confirm_sesskey()) {
    $record = $DB->get_record('local_rvscertificate_course_prices', ['id' => $priceid], '*', MUST_EXIST);
    $record->enabled = $record->enabled ? 0 : 1;
    $record->timemodified = time();
    $DB->update_record('local_rvscertificate_course_prices', $record);
    redirect(new moodle_url('/local/rvscertificate/manage_prices.php'));
}

// Get all courses
$courses = $DB->get_records('course', ['id' => SITEID], '', 'id, shortname, fullname');
$allcourses = $DB->get_records('course', null, 'fullname ASC', 'id, shortname, fullname');

// Get all course prices
$courseprices = $DB->get_records('local_rvscertificate_course_prices', null, 'timemodified DESC');

// Create a map of courseid => price record
$pricemap = [];
foreach ($courseprices as $cp) {
    $pricemap[$cp->courseid] = $cp;
}

echo $OUTPUT->header();

// Add course form
echo html_writer::start_tag('div', ['class' => 'card mb-3']);
echo html_writer::start_tag('div', ['class' => 'card-body']);
echo html_writer::tag('h4', get_string('addcourseprice', 'local_rvscertificate'), ['class' => 'card-title']);

echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => new moodle_url('/local/rvscertificate/manage_prices.php'),
    'class' => 'form-inline'
]);

echo html_writer::input_hidden_params(new moodle_url('', ['action' => 'save']));
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

// Course selector
echo html_writer::start_tag('div', ['class' => 'form-group mr-2']);
echo html_writer::label(get_string('course'), 'courseid', true, ['class' => 'mr-2']);
$courseoptions = [0 => get_string('selectcourse', 'local_rvscertificate')];
foreach ($allcourses as $course) {
    if ($course->id == SITEID) {
        continue; // Skip site course
    }
    // Skip courses that already have prices set
    if (!isset($pricemap[$course->id])) {
        $courseoptions[$course->id] = $course->fullname . ' (' . $course->shortname . ')';
    }
}
echo html_writer::select($courseoptions, 'courseid', $courseid, false, ['class' => 'form-control', 'required' => 'required']);
echo html_writer::end_tag('div');

// Price input
echo html_writer::start_tag('div', ['class' => 'form-group mr-2']);
echo html_writer::label(get_string('price', 'local_rvscertificate'), 'price', true, ['class' => 'mr-2']);
echo html_writer::empty_tag('input', [
    'type' => 'number',
    'class' => 'form-control',
    'id' => 'price',
    'name' => 'price',
    'step' => '0.01',
    'min' => '0',
    'required' => 'required',
    'placeholder' => '0.00'
]);
echo html_writer::end_tag('div');

// Enabled checkbox
echo html_writer::start_tag('div', ['class' => 'form-group mr-2']);
echo html_writer::start_tag('div', ['class' => 'form-check']);
echo html_writer::empty_tag('input', [
    'type' => 'checkbox',
    'class' => 'form-check-input',
    'id' => 'enabled',
    'name' => 'enabled',
    'value' => '1',
    'checked' => 'checked'
]);
echo html_writer::label(get_string('enabled', 'local_rvscertificate'), 'enabled', false, ['class' => 'form-check-label']);
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// Submit button
echo html_writer::start_tag('div', ['class' => 'form-group']);
echo html_writer::tag('button', get_string('add', 'local_rvscertificate'), [
    'type' => 'submit',
    'class' => 'btn btn-primary'
]);
echo html_writer::end_tag('div');

echo html_writer::end_tag('form');
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// List of existing course prices
echo html_writer::start_tag('div', ['class' => 'card']);
echo html_writer::start_tag('div', ['class' => 'card-body']);
echo html_writer::tag('h4', get_string('coursepricelist', 'local_rvscertificate'), ['class' => 'card-title']);

if (empty($courseprices)) {
    echo html_writer::div(get_string('nocourseprices', 'local_rvscertificate'), 'alert alert-info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('course'),
        get_string('price', 'local_rvscertificate'),
        get_string('status'),
        get_string('actions')
    ];
    $table->attributes['class'] = 'generaltable';
    
    foreach ($courseprices as $cp) {
        $course = $DB->get_record('course', ['id' => $cp->courseid], 'id, shortname, fullname');
        if (!$course) {
            continue;
        }
        
        $row = [];
        
        // Course name
        $courselink = html_writer::link(
            new moodle_url('/course/view.php', ['id' => $course->id]),
            $course->fullname . ' (' . $course->shortname . ')'
        );
        $row[] = $courselink;
        
        // Price
        $row[] = 'KES ' . number_format($cp->price, 2);
        
        // Status
        if ($cp->enabled) {
            $status = html_writer::span(get_string('enabled', 'local_rvscertificate'), 'badge badge-success');
        } else {
            $status = html_writer::span(get_string('disabled', 'local_rvscertificate'), 'badge badge-secondary');
        }
        $row[] = $status;
        
        // Actions
        $actions = [];
        
        // Edit button
        $editurl = new moodle_url('/local/rvscertificate/manage_prices.php', [
            'action' => 'edit',
            'priceid' => $cp->id
        ]);
        $actions[] = html_writer::link($editurl, get_string('edit'), ['class' => 'btn btn-sm btn-secondary']);
        
        // Toggle enable/disable
        $toggleurl = new moodle_url('/local/rvscertificate/manage_prices.php', [
            'action' => 'toggle',
            'priceid' => $cp->id,
            'sesskey' => sesskey()
        ]);
        $toggletext = $cp->enabled ? get_string('disable') : get_string('enable');
        $actions[] = html_writer::link($toggleurl, $toggletext, ['class' => 'btn btn-sm btn-warning']);
        
        // Delete button
        $deleteurl = new moodle_url('/local/rvscertificate/manage_prices.php', [
            'action' => 'delete',
            'priceid' => $cp->id,
            'sesskey' => sesskey()
        ]);
        $actions[] = html_writer::link($deleteurl, get_string('delete'), [
            'class' => 'btn btn-sm btn-danger',
            'onclick' => "return confirm('" . get_string('confirmdelete', 'local_rvscertificate') . "');"
        ]);
        
        $row[] = implode(' ', $actions);
        
        $table->data[] = $row;
    }
    
    echo html_writer::table($table);
}

echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// Edit form (if editing)
if ($action === 'edit' && $priceid) {
    $editrecord = $DB->get_record('local_rvscertificate_course_prices', ['id' => $priceid], '*', MUST_EXIST);
    $editcourse = $DB->get_record('course', ['id' => $editrecord->courseid], 'id, shortname, fullname', MUST_EXIST);
    
    echo html_writer::start_tag('div', ['class' => 'card mt-3']);
    echo html_writer::start_tag('div', ['class' => 'card-body']);
    echo html_writer::tag('h4', get_string('editcourseprice', 'local_rvscertificate'), ['class' => 'card-title']);
    
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => new moodle_url('/local/rvscertificate/manage_prices.php')
    ]);
    
    echo html_writer::input_hidden_params(new moodle_url('', ['action' => 'save', 'courseid' => $editrecord->courseid]));
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    
    echo html_writer::start_tag('div', ['class' => 'form-group']);
    echo html_writer::label(get_string('course'), 'edit_course', false);
    echo html_writer::div($editcourse->fullname . ' (' . $editcourse->shortname . ')', 'form-control-plaintext');
    echo html_writer::end_tag('div');
    
    echo html_writer::start_tag('div', ['class' => 'form-group']);
    echo html_writer::label(get_string('price', 'local_rvscertificate'), 'edit_price', true);
    echo html_writer::empty_tag('input', [
        'type' => 'number',
        'class' => 'form-control',
        'id' => 'edit_price',
        'name' => 'price',
        'step' => '0.01',
        'min' => '0',
        'value' => $editrecord->price,
        'required' => 'required'
    ]);
    echo html_writer::end_tag('div');
    
    echo html_writer::start_tag('div', ['class' => 'form-group']);
    echo html_writer::start_tag('div', ['class' => 'form-check']);
    echo html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'class' => 'form-check-input',
        'id' => 'edit_enabled',
        'name' => 'enabled',
        'value' => '1',
        'checked' => $editrecord->enabled ? 'checked' : ''
    ]);
    echo html_writer::label(get_string('enabled', 'local_rvscertificate'), 'edit_enabled', false, ['class' => 'form-check-label']);
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
    
    echo html_writer::start_tag('div', ['class' => 'form-group']);
    echo html_writer::tag('button', get_string('savechanges'), [
        'type' => 'submit',
        'class' => 'btn btn-primary'
    ]);
    echo html_writer::link(
        new moodle_url('/local/rvscertificate/manage_prices.php'),
        get_string('cancel'),
        ['class' => 'btn btn-secondary ml-2']
    );
    echo html_writer::end_tag('div');
    
    echo html_writer::end_tag('form');
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
}

echo $OUTPUT->footer();

