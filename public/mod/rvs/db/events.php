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
 * Event observers for mod_rvs
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname' => '\mod_book\event\chapter_created',
        'callback' => '\mod_rvs\observer::book_chapter_created',
    ),
    array(
        'eventname' => '\mod_book\event\chapter_updated',
        'callback' => '\mod_rvs\observer::book_chapter_updated',
    ),
    array(
        'eventname' => '\mod_resource\event\course_module_viewed',
        'callback' => '\mod_rvs\observer::resource_viewed',
    ),
    array(
        'eventname' => '\core\event\course_module_created',
        'callback' => '\mod_rvs\observer::course_module_created',
    ),
    array(
        'eventname' => '\mod_scorm\event\course_module_viewed',
        'callback' => '\mod_rvs\observer::scorm_viewed',
    ),
);



