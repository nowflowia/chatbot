<?php
/**
 * Web Installer — ChatBot System
 * Acesse /install/ para configurar o sistema.
 * Após a instalação este arquivo é desabilitado automaticamente.
 */
session_start();

define('INSTALL_ROOT', dirname(__DIR__));
define('INSTALL_LOCK', INSTALL_ROOT . '/storage/.installed');
define('INSTALL_ENV',  INSTALL_ROOT . '/.env');

// ── Already installed? ────────────────────────────────────────────
if (file_exists(INSTALL_LOCK)) {
    die(page('Instalação concluída', '<div class="alert alert-success text-center py-4">
        <i class="bi bi-check-circle-fill fs-1 d-block mb-2"></i>
        <h5>Sistema já instalado!</h5>
        <a href="/" class="btn btn-primary mt-2">Acessar o sistema</a>
    </div>', 0));
}

// ── Helpers ───────────────────────────────────────────────────────
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function sess(string $key, mixed $default = ''): mixed
{
    return $_SESSION['install'][$key] ?? $default;
}

function sessSet(string $key, mixed $val): void
{
    $_SESSION['install'][$key] = $val;
}

function redirect(int $step): never
{
    header('Location: ?step=' . $step);
    exit;
}

function testDbConnection(string $host, int $port, string $db, string $user, string $pass): array
{
    try {
        $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]);
        // Try to create DB if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$db}`");
        return ['ok' => true, 'message' => 'Conexão estabelecida com sucesso!'];
    } catch (\Throwable $e) {
        return ['ok' => false, 'message' => $e->getMessage()];
    }
}

function writeEnvFile(array $data): void
{
    $appKey = 'base64:' . base64_encode(random_bytes(32));
    $lines  = [
        'APP_NAME="' . addslashes($data['app_name']) . '"',
        'APP_ENV=production',
        'APP_DEBUG=false',
        'APP_URL=' . rtrim($data['app_url'], '/'),
        'APP_KEY=' . $appKey,
        '',
        'DB_HOST=' . $data['db_host'],
        'DB_PORT=' . $data['db_port'],
        'DB_DATABASE=' . $data['db_name'],
        'DB_USERNAME=' . $data['db_user'],
        'DB_PASSWORD=' . $data['db_pass'],
        '',
        'SESSION_LIFETIME=120',
        'SESSION_SECURE=false',
        '',
        'TIMEZONE=America/Sao_Paulo',
        'LOCALE=pt_BR',
        '',
        '# License',
        'LICENSE_API_URL=' . ($data['license_api'] ?? ''),
        'LICENSE_KEY=' . ($data['license_key'] ?? ''),
    ];
    file_put_contents(INSTALL_ENV, implode("\n", $lines) . "\n");
}

function runMigrations(): array
{
    $log = [];

    // Bootstrap framework constants
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH',   INSTALL_ROOT);
        define('APP_PATH',    ROOT_PATH . '/app');
        define('CORE_PATH',   ROOT_PATH . '/core');
        define('PUBLIC_PATH', ROOT_PATH . '/public');
        define('STORAGE_PATH',ROOT_PATH . '/storage');
        define('VIEW_PATH',   APP_PATH  . '/Views');
        define('START_TIME',  microtime(true));
    }

    // Load .env into $_ENV
    foreach (file(INSTALL_ENV, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k); $v = trim($v, " \t\n\r\"'");
        $_ENV[$k] = $_SERVER[$k] = $v;
        putenv("{$k}={$v}");
    }

    // Load autoloader
    if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
        require_once ROOT_PATH . '/vendor/autoload.php';
    } else {
        require_once ROOT_PATH . '/bootstrap/autoload.php';
    }

    // Load helpers
    require_once ROOT_PATH . '/core/Helpers.php';

    date_default_timezone_set('America/Sao_Paulo');

    // Migrations table
    try {
        \Core\Database::getInstance()->statement("
            CREATE TABLE IF NOT EXISTS `migrations` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `migration` VARCHAR(255) NOT NULL,
                `batch` INT NOT NULL DEFAULT 1,
                `ran_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } catch (\Throwable $e) {
        return [['status' => 'error', 'name' => 'migrations_table', 'msg' => $e->getMessage()]];
    }

    $ran   = array_column(\Core\Database::getInstance()->select("SELECT migration FROM migrations"), 'migration');
    $files = glob(ROOT_PATH . '/database/migrations/*.php') ?: [];
    sort($files);

    $batch = (int)(\Core\Database::getInstance()->selectOne("SELECT MAX(batch) as b FROM migrations")['b'] ?? 0) + 1;

    foreach ($files as $file) {
        $name = pathinfo($file, PATHINFO_FILENAME);
        if (in_array($name, $ran)) {
            $log[] = ['status' => 'skipped', 'name' => $name];
            continue;
        }
        try {
            require_once $file;
            // Derive class name from filename (skip 4 date parts)
            $parts = explode('_', $name);
            $classParts = count($parts) > 4 ? array_slice($parts, 4) : $parts;
            $class = implode('', array_map('ucfirst', $classParts));

            if (!class_exists($class)) {
                $log[] = ['status' => 'error', 'name' => $name, 'msg' => "Class '{$class}' not found"];
                continue;
            }

            $obj = new $class();
            $obj->up();

            \Core\Database::getInstance()->insert(
                "INSERT INTO migrations (migration, batch) VALUES (?, ?)",
                [$name, $batch]
            );
            $log[] = ['status' => 'ok', 'name' => $name];
        } catch (\Throwable $e) {
            $log[] = ['status' => 'error', 'name' => $name, 'msg' => $e->getMessage()];
        }
    }

    return $log;
}

function createAdminUser(array $data): void
{
    $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    $db   = \Core\Database::getInstance();

    // Get admin role id
    $role = $db->selectOne("SELECT id FROM roles WHERE slug='admin' OR slug='administrator' LIMIT 1");
    $roleId = $role['id'] ?? 1;

    $existing = $db->selectOne("SELECT id FROM users WHERE email = ?", [strtolower($data['email'])]);
    if (!$existing) {
        $db->insert(
            "INSERT INTO users (name, email, password, role_id, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, 'active', NOW(), NOW())",
            [$data['name'], strtolower($data['email']), $hash, $roleId]
        );
    }
}

function lockInstaller(): void
{
    @mkdir(INSTALL_ROOT . '/storage', 0755, true);
    file_put_contents(INSTALL_LOCK, date('Y-m-d H:i:s'));
}

// ── Requirements check ────────────────────────────────────────────
function checkRequirements(): array
{
    $checks = [];
    $checks[] = ['label' => 'PHP >= 8.1',         'ok' => version_compare(PHP_VERSION, '8.1.0', '>='), 'value' => PHP_VERSION];
    $checks[] = ['label' => 'Extensão PDO',        'ok' => extension_loaded('pdo'),       'value' => ''];
    $checks[] = ['label' => 'Extensão pdo_mysql',  'ok' => extension_loaded('pdo_mysql'), 'value' => ''];
    $checks[] = ['label' => 'Extensão mbstring',   'ok' => extension_loaded('mbstring'),  'value' => ''];
    $checks[] = ['label' => 'Extensão curl',        'ok' => extension_loaded('curl'),      'value' => ''];
    $checks[] = ['label' => 'Extensão json',        'ok' => extension_loaded('json'),      'value' => ''];
    $checks[] = ['label' => 'Pasta storage/ gravável',  'ok' => is_writable(INSTALL_ROOT . '/storage'), 'value' => ''];
    $checks[] = ['label' => 'Pasta raiz gravável (para .env)', 'ok' => is_writable(INSTALL_ROOT), 'value' => ''];
    return $checks;
}

// ── AJAX: test DB ─────────────────────────────────────────────────
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    if ($_POST['ajax_action'] === 'test_db') {
        $result = testDbConnection(
            trim($_POST['db_host'] ?? 'localhost'),
            (int)($_POST['db_port'] ?? 3306),
            trim($_POST['db_name'] ?? ''),
            trim($_POST['db_user'] ?? ''),
            $_POST['db_pass'] ?? ''
        );
        echo json_encode($result);
        exit;
    }
    echo json_encode(['ok' => false, 'message' => 'Unknown action']);
    exit;
}

// ── POST handlers ─────────────────────────────────────────────────
$step  = max(1, min(5, (int)($_GET['step'] ?? 1)));
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($step === 1) {
        // Just proceed if all requirements pass
        redirect(2);
    }

    if ($step === 2) {
        $dbHost = trim($_POST['db_host'] ?? 'localhost');
        $dbPort = (int)($_POST['db_port'] ?? 3306);
        $dbName = trim($_POST['db_name'] ?? '');
        $dbUser = trim($_POST['db_user'] ?? '');
        $dbPass = $_POST['db_pass'] ?? '';

        if (!$dbName || !$dbUser) { $error = 'Preencha todos os campos obrigatórios.'; }
        else {
            $test = testDbConnection($dbHost, $dbPort, $dbName, $dbUser, $dbPass);
            if (!$test['ok']) { $error = 'Falha na conexão: ' . $test['message']; }
            else {
                sessSet('db_host', $dbHost);
                sessSet('db_port', $dbPort);
                sessSet('db_name', $dbName);
                sessSet('db_user', $dbUser);
                sessSet('db_pass', $dbPass);
                redirect(3);
            }
        }
    }

    if ($step === 3) {
        sessSet('license_api', trim($_POST['license_api'] ?? ''));
        sessSet('license_key', trim($_POST['license_key'] ?? ''));
        redirect(4);
    }

    if ($step === 4) {
        $appName  = trim($_POST['app_name'] ?? 'ChatBot');
        $appUrl   = trim($_POST['app_url']  ?? '');
        $adminName  = trim($_POST['admin_name']  ?? '');
        $adminEmail = trim($_POST['admin_email'] ?? '');
        $adminPass  = $_POST['admin_pass']  ?? '';
        $adminPass2 = $_POST['admin_pass2'] ?? '';

        if (!$appName || !$appUrl || !$adminName || !$adminEmail || !$adminPass) {
            $error = 'Preencha todos os campos obrigatórios.';
        } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'E-mail do administrador inválido.';
        } elseif (strlen($adminPass) < 6) {
            $error = 'A senha deve ter pelo menos 6 caracteres.';
        } elseif ($adminPass !== $adminPass2) {
            $error = 'As senhas não conferem.';
        } else {
            sessSet('app_name',    $appName);
            sessSet('app_url',     $appUrl);
            sessSet('admin_name',  $adminName);
            sessSet('admin_email', $adminEmail);
            sessSet('admin_pass',  $adminPass);
            redirect(5);
        }
    }
}

// ── Step 5: Run installation (via POST from confirmation) ─────────
$installLog = [];
if ($step === 5 && isset($_POST['do_install'])) {
    try {
        // 1. Write .env
        writeEnvFile([
            'app_name'    => sess('app_name', 'ChatBot'),
            'app_url'     => sess('app_url', 'http://localhost'),
            'db_host'     => sess('db_host', 'localhost'),
            'db_port'     => sess('db_port', 3306),
            'db_name'     => sess('db_name'),
            'db_user'     => sess('db_user'),
            'db_pass'     => sess('db_pass'),
            'license_api' => sess('license_api'),
            'license_key' => sess('license_key'),
        ]);
        $installLog[] = ['status' => 'ok', 'name' => 'Arquivo .env criado'];

        // 2. Run migrations
        $migLog = runMigrations();
        $installLog = array_merge($installLog, $migLog);

        $hasError = !empty(array_filter($migLog, fn($l) => $l['status'] === 'error'));

        if (!$hasError) {
            // 3. Create admin user
            createAdminUser([
                'name'     => sess('admin_name'),
                'email'    => sess('admin_email'),
                'password' => sess('admin_pass'),
            ]);
            $installLog[] = ['status' => 'ok', 'name' => 'Usuário administrador criado'];

            // 4. Lock installer
            lockInstaller();
            $installLog[] = ['status' => 'ok', 'name' => 'Instalador desabilitado'];

            // Clear session
            unset($_SESSION['install']);
            $step = 6; // success
        }
    } catch (\Throwable $e) {
        $installLog[] = ['status' => 'error', 'name' => 'Erro inesperado', 'msg' => $e->getMessage()];
    }
}

// ── Layout ────────────────────────────────────────────────────────
function page(string $title, string $body, int $currentStep): string
{
    $steps = ['Requisitos', 'Banco de Dados', 'Licença', 'Configuração', 'Instalar'];
    $stepHtml = '';
    if ($currentStep > 0) {
        foreach ($steps as $i => $label) {
            $n     = $i + 1;
            $state = $n < $currentStep ? 'done' : ($n === $currentStep ? 'active' : 'pending');
            $dot   = match($state) {
                'done'   => '<span class="step-dot done"><i class="bi bi-check-lg"></i></span>',
                'active' => '<span class="step-dot active">'.$n.'</span>',
                default  => '<span class="step-dot">'.$n.'</span>',
            };
            $stepHtml .= '<div class="step-item ' . $state . '">' . $dot
                . '<span class="step-label d-none d-sm-inline">' . $label . '</span>'
                . ($n < count($steps) ? '<div class="step-line"></div>' : '')
                . '</div>';
        }
    }

    return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$title} — Instalação</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
  body{background:#f1f5f9;font-family:'Segoe UI',sans-serif;}
  .installer-wrap{max-width:680px;margin:40px auto;padding:0 16px 60px;}
  .installer-header{background:linear-gradient(135deg,#0f172a,#1e3a8a);border-radius:16px 16px 0 0;padding:28px 32px;color:#fff;}
  .installer-card{background:#fff;border-radius:0 0 16px 16px;box-shadow:0 8px 32px rgba(0,0,0,.1);padding:32px;}
  .step-bar{display:flex;align-items:center;margin-bottom:0;padding-top:4px;}
  .step-item{display:flex;align-items:center;flex:1;}
  .step-dot{width:32px;height:32px;border-radius:50%;background:#334155;color:#94a3b8;display:inline-flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;flex-shrink:0;}
  .step-dot.active{background:#3b82f6;color:#fff;box-shadow:0 0 0 4px rgba(59,130,246,.3);}
  .step-dot.done{background:#10b981;color:#fff;}
  .step-line{flex:1;height:2px;background:#334155;margin:0 6px;}
  .step-item.done .step-line{background:#10b981;}
  .step-item.active .step-line{background:#3b82f6;}
  .step-label{font-size:.72rem;color:#94a3b8;margin-left:6px;white-space:nowrap;}
  .step-item.active .step-label{color:#93c5fd;}
  .step-item.done .step-label{color:#6ee7b7;}
  .req-item{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f1f5f9;}
  .req-item:last-child{border:none;}
  .log-item{font-size:.82rem;padding:4px 0;display:flex;align-items:flex-start;gap:8px;}
  .btn-primary{background:#3b82f6;border-color:#3b82f6;}
  .btn-primary:hover{background:#2563eb;border-color:#2563eb;}
</style>
</head>
<body>
<div class="installer-wrap">
  <div class="installer-header">
    <div class="d-flex align-items-center gap-3 mb-4">
      <div style="width:44px;height:44px;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;">
        <i class="bi bi-chat-dots-fill fs-4 text-white"></i>
      </div>
      <div>
        <div class="fw-bold fs-5">ChatBot System</div>
        <div style="color:#93c5fd;font-size:.85rem;">Assistente de instalação</div>
      </div>
    </div>
    {$stepHtml}
  </div>
  <div class="installer-card">
    <h5 class="fw-bold mb-4">{$title}</h5>
    {$body}
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
HTML;
}

// ── Step rendering ────────────────────────────────────────────────
ob_start();

// ── STEP 6: Success ───────────────────────────────────────────────
if ($step === 6) {
    echo '<div class="text-center py-3">
      <div style="width:72px;height:72px;background:#dcfce7;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:20px;">
        <i class="bi bi-check-circle-fill text-success fs-1"></i>
      </div>
      <h4 class="fw-bold text-success">Instalação concluída!</h4>
      <p class="text-muted">O sistema foi configurado com sucesso.</p>
    </div>';

    echo '<div class="mb-4">';
    foreach ($installLog as $item) {
        $icon  = $item['status'] === 'ok' ? '<i class="bi bi-check-circle-fill text-success"></i>'
                : ($item['status'] === 'skipped' ? '<i class="bi bi-dash-circle text-muted"></i>'
                : '<i class="bi bi-x-circle-fill text-danger"></i>');
        $msg   = isset($item['msg']) ? ' <small class="text-danger">— '.$item['msg'].'</small>' : '';
        echo '<div class="log-item">'.$icon.' '.e($item['name']).$msg.'</div>';
    }
    echo '</div>';
    echo '<a href="/" class="btn btn-primary w-100 fw-semibold py-2">
      <i class="bi bi-arrow-right-circle me-2"></i>Acessar o sistema
    </a>';
    $body = ob_get_clean();
    echo page('Instalação concluída!', $body, 6);
    exit;
}

// ── STEP 5: Review + Install ──────────────────────────────────────
if ($step === 5) {
    if (!empty($installLog)) {
        // Show progress after attempt with errors
        $hasError = !empty(array_filter($installLog, fn($l) => $l['status'] === 'error'));
        echo '<div class="mb-3">';
        foreach ($installLog as $item) {
            $icon = match($item['status']) {
                'ok'      => '<i class="bi bi-check-circle-fill text-success"></i>',
                'skipped' => '<i class="bi bi-dash-circle text-muted"></i>',
                default   => '<i class="bi bi-x-circle-fill text-danger"></i>',
            };
            $msg = isset($item['msg']) ? ' <small class="text-danger">— '.e($item['msg']).'</small>' : '';
            echo '<div class="log-item">'.$icon.' '.e($item['name']).$msg.'</div>';
        }
        echo '</div>';
        if ($hasError) {
            echo '<div class="alert alert-danger">Corrija os erros acima e tente novamente.</div>
                  <a href="?step=2" class="btn btn-outline-secondary">← Voltar ao banco de dados</a>';
        }
    } else {
        // Summary before install
        echo '<div class="alert alert-info mb-4 small">
          <i class="bi bi-info-circle me-1"></i>
          Revise as informações abaixo antes de iniciar a instalação.
        </div>';

        $rows = [
            'Banco de dados' => sess('db_host') . ':' . sess('db_port') . '/' . sess('db_name'),
            'Usuário DB'     => sess('db_user'),
            'App'            => sess('app_name') . ' (' . sess('app_url') . ')',
            'Admin'          => sess('admin_name') . ' &lt;' . e(sess('admin_email')) . '&gt;',
            'Licença'        => sess('license_key') ? '✓ configurada' : 'não configurada (modo dev)',
        ];

        echo '<dl class="row mb-4">';
        foreach ($rows as $label => $value) {
            echo '<dt class="col-4 text-muted fw-normal small">'.$label.'</dt>'
               . '<dd class="col-8 fw-semibold small">'.$value.'</dd>';
        }
        echo '</dl>';

        echo '<div class="alert alert-warning small mb-4">
          <i class="bi bi-exclamation-triangle me-1"></i>
          O instalador será <strong>desabilitado automaticamente</strong> após a conclusão.
        </div>';

        echo '<form method="post">
          <input type="hidden" name="do_install" value="1">
          <div class="d-flex gap-2">
            <a href="?step=4" class="btn btn-outline-secondary flex-shrink-0">← Voltar</a>
            <button type="submit" class="btn btn-primary flex-grow-1 fw-semibold py-2" id="btn-install">
              <span class="spinner-border spinner-border-sm d-none me-2" id="spin"></span>
              <i class="bi bi-lightning-fill me-2" id="ic"></i>Instalar agora
            </button>
          </div>
        </form>
        <script>
          document.getElementById("btn-install").addEventListener("click", function(){
            document.getElementById("spin").classList.remove("d-none");
            document.getElementById("ic").classList.add("d-none");
            this.disabled = true;
            this.closest("form").submit();
          });
        </script>';
    }
}

// ── STEP 4: App + Admin user ──────────────────────────────────────
elseif ($step === 4) {
    $url = sess('app_url') ?: (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    if ($error) echo '<div class="alert alert-danger small">' . e($error) . '</div>';
    echo '<form method="post">
      <div class="mb-3">
        <label class="form-label fw-semibold">Nome do sistema <span class="text-danger">*</span></label>
        <input type="text" name="app_name" class="form-control" placeholder="Meu ChatBot"
               value="' . e(sess('app_name', 'ChatBot')) . '" required>
      </div>
      <div class="mb-4">
        <label class="form-label fw-semibold">URL do sistema <span class="text-danger">*</span></label>
        <input type="url" name="app_url" class="form-control font-monospace"
               placeholder="https://chat.seudominio.com.br"
               value="' . e(sess('app_url', $url)) . '" required>
        <div class="form-text">URL completa onde o sistema está instalado.</div>
      </div>
      <hr class="my-4">
      <p class="fw-semibold mb-3"><i class="bi bi-person-fill me-1"></i>Conta de administrador</p>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Nome completo <span class="text-danger">*</span></label>
          <input type="text" name="admin_name" class="form-control"
                 value="' . e(sess('admin_name')) . '" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">E-mail <span class="text-danger">*</span></label>
          <input type="email" name="admin_email" class="form-control"
                 value="' . e(sess('admin_email')) . '" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Senha <span class="text-danger">*</span></label>
          <input type="password" name="admin_pass" class="form-control"
                 placeholder="Mínimo 6 caracteres" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Confirmar senha <span class="text-danger">*</span></label>
          <input type="password" name="admin_pass2" class="form-control" required>
        </div>
      </div>
      <div class="d-flex gap-2 mt-4">
        <a href="?step=3" class="btn btn-outline-secondary flex-shrink-0">← Voltar</a>
        <button type="submit" class="btn btn-primary flex-grow-1 fw-semibold">Próximo →</button>
      </div>
    </form>';
}

// ── STEP 3: License ───────────────────────────────────────────────
elseif ($step === 3) {
    echo '<div class="alert alert-info small mb-4">
      <i class="bi bi-info-circle me-1"></i>
      Se você não possui uma licença, deixe os campos em branco. O sistema rodará em <strong>modo desenvolvimento</strong> sem restrições.
    </div>
    <form method="post">
      <div class="mb-3">
        <label class="form-label fw-semibold">URL da API de Licença</label>
        <input type="url" name="license_api" class="form-control font-monospace"
               placeholder="https://owner.seudominio.com/public_api"
               value="' . e(sess('license_api')) . '">
      </div>
      <div class="mb-4">
        <label class="form-label fw-semibold">Chave de Licença</label>
        <div class="input-group">
          <input type="password" name="license_key" id="license_key" class="form-control font-monospace"
                 placeholder="Chave fornecida pelo distribuidor"
                 value="' . e(sess('license_key')) . '">
          <button type="button" class="btn btn-outline-secondary" id="btn-show-key">
            <i class="bi bi-eye"></i>
          </button>
        </div>
      </div>
      <div class="d-flex gap-2">
        <a href="?step=2" class="btn btn-outline-secondary flex-shrink-0">← Voltar</a>
        <button type="submit" class="btn btn-primary flex-grow-1 fw-semibold">Próximo →</button>
      </div>
    </form>
    <script>
      document.getElementById("btn-show-key").addEventListener("click", function(){
        var f = document.getElementById("license_key");
        f.type = f.type === "password" ? "text" : "password";
        this.innerHTML = f.type === "password" ? \'<i class="bi bi-eye"></i>\' : \'<i class="bi bi-eye-slash"></i>\';
      });
    </script>';
}

// ── STEP 2: Database ──────────────────────────────────────────────
elseif ($step === 2) {
    if ($error) echo '<div class="alert alert-danger small">' . e($error) . '</div>';
    echo '<form method="post" id="db-form">
      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label fw-semibold">Host do banco de dados <span class="text-danger">*</span></label>
          <input type="text" name="db_host" class="form-control font-monospace"
                 placeholder="localhost" value="' . e(sess('db_host', 'localhost')) . '" required>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Porta</label>
          <input type="number" name="db_port" class="form-control"
                 value="' . e(sess('db_port', '3306')) . '">
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Nome do banco de dados <span class="text-danger">*</span></label>
          <input type="text" name="db_name" class="form-control font-monospace"
                 placeholder="chatbot" value="' . e(sess('db_name')) . '" required>
          <div class="form-text">O banco será criado automaticamente se não existir.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Usuário <span class="text-danger">*</span></label>
          <input type="text" name="db_user" class="form-control font-monospace"
                 placeholder="root" value="' . e(sess('db_user')) . '" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Senha</label>
          <div class="input-group">
            <input type="password" name="db_pass" id="db_pass" class="form-control"
                   value="' . e(sess('db_pass')) . '">
            <button type="button" class="btn btn-outline-secondary" id="btn-show-pass">
              <i class="bi bi-eye"></i>
            </button>
          </div>
        </div>
      </div>

      <div id="test-result" class="mt-3"></div>

      <div class="d-flex gap-2 mt-4">
        <a href="?step=1" class="btn btn-outline-secondary flex-shrink-0">← Voltar</a>
        <button type="button" class="btn btn-outline-info flex-shrink-0" id="btn-test-db">
          <span class="spinner-border spinner-border-sm d-none me-1" id="test-spin"></span>
          <i class="bi bi-wifi me-1" id="test-ic"></i>Testar conexão
        </button>
        <button type="submit" class="btn btn-primary flex-grow-1 fw-semibold">Próximo →</button>
      </div>
    </form>
    <script>
    document.getElementById("btn-show-pass").addEventListener("click", function(){
      var f = document.getElementById("db_pass");
      f.type = f.type === "password" ? "text" : "password";
      this.innerHTML = f.type === "password" ? \'<i class="bi bi-eye"></i>\' : \'<i class="bi bi-eye-slash"></i>\';
    });

    document.getElementById("btn-test-db").addEventListener("click", function(){
      var spin = document.getElementById("test-spin");
      var ic   = document.getElementById("test-ic");
      var box  = document.getElementById("test-result");
      var btn  = this;
      btn.disabled = true;
      spin.classList.remove("d-none"); ic.classList.add("d-none");
      box.innerHTML = "";

      var fd = new FormData(document.getElementById("db-form"));
      fd.set("ajax_action", "test_db");

      fetch("", { method: "POST", body: fd })
        .then(r => r.json())
        .then(function(res){
          btn.disabled = false;
          spin.classList.add("d-none"); ic.classList.remove("d-none");
          var cls = res.ok ? "success" : "danger";
          var ico = res.ok ? "check-circle-fill" : "x-circle-fill";
          box.innerHTML = \'<div class="alert alert-\' + cls + \' small py-2 d-flex align-items-center gap-2">\' +
            \'<i class="bi bi-\' + ico + \'"></i><span>\' + res.message + "</span></div>";
        })
        .catch(function(){
          btn.disabled = false;
          spin.classList.add("d-none"); ic.classList.remove("d-none");
          box.innerHTML = \'<div class="alert alert-danger small py-2">Erro ao testar conexão.</div>\';
        });
    });
    </script>';
}

// ── STEP 1: Requirements ──────────────────────────────────────────
else {
    $checks   = checkRequirements();
    $allOk    = !in_array(false, array_column($checks, 'ok'));

    echo '<p class="text-muted small mb-3">Verificando se o servidor atende aos requisitos mínimos...</p>';
    echo '<div class="mb-4">';
    foreach ($checks as $c) {
        $icon  = $c['ok']
            ? '<i class="bi bi-check-circle-fill text-success fs-5"></i>'
            : '<i class="bi bi-x-circle-fill text-danger fs-5"></i>';
        $extra = $c['value'] ? '<span class="badge bg-secondary-subtle text-secondary ms-auto">'.$c['value'].'</span>' : '';
        echo '<div class="req-item">' . $icon . '<span>' . $c['label'] . '</span>' . $extra . '</div>';
    }
    echo '</div>';

    if ($allOk) {
        echo '<div class="alert alert-success small"><i class="bi bi-check-circle me-1"></i>Todos os requisitos atendidos!</div>';
        echo '<form method="post">
          <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">
            Iniciar instalação →
          </button>
        </form>';
    } else {
        echo '<div class="alert alert-danger small">
          <i class="bi bi-exclamation-triangle me-1"></i>
          Corrija os itens marcados em vermelho antes de continuar.
        </div>
        <button class="btn btn-outline-secondary w-100" onclick="location.reload()">
          <i class="bi bi-arrow-clockwise me-1"></i>Verificar novamente
        </button>';
    }
}

$body = ob_get_clean();
echo page(match($step) {
    1 => 'Verificação de Requisitos',
    2 => 'Configuração do Banco de Dados',
    3 => 'Chave de Licença',
    4 => 'Configurações do Sistema',
    5 => 'Revisar e Instalar',
    default => 'Instalação'
}, $body, $step);
