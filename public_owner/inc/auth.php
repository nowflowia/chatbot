<?php
function ownerAuth(): void
{
    if (empty($_SESSION['owner_logged_in'])) {
        header('Location: ?page=login');
        exit;
    }
}

function ownerLogin(string $username, string $password): bool
{
    if ($username !== OWNER_USERNAME) return false;
    return password_verify($password, OWNER_PASSWORD_HASH);
}

function ownerLogout(): void
{
    $_SESSION = [];
    session_destroy();
    header('Location: ?page=login');
    exit;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfCheck(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
        die('Invalid CSRF token.');
    }
}

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function flash(string $key, string $msg): void { $_SESSION['flash'][$key] = $msg; }
function getFlash(string $key): string { $v = $_SESSION['flash'][$key] ?? ''; unset($_SESSION['flash'][$key]); return $v; }
