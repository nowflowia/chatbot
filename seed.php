#!/usr/bin/env php
<?php

declare(strict_types=1);

define('ROOT_PATH', __DIR__);
define('APP_PATH', ROOT_PATH . '/app');
define('CORE_PATH', ROOT_PATH . '/core');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('VIEW_PATH', APP_PATH . '/Views');

if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
} else {
    require_once ROOT_PATH . '/bootstrap/autoload.php';
}

$envFile = ROOT_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key); $value = trim($value, " \t\n\r\0\x0B\"'");
        $_ENV[$key] = $value; $_SERVER[$key] = $value; putenv("{$key}={$value}");
    }
}

date_default_timezone_set(env('TIMEZONE', 'America/Sao_Paulo'));

$seedName = $argv[1] ?? null;
$seedPath = ROOT_PATH . '/database/seeds';

if ($seedName) {
    $file = $seedPath . '/' . $seedName . '.php';
    if (!file_exists($file)) { echo "Seed not found: {$file}\n"; exit(1); }
    require_once $file;
    $class = preg_replace('/^\d+_/', '', $seedName);
    if (class_exists($class)) {
        echo "Seeding: {$class}\n";
        (new $class())->run();
        echo "Done.\n";
    }
} else {
    $files = glob($seedPath . '/*.php') ?: [];
    sort($files);
    foreach ($files as $file) {
        require_once $file;
        $filename = pathinfo($file, PATHINFO_FILENAME);
        // Strip numeric prefix: "001_RolesSeeder" -> "RolesSeeder"
        $class = preg_replace('/^\d+_/', '', $filename);
        if (class_exists($class)) {
            echo "Seeding: {$class}\n";
            (new $class())->run();
        }
    }
    echo "All seeds ran.\n";
}
