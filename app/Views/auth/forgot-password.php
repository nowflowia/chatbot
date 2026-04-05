<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recuperar Senha | <?= e($appName) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #0f172a, #1e3a5f);
      min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem;
    }
    .card { border-radius: 20px; box-shadow: 0 32px 64px rgba(0,0,0,.4); border: none; max-width: 420px; width: 100%; }
    .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15); }
  </style>
</head>
<body>
<div class="card p-4">
  <div class="text-center mb-4">
    <div style="width:56px;height:56px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:14px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;margin:0 auto .75rem;">
      <i class="bi bi-key-fill"></i>
    </div>
    <h5 class="fw-bold text-dark mb-1">Recuperar senha</h5>
    <p class="text-muted small mb-0">Informe seu e-mail e enviaremos as instruções para redefinir sua senha.</p>
  </div>

  <div id="alert-box"></div>

  <form id="forgot-form" novalidate>
    <?= csrf_field() ?>
    <div class="mb-4">
      <label class="form-label fw-semibold small">E-mail cadastrado</label>
      <div class="input-group">
        <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
        <input type="email" name="email" class="form-control border-start-0 ps-0"
               placeholder="seu@email.com" required autofocus>
      </div>
      <div class="invalid-feedback" id="err-email"></div>
    </div>

    <button type="submit" class="btn w-100 fw-bold py-2 rounded-3 mb-3" id="btn-submit"
            style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border:none;">
      <span class="spinner-border spinner-border-sm d-none me-2" id="spinner"></span>
      <i class="bi bi-send me-2" id="btn-icon"></i>
      <span id="btn-text">Enviar instruções</span>
    </button>

    <div class="text-center">
      <a href="<?= url('login') ?>" class="text-muted small text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i>Voltar ao login
      </a>
    </div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('forgot-form').addEventListener('submit', function (e) {
  e.preventDefault();
  clearErrors();

  const btn  = document.getElementById('btn-submit');
  const spin = document.getElementById('spinner');
  const icon = document.getElementById('btn-icon');
  const text = document.getElementById('btn-text');

  btn.disabled = true;
  spin.classList.remove('d-none');
  icon.className = 'd-none';
  text.textContent = 'Enviando...';

  fetch('<?= url('forgot-password') ?>', {
    method: 'POST',
    body: new FormData(this),
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false;
      spin.classList.add('d-none');
      icon.className = 'bi bi-send me-2';
      text.textContent = 'Enviar instruções';

      const type = res.success ? 'success' : 'danger';
      document.getElementById('alert-box').innerHTML =
        '<div class="alert alert-' + type + ' d-flex align-items-center gap-2 py-2 mb-3">' +
        '<i class="bi bi-' + (res.success ? 'check-circle-fill' : 'exclamation-triangle-fill') + '"></i>' +
        '<span>' + res.message + '</span></div>';

      if (res.success) {
        this.querySelector('[name="email"]').value = '';
      }
      if (res.errors) {
        Object.entries(res.errors).forEach(([field, msgs]) => {
          const el  = document.getElementById('err-' + field);
          const inp = document.querySelector('[name="' + field + '"]');
          if (el && msgs.length) { el.textContent = msgs[0]; el.style.display = 'block'; }
          if (inp) inp.classList.add('is-invalid');
        });
      }
    })
    .catch(() => {
      btn.disabled = false;
      spin.classList.add('d-none');
      icon.className = 'bi bi-send me-2';
      text.textContent = 'Enviar instruções';
      document.getElementById('alert-box').innerHTML =
        '<div class="alert alert-danger">Erro de conexão. Tente novamente.</div>';
    });
});

function clearErrors() {
  document.getElementById('alert-box').innerHTML = '';
  document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
  document.querySelectorAll('.invalid-feedback').forEach(el => { el.textContent = ''; el.style.display = 'none'; });
}
</script>
</body>
</html>
