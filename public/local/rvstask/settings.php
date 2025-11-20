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
 * Plugin settings for RVS Task.
 *
 * @package    local_rvstask
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Create settings page.
    $settings = new admin_settingpage('local_rvstask', get_string('pluginname', 'local_rvstask'));

    // Add to admin tree.
    $ADMIN->add('localplugins', $settings);

    // Enable course completion emails.
    $settings->add(new admin_setting_configcheckbox(
        'local_rvstask/enablecoursecompletion',
        get_string('enablecoursecompletion', 'local_rvstask'),
        '',
        0
    ));

    // Select course completion template.
    $templates = \local_rvstask\template_manager::get_enabled_templates();
    $templateoptions = [0 => get_string('selecttemplate', 'local_rvstask')];
    foreach ($templates as $template) {
        $templateoptions[$template->id] = $template->name;
    }

    $settings->add(new admin_setting_configselect(
        'local_rvstask/coursecompletiontemplateid',
        get_string('coursecompletiontemplate', 'local_rvstask'),
        '',
        0,
        $templateoptions
    ));

    // Add management pages to admin menu.
    $ADMIN->add('localplugins', new admin_category('local_rvstask_cat', get_string('pluginname', 'local_rvstask')));

    $ADMIN->add('local_rvstask_cat', new admin_externalpage(
        'local_rvstask_templates',
        get_string('managetemplates', 'local_rvstask'),
        new moodle_url('/local/rvstask/templates.php'),
        'local/rvstask:manage'
    ));

    $ADMIN->add('local_rvstask_cat', new admin_externalpage(
        'local_rvstask_send',
        get_string('sendemail', 'local_rvstask'),
        new moodle_url('/local/rvstask/send.php'),
        'local/rvstask:send'
    ));
}
