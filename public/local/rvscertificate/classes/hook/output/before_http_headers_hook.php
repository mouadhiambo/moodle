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
 * Hook callback for before_http_headers
 *
 * @package    local_rvscertificate
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rvscertificate\hook\output;

/**
 * Hook callback for before_http_headers
 */
class before_http_headers_hook {
    
    /**
     * Callback to intercept customcert access and enforce payment
     *
     * @param \core\hook\output\before_http_headers $hook
     */
    public static function callback(\core\hook\output\before_http_headers $hook): void {
        global $CFG;
        
        // Only run if headers haven't been sent yet
        if (headers_sent()) {
            return;
        }
        
        // Load intercept script
        if (file_exists($CFG->dirroot . '/local/rvscertificate/intercept.php')) {
            require_once($CFG->dirroot . '/local/rvscertificate/intercept.php');
            local_rvscertificate_intercept_customcert();
        }
    }
}
