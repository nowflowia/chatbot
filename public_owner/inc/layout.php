<?php
function layoutStart(string $title = ''): void { ob_start(); }

function layoutEnd(string $title = '', string $page = ''): void
{
    $content = ob_get_clean();
    $appName = APP_NAME;
    $flash_success = getFlash('success');
    $flash_error   = getFlash('error');
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title ?: $appName) ?> | <?= e($appName) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body { background:#f1f5f9; }
.sidebar { width:220px;min-height:100vh;background:#0f172a;position:fixed;top:0;left:0;z-index:100; }
.sidebar a { color:#94a3b8;text-decoration:none;display:flex;align-items:center;gap:8px;padding:10px 20px;font-size:.88rem; }
.sidebar a:hover,.sidebar a.active { color:#f1f5f9;background:rgba(255,255,255,.07); }
.sidebar .brand { color:#f1f5f9;font-weight:700;font-size:1rem;padding:20px;border-bottom:1px solid rgba(255,255,255,.08); }
.main { margin-left:220px;padding:28px; }
.page-title { font-size:1.25rem;font-weight:700;color:#0f172a;margin-bottom:20px; }
.stat-card { background:#fff;border-radius:12px;padding:20px;box-shadow:0 1px 4px rgba(0,0,0,.06); }
</style>
</head>
<body>
<div class="sidebar">
  <div class="brand"><i class="bi bi-shield-lock-fill me-2 text-primary"></i><?= e($appName) ?></div>
  <nav class="mt-2">
    <a href="?page=dashboard" class="<?= $page==='dashboard'?'active':'' ?>"><i class="bi bi-grid"></i> Dashboard</a>
    <a href="?page=licenses" class="<?= $page==='licenses'?'active':'' ?>"><i class="bi bi-key"></i> Licenças</a>
    <a href="?page=logs" class="<?= $page==='logs'?'active':'' ?>"><i class="bi bi-journal-text"></i> Logs</a>
    <a href="?page=logout" class="mt-4" style="color:#ef4444"><i class="bi bi-box-arrow-right"></i> Sair</a>
  </nav>
</div>
<div class="main">
  <?php if ($flash_success): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= e($flash_success) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= e($flash_error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>
  <?= $content ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php } ?>
