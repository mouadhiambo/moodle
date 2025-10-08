<?php
// Health check endpoint for Render
// This file provides a simple health check for the Moodle application

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', '0');
error_reporting(0);

header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'version' => '1.0.0',
    'service' => 'moodle'
];

// Check if config.php exists
if (!file_exists('../config.php')) {
    $health['status'] = 'warning';
    $health['message'] = 'Configuration file not found - system may still be initializing';
    $health['config_file'] = 'not_found';
    http_response_code(200);
    echo json_encode($health, JSON_PRETTY_PRINT);
    exit(0);
}

// Try to load config
try {
    // Define CLI_SCRIPT to prevent redirects in config
    define('CLI_SCRIPT', true);
    require_once('../config.php');
    
    // Check database connection
    if (isset($CFG) && isset($CFG->dbhost)) {
        try {
            $dsn = "pgsql:host={$CFG->dbhost};dbname={$CFG->dbname}";
            $pdo = new PDO($dsn, $CFG->dbuser, $CFG->dbpass);
            $health['database'] = 'connected';
        } catch (PDOException $e) {
            $health['database'] = 'connection_failed';
            $health['status'] = 'warning';
        }
    } else {
        $health['database'] = 'not_configured';
        $health['status'] = 'warning';
    }
    
    // Check data directory
    if (isset($CFG) && isset($CFG->dataroot)) {
        if (is_writable($CFG->dataroot)) {
            $health['data_directory'] = 'writable';
        } else {
            $health['data_directory'] = 'not_writable';
            $health['status'] = 'warning';
        }
    }
    
} catch (Exception $e) {
    $health['status'] = 'warning';
    $health['message'] = 'System initialization in progress';
    $health['details'] = substr($e->getMessage(), 0, 100);
}

http_response_code(200);
echo json_encode($health, JSON_PRETTY_PRINT);
exit(0);
