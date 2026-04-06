<?php \Core\View::section('title') ?>Atualização do Sistema<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>Atualização do Sistema<?php \Core\View::endSection() ?>

<?php \Core\View::section('content') ?>

<div class="row justify-content-center">
  <div class="col-lg-7">

    <!-- Status card -->
    <div class="card border-0 shadow-sm mb-4" id="status-card">
      <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div id="status-icon-wrap"
               style="width:52px;height:52px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi bi-cloud-arrow-down-fill fs-4 text-primary" id="status-icon"></i>
          </div>
          <div>
            <h6 class="fw-bold mb-0" id="status-title">Verificando atualizações…</h6>
            <small class="text-muted" id="status-sub">Consultando o repositório remoto</small>
          </div>
          <button class="btn btn-sm btn-outline-secondary ms-auto d-flex align-items-center gap-2"
                  id="btn-check" onclick="checkStatus()">
            <i class="bi bi-arrow-clockwise" id="check-icon"></i> Verificar
          </button>
        </div>

        <!-- Commit info -->
        <?php if (!empty($lastCommit)): ?>
        <div class="bg-light rounded p-3 small" id="commit-block">
          <div class="text-muted mb-1 fw-semibold">Versão instalada</div>
          <div class="d-flex align-items-start gap-2">
            <code class="text-primary fw-bold" id="commit-hash"><?= e($lastCommit['hash'] ?? '') ?></code>
            <span id="commit-subject" class="text-dark"><?= e($lastCommit['subject'] ?? '') ?></span>
          </div>
          <div class="text-muted mt-1" style="font-size:.72rem;">
            <i class="bi bi-person me-1"></i><span id="commit-author"><?= e($lastCommit['author'] ?? '') ?></span>
            &nbsp;·&nbsp;
            <i class="bi bi-clock me-1"></i><span id="commit-date"><?= e($lastCommit['date'] ?? '') ?></span>
          </div>
        </div>
        <?php else: ?>
        <div class="bg-light rounded p-3 small text-muted" id="commit-block">
          <?php if (!$gitAvailable): ?>
          <i class="bi bi-exclamation-triangle text-warning me-1"></i>
          Git não está disponível neste servidor ou o repositório não foi inicializado.
          <?php else: ?>
          Carregando informações do commit…
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Pending commits badge -->
        <div id="pending-info" class="mt-3" style="display:none;">
          <div class="alert alert-warning py-2 small mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-arrow-up-circle-fill"></i>
            <span id="pending-text"></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Action card -->
    <?php if ($gitAvailable): ?>
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body p-4">
        <h6 class="fw-bold mb-1">Atualizar Sistema</h6>
        <p class="text-muted small mb-3">
          Executa <code>git pull</code> no servidor, baixando e aplicando os commits mais recentes do repositório.
          Após a atualização, a página será recarregada automaticamente.
        </p>

        <div id="pull-alert" class="mb-3" style="display:none;"></div>

        <div class="d-flex align-items-center gap-3">
          <button class="btn btn-primary fw-semibold px-4 d-flex align-items-center gap-2"
                  id="btn-pull" onclick="runPull()">
            <span class="spinner-border spinner-border-sm d-none" id="pull-spinner"></span>
            <i class="bi bi-cloud-download-fill" id="pull-icon"></i>
            <span id="pull-btn-text">Atualizar Agora</span>
          </button>
          <small class="text-muted">Recomenda-se fazer backup antes de atualizar.</small>
        </div>
      </div>
    </div>

    <!-- Output log -->
    <div class="card border-0 shadow-sm" id="output-card" style="display:none;">
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
    <div class="alert alert-warning d-flex gap-2 mb-3">
      <i class="bi bi-exclamation-triangle-fill mt-1 flex-shrink-0"></i>
      <div>
        <strong>Atualização automática não disponível.</strong>
        <ul class="mb-0 mt-2 small">
          <?php if (!$execEnabled): ?>
          <li><strong>shell_exec / exec estão desabilitados</strong> no php.ini deste servidor.
              No cPanel vá em <em>Software → Select PHP Version → Extensions</em> e verifique as funções bloqueadas em <code>disable_functions</code>.</li>
          <?php endif; ?>
          <?php if (!$gitBin): ?>
          <li><strong>Git não encontrado</strong> nos caminhos padrão. No cPanel o caminho costuma ser
              <code>/usr/local/cpanel/3rdparty/bin/git</code> ou <code>/opt/cpanel/ea-git/root/usr/bin/git</code>.
              Confirme rodando <code>which git</code> via SSH.</li>
          <?php endif; ?>
          <?php if (!$hasRepo): ?>
          <li><strong>Repositório Git não inicializado</strong> neste diretório. Faça o deploy via
              <code>git clone</code> em vez de upload manual de arquivos.</li>
          <?php endif; ?>
        </ul>
        <div class="mt-2">Como alternativa, faça a atualização via SSH: <code>git -C <?= e(dirname(PUBLIC_PATH)) ?> pull</code></div>
      </div>
    </div>
    <?php endif; ?>

    <!-- System info -->
    <div class="card border-0 shadow-sm mt-4">
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
              <td class="fw-semibold text-truncate" style="max-width:300px;"><?= e(dirname(PUBLIC_PATH)) ?></td>
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
  if (btn) btn.disabled = true;
  if (icon) icon.style.animation = 'spin 1s linear infinite';

  setStatusLoading('Verificando atualizações…', 'Consultando o repositório remoto');

  const fd = new FormData();
  fd.append('_csrf_token', CSRF);

  fetch('<?= url('admin/system-update/status') ?>', {
    method: 'POST',
    body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
    .then(r => r.json())
    .then(res => {
      if (btn) btn.disabled = false;
      if (icon) icon.style.animation = '';

      if (!res.success) {
        setStatusError(res.message || 'Erro ao verificar.');
        return;
      }

      const d = res.data;
      updateCommitBlock(d.last_commit);

      if (d.up_to_date) {
        setStatusOk('Sistema atualizado', 'Você está na versão mais recente.');
        hidePending();
      } else {
        const n = d.pending;
        setStatusWarning('Atualização disponível', `${n} commit(s) novo(s) no repositório remoto.`);
        showPending(n);
      }
    })
    .catch(() => {
      if (btn) btn.disabled = false;
      if (icon) icon.style.animation = '';
      setStatusError('Não foi possível verificar. Verifique a conexão com o repositório.');
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

  btn.disabled    = true;
  spin.classList.remove('d-none');
  icon.style.display = 'none';
  btnTxt.textContent = 'Atualizando…';
  alert.style.display = 'none';

  const fd = new FormData();
  fd.append('_csrf_token', CSRF);

  fetch('<?= url('admin/system-update/pull') ?>', {
    method: 'POST',
    body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false;
      spin.classList.add('d-none');
      icon.style.display = '';
      btnTxt.textContent = 'Atualizar Agora';

      // Show git output
      if (res.data?.output) {
        output.textContent = res.data.output;
        outCard.style.display = '';
      }

      if (res.success) {
        alert.style.display = '';
        alert.innerHTML = '<div class="alert alert-success py-2 small d-flex gap-2"><i class="bi bi-check-circle-fill mt-1 text-success"></i><span>' + res.message + '</span></div>';
        updateCommitBlock(res.data?.last_commit);
        setStatusOk('Sistema atualizado', 'Recarregando em 3 segundos…');
        hidePending();
        setTimeout(() => location.reload(), 3000);
      } else {
        alert.style.display = '';
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

function setStatusLoading(title, sub) {
  setStatus('#f1f5f9', 'text-primary', 'bi-arrow-repeat', title, sub, true);
}
function setStatusOk(title, sub) {
  setStatus('#dcfce7', 'text-success', 'bi-check-circle-fill', title, sub);
}
function setStatusWarning(title, sub) {
  setStatus('#fef3c7', 'text-warning', 'bi-exclamation-circle-fill', title, sub);
}
function setStatusError(msg) {
  setStatus('#fee2e2', 'text-danger', 'bi-x-circle-fill', 'Erro', msg);
}
function setStatus(bg, textClass, icon, title, sub, spin = false) {
  const wrap = document.getElementById('status-icon-wrap');
  const ico  = document.getElementById('status-icon');
  const ttl  = document.getElementById('status-title');
  const sub_ = document.getElementById('status-sub');
  if (wrap) wrap.style.background = bg;
  if (ico)  { ico.className = 'bi ' + icon + ' fs-4 ' + textClass; ico.style.animation = spin ? 'spin 1s linear infinite' : ''; }
  if (ttl)  ttl.textContent = title;
  if (sub_) sub_.textContent = sub;
}

function showPending(n) {
  const el = document.getElementById('pending-info');
  const tx = document.getElementById('pending-text');
  if (el) el.style.display = '';
  if (tx) tx.textContent = `Há ${n} commit(s) disponível(is) para atualização.`;
}
function hidePending() {
  const el = document.getElementById('pending-info');
  if (el) el.style.display = 'none';
}

function updateCommitBlock(commit) {
  if (!commit) return;
  const h = document.getElementById('commit-hash');
  const s = document.getElementById('commit-subject');
  const a = document.getElementById('commit-author');
  const d = document.getElementById('commit-date');
  if (h) h.textContent = commit.hash    || '';
  if (s) s.textContent = commit.subject || '';
  if (a) a.textContent = commit.author  || '';
  if (d) d.textContent = commit.date    || '';
}
</script>
<?php \Core\View::endSection() ?>
