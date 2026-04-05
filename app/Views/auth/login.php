<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | <?= e($appName) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1rem;
    }
    .login-wrapper { width: 100%; max-width: 420px; }
    .login-card {
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 32px 64px rgba(0, 0, 0, .4);
      padding: 2.5rem;
    }
    .brand-icon {
      width: 60px; height: 60px;
      background: linear-gradient(135deg, #3b82f6, #1d4ed8);
      border-radius: 16px;
      display: flex; align-items: center; justify-content: center;
      color: #fff; font-size: 1.6rem;
      margin: 0 auto 1.25rem;
      box-shadow: 0 8px 20px rgba(59,130,246,.4);
    }
    .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15); }
    .btn-login {
      background: linear-gradient(135deg, #3b82f6, #1d4ed8);
      border: none; color: #fff; font-weight: 700;
      padding: .75rem;
      transition: opacity .2s;
    }
    .btn-login:hover { opacity: .9; color: #fff; }
    .btn-login:disabled { opacity: .6; }
    .divider { border-top: 1px solid #e2e8f0; margin: 1.5rem 0; }
  </style>
</head>
<body>
<div class="login-wrapper">
  <div class="login-card">
    <div class="text-center mb-4">
      <?php if (!empty($logoPath)): ?>
        <img src="<?= url($logoPath) ?>" alt="<?= e($appName) ?>"
             style="max-height:70px;max-width:240px;object-fit:contain;margin:0 auto 1.25rem;display:block;">
      <?php else: ?>
        <div class="brand-icon"><i class="bi bi-chat-dots-fill"></i></div>
        <h4 class="fw-bold text-dark mb-1"><?= e($appName) ?></h4>
      <?php endif; ?>
      <p class="text-muted small mb-0">Atendimento via WhatsApp</p>
    </div>

    <div id="alert-box"></div>

    <form id="login-form" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label fw-semibold text-dark small">E-mail</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
          <input type="email" name="email" id="email"
                 class="form-control border-start-0 ps-0"
                 placeholder="seu@email.com" required autofocus
                 value="<?= e(old('email', '')) ?>">
        </div>
        <div class="invalid-feedback" id="err-email"></div>
      </div>

      <div class="mb-4">
        <div class="d-flex justify-content-between">
          <label class="form-label fw-semibold text-dark small">Senha</label>
          <a href="<?= url('forgot-password') ?>" class="small text-muted text-decoration-none">Esqueci a senha</a>
        </div>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
          <input type="password" name="password" id="password"
                 class="form-control border-start-0 border-end-0 ps-0"
                 placeholder="••••••••" required>
          <button type="button" class="input-group-text bg-light border-start-0" id="toggle-pwd">
            <i class="bi bi-eye text-muted" id="pwd-icon"></i>
          </button>
        </div>
        <div class="invalid-feedback" id="err-password"></div>
      </div>

      <button type="submit" class="btn btn-login w-100 rounded-3" id="btn-login">
        <span class="spinner-border spinner-border-sm d-none me-2" id="spinner" role="status"></span>
        <span id="btn-text">Entrar</span>
      </button>
    </form>
  </div>

  <div class="text-center mt-3">
    <small class="text-white-50"><?= e($appName) ?> &mdash; Powered by NowFlow | v1.0.1</small>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle password visibility
document.getElementById('toggle-pwd').addEventListener('click', function () {
  const inp = document.getElementById('password');
  const ico = document.getElementById('pwd-icon');
  if (inp.type === 'password') {
    inp.type = 'text';
    ico.className = 'bi bi-eye-slash text-muted';
  } else {
    inp.type = 'password';
    ico.className = 'bi bi-eye text-muted';
  }
});

// Login form submit
document.getElementById('login-form').addEventListener('submit', function (e) {
  e.preventDefault();
  clearErrors();

  const btn     = document.getElementById('btn-login');
  const spinner = document.getElementById('spinner');
  const btnText = document.getElementById('btn-text');

  btn.disabled = true;
  spinner.classList.remove('d-none');
  btnText.textContent = 'Entrando...';

  const fd = new FormData(this);

  fetch('<?= url('login') ?>', {
    method: 'POST',
    body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false;
      spinner.classList.add('d-none');
      btnText.textContent = 'Entrar';

      if (res.success) {
        btnText.textContent = 'Redirecionando...';
        window.location.href = res.data.redirect || '<?= url('admin/dashboard') ?>';
      } else {
        showAlert(res.message || 'Erro ao fazer login.', 'danger');

        if (res.errors) {
          Object.entries(res.errors).forEach(([field, msgs]) => {
            const el = document.getElementById('err-' + field);
            const inp = document.querySelector('[name="' + field + '"]');
            if (el && msgs.length) {
              el.textContent = msgs[0];
              el.style.display = 'block';
              if (inp) inp.classList.add('is-invalid');
            }
          });
        }
      }
    })
    .catch(() => {
      btn.disabled = false;
      spinner.classList.add('d-none');
      btnText.textContent = 'Entrar';
      showAlert('Erro de conexão. Tente novamente.', 'danger');
    });
});

function showAlert(msg, type) {
  document.getElementById('alert-box').innerHTML =
    '<div class="alert alert-' + type + ' d-flex align-items-center gap-2 py-2 mb-3">' +
    '<i class="bi bi-' + (type === 'danger' ? 'exclamation-triangle-fill' : 'check-circle-fill') + '"></i>' +
    '<span>' + msg + '</span></div>';
}

function clearErrors() {
  document.getElementById('alert-box').innerHTML = '';
  document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
  document.querySelectorAll('.invalid-feedback').forEach(el => { el.textContent = ''; el.style.display = 'none'; });
}
</script>
</body>
</html>
