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
 * M-Pesa API Logs Viewer
 *
 * @package    local_rvscertificate
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

// This page is defined as 'local_rvscertificate_logs' in settings.php under the category
// So we need to use that exact identifier
admin_externalpage_setup('local_rvscertificate_logs');

$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 50, PARAM_INT);
$logtype = optional_param('type', '', PARAM_ALPHA);
$logid = optional_param('logid', 0, PARAM_INT);

$PAGE->set_title(get_string('pluginname', 'local_rvscertificate') . ' - M-Pesa API Logs');
$PAGE->set_heading(get_string('pluginname', 'local_rvscertificate') . ' - M-Pesa API Logs');

echo $OUTPUT->header();
echo $OUTPUT->heading('M-Pesa API Transaction Logs');

// Show detailed log view if logid is specified
if ($logid > 0) {
    $log = $DB->get_record('local_rvscertificate_logs', ['id' => $logid], '*', MUST_EXIST);
    
    echo html_writer::start_tag('div', ['class' => 'card mb-3']);
    echo html_writer::start_tag('div', ['class' => 'card-body']);
    echo html_writer::tag('h4', 'Log Details', ['class' => 'card-title']);
    
    echo html_writer::start_tag('dl', ['class' => 'row']);
    
    echo html_writer::tag('dt', 'Log ID', ['class' => 'col-sm-2']);
    echo html_writer::tag('dd', $log->id, ['class' => 'col-sm-10']);
    
    echo html_writer::tag('dt', 'Type', ['class' => 'col-sm-2']);
    echo html_writer::tag('dd', html_writer::tag('span', strtoupper($log->type), ['class' => 'badge badge-primary']), ['class' => 'col-sm-10']);
    
    echo html_writer::tag('dt', 'Result Code', ['class' => 'col-sm-2']);
    echo html_writer::tag('dd', $log->resultcode ?? 'N/A', ['class' => 'col-sm-10']);
    
    echo html_writer::tag('dt', 'Result Description', ['class' => 'col-sm-2']);
    echo html_writer::tag('dd', $log->resultdesc ?? 'N/A', ['class' => 'col-sm-10']);
    
    echo html_writer::tag('dt', 'Date/Time', ['class' => 'col-sm-2']);
    echo html_writer::tag('dd', userdate($log->timecreated, '%Y-%m-%d %H:%M:%S'), ['class' => 'col-sm-10']);
    
    if ($log->paymentid) {
        echo html_writer::tag('dt', 'Payment ID', ['class' => 'col-sm-2']);
        $paymentlink = html_writer::link(
            new moodle_url('/local/rvscertificate/report.php'),
            $log->paymentid,
            ['target' => '_blank']
        );
        echo html_writer::tag('dd', $paymentlink, ['class' => 'col-sm-10']);
    }
    
    echo html_writer::end_tag('dl');
    
    // Request data
    if ($log->request) {
        echo html_writer::tag('h5', 'Request Data', ['class' => 'mt-3']);
        echo html_writer::tag('pre', htmlspecialchars(json_encode(json_decode($log->request), JSON_PRETTY_PRINT)), ['class' => 'bg-light p-3 border']);
    }
    
    // Response data
    if ($log->response) {
        echo html_writer::tag('h5', 'Response Data', ['class' => 'mt-3']);
        echo html_writer::tag('pre', htmlspecialchars(json_encode(json_decode($log->response), JSON_PRETTY_PRINT)), ['class' => 'bg-light p-3 border']);
    }
    
    echo html_writer::link(
        new moodle_url('/local/rvscertificate/logs.php'),
        'Back to Logs List',
        ['class' => 'btn btn-secondary mt-3']
    );
    
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
    
} else {
    // Show filter form
    echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'form-inline mb-3']);
    
    echo html_writer::label('Filter by Type:', 'type', true, ['class' => 'mr-2']);
    $typeoptions = [
        '' => 'All Types',
        'stkpush' => 'STK Push',
        'callback' => 'Callback',
        'query' => 'Query',
        'error' => 'Error'
    ];
    echo html_writer::select($typeoptions, 'type', $logtype, false, ['class' => 'form-control mr-2']);
    
    echo html_writer::tag('button', 'Filter', ['type' => 'submit', 'class' => 'btn btn-primary']);
    
    if ($logtype) {
        echo html_writer::link(
            new moodle_url('/local/rvscertificate/logs.php'),
            'Clear Filter',
            ['class' => 'btn btn-secondary ml-2']
        );
    }
    
    echo html_writer::end_tag('form');
    
    // Create table
    $table = new flexible_table('local_rvscertificate_logs');
    $table->define_columns(['id', 'type', 'resultcode', 'resultdesc', 'timecreated', 'actions']);
    $table->define_headers([
        'ID',
        'Type',
        'Result Code',
        'Result Description',
        'Date/Time',
        'Actions'
    ]);
    
    $table->define_baseurl(new moodle_url('/local/rvscertificate/logs.php', ['type' => $logtype]));
    $table->sortable(true, 'timecreated', SORT_DESC);
    $table->pageable(true);
    $table->setup();
    
    // Get sort parameters
    $sort = $table->get_sql_sort();
    if (empty($sort)) {
        $sort = 'timecreated DESC';
    }
    
    // Build query
    $where = '1=1';
    $params = [];
    if ($logtype) {
        $where .= ' AND type = :type';
        $params['type'] = $logtype;
    }
    
    // Get data
    $sql = "SELECT *
              FROM {local_rvscertificate_logs}
             WHERE {$where}
          ORDER BY {$sort}";
    
    $logs = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);
    $totalcount = $DB->count_records_select('local_rvscertificate_logs', $where, $params);
    
    foreach ($logs as $log) {
        $typeclass = 'badge badge-info';
        if ($log->type === 'error') {
            $typeclass = 'badge badge-danger';
        } else if ($log->type === 'stkpush') {
            $typeclass = 'badge badge-primary';
        } else if ($log->type === 'callback') {
            $typeclass = 'badge badge-success';
        }
        
        $resultclass = 'text-muted';
        if ($log->resultcode === '0') {
            $resultclass = 'text-success font-weight-bold';
        } else if ($log->resultcode && $log->resultcode !== '0') {
            $resultclass = 'text-danger font-weight-bold';
        }
        
        $viewurl = new moodle_url('/local/rvscertificate/logs.php', ['logid' => $log->id]);
        
        $table->add_data([
            $log->id,
            html_writer::tag('span', strtoupper($log->type), ['class' => $typeclass]),
            html_writer::tag('span', $log->resultcode ?? 'N/A', ['class' => $resultclass]),
            s(substr($log->resultdesc ?? 'N/A', 0, 50)) . (strlen($log->resultdesc ?? '') > 50 ? '...' : ''),
            userdate($log->timecreated, '%Y-%m-%d %H:%M:%S'),
            html_writer::link($viewurl, 'View Details', ['class' => 'btn btn-sm btn-info'])
        ]);
    }
    
    $table->finish_output();
    
    // Show summary statistics
    echo html_writer::start_tag('div', ['class' => 'row mt-4']);
    
    echo html_writer::start_tag('div', ['class' => 'col-md-3']);
    echo html_writer::start_tag('div', ['class' => 'card']);
    echo html_writer::start_tag('div', ['class' => 'card-body']);
    echo html_writer::tag('h5', 'Total Logs', ['class' => 'card-title']);
    echo html_writer::tag('h2', $totalcount, ['class' => 'text-primary']);
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
    
    $successcount = $DB->count_records_select('local_rvscertificate_logs', 'resultcode = :code', ['code' => '0']);
    echo html_writer::start_tag('div', ['class' => 'col-md-3']);
    echo html_writer::start_tag('div', ['class' => 'card']);
    echo html_writer::start_tag('div', ['class' => 'card-body']);
    echo html_writer::tag('h5', 'Successful', ['class' => 'card-title']);
    echo html_writer::tag('h2', $successcount, ['class' => 'text-success']);
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
    
    $errorcount = $DB->count_records('local_rvscertificate_logs', ['type' => 'error']);
    echo html_writer::start_tag('div', ['class' => 'col-md-3']);
    echo html_writer::start_tag('div', ['class' => 'card']);
    echo html_writer::start_tag('div', ['class' => 'card-body']);
    echo html_writer::tag('h5', 'Errors', ['class' => 'card-title']);
    echo html_writer::tag('h2', $errorcount, ['class' => 'text-danger']);
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
    
    $recentlogs = $DB->get_records('local_rvscertificate_logs', null, 'timecreated DESC', '*', 0, 1);
    $lastlog = reset($recentlogs);
    echo html_writer::start_tag('div', ['class' => 'col-md-3']);
    echo html_writer::start_tag('div', ['class' => 'card']);
    echo html_writer::start_tag('div', ['class' => 'card-body']);
    echo html_writer::tag('h5', 'Last Log', ['class' => 'card-title']);
    echo html_writer::tag('p', $lastlog ? userdate($lastlog->timecreated, '%Y-%m-%d %H:%M:%S') : 'N/A', ['class' => 'text-info']);
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
    
    echo html_writer::end_tag('div');
}

echo $OUTPUT->footer();
