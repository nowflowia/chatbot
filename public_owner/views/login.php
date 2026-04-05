<?php
if (!empty($_SESSION['owner_logged_in'])) {
    header('Location: ?page=dashboard'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (ownerLogin(trim($_POST['username'] ?? ''), $_POST['password'] ?? '')) {
        $_SESSION['owner_logged_in'] = true;
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        header('Location: ?page=dashboard'); exit;
    }
    $error = 'Usuário ou senha incorretos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login | <?= APP_NAME ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body { background:linear-gradient(135deg,#0f172a,#1e3a5f);min-height:100vh;display:flex;align-items:center;justify-content:center; }
.card { border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.4); }
</style>
</head>
<body>
<div style="width:100%;max-width:380px;padding:1rem;">
  <div class="card border-0 p-4">
    <div class="text-center mb-4">
      <div style="width:52px;height:52px;background:#3b82f6;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;">
        <i class="bi bi-shield-lock-fill text-white fs-4"></i>
      </div>
      <h5 class="fw-bold mb-0"><?= APP_NAME ?></h5>
      <small class="text-muted">Painel do Owner</small>
    </div>
    <?php if ($error): ?>
    <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post">
      <div class="mb-3">
        <label class="form-label fw-semibold small">Usuário</label>
        <input type="text" name="username" class="form-control" autofocus>
      </div>
      <div class="mb-4">
        <label class="form-label fw-semibold small">Senha</label>
        <input type="password" name="password" class="form-control">
      </div>
      <button type="submit" class="btn btn-primary w-100 fw-semibold">Entrar</button>
    </form>
  </div>
</div>
</body>
</html>
