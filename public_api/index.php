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

// ── Legacy license verification (back-compat) ─────────────────────
// Allows clients with LICENSE_API_URL=https://apichat.domain.com (no path)
// to keep working — query is GET ?action=verify&domain=X&key=Y
if (($_GET['action'] ?? '') === 'verify') {
    licenseVerifyHandler();
    exit;
}

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


// ──────────────────────────────────────────────────────────────────
//  License verification (public_api back-compat)
// ──────────────────────────────────────────────────────────────────
function licenseVerifyHandler(): void
{
    $cfgPath = ROOT_PATH . '/config/license_db.php';
    if (!file_exists($cfgPath)) {
        http_response_code(503);
        echo json_encode(['valid' => false, 'error' => 'License server not configured.']);
        return;
    }

    $domain = strtolower(trim((string)($_GET['domain'] ?? '')));
    $key    = trim((string)($_GET['key'] ?? ''));

    // Normalize domain: strip scheme, www., trailing path
    $domain = preg_replace('#^https?://#i', '', $domain);
    $domain = preg_replace('#^www\.#i', '', $domain);
    $domain = strtok($domain, '/') ?: $domain;

    if (!$domain || !$key) {
        http_response_code(400);
        echo json_encode(['valid' => false, 'error' => 'Missing domain or key']);
        return;
    }

    try {
        $cfg = require $cfgPath;
        $dsn = "mysql:host={$cfg['host']};dbname={$cfg['database']};charset={$cfg['charset']}";
        $pdo = new \PDO($dsn, $cfg['username'], $cfg['password'], [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_TIMEOUT            => 5,
        ]);

        // Lookup: domain + key (preferred), then key only as fallback
        $stmt = $pdo->prepare("SELECT * FROM licenses WHERE secret_key=? AND domain=? LIMIT 1");
        $stmt->execute([$key, $domain]);
        $license = $stmt->fetch();

        if (!$license) {
            $stmt = $pdo->prepare("SELECT * FROM licenses WHERE secret_key=? LIMIT 1");
            $stmt->execute([$key]);
            $license = $stmt->fetch();
        }

        // Audit log (optional)
        try {
            $pdo->prepare("INSERT INTO license_logs (domain, ip_address, status, checked_at) VALUES (?, ?, ?, NOW())")
                ->execute([$domain, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', $license['status'] ?? 'not_found']);
        } catch (\Throwable $e) { /* log table optional */ }

        if (!$license) {
            echo json_encode(['valid' => false, 'error' => 'License not found']);
            return;
        }

        $status = (string)($license['status'] ?? 'inactive');
        $valid  = $status === 'active';

        if ($valid && !empty($license['expires_at'])) {
            $valid = strtotime((string)$license['expires_at']) > time();
            if (!$valid) {
                $status = 'expired';
                try {
                    $pdo->prepare("UPDATE licenses SET status='expired' WHERE id=?")
                        ->execute([(int)$license['id']]);
                } catch (\Throwable $e) {}
            }
        }

        // License-level features
        $features = [];
        if (!empty($license['features'])) {
            $decoded = json_decode((string)$license['features'], true);
            if (is_array($decoded)) $features = $decoded;
        }

        // Merge plan-level features
        if (!empty($license['plan'])) {
            try {
                $pstmt = $pdo->prepare("SELECT features FROM plans WHERE slug=? LIMIT 1");
                $pstmt->execute([$license['plan']]);
                $plan = $pstmt->fetch();
                if ($plan && !empty($plan['features'])) {
                    $planFeatures = json_decode((string)$plan['features'], true) ?: [];
                    if (is_array($planFeatures)) {
                        $features = array_values(array_unique(array_merge($planFeatures, $features)));
                    }
                }
            } catch (\Throwable $e) { /* plans table optional */ }
        }

        echo json_encode([
            'valid'      => $valid,
            'domain'     => $license['domain'],
            'plan'       => $license['plan']        ?? 'unknown',
            'max_users'  => (int)($license['max_users'] ?? 0),
            'max_flows'  => (int)($license['max_flows'] ?? 0),
            'status'     => $status,
            'expires_at' => $license['expires_at']  ?? null,
            'features'   => $features,
            'error'      => null,
        ]);
    } catch (\Throwable $e) {
        error_log('[LicenseVerify] ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['valid' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    }
}
