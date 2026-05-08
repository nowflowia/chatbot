<?php \Core\View::section('title') ?>Instagram — Configurações<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>Instagram — Configurações<?php \Core\View::endSection() ?>
<?php \Core\View::section('content') ?>

<?php
$s    = $settings ?? [];
$st   = $s['status'] ?? 'inactive';
$stMap = [
  'active'   => ['#dcfce7','#166534','#16a34a','Conectado'],
  'inactive' => ['#f1f5f9','#475569','#94a3b8','Não testado'],
  'error'    => ['#fee2e2','#991b1b','#dc2626','Erro'],
];
[$stBg,$stColor,$stDot,$stLabel] = $stMap[$st] ?? $stMap['inactive'];
?>

<?php if (!empty($oauthOk)): ?>
<div class="alert alert-success d-flex gap-2 align-items-center py-2 mb-4">
  <i class="bi bi-check-circle-fill"></i> <?= e($oauthOk) ?>
</div>
<?php endif; ?>
<?php if (!empty($oauthError)): ?>
<div class="alert alert-danger d-flex gap-2 align-items-center py-2 mb-4">
  <i class="bi bi-exclamation-triangle-fill"></i> <?= e($oauthError) ?>
</div>
<?php endif; ?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-0 text-dark">Instagram — Integração API</h5>
    <small class="text-muted">Conecte sua conta Instagram Business para publicar e receber dados</small>
  </div>
  <div class="d-flex align-items-center gap-2 px-3 py-2 rounded-pill"
       style="background:<?= $stBg ?>;color:<?= $stColor ?>;">
    <span style="width:8px;height:8px;border-radius:50%;background:<?= $stDot ?>;display:inline-block;"></span>
    <span class="fw-semibold small"><?= $stLabel ?></span>
    <?php if (!empty($s['last_tested_at'])): ?>
    <span class="ms-1 opacity-75" style="font-size:.72rem;">
      — testado <?= date('d/m H:i', strtotime($s['last_tested_at'])) ?>
    </span>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($s['instagram_account_id'])): ?>
<div class="alert alert-info d-flex gap-3 align-items-center py-3 mb-4" style="border-left:4px solid #0ea5e9;">
  <i class="bi bi-instagram fs-4" style="color:#e1306c;"></i>
  <div>
    <div class="fw-semibold">Conta conectada</div>
    <div class="small text-muted">Instagram Account ID: <code><?= e($s['instagram_account_id']) ?></code>
      <?= !empty($s['page_id']) ? '· Page ID: <code>' . e($s['page_id']) . '</code>' : '' ?>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="row g-4">

  <!-- ── Conectar via OAuth ── -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body p-4">
        <h6 class="fw-bold mb-1">
          <i class="bi bi-instagram me-2" style="color:#e1306c;"></i>Conectar com Facebook/Instagram
        </h6>
        <p class="text-muted small mb-3">
          Autorize o app Facebook para obter o token automaticamente.
          Requer App ID e App Secret configurados abaixo.
        </p>
        <a href="<?= url('admin/instagram/oauth/start') ?>"
           class="btn w-100 fw-semibold mb-2"
           style="background:linear-gradient(135deg,#833ab4,#fd1d1d,#fcb045);color:#fff;">
          <i class="bi bi-instagram me-2"></i>Conectar via Facebook OAuth
        </a>
        <p class="text-muted" style="font-size:.72rem;">
          Permissões solicitadas: <code>instagram_basic</code>, <code>instagram_content_publish</code>,
          <code>pages_read_engagement</code>
        </p>
      </div>
    </div>
  </div>

  <!-- ── Configurações manuais ── -->
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-bottom-0 pt-3 px-4 pb-0">
        <h6 class="fw-bold mb-0">Configurações da API</h6>
        <p class="text-muted small mb-0">Ou preencha manualmente com seus dados do Meta for Developers</p>
      </div>
      <div class="card-body px-4 pb-4 pt-3">
        <div id="ig-alert"></div>
        <form id="ig-form" novalidate>
          <?= csrf_field() ?>
          <div class="row g-3">

            <div class="col-md-6">
              <label class="form-label fw-semibold small">App ID <span class="text-danger">*</span></label>
              <input type="text" name="app_id" class="form-control font-monospace"
                     placeholder="123456789012345"
                     value="<?= e($s['app_id'] ?? '') ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">App Secret</label>
              <input type="password" name="app_secret" class="form-control font-monospace"
                     placeholder="••••••••••••••••"
                     value="<?= e($s['app_secret'] ?? '') ?>">
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold small">Access Token (Long-lived) <span class="text-danger">*</span></label>
              <textarea name="access_token" class="form-control font-monospace" rows="2"
                        placeholder="EAAxxxxxx..."><?= e($s['access_token'] ?? '') ?></textarea>
              <div class="form-text">Token de longa duração (~60 dias). Obtenha em <strong>Meta for Developers → Graph API Explorer</strong>.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Instagram Account ID <span class="text-danger">*</span></label>
              <input type="text" name="instagram_account_id" class="form-control font-monospace"
                     placeholder="17841400000000000"
                     value="<?= e($s['instagram_account_id'] ?? '') ?>">
              <div class="form-text">ID da conta Instagram Business.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Page ID (Facebook)</label>
              <input type="text" name="page_id" class="form-control font-monospace"
                     placeholder="123456789"
                     value="<?= e($s['page_id'] ?? '') ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Webhook Verify Token</label>
              <input type="text" name="webhook_verify_token" class="form-control font-monospace"
                     placeholder="meu_token_secreto"
                     value="<?= e($s['webhook_verify_token'] ?? '') ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Versão da API</label>
              <select name="api_version" class="form-select">
                <?php foreach (['v21.0','v20.0','v19.0','v18.0'] as $v): ?>
                <option value="<?= $v ?>" <?= ($s['api_version'] ?? 'v21.0') === $v ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
              </select>
            </div>

          </div>
          <div class="d-flex gap-2 mt-4">
            <button type="button" class="btn btn-primary fw-semibold px-4" onclick="saveIgSettings()">
              <span class="spinner-border spinner-border-sm d-none me-1" id="save-spin"></span>
              <i class="bi bi-floppy me-1" id="save-icon"></i> Salvar
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="testIgConnection()">
              <span class="spinner-border spinner-border-sm d-none me-1" id="test-spin"></span>
              <i class="bi bi-wifi me-1" id="test-icon"></i> Testar Conexão
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ── Como obter as credenciais ── -->
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-body px-4 py-3">
        <h6 class="fw-bold mb-2"><i class="bi bi-book me-2 text-info"></i>Como obter as credenciais</h6>
        <div class="row g-4 small text-muted">
          <div class="col-md-4">
            <div class="fw-semibold text-dark mb-1">1. Criar App no Meta</div>
            <ol class="ps-3 mb-0" style="line-height:1.9;">
              <li>Acesse <strong>developers.facebook.com</strong></li>
              <li>Crie um App do tipo <strong>Business</strong></li>
              <li>Adicione o produto <strong>Instagram Graph API</strong></li>
              <li>Copie o <strong>App ID</strong> e <strong>App Secret</strong></li>
            </ol>
          </div>
          <div class="col-md-4">
            <div class="fw-semibold text-dark mb-1">2. Obter Access Token</div>
            <ol class="ps-3 mb-0" style="line-height:1.9;">
              <li>Use o <strong>Graph API Explorer</strong></li>
              <li>Selecione seu App e permissões</li>
              <li>Gere o token e troque por <strong>long-lived</strong></li>
              <li>Ou use o botão <strong>OAuth</strong> acima</li>
            </ol>
          </div>
          <div class="col-md-4">
            <div class="fw-semibold text-dark mb-1">3. Obter Instagram Account ID</div>
            <ol class="ps-3 mb-0" style="line-height:1.9;">
              <li>Conecte via OAuth (automático)</li>
              <li>Ou consulte: <code>GET /me/accounts</code></li>
              <li>Na resposta, veja <code>instagram_business_account.id</code></li>
              <li>Cole no campo <strong>Instagram Account ID</strong></li>
            </ol>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

<?php \Core\View::endSection() ?>

<?php \Core\View::section('scripts') ?>
<script>
const IG_ADMIN = {
  save: '<?= url('admin/instagram/settings') ?>',
  test: '<?= url('admin/instagram/test') ?>',
};

function saveIgSettings() {
  const btn  = document.getElementById('save-spin');
  const icon = document.getElementById('save-icon');
  btn.classList.remove('d-none'); icon.classList.add('d-none');
  document.getElementById('ig-alert').innerHTML = '';

  fetch(IG_ADMIN.save, {
    method: 'POST',
    body: new FormData(document.getElementById('ig-form')),
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
  })
    .then(r => r.json())
    .then(res => {
      btn.classList.add('d-none'); icon.classList.remove('d-none');
      Toast.show(res.message, res.success ? 'success' : 'error');
    })
    .catch(() => { btn.classList.add('d-none'); icon.classList.remove('d-none'); Toast.show('Erro de conexão.', 'error'); });
}

function testIgConnection() {
  const btn  = document.getElementById('test-spin');
  const icon = document.getElementById('test-icon');
  btn.classList.remove('d-none'); icon.classList.add('d-none');
  document.getElementById('ig-alert').innerHTML = '';

  const fd = new FormData();
  fd.append('_csrf_token', '<?= csrf_token() ?>');

  fetch(IG_ADMIN.test, {
    method: 'POST',
    body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
  })
    .then(r => r.json())
    .then(res => {
      btn.classList.add('d-none'); icon.classList.remove('d-none');
      const cls = res.success ? 'success' : 'danger';
      document.getElementById('ig-alert').innerHTML =
        `<div class="alert alert-${cls} py-2 small d-flex gap-2 align-items-center mb-3">
           <i class="bi bi-${res.success ? 'check-circle-fill' : 'exclamation-triangle-fill'}"></i>
           ${escHtml(res.message)}
         </div>`;
      if (res.success) setTimeout(() => location.reload(), 1500);
    })
    .catch(() => { btn.classList.add('d-none'); icon.classList.remove('d-none'); Toast.show('Erro de conexão.', 'error'); });
}

function escHtml(s) {
  const d = document.createElement('div');
  d.appendChild(document.createTextNode(String(s)));
  return d.innerHTML;
}
</script>
<?php \Core\View::endSection() ?>
