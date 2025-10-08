<?php
// Simple health check endpoint for Render
// This file provides a basic health check without requiring full Moodle configuration

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', '0');
error_reporting(0);

header('Content-Type: application/json');
http_response_code(200);

$health = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'version' => '1.0.0',
    'service' => 'moodle',
    'type' => 'simple'
];

// Basic checks
$health['php_version'] = PHP_VERSION;
$health['server_time'] = date('Y-m-d H:i:s');
$health['timezone'] = date_default_timezone_get();

// Check if we can access the parent directory
if (is_dir('..')) {
    $health['parent_directory'] = 'accessible';
} else {
    $health['parent_directory'] = 'not_accessible';
    $health['status'] = 'warning';
}

// Check if config.php exists in parent directory
if (file_exists('../config.php')) {
    $health['config_file'] = 'exists';
} else {
    $health['config_file'] = 'not_found';
    $health['status'] = 'warning';
}

echo json_encode($health, JSON_PRETTY_PRINT);
exit(0);