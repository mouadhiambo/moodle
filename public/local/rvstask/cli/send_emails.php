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
 * CLI script to manually run the email sending task.
 *
 * @package    local_rvstask
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Get cli options.
list($options, $unrecognized) = cli_get_params(
    [
        'help' => false,
        'execute' => false,
    ],
    [
        'h' => 'help',
        'e' => 'execute',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help = "Run the RVS Task email sending task manually.

Options:
-h, --help          Print out this help
-e, --execute       Execute the task (without this, only stats are shown)

Example:
\$ php local/rvstask/cli/send_emails.php --execute
";

if ($options['help']) {
    echo $help;
    exit(0);
}

cli_heading('RVS Task Email Sender');

// Get queue statistics.
$stats = \local_rvstask\queue_manager::get_queue_stats();
echo "Queue Statistics:\n";
echo "  Pending: {$stats->pending}\n";
echo "  Sent: {$stats->sent}\n";
echo "  Failed: {$stats->failed}\n";
echo "\n";

if ($options['execute']) {
    echo "Executing email sending task...\n";
    echo str_repeat('-', 60) . "\n";
    
    $task = new \local_rvstask\task\send_emails_task();
    $task->execute();
    
    echo str_repeat('-', 60) . "\n";
    
    // Show updated stats.
    $newstats = \local_rvstask\queue_manager::get_queue_stats();
    echo "\nUpdated Statistics:\n";
    echo "  Pending: {$newstats->pending}\n";
    echo "  Sent: {$newstats->sent}\n";
    echo "  Failed: {$newstats->failed}\n";
    echo "\nEmails processed: " . ($newstats->sent - $stats->sent) . "\n";
} else {
    echo "Use --execute to run the task.\n";
}

exit(0);
