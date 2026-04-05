<?php
/**
 * Owner Panel — Entry Point
 * Access: /public_owner/
 */
define('OWNER_ROOT', __DIR__);

// Load .env from project root
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $v = trim($v, " \t\n\r\"'");
        if (!array_key_exists(trim($k), $_ENV)) $_ENV[trim($k)] = $v;
    }
}

require __DIR__ . '/inc/config.php';
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/layout.php';

// Global error handler — show friendly message instead of blank 500
set_exception_handler(function (\Throwable $e): void {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Erro</title>'
       . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
       . '<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">'
       . '<div class="card border-0 shadow p-4" style="max-width:560px">'
       . '<h5 class="text-danger fw-bold">Erro de configuração</h5>'
       . '<p class="mb-1">' . htmlspecialchars($e->getMessage()) . '</p>'
       . '<hr><p class="small text-muted mb-0">Verifique as variáveis <code>LICENSE_DB_*</code> no <code>.env</code> '
       . 'e certifique-se de ter executado o <code>setup.php</code>.</p>'
       . '</div></body></html>';
    exit(1);
});

session_name(SESSION_NAME);
session_start();

$page = preg_replace('/[^a-z_]/', '', strtolower($_GET['page'] ?? 'dashboard'));

// Handle logout before auth check
if ($page === 'logout') {
    ownerLogout();
}

// Login page doesn't need auth
if ($page === 'login') {
    require __DIR__ . '/views/login.php';
    exit;
}

// All other pages require auth
ownerAuth();

$allowed = ['dashboard', 'licenses', 'logs'];
if (!in_array($page, $allowed)) $page = 'dashboard';

require __DIR__ . '/views/' . $page . '.php';
