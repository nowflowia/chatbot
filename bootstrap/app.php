<?php

use Core\Session;
use Core\CSRF;
use Core\Request;
use Core\Router;

// Load .env
$envFile = ROOT_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value]  = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

// Error handling
$debug = filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);

if ($debug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

// Timezone
date_default_timezone_set(env('TIMEZONE', 'America/Sao_Paulo'));

// Error handler
set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    logger("[PHP Error] [{$errno}] {$errstr} in {$errfile}:{$errline}", 'error');
    return false;
});

set_exception_handler(function (\Throwable $e): void {
    logger('[Uncaught Exception] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(), 'error');

    if (filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN)) {
        http_response_code(500);
        echo '<pre style="background:#1a1a2e;color:#e94560;padding:20px;font-size:13px;">';
        echo '<strong>' . get_class($e) . '</strong>: ' . htmlspecialchars($e->getMessage()) . "\n\n";
        echo htmlspecialchars($e->getTraceAsString());
        echo '</pre>';
    } else {
        http_response_code(500);
        if (file_exists(VIEW_PATH . '/errors/500.php')) {
            require VIEW_PATH . '/errors/500.php';
        } else {
            echo '<h1>500 - Internal Server Error</h1>';
        }
    }
    exit(1);
});

// Start session
Session::start();

// Build request
$request = new Request();

// CSRF check
CSRF::check($request);

// Load routes
$router = new Router();
require ROOT_PATH . '/routes/web.php';
require ROOT_PATH . '/routes/api.php';

// Dispatch
$router->dispatch($request);
