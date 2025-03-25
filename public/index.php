<?php

// Add detailed error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// Ensure the build directory exists and is accessible
$buildPath = __DIR__ . '/build';
if (file_exists($buildPath) && is_dir($buildPath)) {
    $_SERVER['VITE_MANIFEST_PATH'] = $buildPath . '/.vite/manifest.json';
}

// Process the request
$app->handleRequest(Request::capture());
