<?php
/**
 * License Verification API
 * Hosted by the software owner. Clients call this to validate their license.
 *
 * Endpoints:
 *   GET  ?action=verify&domain=client.com&key=SECRET_KEY
 *   GET  ?action=ping
 */

define('ROOT_PATH', dirname(__DIR__));

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Load .env if exists
$envFile = ROOT_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $v = trim($v, " \t\n\r\"'");
        if (!array_key_exists(trim($k), $_ENV)) {
            $_ENV[trim($k)] = $v;
        }
    }
}

function jsonOut(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function connectLicenseDb(): PDO
{
    $cfg = require ROOT_PATH . '/config/license_db.php';
    $dsn = "mysql:host={$cfg['host']};dbname={$cfg['database']};charset={$cfg['charset']}";
    return new PDO($dsn, $cfg['username'], $cfg['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 5,
    ]);
}

function normalizeDomain(string $raw): string
{
    $raw = strtolower(trim($raw));
    $raw = preg_replace('#^https?://#', '', $raw);
    $raw = preg_replace('#^www\.#', '', $raw);
    $raw = strtok($raw, '/');  // remove path
    return $raw;
}

$action = $_GET['action'] ?? '';

// ── Ping ─────────────────────────────────────────────────────────
if ($action === 'ping') {
    jsonOut(['ok' => true, 'ts' => time()]);
}

// ── Verify ───────────────────────────────────────────────────────
if ($action === 'verify') {
    $domain = normalizeDomain($_GET['domain'] ?? '');
    $key    = trim($_GET['key'] ?? '');
    $ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if (!$domain || !$key) {
        jsonOut(['valid' => false, 'error' => 'Missing domain or key'], 400);
    }

    try {
        $pdo = connectLicenseDb();

        $stmt = $pdo->prepare(
            "SELECT * FROM licenses WHERE domain = ? AND secret_key = ? LIMIT 1"
        );
        $stmt->execute([$domain, $key]);
        $license = $stmt->fetch();

        // Log check
        $pdo->prepare(
            "INSERT INTO license_logs (domain, ip_address, status, checked_at) VALUES (?, ?, ?, NOW())"
        )->execute([$domain, $ip, $license ? $license['status'] : 'not_found']);

        if (!$license) {
            jsonOut(['valid' => false, 'error' => 'License not found']);
        }

        // Check expiry
        if ($license['expires_at'] && strtotime($license['expires_at']) < time()) {
            $pdo->prepare("UPDATE licenses SET status='expired' WHERE id=?")->execute([$license['id']]);
            jsonOut(['valid' => false, 'error' => 'License expired', 'expires_at' => $license['expires_at']]);
        }

        if (!in_array($license['status'], ['active', 'trial'])) {
            jsonOut(['valid' => false, 'error' => 'License ' . $license['status']]);
        }

        jsonOut([
            'valid'      => true,
            'domain'     => $license['domain'],
            'plan'       => $license['plan'],
            'max_users'  => (int)$license['max_users'],
            'max_flows'  => (int)$license['max_flows'],
            'status'     => $license['status'],
            'expires_at' => $license['expires_at'],
            'features'   => json_decode($license['features'] ?? '[]', true),
        ]);

    } catch (\Throwable $e) {
        // Never expose DB errors to clients
        error_log('[LicenseAPI] ' . $e->getMessage());
        jsonOut(['valid' => false, 'error' => 'Server error'], 500);
    }
}

jsonOut(['valid' => false, 'error' => 'Unknown action'], 400);
