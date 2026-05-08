<?php \Core\View::section('title') ?>META — Configurações<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>META Ads — Configurações<?php \Core\View::endSection() ?>
<?php \Core\View::section('content') ?>
<?php
$s  = $settings ?? [];
$st = $s['status'] ?? 'inactive';
$aiOk = $aiConfigured ?? false;
$stMap = [
    'active'   => ['#dcfce7','#166534','#16a34a','Conectado'],
    'inactive' => ['#f1f5f9','#475569','#94a3b8','Não testado'],
    'error'    => ['#fee2e2','#991b1b','#dc2626','Erro na API'],
];
[$stBg,$stColor,$stDot,$stLabel] = $stMap[$st] ?? $stMap['inactive'];
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-0">META Ads — Integração API</h5>
    <small class="text-muted">Conecte a Meta Business Suite para criar e monitorar campanhas de Facebook e Instagram</small>
  </div>
  <div class="d-flex align-items-center gap-2 px-3 py-2 rounded-pill"
       style="background:<?= $stBg ?>;color:<?= $stColor ?>">
    <span style="width:8px;height:8px;background:<?= $stDot ?>;border-radius:50%;display:inline-block"></span>
    <span class="fw-semibold small"><?= $stLabel ?></span>
    <?php if (!empty($s['last_tested_at'])): ?>
    <span class="opacity-75" style="font-size:.72rem;">— <?= date('d/m H:i', strtotime($s['last_tested_at'])) ?></span>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($s['ad_account_id'])): ?>
<div class="alert alert-info d-flex gap-3 align-items-center py-3 mb-4" style="border-left:4px solid #1877f2">
  <svg width="28" height="28" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
    <circle cx="18" cy="18" r="18" fill="#1877F2"/>
    <path d="M25 18c0-3.866-3.134-7-7-7s-7 3.134-7 7c0 3.492 2.558 6.39 5.906 6.921V20.25h-1.778V18h1.778v-1.543c0-1.755 1.046-2.723 2.643-2.723.766 0 1.567.137 1.567.137v1.723h-.882c-.869 0-1.14.54-1.14 1.094V18h1.938l-.31 2.25H19.094v4.671C22.442 24.39 25 21.492 25 18z" fill="white"/>
  </svg>
  <div>
    <div class="fw-semibold">Conta de Anúncios conectada</div>
    <div class="small text-muted">Ad Account: <code><?= e($s['ad_account_id']) ?></code>
      <?= !empty($s['business_id']) ? ' · Business: <code>' . e($s['business_id']) . '</code>' : '' ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- AI Config notice -->
<div class="alert d-flex gap-3 align-items-center py-3 mb-4 <?= $aiOk ? 'alert-success' : 'alert-warning' ?>"
     style="border-left:4px solid <?= $aiOk ? '#16a34a' : '#d97706' ?>">
  <i class="bi <?= $aiOk ? 'bi-robot text-success' : 'bi-exclamation-triangle-fill text-warning' ?> fs-5 flex-shrink-0"></i>
  <div class="flex-grow-1">
    <div class="fw-semibold">
      IA Claude (Anthropic) —
      <?= $aiOk ? 'API Key configurada' : 'API Key não configurada' ?>
    </div>
    <div class="small text-muted">
      O agente META usa exclusivamente <strong>Claude (Anthropic)</strong> para criar estratégias e campanhas.
      <?php if (!$aiOk): ?>
        Configure sua chave antes de usar o agente.
      <?php endif; ?>
    </div>
  </div>
  <a href="<?= url('admin/settings?tab=ai') ?>" class="btn btn-sm <?= $aiOk ? 'btn-outline-success' : 'btn-warning' ?> fw-semibold flex-shrink-0">
    <i class="bi bi-gear me-1"></i><?= $aiOk ? 'Ver config' : 'Configurar agora' ?>
  </a>
</div>

<div class="row g-4">

  <!-- Formulário de configuração -->
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0 pt-3 px-4 pb-0">
        <h6 class="fw-bold mb-0">Credenciais da API</h6>
        <p class="text-muted small mt-1 mb-0">Obtenha em <strong>developers.facebook.com → Meus Apps → Marketing API</strong></p>
      </div>
      <div class="card-body px-4 pb-4 pt-3">
        <div id="meta-alert"></div>
        <form id="meta-form" novalidate>
          <?= csrf_field() ?>
          <div class="row g-3">

            <div class="col-md-6">
              <label class="form-label fw-semibold small">App ID <span class="text-danger">*</span></label>
              <input type="text" name="app_id" class="form-control font-monospace"
                     value="<?= e($s['app_id'] ?? '') ?>" placeholder="123456789012345">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small">App Secret</label>
              <input type="password" name="app_secret" class="form-control font-monospace"
                     value="<?= e($s['app_secret'] ?? '') ?>" placeholder="••••••••••••••••">
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold small">Access Token <span class="text-danger">*</span>
                <span class="badge bg-warning text-dark ms-1 fw-normal">ads_management permission</span>
              </label>
              <textarea name="access_token" class="form-control font-monospace" rows="2"
                        placeholder="EAAxxxxxx..."><?= e($s['access_token'] ?? '') ?></textarea>
              <div class="form-text">Token com permissão <code>ads_management</code>. Gere em Graph API Explorer ou via SDK.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Ad Account ID <span class="text-danger">*</span></label>
              <input type="text" name="ad_account_id" class="form-control font-monospace"
                     value="<?= e($s['ad_account_id'] ?? '') ?>" placeholder="act_1234567890">
              <div class="form-text">Formato: <code>act_XXXXXXXXX</code></div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Business Manager ID</label>
              <input type="text" name="business_id" class="form-control font-monospace"
                     value="<?= e($s['business_id'] ?? '') ?>" placeholder="1234567890">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Facebook Page ID</label>
              <input type="text" name="page_id" class="form-control font-monospace"
                     value="<?= e($s['page_id'] ?? '') ?>" placeholder="1234567890">
              <div class="form-text">Página vinculada aos anúncios.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Instagram Actor ID</label>
              <input type="text" name="instagram_actor_id" class="form-control font-monospace"
                     value="<?= e($s['instagram_actor_id'] ?? '') ?>" placeholder="17841400000000000">
              <div class="form-text">ID da conta IG usada nos anúncios.</div>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold small">Versão da API</label>
              <select name="api_version" class="form-select">
                <?php foreach (['v21.0','v20.0','v19.0','v18.0'] as $v): ?>
                <option value="<?= $v ?>" <?= ($s['api_version'] ?? 'v21.0') === $v ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
              </select>
            </div>

          </div>
          <div class="d-flex gap-2 mt-4">
            <button type="button" class="btn btn-primary fw-semibold px-4" onclick="saveMetaSettings()">
              <span class="spinner-border spinner-border-sm d-none me-1" id="save-spin"></span>
              <i class="bi bi-floppy me-1" id="save-icon"></i> Salvar
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="testMetaConn()">
              <span class="spinner-border spinner-border-sm d-none me-1" id="test-spin"></span>
              <i class="bi bi-wifi me-1" id="test-icon"></i> Testar Conexão
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Guia -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-body px-4 py-3">
        <h6 class="fw-bold mb-2"><i class="bi bi-book me-2 text-primary"></i>Passo a passo</h6>
        <ol class="small text-muted ps-3 mb-0" style="line-height:2;">
          <li>Acesse <strong>developers.facebook.com</strong></li>
          <li>Crie ou selecione um App → tipo <strong>Business</strong></li>
          <li>Adicione o produto <strong>Marketing API</strong></li>
          <li>Gere um token com <code>ads_management</code></li>
          <li>Copie o <strong>Ad Account ID</strong> em <em>Gerenciador de Anúncios → Configurações</em></li>
          <li>Cole os dados aqui e clique em <strong>Testar Conexão</strong></li>
        </ol>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-body px-4 py-3">
        <h6 class="fw-bold mb-2"><i class="bi bi-shield-check text-success me-2"></i>Permissões necessárias</h6>
        <div class="d-flex flex-column gap-1">
          <?php foreach (['ads_management','ads_read','business_management','pages_read_engagement','instagram_basic'] as $perm): ?>
          <code class="bg-light border rounded px-2 py-1 small"><?= $perm ?></code>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

</div>

<?php \Core\View::endSection() ?>
<?php \Core\View::section('scripts') ?>
<script>
const META_ADMIN = {
  save: '<?= url('admin/meta/settings') ?>',
  test: '<?= url('admin/meta/test') ?>',
};
function saveMetaSettings() {
  toggle('save', true);
  document.getElementById('meta-alert').innerHTML = '';
  fetch(META_ADMIN.save, { method:'POST', body: new FormData(document.getElementById('meta-form')), headers:{'X-Requested-With':'XMLHttpRequest'} })
    .then(r=>r.json()).then(res=>{ toggle('save',false); Toast.show(res.message, res.success?'success':'error'); })
    .catch(()=>{ toggle('save',false); Toast.show('Erro de conexão.','error'); });
}
function testMetaConn() {
  toggle('test', true);
  document.getElementById('meta-alert').innerHTML = '';
  const fd = new FormData(); fd.append('_csrf_token','<?= csrf_token() ?>');
  fetch(META_ADMIN.test, { method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'} })
    .then(r=>r.json()).then(res=>{
      toggle('test',false);
      const cls = res.success ? 'success' : 'danger';
      document.getElementById('meta-alert').innerHTML =
        `<div class="alert alert-${cls} py-2 small d-flex gap-2 mb-3"><i class="bi bi-${res.success?'check-circle-fill':'exclamation-triangle-fill'}"></i>${escHtml(res.message)}</div>`;
      if (res.success) setTimeout(()=>location.reload(), 1500);
    })
    .catch(()=>{ toggle('test',false); Toast.show('Erro de conexão.','error'); });
}
function toggle(id, loading) {
  document.getElementById(id+'-spin').classList.toggle('d-none', !loading);
  document.getElementById(id+'-icon').classList.toggle('d-none', loading);
}
function escHtml(s) { const d=document.createElement('div'); d.appendChild(document.createTextNode(String(s))); return d.innerHTML; }
</script>
<?php \Core\View::endSection() ?>
