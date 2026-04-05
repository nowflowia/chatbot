<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Criar Senha | <?= e($appName) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #0f172a, #1e3a5f);
      min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem;
    }
    .card { border-radius: 20px; box-shadow: 0 32px 64px rgba(0,0,0,.4); border: none; max-width: 440px; width: 100%; }
    .strength-bar { height: 4px; border-radius: 2px; transition: all .3s; }
    .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15); }
  </style>
</head>
<body>
<div class="card p-4">
  <div class="text-center mb-4">
    <div style="width:56px;height:56px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);border-radius:14px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;margin:0 auto .75rem;">
      <i class="bi bi-shield-lock-fill"></i>
    </div>
    <h5 class="fw-bold text-dark mb-1">Criar sua senha</h5>
    <p class="text-muted small mb-0">
      Olá, <strong><?= e($invite['user_name'] ?? '') ?></strong>! Defina uma senha segura para acessar o sistema.
    </p>
  </div>

  <div id="alert-box"></div>

  <form id="set-pwd-form" novalidate>
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label fw-semibold small">Nova senha</label>
      <div class="input-group">
        <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
        <input type="password" name="password" id="password"
               class="form-control border-start-0 border-end-0 ps-0"
               placeholder="Mínimo 8 caracteres" required minlength="8"
               oninput="checkStrength(this.value)">
        <button type="button" class="input-group-text bg-light border-start-0" onclick="toggleField('password','pwd-icon1')">
          <i class="bi bi-eye text-muted" id="pwd-icon1"></i>
        </button>
      </div>
      <div class="mt-1 mb-1">
        <div class="strength-bar bg-secondary" id="strength-bar" style="width:0%"></div>
      </div>
      <small class="text-muted" id="strength-text"></small>
      <div class="invalid-feedback" id="err-password"></div>
    </div>

    <div class="mb-4">
      <label class="form-label fw-semibold small">Confirmar senha</label>
      <div class="input-group">
        <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock-fill text-muted"></i></span>
        <input type="password" name="password_confirmation" id="password_confirmation"
               class="form-control border-start-0 border-end-0 ps-0"
               placeholder="Repita a senha" required>
        <button type="button" class="input-group-text bg-light border-start-0" onclick="toggleField('password_confirmation','pwd-icon2')">
          <i class="bi bi-eye text-muted" id="pwd-icon2"></i>
        </button>
      </div>
      <div class="invalid-feedback" id="err-password_confirmation"></div>
    </div>

    <button type="submit" class="btn w-100 fw-bold py-2 rounded-3" id="btn-submit"
            style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);color:#fff;border:none;">
      <span class="spinner-border spinner-border-sm d-none me-2" id="spinner"></span>
      <i class="bi bi-check-circle me-2" id="btn-icon"></i>
      <span id="btn-text">Criar senha e acessar</span>
    </button>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleField(id, iconId) {
  const inp = document.getElementById(id);
  const ico = document.getElementById(iconId);
  if (inp.type === 'password') { inp.type = 'text'; ico.className = 'bi bi-eye-slash text-muted'; }
  else { inp.type = 'password'; ico.className = 'bi bi-eye text-muted'; }
}

function checkStrength(val) {
  const bar  = document.getElementById('strength-bar');
  const text = document.getElementById('strength-text');
  let score  = 0;
  if (val.length >= 8)  score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;

  const levels = [
    { pct: '0%',   cls: 'bg-secondary', label: '' },
    { pct: '25%',  cls: 'bg-danger',    label: 'Muito fraca' },
    { pct: '50%',  cls: 'bg-warning',   label: 'Fraca' },
    { pct: '75%',  cls: 'bg-info',      label: 'Razoável' },
    { pct: '100%', cls: 'bg-success',   label: 'Forte' },
  ];
  const lvl = levels[score] || levels[0];
  bar.style.width = lvl.pct;
  bar.className   = 'strength-bar ' + lvl.cls;
  text.textContent = lvl.label;
}

document.getElementById('set-pwd-form').addEventListener('submit', function (e) {
  e.preventDefault();
  clearErrors();

  const btn  = document.getElementById('btn-submit');
  const spin = document.getElementById('spinner');
  const icon = document.getElementById('btn-icon');
  const text = document.getElementById('btn-text');

  const pwd  = document.getElementById('password').value;
  const conf = document.getElementById('password_confirmation').value;

  if (pwd !== conf) {
    showFieldError('password_confirmation', 'As senhas não conferem.');
    return;
  }

  btn.disabled = true;
  spin.classList.remove('d-none');
  icon.className = 'd-none';
  text.textContent = 'Salvando...';

  const fd = new FormData(this);

  fetch('<?= url('invite/' . $token) ?>', {
    method: 'POST',
    body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        text.textContent = 'Redirecionando...';
        window.location.href = res.data?.redirect || '<?= url('admin/dashboard') ?>';
      } else {
        btn.disabled = false;
        spin.classList.add('d-none');
        icon.className = 'bi bi-check-circle me-2';
        text.textContent = 'Criar senha e acessar';
        showAlert(res.message || 'Erro ao salvar.', 'danger');
        if (res.errors) {
          Object.entries(res.errors).forEach(([field, msgs]) => showFieldError(field, msgs[0]));
        }
      }
    })
    .catch(() => {
      btn.disabled = false;
      spin.classList.add('d-none');
      icon.className = 'bi bi-check-circle me-2';
      text.textContent = 'Criar senha e acessar';
      showAlert('Erro de conexão.', 'danger');
    });
});

function showAlert(msg, type) {
  document.getElementById('alert-box').innerHTML =
    '<div class="alert alert-' + type + ' d-flex align-items-center gap-2 py-2 mb-3">' +
    '<i class="bi bi-exclamation-triangle-fill"></i><span>' + msg + '</span></div>';
}
function showFieldError(field, msg) {
  const el  = document.getElementById('err-' + field);
  const inp = document.querySelector('[name="' + field + '"]');
  if (el)  { el.textContent = msg; el.style.display = 'block'; }
  if (inp) inp.classList.add('is-invalid');
}
function clearErrors() {
  document.getElementById('alert-box').innerHTML = '';
  document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
  document.querySelectorAll('.invalid-feedback').forEach(el => { el.textContent = ''; el.style.display = 'none'; });
}
</script>
</body>
</html>
