<?php

declare(strict_types=1);

/**
 * NowFlow REST API — External entry point
 *
 * Point your subdomain (e.g. api.yourdomain.com) to this directory.
 * All REST API endpoints are served here without CSRF or session overhead.
 *
 * Base URL : https://api.yourdomain.com/v1/
 * Auth     : Authorization: Bearer <api_key>   OR   X-API-Key: <api_key>
 */

ob_start();

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH',     ROOT_PATH . '/app');
define('CORE_PATH',    ROOT_PATH . '/core');
define('PUBLIC_PATH',  ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('VIEW_PATH',    APP_PATH  . '/Views');
define('START_TIME', microtime(true));

// ── Autoloader ────────────────────────────────────────────────────
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
} else {
    require_once ROOT_PATH . '/bootstrap/autoload.php';
}

// ── .env ──────────────────────────────────────────────────────────
$envFile = ROOT_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v, " \t\n\r\0\x0B\"'");
        if (!array_key_exists($k, $_ENV)) {
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
            putenv("{$k}={$v}");
        }
    }
}

// ── Error handling ────────────────────────────────────────────────
$debug = filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);
ini_set('display_errors', $debug ? '1' : '0');
error_reporting($debug ? E_ALL : E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE & ~E_WARNING);

set_exception_handler(function (\Throwable $e): void {
    ob_clean();
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    $body = ['ok' => false, 'message' => 'Internal server error'];
    if (filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN)) {
        $body['debug'] = $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
    }
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit(1);
});

// ── Timezone ──────────────────────────────────────────────────────
date_default_timezone_set(env('TIMEZONE', 'America/Sao_Paulo'));

// ── CORS ──────────────────────────────────────────────────────────
$allowedOrigins = array_filter(array_map('trim', explode(',', env('API_CORS_ORIGINS', '*'))));
$origin         = $_SERVER['HTTP_ORIGIN'] ?? '*';

if ($allowedOrigins === ['*'] || in_array('*', $allowedOrigins)) {
    header('Access-Control-Allow-Origin: *');
} elseif (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: {$origin}");
    header('Vary: Origin');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, X-API-Key, Content-Type, Accept');
header('Access-Control-Max-Age: 86400');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Force JSON content type ───────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ── Not installed? ────────────────────────────────────────────────
if (!file_exists(ROOT_PATH . '/storage/.installed') && !file_exists(ROOT_PATH . '/.env')) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'message' => 'Application not installed']);
    exit;
}

// ── Router ────────────────────────────────────────────────────────
use Core\Router;
use Core\Request;

$router  = new Router();
$request = new Request();

require ROOT_PATH . '/routes/api_v1.php';

$router->dispatch($request);
