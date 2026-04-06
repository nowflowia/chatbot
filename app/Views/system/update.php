<?php \Core\View::section('title') ?>Atualização do Sistema<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>Atualização do Sistema<?php \Core\View::endSection() ?>

<?php \Core\View::section('content') ?>

<div class="row justify-content-center">
  <div class="col-lg-8">

    <!-- Version comparison card -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h6 class="fw-bold mb-0">Versões</h6>
          <button class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2"
                  id="btn-check" onclick="checkStatus()">
            <i class="bi bi-arrow-clockwise" id="check-icon"></i> Verificar atualização
          </button>
        </div>

        <div class="row g-3">
          <!-- Local version -->
          <div class="col-md-6">
            <div class="bg-light rounded p-3">
              <div class="text-muted small fw-semibold mb-2">
                <i class="bi bi-hdd me-1"></i> Versão Instalada
              </div>
              <?php if (!empty($lastCommit['hash'])): ?>
              <code class="text-primary fw-bold fs-6" id="local-hash"><?= e($lastCommit['hash']) ?></code>
              <div class="text-dark small mt-1" id="local-subject"><?= e($lastCommit['subject'] ?? '') ?></div>
              <div class="text-muted mt-1" style="font-size:.72rem;">
                <i class="bi bi-person me-1"></i><span id="local-author"><?= e($lastCommit['author'] ?? '') ?></span>
                &nbsp;·&nbsp;
                <i class="bi bi-clock me-1"></i><span id="local-date"><?= e($lastCommit['date'] ?? '') ?></span>
              </div>
              <?php elseif (!empty($localVersion)): ?>
              <div class="fw-bold text-primary" id="local-hash">v<?= e($localVersion['version'] ?? '?') ?></div>
              <div class="text-muted small mt-1" id="local-subject"><?= e($localVersion['changelog'] ?? '') ?></div>
              <div class="text-muted mt-1" style="font-size:.72rem;" id="local-date"><?= e($localVersion['released_at'] ?? '') ?></div>
              <?php else: ?>
              <div class="text-muted small" id="local-hash">Desconhecida</div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Remote version -->
          <div class="col-md-6">
            <div class="bg-light rounded p-3">
              <div class="text-muted small fw-semibold mb-2">
                <i class="bi bi-github me-1"></i> Versão no Repositório
              </div>
              <div id="remote-loading" class="text-muted small">
                <span class="spinner-border spinner-border-sm me-2"></span>Consultando GitHub…
              </div>
              <div id="remote-info" style="display:none;">
                <code class="text-success fw-bold fs-6" id="remote-hash">—</code>
                <div class="text-dark small mt-1" id="remote-subject">—</div>
                <div class="text-muted mt-1" style="font-size:.72rem;">
                  <i class="bi bi-person me-1"></i><span id="remote-author">—</span>
                  &nbsp;·&nbsp;
                  <i class="bi bi-clock me-1"></i><span id="remote-date">—</span>
                </div>
              </div>
              <div id="remote-error" style="display:none;" class="text-danger small">
                <i class="bi bi-exclamation-triangle me-1"></i><span id="remote-error-msg"></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Status banner -->
        <div id="status-banner" class="mt-3" style="display:none;">
          <div id="status-banner-inner" class="alert py-2 small mb-0 d-flex align-items-center gap-2"></div>
        </div>
      </div>
    </div>

    <!-- Action card -->
    <?php if ($gitAvailable): ?>
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body p-4">
        <h6 class="fw-bold mb-1">Atualizar Sistema</h6>
        <p class="text-muted small mb-3">
          Executa <code>git pull origin main</code> no servidor, baixando e aplicando os commits mais recentes.
          A página recarrega automaticamente após a atualização.
        </p>
        <div id="pull-alert" class="mb-3" style="display:none;"></div>
        <div class="d-flex align-items-center gap-3">
          <button class="btn btn-primary fw-semibold px-4 d-flex align-items-center gap-2"
                  id="btn-pull" onclick="runPull()">
            <span class="spinner-border spinner-border-sm d-none" id="pull-spinner"></span>
            <i class="bi bi-cloud-download-fill" id="pull-icon"></i>
            <span id="pull-btn-text">Atualizar Agora</span>
          </button>
          <small class="text-muted">Recomenda-se backup antes de atualizar.</small>
        </div>
      </div>
    </div>

    <!-- Output log -->
    <div class="card border-0 shadow-sm mb-4" id="output-card" style="display:none;">
      <div class="card-header bg-dark d-flex align-items-center gap-2 py-2 px-3" style="border-radius:.5rem .5rem 0 0;">
        <i class="bi bi-terminal-fill text-success small"></i>
        <span class="text-white small fw-semibold">Saída do Git</span>
      </div>
      <div class="card-body p-0">
        <pre id="git-output"
             style="margin:0;padding:1rem;background:#1e1e1e;color:#d4d4d4;font-size:.8rem;border-radius:0 0 .5rem .5rem;overflow-x:auto;white-space:pre-wrap;word-break:break-all;"></pre>
      </div>
    </div>

    <?php else: ?>

    <!-- Diagnostics -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body p-4">
        <h6 class="fw-bold mb-3 text-warning"><i class="bi bi-exclamation-triangle me-2"></i>Atualização automática não disponível</h6>

        <div class="d-flex flex-column gap-3">

          <!-- Exec check -->
          <div class="d-flex align-items-start gap-3 p-3 rounded"
               style="background:<?= $execEnabled ? '#f0fdf4' : '#fef2f2' ?>">
            <i class="bi <?= $execEnabled ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' ?> fs-5 mt-1 flex-shrink-0"></i>
            <div>
              <div class="fw-semibold small"><?= $execEnabled ? 'Execução de comandos: OK' : 'Execução de comandos: Bloqueada' ?></div>
              <?php if (!$execEnabled): ?>
              <div class="text-muted small mt-1">
                <code>shell_exec</code>, <code>exec</code> e <code>proc_open</code> estão desabilitados.<br>
                No cPanel: <strong>Software → Select PHP Version → aba "Options"</strong> → remova essas funções do campo <code>disable_functions</code>.
              </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Git binary check -->
          <div class="d-flex align-items-start gap-3 p-3 rounded"
               style="background:<?= $gitBin ? '#f0fdf4' : '#fef2f2' ?>">
            <i class="bi <?= $gitBin ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' ?> fs-5 mt-1 flex-shrink-0"></i>
            <div>
              <div class="fw-semibold small"><?= $gitBin ? 'Git encontrado: ' . e($gitBin) : 'Git: não encontrado nos caminhos padrão' ?></div>
              <?php if (!$gitBin): ?>
              <div class="text-muted small mt-1">
                Via SSH, execute <code>which git</code> para obter o caminho correto e adicione ao <code>.env</code>:<br>
                <code>GIT_PATH=/caminho/para/git</code>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Git path from .env -->
          <?php if ($gitPathHint): ?>
          <div class="d-flex align-items-start gap-3 p-3 rounded" style="background:#fef3c7">
            <i class="bi bi-info-circle-fill text-warning fs-5 mt-1 flex-shrink-0"></i>
            <div class="small">
              <strong>GIT_PATH configurado:</strong> <code><?= e($gitPathHint) ?></code><br>
              <span class="text-muted">Este caminho não está acessível pelo processo PHP. Verifique se o arquivo existe e tem permissão de execução.</span>
            </div>
          </div>
          <?php endif; ?>

          <!-- Repo check -->
          <div class="d-flex align-items-start gap-3 p-3 rounded"
               style="background:<?= $hasRepo ? '#f0fdf4' : '#fef2f2' ?>">
            <i class="bi <?= $hasRepo ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' ?> fs-5 mt-1 flex-shrink-0"></i>
            <div>
              <div class="fw-semibold small"><?= $hasRepo ? 'Repositório Git: inicializado' : 'Repositório Git: não encontrado' ?></div>
              <?php if (!$hasRepo): ?>
              <div class="text-muted small mt-1">
                O sistema foi instalado via FTP/upload. Via SSH, inicialize o repositório:<br>
                <code>cd <?= e(dirname(PUBLIC_PATH)) ?></code><br>
                <code>git init && git remote add origin https://github.com/nowflowia/chatbot.git</code><br>
                <code>git fetch && git reset --hard origin/main</code>
              </div>
              <?php endif; ?>
            </div>
          </div>

        </div>

        <!-- Manual update via SSH -->
        <div class="mt-4 p-3 bg-light rounded">
          <div class="fw-semibold small mb-2"><i class="bi bi-terminal me-1"></i>Atualização manual via SSH</div>
          <pre class="mb-0" style="font-size:.8rem;background:transparent;">cd <?= e(dirname(PUBLIC_PATH)) ?>

git pull origin main</pre>
          <div class="text-muted small mt-2">
            Caso dê erro de branch: <code>git branch --set-upstream-to=origin/main master && git pull origin main</code>
          </div>
        </div>
      </div>
    </div>

    <?php endif; ?>

    <!-- Environment info -->
    <div class="card border-0 shadow-sm">
      <div class="card-body p-4">
        <h6 class="fw-bold mb-3 small text-muted text-uppercase">Informações do Ambiente</h6>
        <table class="table table-sm table-borderless mb-0 small">
          <tbody>
            <tr>
              <td class="text-muted" style="width:160px;">PHP</td>
              <td class="fw-semibold"><?= phpversion() ?></td>
            </tr>
            <tr>
              <td class="text-muted">Servidor Web</td>
              <td class="fw-semibold"><?= e($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') ?></td>
            </tr>
            <tr>
              <td class="text-muted">Git</td>
              <td class="fw-semibold"><?= e($gitVersion ?? 'Não disponível') ?></td>
            </tr>
            <tr>
              <td class="text-muted">Diretório</td>
              <td class="fw-semibold"><?= e(dirname(PUBLIC_PATH)) ?></td>
            </tr>
            <tr>
              <td class="text-muted">Repositório Git</td>
              <td class="fw-semibold"><?= $hasRepo ? '<span class="text-success">Inicializado</span>' : '<span class="text-danger">Não inicializado</span>' ?></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<?php \Core\View::endSection() ?>

<?php \Core\View::section('scripts') ?>
<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
<script>
const CSRF = '<?= csrf_token() ?>';

document.addEventListener('DOMContentLoaded', checkStatus);

function checkStatus() {
  const btn  = document.getElementById('btn-check');
  const icon = document.getElementById('check-icon');
  if (btn)  btn.disabled = true;
  if (icon) icon.style.animation = 'spin 1s linear infinite';

  showRemoteLoading();
  hideBanner();

  const fd = new FormData();
  fd.append('_csrf_token', CSRF);

  fetch('<?= url('admin/system-update/status') ?>', {
    method: 'POST', body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
    .then(r => r.json())
    .then(res => {
      if (btn)  btn.disabled = false;
      if (icon) icon.style.animation = '';

      if (!res.success) {
        showRemoteError(res.message || 'Erro ao verificar.');
        return;
      }

      const d = res.data;
      showRemoteCommit(d.remote_commit, d.remote_hash);
      updateLocalCommit(d.last_commit, d.local_hash);

      if (d.up_to_date) {
        showBanner('success', '<i class="bi bi-check-circle-fill"></i> Sistema atualizado — você está na versão mais recente.');
      } else {
        const n = d.pending || '';
        const msg = n ? `${n} commit(s) disponível(is) para atualização.` : 'Nova versão disponível no repositório.';
        showBanner('warning', '<i class="bi bi-arrow-up-circle-fill"></i> ' + msg);
      }
    })
    .catch(e => {
      if (btn)  btn.disabled = false;
      if (icon) icon.style.animation = '';
      showRemoteError('Não foi possível consultar o GitHub.');
    });
}

function runPull() {
  const btn    = document.getElementById('btn-pull');
  const spin   = document.getElementById('pull-spinner');
  const icon   = document.getElementById('pull-icon');
  const btnTxt = document.getElementById('pull-btn-text');
  const alert  = document.getElementById('pull-alert');
  const outCard= document.getElementById('output-card');
  const output = document.getElementById('git-output');

  btn.disabled = true;
  spin.classList.remove('d-none');
  icon.style.display = 'none';
  btnTxt.textContent = 'Atualizando…';
  alert.style.display = 'none';

  const fd = new FormData();
  fd.append('_csrf_token', CSRF);

  fetch('<?= url('admin/system-update/pull') ?>', {
    method: 'POST', body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false;
      spin.classList.add('d-none');
      icon.style.display = '';
      btnTxt.textContent = 'Atualizar Agora';

      if (res.data?.output) {
        output.textContent = res.data.output;
        outCard.style.display = '';
      }

      alert.style.display = '';
      if (res.success) {
        alert.innerHTML = '<div class="alert alert-success py-2 small d-flex gap-2"><i class="bi bi-check-circle-fill text-success mt-1"></i><span>' + res.message + ' Recarregando em 3 segundos…</span></div>';
        updateLocalCommit(res.data?.last_commit, '');
        showBanner('success', '<i class="bi bi-check-circle-fill"></i> ' + res.message);
        setTimeout(() => location.reload(), 3000);
      } else {
        alert.innerHTML = '<div class="alert alert-danger py-2 small d-flex gap-2"><i class="bi bi-exclamation-triangle-fill mt-1"></i><span>' + res.message + '</span></div>';
      }
    })
    .catch(() => {
      btn.disabled = false;
      spin.classList.add('d-none');
      icon.style.display = '';
      btnTxt.textContent = 'Atualizar Agora';
      alert.style.display = '';
      alert.innerHTML = '<div class="alert alert-danger py-2 small">Erro de conexão.</div>';
    });
}

// ── helpers ───────────────────────────────────────────────────────

function showRemoteLoading() {
  el('remote-loading').style.display = '';
  el('remote-info').style.display    = 'none';
  el('remote-error').style.display   = 'none';
}
function showRemoteCommit(commit, hash) {
  el('remote-loading').style.display = 'none';
  el('remote-info').style.display    = '';
  el('remote-error').style.display   = 'none';
  el('remote-hash').textContent    = hash || (commit?.sha || '').substring(0,7) || '—';
  el('remote-subject').textContent = commit?.message || '—';
  el('remote-author').textContent  = commit?.author  || '—';
  el('remote-date').textContent    = commit?.date ? formatDate(commit.date) : '—';
}
function showRemoteError(msg) {
  el('remote-loading').style.display = 'none';
  el('remote-info').style.display    = 'none';
  el('remote-error').style.display   = '';
  el('remote-error-msg').textContent = msg;
}
function updateLocalCommit(commit, hash) {
  if (!commit) return;
  const h = el('local-hash');
  const s = el('local-subject');
  const a = el('local-author');
  const d = el('local-date');
  if (h) h.textContent = commit.hash    || hash || '—';
  if (s) s.textContent = commit.subject || commit.changelog || '—';
  if (a) a.textContent = commit.author  || '—';
  if (d) d.textContent = commit.date    || commit.released_at || '—';
}
function showBanner(type, html) {
  const b = el('status-banner');
  const i = el('status-banner-inner');
  if (!b || !i) return;
  i.className = 'alert py-2 small mb-0 d-flex align-items-center gap-2 alert-' + type;
  i.innerHTML = html;
  b.style.display = '';
}
function hideBanner() {
  const b = el('status-banner');
  if (b) b.style.display = 'none';
}
function el(id) { return document.getElementById(id); }
function formatDate(iso) {
  try { return new Date(iso).toLocaleDateString('pt-BR', {day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'}); }
  catch { return iso; }
}
</script>
<?php \Core\View::endSection() ?>
