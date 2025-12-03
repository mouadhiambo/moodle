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
 * Section/Activity Payment Report for RVS Payment availability condition.
 *
 * @package    availability_rvspayment
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/tablelib.php');

$courseid = optional_param('courseid', 0, PARAM_INT);
$status = optional_param('status', '', PARAM_ALPHA);
$download = optional_param('download', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 50, PARAM_INT);

// Check permissions.
if ($courseid) {
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    require_login($course);
    $context = context_course::instance($course->id);
    require_capability('moodle/course:update', $context);
} else {
    require_login();
    $context = context_system::instance();
    require_capability('moodle/site:config', $context);
}

// Set up the page.
$PAGE->set_url(new moodle_url('/availability/condition/rvspayment/report.php', [
    'courseid' => $courseid,
    'status' => $status,
]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('report_title', 'availability_rvspayment'));
$PAGE->set_heading($courseid ? $course->fullname : get_string('report_title', 'availability_rvspayment'));
$PAGE->set_pagelayout('report');

// Build the query.
$params = [];
$where = [];

if ($courseid) {
    $where[] = 'p.courseid = :courseid';
    $params['courseid'] = $courseid;
}

if ($status) {
    $where[] = 'p.status = :status';
    $params['status'] = $status;
}

$wheresql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total records.
$countsql = "SELECT COUNT(*) FROM {availability_rvspayment_pay} p $wheresql";
$totalcount = $DB->count_records_sql($countsql, $params);

// Define table columns.
$columns = [
    'id' => get_string('report_col_id', 'availability_rvspayment'),
    'fullname' => get_string('report_col_user', 'availability_rvspayment'),
    'coursename' => get_string('report_col_course', 'availability_rvspayment'),
    'iteminfo' => get_string('report_col_item', 'availability_rvspayment'),
    'amount' => get_string('report_col_amount', 'availability_rvspayment'),
    'phone' => get_string('report_col_phone', 'availability_rvspayment'),
    'status' => get_string('report_col_status', 'availability_rvspayment'),
    'mpesareceiptnumber' => get_string('report_col_receipt', 'availability_rvspayment'),
    'timecreated' => get_string('report_col_date', 'availability_rvspayment'),
];

// Set up the table.
$table = new flexible_table('availability_rvspayment_report');
$table->define_columns(array_keys($columns));
$table->define_headers(array_values($columns));
$table->define_baseurl($PAGE->url);
$table->sortable(true, 'timecreated', SORT_DESC);
$table->no_sorting('iteminfo');
$table->set_attribute('class', 'generaltable generalbox');
$table->setup();

// Handle download.
if ($download) {
    $table->is_downloading($download, 'rvspayment_report_' . date('Y-m-d'));
}

if (!$table->is_downloading()) {
    echo $OUTPUT->header();
    
    // Page heading and description.
    echo $OUTPUT->heading(get_string('report_title', 'availability_rvspayment'));
    echo html_writer::tag('p', get_string('report_description', 'availability_rvspayment'));
    
    // Filter form.
    echo html_writer::start_tag('form', [
        'method' => 'get',
        'action' => $PAGE->url->out_omit_querystring(),
        'class' => 'mb-4',
    ]);
    
    echo html_writer::start_div('row');
    
    // Course filter (only for site admin).
    if (!$courseid) {
        echo html_writer::start_div('col-md-4 mb-2');
        $courses = $DB->get_records_sql("
            SELECT DISTINCT c.id, c.fullname 
            FROM {course} c 
            JOIN {availability_rvspayment_pay} p ON p.courseid = c.id 
            ORDER BY c.fullname
        ");
        $courseoptions = ['' => get_string('allcourses', 'availability_rvspayment')];
        foreach ($courses as $c) {
            $courseoptions[$c->id] = $c->fullname;
        }
        echo html_writer::select($courseoptions, 'courseid', $courseid, null, ['class' => 'form-control']);
        echo html_writer::end_div();
    } else {
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => $courseid]);
    }
    
    // Status filter.
    echo html_writer::start_div('col-md-3 mb-2');
    $statusoptions = [
        '' => get_string('allstatuses', 'availability_rvspayment'),
        'pending' => get_string('status_pending', 'availability_rvspayment'),
        'completed' => get_string('status_completed', 'availability_rvspayment'),
        'failed' => get_string('status_failed', 'availability_rvspayment'),
    ];
    echo html_writer::select($statusoptions, 'status', $status, null, ['class' => 'form-control']);
    echo html_writer::end_div();
    
    // Submit button.
    echo html_writer::start_div('col-md-2 mb-2');
    echo html_writer::tag('button', get_string('filter', 'availability_rvspayment'), [
        'type' => 'submit',
        'class' => 'btn btn-primary',
    ]);
    echo html_writer::end_div();
    
    echo html_writer::end_div();
    echo html_writer::end_tag('form');
    
    // Summary stats.
    $stats = $DB->get_record_sql("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
            SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as totalamount
        FROM {availability_rvspayment_pay} p
        $wheresql
    ", $params);
    
    echo html_writer::start_div('row mb-4');
    
    echo html_writer::start_div('col-md-3');
    echo html_writer::start_div('card text-center');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', $stats->total ?? 0, ['class' => 'card-title']);
    echo html_writer::tag('p', get_string('stat_total', 'availability_rvspayment'), ['class' => 'card-text']);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
    
    echo html_writer::start_div('col-md-3');
    echo html_writer::start_div('card text-center bg-success text-white');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', $stats->completed ?? 0, ['class' => 'card-title']);
    echo html_writer::tag('p', get_string('stat_completed', 'availability_rvspayment'), ['class' => 'card-text']);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
    
    echo html_writer::start_div('col-md-3');
    echo html_writer::start_div('card text-center bg-warning');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', $stats->pending ?? 0, ['class' => 'card-title']);
    echo html_writer::tag('p', get_string('stat_pending', 'availability_rvspayment'), ['class' => 'card-text']);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
    
    echo html_writer::start_div('col-md-3');
    echo html_writer::start_div('card text-center bg-info text-white');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', 'KES ' . number_format($stats->totalamount ?? 0, 2), ['class' => 'card-title']);
    echo html_writer::tag('p', get_string('stat_revenue', 'availability_rvspayment'), ['class' => 'card-text']);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
    
    echo html_writer::end_div();
    
    // Download buttons.
    echo html_writer::start_div('mb-3');
    $downloadurl = new moodle_url($PAGE->url, ['download' => 'csv']);
    echo html_writer::link($downloadurl, get_string('downloadcsv', 'availability_rvspayment'), ['class' => 'btn btn-secondary mr-2']);
    $downloadurl->param('download', 'excel');
    echo html_writer::link($downloadurl, get_string('downloadexcel', 'availability_rvspayment'), ['class' => 'btn btn-secondary']);
    echo html_writer::end_div();
}

// Get the data with sorting.
$sort = $table->get_sql_sort();
if (!$sort) {
    $sort = 'p.timecreated DESC';
}

$sql = "SELECT p.*, 
               u.firstname, u.lastname, u.email,
               c.fullname as coursename
        FROM {availability_rvspayment_pay} p
        JOIN {user} u ON u.id = p.userid
        JOIN {course} c ON c.id = p.courseid
        $wheresql
        ORDER BY $sort";

$payments = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

foreach ($payments as $payment) {
    $row = [];
    
    // ID.
    $row[] = $payment->id;
    
    // User.
    $userurl = new moodle_url('/user/profile.php', ['id' => $payment->userid]);
    $row[] = html_writer::link($userurl, fullname($payment));
    
    // Course.
    $courseurl = new moodle_url('/course/view.php', ['id' => $payment->courseid]);
    $row[] = html_writer::link($courseurl, $payment->coursename);
    
    // Item info.
    $iteminfo = $payment->itemtype . ' #' . $payment->itemid;
    if ($payment->itemtype === 'section') {
        $section = $DB->get_record('course_sections', ['id' => $payment->itemid]);
        if ($section) {
            $iteminfo = get_string('section') . ' ' . $section->section;
            if (!empty($section->name)) {
                $iteminfo .= ': ' . format_string($section->name);
            }
        }
    } else if ($payment->itemtype === 'module') {
        $cm = get_coursemodule_from_id('', $payment->itemid);
        if ($cm) {
            $iteminfo = format_string($cm->name);
        }
    }
    $row[] = $iteminfo;
    
    // Amount.
    $row[] = $payment->currency . ' ' . number_format($payment->amount, 2);
    
    // Phone.
    $row[] = $payment->phone;
    
    // Status.
    $statusclass = '';
    switch ($payment->status) {
        case 'completed':
            $statusclass = 'badge badge-success bg-success';
            break;
        case 'pending':
            $statusclass = 'badge badge-warning bg-warning';
            break;
        case 'failed':
            $statusclass = 'badge badge-danger bg-danger';
            break;
    }
    $row[] = html_writer::tag('span', get_string('status_' . $payment->status, 'availability_rvspayment'), 
        ['class' => $statusclass]);
    
    // M-Pesa receipt.
    $row[] = $payment->mpesareceiptnumber ?: '-';
    
    // Date.
    $row[] = userdate($payment->timecreated, get_string('strftimedatetime', 'langconfig'));
    
    $table->add_data($row);
}

$table->finish_output();

if (!$table->is_downloading()) {
    // Pagination.
    echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $PAGE->url);
    
    echo $OUTPUT->footer();
}
