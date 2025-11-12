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
 * Plugin settings
 *
 * @package    local_rvscertificate
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_rvscertificate', get_string('pluginname', 'local_rvscertificate'));
    $ADMIN->add('localplugins', $settings);

    // Certificate pricing settings
    $coursepricesurl = new moodle_url('/local/rvscertificate/manage_prices.php');
    $coursepriceslink = html_writer::link($coursepricesurl, get_string('managecourseprices', 'local_rvscertificate'));
    
    $settings->add(new admin_setting_heading(
        'local_rvscertificate/pricing_heading',
        get_string('pricing_heading', 'local_rvscertificate'),
        get_string('pricing_heading_desc', 'local_rvscertificate') . '<br><br>' . 
        html_writer::tag('strong', get_string('note', 'local_rvscertificate') . ': ') . 
        get_string('courseprices_note', 'local_rvscertificate', $coursepriceslink)
    ));

    $settings->add(new admin_setting_configtext(
        'local_rvscertificate/certificate_price',
        get_string('certificate_price', 'local_rvscertificate'),
        get_string('certificate_price_desc', 'local_rvscertificate'),
        '500',
        PARAM_FLOAT
    ));

    // M-Pesa API settings
    $settings->add(new admin_setting_heading(
        'local_rvscertificate/mpesa_heading',
        get_string('mpesa_heading', 'local_rvscertificate'),
        get_string('mpesa_heading_desc', 'local_rvscertificate')
    ));

    $settings->add(new admin_setting_configselect(
        'local_rvscertificate/mpesa_environment',
        get_string('mpesa_environment', 'local_rvscertificate'),
        get_string('mpesa_environment_desc', 'local_rvscertificate'),
        'sandbox',
        [
            'sandbox' => get_string('mpesa_sandbox', 'local_rvscertificate'),
            'production' => get_string('mpesa_production', 'local_rvscertificate')
        ]
    ));

    $settings->add(new admin_setting_configtext(
        'local_rvscertificate/mpesa_consumer_key',
        get_string('mpesa_consumer_key', 'local_rvscertificate'),
        get_string('mpesa_consumer_key_desc', 'local_rvscertificate'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_rvscertificate/mpesa_consumer_secret',
        get_string('mpesa_consumer_secret', 'local_rvscertificate'),
        get_string('mpesa_consumer_secret_desc', 'local_rvscertificate'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_rvscertificate/mpesa_shortcode',
        get_string('mpesa_shortcode', 'local_rvscertificate'),
        get_string('mpesa_shortcode_desc', 'local_rvscertificate'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_rvscertificate/mpesa_passkey',
        get_string('mpesa_passkey', 'local_rvscertificate'),
        get_string('mpesa_passkey_desc', 'local_rvscertificate'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_rvscertificate/mpesa_callback_url',
        get_string('mpesa_callback_url', 'local_rvscertificate'),
        get_string('mpesa_callback_url_desc', 'local_rvscertificate'),
        '',
        PARAM_URL
    ));

    // Email settings
    $settings->add(new admin_setting_heading(
        'local_rvscertificate/email_heading',
        get_string('email_heading', 'local_rvscertificate'),
        get_string('email_heading_desc', 'local_rvscertificate')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_rvscertificate/send_email',
        get_string('send_email', 'local_rvscertificate'),
        get_string('send_email_desc', 'local_rvscertificate'),
        1
    ));

    // Add M-Pesa logs external page
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_rvscertificate_logs',
        get_string('mpesa_logs', 'local_rvscertificate'),
        new moodle_url('/local/rvscertificate/logs.php')
    ));
}
