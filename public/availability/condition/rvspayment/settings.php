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
 * Settings for availability_rvspayment.
 *
 * @package    availability_rvspayment
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('availability_rvspayment_settings', 
        get_string('pluginname', 'availability_rvspayment'));

    if ($ADMIN->fulltree) {
        // Default price setting.
        $settings->add(new admin_setting_configtext(
            'availability_rvspayment/default_price',
            get_string('default_price', 'availability_rvspayment'),
            get_string('default_price_desc', 'availability_rvspayment'),
            '100',
            PARAM_FLOAT
        ));

        // Callback URL info - uses the same callback as rvscertificate.
        $callbackurl = new moodle_url('/local/rvscertificate/callback.php');
        $settings->add(new admin_setting_description(
            'availability_rvspayment/callback_info',
            get_string('callback_url', 'availability_rvspayment'),
            html_writer::tag('div',
                get_string('callback_url_desc', 'availability_rvspayment', $callbackurl->out()) .
                '<br><small class="text-muted">This plugin shares the callback URL with the RVS Certificate plugin.</small>',
                ['class' => 'alert alert-secondary']
            )
        ));

        // Note about M-Pesa configuration.
        $mpesasettingsurl = new moodle_url('/admin/settings.php', ['section' => 'local_rvscertificate_settings']);
        $settings->add(new admin_setting_description(
            'availability_rvspayment/mpesa_note',
            '',
            html_writer::tag('div',
                html_writer::tag('strong', 'Note: ') . 
                'M-Pesa API credentials are configured in the ' . 
                html_writer::link($mpesasettingsurl, 'RVS Certificate plugin settings') . '.',
                ['class' => 'alert alert-info']
            )
        ));

        // Link to payment report.
        $reporturl = new moodle_url('/availability/condition/rvspayment/report.php');
        $settings->add(new admin_setting_description(
            'availability_rvspayment/report_link',
            get_string('report_title', 'availability_rvspayment'),
            html_writer::link($reporturl, get_string('report_title', 'availability_rvspayment'), 
                ['class' => 'btn btn-primary']) .
            html_writer::tag('p', get_string('report_description', 'availability_rvspayment'), 
                ['class' => 'mt-2 text-muted'])
        ));
    }
}
