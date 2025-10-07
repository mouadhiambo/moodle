<?php
// Health check endpoint for Render
// This file provides a simple health check for the Moodle application

header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'version' => '1.0.0',
    'service' => 'moodle'
];

// Check if config.php exists
if (!file_exists('../config.php')) {
    $health['status'] = 'error';
    $health['error'] = 'Configuration file not found';
    http_response_code(500);
    echo json_encode($health);
    exit;
}

// Try to load config
try {
    require_once('../config.php');
    
    // Check database connection
    if (isset($CFG) && isset($CFG->dbhost)) {
        $dsn = "pgsql:host={$CFG->dbhost};dbname={$CFG->dbname}";
        $pdo = new PDO($dsn, $CFG->dbuser, $CFG->dbpass);
        $health['database'] = 'connected';
    } else {
        $health['database'] = 'not_configured';
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
    $health['status'] = 'error';
    $health['error'] = $e->getMessage();
    http_response_code(500);
}

echo json_encode($health, JSON_PRETTY_PRINT);
