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
 * Report module for RVS
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

echo html_writer::start_div('rvs-report');

$report = $DB->get_record('rvs_report', array('rvsid' => $rvs->id));

if (!$report) {
    echo html_writer::tag('div', get_string('noreport', 'mod_rvs'), array('class' => 'alert alert-info'));
    
    if (has_capability('mod/rvs:generate', $modulecontext)) {
        $regenerateurl = new moodle_url('/mod/rvs/regenerate.php', array('id' => $cm->id, 'module' => 'report'));
        echo html_writer::link(
            $regenerateurl,
            get_string('generatereport', 'mod_rvs'),
            array('class' => 'btn btn-primary')
        );
    }
} else {
    // Validate report data.
    if (empty($report->content)) {
        // Display error for missing content.
        echo $OUTPUT->notification(
            get_string('reportdatamissing', 'mod_rvs'),
            \core\output\notification::NOTIFY_ERROR
        );
        
        if (has_capability('mod/rvs:generate', $modulecontext)) {
            $regenerateurl = new moodle_url('/mod/rvs/regenerate.php', array('id' => $cm->id, 'module' => 'report'));
            echo html_writer::div(
                html_writer::link(
                    $regenerateurl,
                    get_string('regeneratereport', 'mod_rvs'),
                    array('class' => 'btn btn-warning')
                ),
                'mt-3'
            );
        }
    } else {
        echo html_writer::tag('h3', format_string($report->title));
        
        // Ensure proper HTML structure rendering.
        // The content should already be in HTML format with proper headings.
        $reportcontent = $report->content;
        
        // Add some CSS classes to improve rendering of report sections.
        $reportcontent = preg_replace(
            '/<h1>/i',
            '<h1 class="report-section-title mt-4 mb-3">',
            $reportcontent
        );
        $reportcontent = preg_replace(
            '/<h2>/i',
            '<h2 class="report-subsection-title mt-3 mb-2">',
            $reportcontent
        );
        $reportcontent = preg_replace(
            '/<h3>/i',
            '<h3 class="report-subheading mt-2 mb-2">',
            $reportcontent
        );
        
        // Display report content with proper HTML structure.
        echo html_writer::div(
            format_text($reportcontent, FORMAT_HTML),
            'report-content card card-body',
            array('style' => 'margin: 20px 0; padding: 30px;')
        );
        
        // Download buttons for multiple formats.
        echo html_writer::start_div('mt-3');
        echo html_writer::tag('p', get_string('downloadreportas', 'mod_rvs'), array('class' => 'mb-2'));
        
        $formats = array('html', 'pdf', 'docx');
        foreach ($formats as $format) {
            $downloadurl = new moodle_url('/mod/rvs/download.php', array(
                'id' => $cm->id, 
                'type' => 'report', 
                'format' => $format
            ));
            
            // Add appropriate icons for each format.
            $icon = '';
            switch ($format) {
                case 'html':
                    $icon = '📄';
                    break;
                case 'pdf':
                    $icon = '📕';
                    break;
                case 'docx':
                    $icon = '📘';
                    break;
            }
            
            echo html_writer::link(
                $downloadurl,
                $icon . ' ' . get_string('downloadas', 'mod_rvs', strtoupper($format)),
                array('class' => 'btn btn-secondary mr-2 mb-2')
            );
        }
        
        echo html_writer::end_div();
    }
}

echo html_writer::end_div();



