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
 * Plugin administration pages are defined here.
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // AI Provider Settings.
    $settings->add(new admin_setting_heading('mod_rvs/aiheading',
        get_string('aisettings', 'mod_rvs'),
        get_string('aisettings_desc', 'mod_rvs')));

    // Default AI provider.
    $settings->add(new admin_setting_configtext('mod_rvs/default_provider',
        get_string('default_provider', 'mod_rvs'),
        get_string('default_provider_desc', 'mod_rvs'),
        'openai', PARAM_TEXT));

    // API Configuration.
    $settings->add(new admin_setting_configtext('mod_rvs/api_key',
        get_string('api_key', 'mod_rvs'),
        get_string('api_key_desc', 'mod_rvs'),
        '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('mod_rvs/api_endpoint',
        get_string('api_endpoint', 'mod_rvs'),
        get_string('api_endpoint_desc', 'mod_rvs'),
        'https://api.openai.com/v1', PARAM_URL));

    // Generation Settings.
    $settings->add(new admin_setting_heading('mod_rvs/generationheading',
        get_string('generationsettings', 'mod_rvs'),
        get_string('generationsettings_desc', 'mod_rvs')));

    $settings->add(new admin_setting_configtext('mod_rvs/max_flashcards',
        get_string('max_flashcards', 'mod_rvs'),
        get_string('max_flashcards_desc', 'mod_rvs'),
        15, PARAM_INT));

    $settings->add(new admin_setting_configtext('mod_rvs/max_quiz_questions',
        get_string('max_quiz_questions', 'mod_rvs'),
        get_string('max_quiz_questions_desc', 'mod_rvs'),
        15, PARAM_INT));

    $settings->add(new admin_setting_configcheckbox('mod_rvs/enable_audio_generation',
        get_string('enable_audio_generation', 'mod_rvs'),
        get_string('enable_audio_generation_desc', 'mod_rvs'),
        0));

    $settings->add(new admin_setting_configcheckbox('mod_rvs/enable_video_generation',
        get_string('enable_video_generation', 'mod_rvs'),
        get_string('enable_video_generation_desc', 'mod_rvs'),
        0));

    // Content Detection.
    $settings->add(new admin_setting_heading('mod_rvs/detectionheading',
        get_string('detectionsettings', 'mod_rvs'),
        get_string('detectionsettings_desc', 'mod_rvs')));

    $settings->add(new admin_setting_configcheckbox('mod_rvs/auto_detect_new_content',
        get_string('auto_detect_new_content', 'mod_rvs'),
        get_string('auto_detect_new_content_desc', 'mod_rvs'),
        1));

    $settings->add(new admin_setting_configcheckbox('mod_rvs/auto_regenerate',
        get_string('auto_regenerate', 'mod_rvs'),
        get_string('auto_regenerate_desc', 'mod_rvs'),
        0));

    // Error Handling and Notifications.
    $settings->add(new admin_setting_heading('mod_rvs/errorheading',
        get_string('errorsettings', 'mod_rvs'),
        get_string('errorsettings_desc', 'mod_rvs')));

    $settings->add(new admin_setting_configcheckbox('mod_rvs/enable_admin_notifications',
        get_string('enable_admin_notifications', 'mod_rvs'),
        get_string('enable_admin_notifications_desc', 'mod_rvs'),
        1));
}

