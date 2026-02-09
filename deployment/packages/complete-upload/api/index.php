<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Don't display errors, but log them
ini_set('log_errors', '1');

// Check if vendor autoload exists
$vendorPath = __DIR__.'/../backend/vendor/autoload.php';
if (!file_exists($vendorPath)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Backend dependencies missing',
        'message' => 'The vendor/autoload.php file is missing. Please ensure all dependencies are installed.',
        'path' => $vendorPath
    ]);
    exit;
}

// Check if .env exists
$envPath = __DIR__.'/../backend/.env';
if (!file_exists($envPath)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Configuration missing',
        'message' => 'The .env file is missing. Please copy .env.example to .env and configure it.',
        'path' => $envPath
    ]);
    exit;
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../backend/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $vendorPath;

try {
    // Bootstrap Laravel and handle the request...
    /** @var Application $app */
    $app = require_once __DIR__.'/../backend/bootstrap/app.php';
    
    $app->handleRequest(Request::capture());
} catch (Throwable $e) {
    // Log the error
    $logPath = __DIR__.'/../backend/storage/logs/laravel.log';
    $logDir = dirname($logPath);
    if (is_writable($logDir)) {
        error_log(
            date('Y-m-d H:i:s') . " - " . 
            get_class($e) . ": " . $e->getMessage() . "\n" .
            "File: " . $e->getFile() . ":" . $e->getLine() . "\n" .
            $e->getTraceAsString() . "\n\n",
            3,
            $logPath
        );
    }
    
    // Return JSON error response
    http_response_code(500);
    header('Content-Type: application/json');
    
    // Only show detailed errors if APP_DEBUG is true
    $env = parse_ini_file($envPath);
    $debug = isset($env['APP_DEBUG']) && $env['APP_DEBUG'] === 'true';
    
    $response = [
        'error' => 'Internal Server Error',
        'message' => 'An error occurred while processing your request.'
    ];
    
    if ($debug) {
        $response['details'] = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'type' => get_class($e)
        ];
    }
    
    echo json_encode($response);
    exit;
}
