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
 * Admin pages configuration
 *
 * @package    local_rvscertificate
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_category('local_rvscertificate_category', 
        get_string('pluginname', 'local_rvscertificate')));

    // Settings page
    $settingspage = new admin_settingpage('local_rvscertificate_settings', 
        get_string('pluginname', 'local_rvscertificate') . ' Settings');

    // Certificate pricing settings
    $settingspage->add(new admin_setting_heading(
        'local_rvscertificate/pricing_heading',
        get_string('pricing_heading', 'local_rvscertificate'),
        get_string('pricing_heading_desc', 'local_rvscertificate')
    ));

    $settingspage->add(new admin_setting_configtext(
        'local_rvscertificate/certificate_price',
        get_string('certificate_price', 'local_rvscertificate'),
        get_string('certificate_price_desc', 'local_rvscertificate'),
        '500',
        PARAM_FLOAT
    ));

    // M-Pesa API settings
    $settingspage->add(new admin_setting_heading(
        'local_rvscertificate/mpesa_heading',
        get_string('mpesa_heading', 'local_rvscertificate'),
        get_string('mpesa_heading_desc', 'local_rvscertificate')
    ));

    $settingspage->add(new admin_setting_configselect(
        'local_rvscertificate/mpesa_environment',
        get_string('mpesa_environment', 'local_rvscertificate'),
        get_string('mpesa_environment_desc', 'local_rvscertificate'),
        'sandbox',
        [
            'sandbox' => get_string('mpesa_sandbox', 'local_rvscertificate'),
            'production' => get_string('mpesa_production', 'local_rvscertificate')
        ]
    ));

    $settingspage->add(new admin_setting_configtext(
        'local_rvscertificate/mpesa_consumer_key',
        get_string('mpesa_consumer_key', 'local_rvscertificate'),
        get_string('mpesa_consumer_key_desc', 'local_rvscertificate'),
        '',
        PARAM_TEXT
    ));

    $settingspage->add(new admin_setting_configpasswordunmask(
        'local_rvscertificate/mpesa_consumer_secret',
        get_string('mpesa_consumer_secret', 'local_rvscertificate'),
        get_string('mpesa_consumer_secret_desc', 'local_rvscertificate'),
        ''
    ));

    $settingspage->add(new admin_setting_configtext(
        'local_rvscertificate/mpesa_shortcode',
        get_string('mpesa_shortcode', 'local_rvscertificate'),
        get_string('mpesa_shortcode_desc', 'local_rvscertificate'),
        '',
        PARAM_TEXT
    ));

    $settingspage->add(new admin_setting_configpasswordunmask(
        'local_rvscertificate/mpesa_passkey',
        get_string('mpesa_passkey', 'local_rvscertificate'),
        get_string('mpesa_passkey_desc', 'local_rvscertificate'),
        ''
    ));

    $settingspage->add(new admin_setting_configtext(
        'local_rvscertificate/mpesa_callback_url',
        get_string('mpesa_callback_url', 'local_rvscertificate'),
        get_string('mpesa_callback_url_desc', 'local_rvscertificate'),
        '',
        PARAM_URL
    ));

    // Email settings
    $settingspage->add(new admin_setting_heading(
        'local_rvscertificate/email_heading',
        get_string('email_heading', 'local_rvscertificate'),
        get_string('email_heading_desc', 'local_rvscertificate')
    ));

    $settingspage->add(new admin_setting_configcheckbox(
        'local_rvscertificate/send_email',
        get_string('send_email', 'local_rvscertificate'),
        get_string('send_email_desc', 'local_rvscertificate'),
        1
    ));

    $ADMIN->add('local_rvscertificate_category', $settingspage);

    // Report page
    $ADMIN->add('local_rvscertificate_category', new admin_externalpage(
        'local_rvscertificate_report',
        'Payment Report',
        new moodle_url('/local/rvscertificate/report.php')
    ));
}
