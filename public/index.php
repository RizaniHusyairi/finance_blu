<?php

use Illuminate\Http\Request;

// Sembunyikan deprecation notice (mis. PDO::MYSQL_ATTR_SSL_CA pada PHP 8.5)
// agar tidak bocor ke response HTML/PDF.
error_reporting(error_reporting() & ~E_DEPRECATED & ~E_USER_DEPRECATED);

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
