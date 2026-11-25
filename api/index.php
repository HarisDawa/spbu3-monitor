<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$storagePath = '/tmp/storage';
foreach ([
    $storagePath.'/framework/sessions',
    $storagePath.'/framework/views',
    $storagePath.'/framework/cache',
    $storagePath.'/logs',
    '/tmp/bootstrap/cache',
] as $dir) {
    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

$sourceSqlite = __DIR__.'/../database/database.sqlite';
$tmpSqlite = '/tmp/database.sqlite';
$needsMigration = false;
if (! file_exists($tmpSqlite)) {
    if (file_exists($sourceSqlite)) {
        copy($sourceSqlite, $tmpSqlite);
    } else {
        touch($tmpSqlite);
        $needsMigration = true;
    }
}

$overrides = [
    'APP_CONFIG_CACHE' => '/tmp/bootstrap/cache/config.php',
    'APP_EVENTS_CACHE' => '/tmp/bootstrap/cache/events.php',
    'APP_PACKAGES_CACHE' => '/tmp/bootstrap/cache/packages.php',
    'APP_ROUTES_CACHE' => '/tmp/bootstrap/cache/routes.php',
    'APP_SERVICES_CACHE' => '/tmp/bootstrap/cache/services.php',
    'VIEW_COMPILED_PATH' => $storagePath.'/framework/views',
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'cookie',
    'LOG_CHANNEL' => 'stderr',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => $tmpSqlite,
];

if (($appKey = getenv('APP_KEY')) !== false) {
    $overrides['APP_KEY'] = trim($appKey);
}

foreach ($overrides as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->useStoragePath($storagePath);

if ($needsMigration) {
    $kernel = $app->make(Kernel::class);
    $kernel->call('migrate', ['--force' => true]);
    $kernel->call('db:seed', ['--force' => true]);
}

$app->handleRequest(Request::capture());
