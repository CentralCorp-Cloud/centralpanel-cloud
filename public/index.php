<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Auto-create .env from .env.example if it doesn't exist (for fresh installations)
$envPath = __DIR__ . '/../.env';
$envExamplePath = __DIR__ . '/../.env.example';

if (getenv('PANEL_MANAGED') !== 'true' && !file_exists($envPath) && file_exists($envExamplePath)) {
    copy($envExamplePath, $envPath);
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__ . '/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__ . '/../bootstrap/app.php')
    ->handleRequest(Request::capture());
