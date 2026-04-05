<?php
/**
 * Owner Panel Setup — Run ONCE to create the license database and initial admin user.
 * DELETE THIS FILE after setup is complete!
 *
 * Usage: php setup.php  OR  access via browser at /public_owner/setup.php
 *
 * After running:
 *   1. Set OWNER_PASSWORD_HASH in public_owner/inc/config.php
 *   2. Delete this file
 */

define('OWNER_ROOT', __DIR__);

$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        if (!array_key_exists(trim($k), $_ENV)) $_ENV[trim($k)] = trim($v, " \t\n\r\"'");
    }
}

$cfg = require dirname(__DIR__) . '/config/license_db.php';

// First, create the database if it doesn't exist
try {
    $pdo = new PDO(
        "mysql:host={$cfg['host']};charset={$cfg['charset']}",
        $cfg['username'],
        $cfg['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $db = $cfg['database'];
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$db}`");
} catch (\Throwable $e) {
    die("DB connection failed: " . $e->getMessage());
}

$schema = <<<SQL
CREATE TABLE IF NOT EXISTS licenses (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    domain      VARCHAR(255) NOT NULL UNIQUE,
    secret_key  VARCHAR(64)  NOT NULL,
    plan        VARCHAR(30)  NOT NULL DEFAULT 'trial',
    max_users   INT          NOT NULL DEFAULT 3,
    max_flows   INT          NOT NULL DEFAULT 10,
    status      ENUM('active','trial','suspended','expired') NOT NULL DEFAULT 'trial',
    expires_at  DATE         NULL,
    features    JSON         NULL,
    notes       TEXT         NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS license_logs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    domain      VARCHAR(255) NOT NULL,
    ip_address  VARCHAR(45)  NOT NULL DEFAULT '',
    status      VARCHAR(30)  NOT NULL DEFAULT '',
    checked_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_domain (domain),
    INDEX idx_checked_at (checked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

foreach (array_filter(array_map('trim', explode(';', $schema))) as $sql) {
    $pdo->exec($sql);
}

// Generate password hash
$password = $_POST['password'] ?? ($_GET['password'] ?? 'admin123');
$hash     = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

$isCli = php_sapi_name() === 'cli';
if ($isCli) {
    echo "✅ Database '{$cfg['database']}' created successfully!\n";
    echo "✅ Tables: licenses, license_logs\n";
    echo "\nGenerated password hash for '{$password}':\n{$hash}\n";
    echo "\nCopy the hash above to public_owner/inc/config.php → OWNER_PASSWORD_HASH\n";
    echo "Then DELETE this file: rm public_owner/setup.php\n";
} else {
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Setup — License Manager</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container" style="max-width:600px;padding-top:60px">
  <div class="card shadow-sm border-0 p-4">
    <h4 class="fw-bold mb-3">✅ Setup concluído!</h4>
    <p>Banco de dados <code><?= htmlspecialchars($cfg['database']) ?></code> e tabelas criados com sucesso.</p>

    <div class="alert alert-warning">
      <strong>Guarde a hash abaixo</strong> e coloque em <code>public_owner/inc/config.php</code>
      no campo <code>OWNER_PASSWORD_HASH</code> ou na variável de ambiente <code>OWNER_PASSWORD_HASH</code>.
    </div>

    <p><strong>Senha utilizada:</strong> <code><?= htmlspecialchars($password) ?></code></p>
    <p><strong>Hash bcrypt:</strong></p>
    <textarea class="form-control font-monospace mb-3" rows="3" readonly onclick="this.select()"><?= htmlspecialchars($hash) ?></textarea>

    <form method="post" class="mb-3">
      <label class="form-label fw-semibold">Gerar hash para outra senha:</label>
      <div class="input-group">
        <input type="text" name="password" class="form-control" placeholder="Nova senha">
        <button class="btn btn-primary">Gerar</button>
      </div>
    </form>

    <div class="alert alert-danger">
      <strong>⚠️ IMPORTANTE:</strong> Delete este arquivo após configurar:<br>
      <code>rm <?= htmlspecialchars(__FILE__) ?></code>
    </div>
  </div>
</div>
</body>
</html>
<?php
}
