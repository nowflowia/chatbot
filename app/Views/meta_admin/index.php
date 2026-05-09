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

    <!-- ── Log Viewer ──────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mt-4">
      <div class="card-header bg-white d-flex align-items-center justify-content-between py-3 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-terminal text-secondary"></i>
          <span class="fw-semibold">Log da aplicação</span>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <div class="btn-group btn-group-sm" role="group">
            <button id="filter-meta" class="btn btn-primary btn-sm" onclick="setFilter('meta')">META / Imagens</button>
            <button id="filter-all"  class="btn btn-outline-secondary btn-sm" onclick="setFilter('all')">Todos</button>
          </div>
          <input type="date" id="log-date" class="form-control form-control-sm" style="width:150px"
                 value="<?= date('Y-m-d') ?>" onchange="loadLogs()">
          <button class="btn btn-sm btn-outline-secondary" onclick="loadLogs()">
            <i class="bi bi-arrow-clockwise"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger" onclick="clearLogs()">
            <i class="bi bi-trash"></i> Limpar
          </button>
        </div>
      </div>
      <div class="card-body p-0">
        <div id="log-output" style="background:#0f172a;color:#e2e8f0;font-family:monospace;font-size:.78rem;
             height:300px;overflow-y:auto;padding:1rem;border-radius:0 0 .5rem .5rem;">
          <span class="text-muted">Carregando logs...</span>
        </div>
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

    <!-- Image Generation -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-body px-4 py-3">
        <h6 class="fw-bold mb-2">
          <i class="bi bi-image-fill text-primary me-2"></i>Geração de Imagens via IA
          <?php if ($openaiOk ?? false): ?>
          <span class="badge bg-success ms-1 fw-normal" style="font-size:.65rem;">Ativo</span>
          <?php else: ?>
          <span class="badge bg-warning text-dark ms-1 fw-normal" style="font-size:.65rem;">Não configurado</span>
          <?php endif; ?>
        </h6>
        <p class="text-muted small mb-3">
          O agente usa <strong>gpt-image-1</strong> (OpenAI) para criar as imagens dos anúncios.
          Claude propõe o conceito visual → você aprova → imagem gerada automaticamente → usada no criativo.
        </p>
        <div id="openai-alert"></div>
        <div class="mb-2">
          <label class="form-label fw-semibold small">OpenAI API Key</label>
          <div class="input-group">
            <input type="password" id="openai-key-input" class="form-control font-monospace"
                   value="<?= e($openaiKey ?? '') ?>" placeholder="sk-proj-...">
            <button class="btn btn-outline-secondary" type="button"
                    onclick="toggleKeyVis()"><i class="bi bi-eye" id="eye-icon"></i></button>
          </div>
          <div class="form-text">Obtenha em <strong>platform.openai.com → API keys</strong></div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold small">Modelo de geração de imagem</label>
          <select id="image-model-select" class="form-select form-select-sm">
            <?php foreach ($imageModels ?? [] as $modelId => $modelLabel): ?>
            <option value="<?= e($modelId) ?>"
              <?= ($openaiModel ?? 'gpt-image-1') === $modelId ? 'selected' : '' ?>>
              <?= e($modelLabel) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn-primary btn-sm w-100 fw-semibold" onclick="saveOpenAiKey()">
          <span class="spinner-border spinner-border-sm d-none me-1" id="oai-spin"></span>
          <i class="bi bi-floppy me-1" id="oai-icon"></i>Salvar configurações de imagem
        </button>
        <div class="mt-3 pt-2 border-top">
          <div class="small text-muted fw-semibold mb-1">Formatos gerados:</div>
          <div class="d-flex flex-column gap-1">
            <div class="small text-muted"><code>1024×1024</code> — Feed quadrado (FB/IG)</div>
            <div class="small text-muted"><code>1024×1536</code> — Portrait (Stories/Reels)</div>
            <div class="small text-muted"><code>1536×1024</code> — Landscape (banners)</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Claude Model Selector -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-body px-4 py-3">
        <h6 class="fw-bold mb-2">
          <i class="bi bi-robot text-primary me-2"></i>Modelo Claude — Agente META
        </h6>
        <p class="text-muted small mb-3">
          Escolha o modelo Claude usado exclusivamente pelo agente META Ads.
          Não afeta o modelo do chat de atendimento.
        </p>
        <div id="model-alert"></div>
        <select id="meta-ai-model" class="form-select form-select-sm mb-3">
          <?php foreach ($aiModels ?? [] as $modelId => $modelLabel): ?>
          <option value="<?= e($modelId) ?>"
            <?= ($settings['ai_model'] ?? 'claude-sonnet-4-6') === $modelId ? 'selected' : '' ?>>
            <?= e($modelLabel) ?>
          </option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-primary btn-sm w-100 fw-semibold" onclick="saveMetaModel()">
          <span class="spinner-border spinner-border-sm d-none me-1" id="model-spin"></span>
          <i class="bi bi-floppy me-1" id="model-icon"></i>Salvar modelo
        </button>

        <hr class="my-3">

        <h6 class="fw-bold mb-2">
          <i class="bi bi-person-badge text-success me-2"></i>Persona do Agente
        </h6>
        <p class="text-muted small mb-2">
          Instruções adicionais que serão concatenadas ao prompt base do agente.
          Use para definir tom, estratégias preferidas, marcas, restrições da empresa, etc.
        </p>
        <div id="persona-alert"></div>
        <textarea id="agent-persona" class="form-control font-monospace mb-2"
                  rows="8" style="font-size:.78rem;"
                  placeholder="Ex: Você é especialista em campanhas para o setor financeiro brasileiro. Sempre priorize objetivo de Leads. Use copy direto, com prova social..."><?= e($settings['agent_persona'] ?? '') ?></textarea>
        <button class="btn btn-success btn-sm w-100 fw-semibold" onclick="savePersona()">
          <span class="spinner-border spinner-border-sm d-none me-1" id="persona-spin"></span>
          <i class="bi bi-floppy me-1" id="persona-icon"></i>Salvar persona
        </button>
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
  save:      '<?= url('admin/meta/settings') ?>',
  test:      '<?= url('admin/meta/test') ?>',
  openaiKey: '<?= url('admin/meta/openai-key') ?>',
  aiModel:   '<?= url('admin/meta/ai-model') ?>',
  persona:   '<?= url('admin/meta/persona') ?>',
  logs:      '<?= url('admin/meta/logs') ?>',
  logsClear: '<?= url('admin/meta/logs/clear') ?>',
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

function saveMetaModel() {
  document.getElementById('model-spin').classList.remove('d-none');
  document.getElementById('model-icon').classList.add('d-none');
  document.getElementById('model-alert').innerHTML = '';
  const fd = new FormData();
  fd.append('_csrf_token', '<?= csrf_token() ?>');
  fd.append('ai_model', document.getElementById('meta-ai-model').value);
  fetch(META_ADMIN.aiModel, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json())
    .then(res => {
      document.getElementById('model-spin').classList.add('d-none');
      document.getElementById('model-icon').classList.remove('d-none');
      if (res.success) {
        document.getElementById('model-alert').innerHTML =
          `<div class="alert alert-success py-2 small mb-2"><i class="bi bi-check-circle-fill me-1"></i>${escHtml(res.message)}</div>`;
      } else {
        Toast.show(res.message, 'error');
      }
    })
    .catch(() => { document.getElementById('model-spin').classList.add('d-none'); document.getElementById('model-icon').classList.remove('d-none'); Toast.show('Erro de conexão.', 'error'); });
}

function savePersona() {
  document.getElementById('persona-spin').classList.remove('d-none');
  document.getElementById('persona-icon').classList.add('d-none');
  document.getElementById('persona-alert').innerHTML = '';
  const fd = new FormData();
  fd.append('_csrf_token', '<?= csrf_token() ?>');
  fd.append('agent_persona', document.getElementById('agent-persona').value);
  fetch(META_ADMIN.persona, { method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'} })
    .then(r => r.json())
    .then(res => {
      document.getElementById('persona-spin').classList.add('d-none');
      document.getElementById('persona-icon').classList.remove('d-none');
      if (res.success) {
        document.getElementById('persona-alert').innerHTML =
          `<div class="alert alert-success py-2 small mb-2"><i class="bi bi-check-circle-fill me-1"></i>${escHtml(res.message)}</div>`;
      } else Toast.show(res.message, 'error');
    })
    .catch(() => { document.getElementById('persona-spin').classList.add('d-none'); document.getElementById('persona-icon').classList.remove('d-none'); Toast.show('Erro de conexão.', 'error'); });
}

function saveOpenAiKey() {
  const spin = document.getElementById('oai-spin');
  const icon = document.getElementById('oai-icon');
  spin.classList.remove('d-none'); icon.classList.add('d-none');
  document.getElementById('openai-alert').innerHTML = '';
  const fd = new FormData();
  fd.append('_csrf_token', '<?= csrf_token() ?>');
  fd.append('api_key', document.getElementById('openai-key-input').value);
  fd.append('image_model', document.getElementById('image-model-select').value);
  fetch(META_ADMIN.openaiKey, { method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'} })
    .then(r=>r.json())
    .then(res=>{
      spin.classList.add('d-none'); icon.classList.remove('d-none');
      if (res.success) {
        document.getElementById('openai-alert').innerHTML =
          `<div class="alert alert-success py-2 small mb-2"><i class="bi bi-check-circle-fill me-1"></i>${escHtml(res.message)}</div>`;
        setTimeout(()=>location.reload(), 1200);
      } else {
        Toast.show(res.message, 'error');
      }
    })
    .catch(()=>{ spin.classList.add('d-none'); icon.classList.remove('d-none'); Toast.show('Erro de conexão.','error'); });
}

function toggleKeyVis() {
  const inp  = document.getElementById('openai-key-input');
  const icon = document.getElementById('eye-icon');
  const isPass = inp.type === 'password';
  inp.type = isPass ? 'text' : 'password';
  icon.className = isPass ? 'bi bi-eye-slash' : 'bi bi-eye';
}

let logFilter = 'meta';
const META_KEYWORDS = ['ImageGen', 'MetaAgent', 'MetaAds', 'Meta API', 'openai', 'gpt-image'];

function setFilter(f) {
  logFilter = f;
  document.getElementById('filter-meta').className = f === 'meta' ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-outline-secondary';
  document.getElementById('filter-all').className  = f === 'all'  ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-outline-secondary';
  renderLogs();
}

let allLogLines = [];

function loadLogs() {
  const date = document.getElementById('log-date').value;
  const out  = document.getElementById('log-output');
  fetch(META_ADMIN.logs + '?date=' + encodeURIComponent(date), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json())
    .then(res => { allLogLines = res.data?.lines ?? []; renderLogs(); })
    .catch(() => { document.getElementById('log-output').innerHTML = '<span style="color:#f87171">Erro ao carregar logs.</span>'; });
}

function renderLogs() {
  const out = document.getElementById('log-output');
  let lines = allLogLines;
  if (logFilter === 'meta') {
    lines = lines.filter(l => META_KEYWORDS.some(k => l.toLowerCase().includes(k.toLowerCase())));
  }
  if (!lines.length) {
    out.innerHTML = '<span style="color:#94a3b8">Nenhum log ' + (logFilter === 'meta' ? 'do META/Imagens' : '') + ' para esta data.</span>';
    return;
  }
  out.innerHTML = lines.map(l => {
    let cls = 'color:#e2e8f0';
    if (l.includes('] error:'))                          cls = 'color:#f87171';
    if (l.includes('] warning:'))                        cls = 'color:#fbbf24';
    if (l.includes('ImageGen') || l.includes('gpt-image')) cls = 'color:#34d399';
    if (l.includes('MetaAgent'))                         cls = 'color:#60a5fa';
    return `<div style="${cls};white-space:pre-wrap;word-break:break-all;margin-bottom:2px">${escHtml(l)}</div>`;
  }).join('');
  out.scrollTop = 0;
}

function clearLogs() {
  if (!confirm('Limpar o log de hoje?')) return;
  const fd = new FormData();
  fd.append('_csrf_token', '<?= csrf_token() ?>');
  fd.append('date', document.getElementById('log-date').value);
  fetch(META_ADMIN.logsClear, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json())
    .then(() => { allLogLines = []; renderLogs(); Toast.show('Log limpo.', 'success'); })
    .catch(() => Toast.show('Erro ao limpar log.', 'error'));
}

document.addEventListener('DOMContentLoaded', loadLogs);
setInterval(loadLogs, 15000);
</script>
<?php \Core\View::endSection() ?>
