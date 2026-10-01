<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Auto-detect silab_backend path
$candidates = [
    __DIR__ . '/../../silab_backend',
    __DIR__ . '/../../../silab_backend',
    __DIR__ . '/../../../../silab_backend',
    dirname(__DIR__, 2) . '/silab_backend',
    dirname(__DIR__, 3) . '/silab_backend',
    dirname(__DIR__, 4) . '/silab_backend',
];

$backendPath = null;
foreach ($candidates as $candidate) {
    if (file_exists($candidate . '/bootstrap/app.php')) {
        $backendPath = realpath($candidate);
        break;
    }
}

if (!$backendPath) {
    http_response_code(500);
    die("SILAB Backend tidak ditemukan. Pastikan folder 'silab_backend' diletakkan sejajar atau di luar public_html.");
}

// Maintenance mode check
if (file_exists($maintenance = $backendPath . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register Composer autoloader
require $backendPath . '/vendor/autoload.php';

// Bootstrap Laravel and handle request
/** @var Application $app */
$app = require_once $backendPath . '/bootstrap/app.php';

$app->handleRequest(Request::capture());
