<?php
function ownerDb(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;

    // Load .env from project root (one level above public_owner/)
    $root    = dirname(OWNER_ROOT);
    $envFile = $root . '/.env';
    if (file_exists($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
            [$k, $v] = explode('=', $line, 2);
            $v = trim($v, " \t\n\r\"'");
            if (!array_key_exists(trim($k), $_ENV)) $_ENV[trim($k)] = $v;
        }
    }

    $cfgFile = $root . '/config/license_db.php';
    if (!file_exists($cfgFile)) {
        throw new \RuntimeException("License DB config not found: {$cfgFile}");
    }

    $cfg = require $cfgFile;
    $dsn = "mysql:host={$cfg['host']};dbname={$cfg['database']};charset={$cfg['charset']}";

    try {
        $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (\PDOException $e) {
        throw new \RuntimeException(
            "License DB connection failed. Check LICENSE_DB_* in .env\n" . $e->getMessage()
        );
    }

    return $pdo;
}
