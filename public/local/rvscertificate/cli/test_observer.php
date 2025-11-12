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
 * CLI script to test observer configuration
 *
 * @package    local_rvscertificate
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Ensure running as CLI
if (!CLI_SCRIPT) {
    die('This script can only be run from the command line.');
}

cli_heading('RVS Certificate Observer Test');

// Check if observer is registered
echo "\n" . "Checking event observers...\n";
$observers = $DB->get_records('events_handlers');
echo "Total event handlers in database: " . count($observers) . "\n";

// Check if the plugin is installed
$plugin = $DB->get_record('config_plugins', ['plugin' => 'local_rvscertificate', 'name' => 'version']);
if ($plugin) {
    echo "✓ Plugin installed - version: " . $plugin->value . "\n";
} else {
    echo "✗ Plugin NOT installed!\n";
}

// Check if customcert is available
$customcert = $DB->get_record('modules', ['name' => 'customcert']);
if ($customcert) {
    echo "✓ CustomCert module found - ID: " . $customcert->id . ", Visible: " . $customcert->visible . "\n";
} else {
    echo "✗ CustomCert module NOT found!\n";
}

// Check capabilities
echo "\n" . "Checking capabilities...\n";
$capabilities = $DB->get_records_select('capabilities', "name LIKE 'local/rvscertificate:%'");
if (count($capabilities) > 0) {
    echo "✓ Found " . count($capabilities) . " capabilities:\n";
    foreach ($capabilities as $cap) {
        echo "  - " . $cap->name . "\n";
    }
} else {
    echo "✗ No capabilities found! Run upgrade.\n";
}

// Check scheduled tasks
echo "\n" . "Checking scheduled tasks...\n";
$tasks = \core\task\manager::get_all_scheduled_tasks();
$found = false;
foreach ($tasks as $task) {
    if (strpos(get_class($task), 'local_rvscertificate') !== false) {
        echo "✓ Found task: " . get_class($task) . " - " . $task->get_name() . "\n";
        $found = true;
    }
}
if (!$found) {
    echo "✗ No scheduled tasks found!\n";
}

// Check database tables
echo "\n" . "Checking database tables...\n";
$dbman = $DB->get_manager();
$tables = ['local_rvscertificate_payments', 'local_rvscertificate_logs', 'local_rvscertificate_course_prices'];
foreach ($tables as $tablename) {
    $table = new xmldb_table($tablename);
    if ($dbman->table_exists($table)) {
        $count = $DB->count_records($tablename);
        echo "✓ Table '{$tablename}' exists with {$count} records\n";
    } else {
        echo "✗ Table '{$tablename}' does NOT exist!\n";
    }
}

// Check M-Pesa configuration
echo "\n" . "Checking M-Pesa configuration...\n";
$config_keys = ['mpesa_environment', 'mpesa_consumer_key', 'mpesa_consumer_secret', 'mpesa_shortcode', 'mpesa_passkey', 'certificate_price'];
foreach ($config_keys as $key) {
    $value = get_config('local_rvscertificate', $key);
    if (!empty($value)) {
        if (strpos($key, 'secret') !== false || strpos($key, 'passkey') !== false || strpos($key, 'key') !== false) {
            echo "✓ {$key}: " . str_repeat('*', min(strlen($value), 20)) . "\n";
        } else {
            echo "✓ {$key}: {$value}\n";
        }
    } else {
        echo "✗ {$key}: NOT SET\n";
    }
}

// Test a course with customcert
echo "\n" . "Looking for courses with customcert...\n";
$sql = "SELECT c.id, c.fullname, cm.id as cmid
          FROM {course} c
          JOIN {course_modules} cm ON cm.course = c.id
          JOIN {modules} m ON m.id = cm.module
         WHERE m.name = 'customcert'
           AND c.id != 1
         LIMIT 5";
$courses = $DB->get_records_sql($sql);
if (count($courses) > 0) {
    echo "✓ Found " . count($courses) . " courses with customcert:\n";
    foreach ($courses as $course) {
        echo "  - Course {$course->id}: {$course->fullname} (CM ID: {$course->cmid})\n";
        
        // Check if there are any payments for this course
        $payments = $DB->count_records('local_rvscertificate_payments', ['courseid' => $course->id]);
        echo "    Payments: {$payments}\n";
    }
} else {
    echo "✗ No courses with customcert found!\n";
}

echo "\n" . "Test complete!\n\n";

echo "If the observer is not working, try:\n";
echo "1. Clear caches: php admin/cli/purge_caches.php\n";
echo "2. Upgrade database: php admin/cli/upgrade.php\n";
echo "3. Enable debugging: Site administration > Development > Debugging > Developer level\n";
echo "4. Check logs: Site administration > Reports > Logs\n\n";

exit(0);
