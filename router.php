<?php
/**
 * Router script for PHP built-in server
 * This handles URL routing for Moodle when using PHP's built-in server
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Handle static files
if (file_exists(__DIR__ . $uri)) {
    return false; // Serve the file as-is
}

// Handle health check endpoints
if ($uri === '/public/health.php' || $uri === '/public/health' || 
    $uri === '/public/health-simple.php' || $uri === '/public/health-simple') {
    if ($uri === '/public/health-simple.php' || $uri === '/public/health-simple') {
        require_once __DIR__ . '/health-simple.php';
    } else {
        require_once __DIR__ . '/health.php';
    }
    return true;
}

// Handle all other requests through index.php
if ($uri === '/' || $uri === '/index.php' || strpos($uri, '/public/') === 0) {
    // Remove /public prefix if present
    $cleanUri = str_replace('/public', '', $uri);
    if ($cleanUri === '') {
        $cleanUri = '/';
    }
    
    // Set the correct REQUEST_URI
    $_SERVER['REQUEST_URI'] = $cleanUri;
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    
    require_once __DIR__ . '/index.php';
    return true;
}

// Handle other Moodle routes
if (strpos($uri, '/public/') === 0) {
    $cleanUri = str_replace('/public', '', $uri);
    $_SERVER['REQUEST_URI'] = $cleanUri;
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    
    require_once __DIR__ . '/index.php';
    return true;
}

// Default: serve index.php
require_once __DIR__ . '/index.php';
return true;
