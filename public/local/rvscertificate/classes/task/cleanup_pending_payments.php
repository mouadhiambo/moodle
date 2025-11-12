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
 * Scheduled task to clean up old pending payments
 *
 * @package    local_rvscertificate
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rvscertificate\task;

/**
 * Cleanup old pending payments
 */
class cleanup_pending_payments extends \core\task\scheduled_task {
    
    /**
     * Get task name
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_cleanup_pending_payments', 'local_rvscertificate');
    }
    
    /**
     * Execute task
     */
    public function execute() {
        global $DB;
        
        // Mark payments older than 15 minutes as failed
        $cutofftime = time() - (15 * 60);
        
        $sql = "UPDATE {local_rvscertificate_payments}
                   SET status = 'failed',
                       timemodified = :now
                 WHERE status = 'pending'
                   AND timecreated < :cutoff";
        
        $updated = $DB->execute($sql, [
            'now' => time(),
            'cutoff' => $cutofftime
        ]);
        
        if ($updated) {
            mtrace('Cleaned up old pending payments');
        }
        
        return true;
    }
}
