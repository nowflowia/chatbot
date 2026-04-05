<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Link Expirado</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    body { background: linear-gradient(135deg,#0f172a,#1e3a5f); min-height:100vh; display:flex; align-items:center; justify-content:center; }
    .card { border-radius:20px; box-shadow:0 32px 64px rgba(0,0,0,.4); border:none; max-width:420px; width:100%; }
  </style>
</head>
<body>
<div class="card p-4 text-center">
  <div style="font-size:3rem;color:#ef4444;" class="mb-3"><i class="bi bi-clock-history"></i></div>
  <h5 class="fw-bold text-dark mb-2">Link expirado ou inválido</h5>
  <p class="text-muted small mb-4">
    Este link de convite não é mais válido. Peça ao administrador que envie um novo convite para sua conta.
  </p>
  <a href="<?= url('login') ?>" class="btn btn-primary rounded-3 fw-semibold">
    <i class="bi bi-arrow-left me-2"></i>Ir para o login
  </a>
</div>
</body>
</html>
