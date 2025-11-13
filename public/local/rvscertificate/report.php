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
 * Admin report page
 *
 * @package    local_rvscertificate
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

// This page is defined as 'local_rvscertificate_report' in settings.php
admin_externalpage_setup('local_rvscertificate_report');

$page = optional_param('page', 0, PARAM_INT);
$perpage = 50;

$PAGE->set_title(get_string('pluginname', 'local_rvscertificate') . ' - Report');
$PAGE->set_heading(get_string('pluginname', 'local_rvscertificate') . ' - Report');

echo $OUTPUT->header();
echo $OUTPUT->heading('Certificate Payment Report');

// Create table
$table = new flexible_table('local_rvscertificate_report');
$table->define_columns(['userid', 'fullname', 'email', 'course', 'amount', 'phone', 'status', 'verificationcode', 'mpesareceipt', 'timecreated']);
$table->define_headers([
    'User ID',
    'User',
    'Email',
    'Course',
    'Amount',
    'Phone',
    'Status',
    'Verification Code',
    'M-Pesa Receipt',
    'Date'
]);

$table->define_baseurl($PAGE->url);
$table->sortable(true, 'timecreated', SORT_DESC);
$table->pageable(true);
$table->setup();

// Get sort parameters
$sort = $table->get_sql_sort();
if (empty($sort)) {
    $sort = 'p.timecreated DESC';
}

// Get data
$sql = "SELECT p.*, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, 
               u.middlename, u.alternatename, u.email, c.fullname as coursename
          FROM {local_rvscertificate_payments} p
          JOIN {user} u ON u.id = p.userid
          JOIN {course} c ON c.id = p.courseid
         ORDER BY {$sort}";

$payments = $DB->get_records_sql($sql, [], $page * $perpage, $perpage);
$totalcount = $DB->count_records('local_rvscertificate_payments');

foreach ($payments as $payment) {
    $statusclass = '';
    switch ($payment->status) {
        case 'completed':
            $statusclass = 'badge badge-success';
            break;
        case 'pending':
            $statusclass = 'badge badge-warning';
            break;
        case 'failed':
            $statusclass = 'badge badge-danger';
            break;
        default:
            $statusclass = 'badge badge-secondary';
    }
    
    // Create user profile link
    $userlink = html_writer::link(
        new moodle_url('/user/profile.php', ['id' => $payment->userid]),
        $payment->userid,
        ['target' => '_blank']
    );
    
    $table->add_data([
        $userlink,
        fullname($payment),
        html_writer::link('mailto:' . $payment->email, $payment->email),
        format_string($payment->coursename),
        'KES ' . number_format($payment->amount, 2),
        $payment->phone,
        html_writer::tag('span', $payment->status, ['class' => $statusclass]),
        $payment->verificationcode ?? '-',
        $payment->mpesareceiptnumber ?? '-',
        userdate($payment->timecreated)
    ]);
}

$table->finish_output();

// Show summary statistics
echo html_writer::start_tag('div', ['class' => 'row mt-4']);

// Total payments
$completedpayments = $DB->count_records('local_rvscertificate_payments', ['status' => 'completed']);
$totalsql = "SELECT SUM(amount) as total FROM {local_rvscertificate_payments} WHERE status = 'completed'";
$totalamount = $DB->get_field_sql($totalsql);
// Ensure $totalamount is not null to avoid deprecated warning in PHP 8.1+
$totalamount = $totalamount ?? 0;

echo html_writer::start_tag('div', ['class' => 'col-md-3']);
echo html_writer::start_tag('div', ['class' => 'card']);
echo html_writer::start_tag('div', ['class' => 'card-body']);
echo html_writer::tag('h5', 'Total Certificates', ['class' => 'card-title']);
echo html_writer::tag('h2', $completedpayments, ['class' => 'text-primary']);
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', ['class' => 'col-md-3']);
echo html_writer::start_tag('div', ['class' => 'card']);
echo html_writer::start_tag('div', ['class' => 'card-body']);
echo html_writer::tag('h5', 'Total Revenue', ['class' => 'card-title']);
echo html_writer::tag('h2', 'KES ' . number_format($totalamount, 2), ['class' => 'text-success']);
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

$pendingpayments = $DB->count_records('local_rvscertificate_payments', ['status' => 'pending']);
echo html_writer::start_tag('div', ['class' => 'col-md-3']);
echo html_writer::start_tag('div', ['class' => 'card']);
echo html_writer::start_tag('div', ['class' => 'card-body']);
echo html_writer::tag('h5', 'Pending Payments', ['class' => 'card-title']);
echo html_writer::tag('h2', $pendingpayments, ['class' => 'text-warning']);
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

$failedpayments = $DB->count_records('local_rvscertificate_payments', ['status' => 'failed']);
echo html_writer::start_tag('div', ['class' => 'col-md-3']);
echo html_writer::start_tag('div', ['class' => 'card']);
echo html_writer::start_tag('div', ['class' => 'card-body']);
echo html_writer::tag('h5', 'Failed Payments', ['class' => 'card-title']);
echo html_writer::tag('h2', $failedpayments, ['class' => 'text-danger']);
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

echo html_writer::end_tag('div');

echo $OUTPUT->footer();
