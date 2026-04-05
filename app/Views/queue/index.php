<?php \Core\View::section('title') ?>Fila<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>Fila de Atendimento<?php \Core\View::endSection() ?>
<?php \Core\View::section('content') ?>

<div class="d-flex flex-column gap-3" style="max-width:900px">

  <!-- Stats bar -->
  <div class="row g-3" id="queue-stats">
    <div class="col-6 col-md-3">
      <div class="card text-center py-3">
        <div class="fs-2 fw-bold text-warning" id="stat-waiting">—</div>
        <div class="small text-muted">Aguardando</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card text-center py-3">
        <div class="fs-2 fw-bold text-info" id="stat-bot">—</div>
        <div class="small text-muted">Com bot</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card text-center py-3">
        <div class="fs-2 fw-bold text-success" id="stat-progress">—</div>
        <div class="small text-muted">Em atendimento</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card text-center py-3">
        <div class="fs-2 fw-bold text-secondary" id="stat-finished">—</div>
        <div class="small text-muted">Finalizados hoje</div>
      </div>
    </div>
  </div>

  <!-- Search + refresh -->
  <div class="d-flex gap-2">
    <input type="search" id="queue-search" class="form-control" placeholder="Buscar por nome ou telefone…" style="max-width:300px">
    <button class="btn btn-outline-secondary btn-sm" id="btn-refresh" title="Atualizar agora">
      <i class="bi bi-arrow-clockwise"></i>
    </button>
    <span class="ms-auto small text-muted align-self-center" id="last-updated"></span>
  </div>

  <!-- Queue table -->
  <div class="card p-0 overflow-hidden">
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th>Contato</th>
            <th>Última mensagem</th>
            <th>Status</th>
            <th>Aguarda</th>
            <th>Atribuído</th>
            <th class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody id="queue-body">
          <tr id="queue-loading">
            <td colspan="6" class="text-center py-5 text-muted">
              <div class="spinner-border spinner-border-sm me-2"></div>Carregando…
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Transfer modal -->
<div class="modal fade" id="transferModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">Transferir conversa</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="transfer-chat-id">
        <label class="form-label small fw-semibold">Atendente</label>
        <select class="form-select" id="transfer-agent">
          <option value="">Selecione…</option>
          <?php foreach ($agents as $a): ?>
          <option value="<?= (int)$a['id'] ?>"><?= e($a['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="invalid-feedback" id="err-agent"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-sm btn-primary" id="btn-confirm-transfer">
          <span class="spinner-border spinner-border-sm d-none me-1" id="transfer-spinner"></span>
          Transferir
        </button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  'use strict';

  const BASE   = '<?= url('admin') ?>';
  let pollTimer = null;

  // ── Fetch & render ──────────────────────────────────────────────
  function load() {
    const search = document.getElementById('queue-search').value.trim();
    Api.get(BASE + '/queue/list' + (search ? '?search=' + encodeURIComponent(search) : ''))
      .then(res => {
        if (!res.success) return;
        renderStats(res.data.counts);
        renderRows(res.data.chats);
        document.getElementById('last-updated').textContent =
          'Atualizado às ' + new Date().toLocaleTimeString('pt-BR', {hour:'2-digit', minute:'2-digit', second:'2-digit'});
      });
  }

  function renderStats(counts) {
    document.getElementById('stat-waiting').textContent  = counts.waiting  ?? 0;
    document.getElementById('stat-bot').textContent      = counts.bot      ?? 0;
    document.getElementById('stat-progress').textContent = counts.in_progress ?? 0;
    document.getElementById('stat-finished').textContent = counts.finished  ?? 0;
  }

  function renderRows(chats) {
    const tbody = document.getElementById('queue-body');
    if (!chats.length) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-check-circle fs-3 d-block mb-2 text-success opacity-50"></i>Fila vazia</td></tr>';
      return;
    }

    tbody.innerHTML = chats.map(c => {
      const initials = c.contact.initials || '?';
      const color    = c.contact.color    || '#6366f1';
      const name     = esc(c.contact.name);
      const phone    = esc(c.contact.phone);
      const msg      = esc(c.last_message || '—');
      const wait     = esc(c.wait_time   || '—');
      const assigned = c.assigned_user_name ? esc(c.assigned_user_name) : '<span class="text-muted">—</span>';

      const statusBadge = c.status === 'bot'
        ? '<span class="badge bg-info-subtle text-info">Bot</span>'
        : '<span class="badge bg-warning-subtle text-warning">Aguardando</span>';

      const unread = c.unread_count
        ? `<span class="badge bg-danger ms-1">${c.unread_count}</span>` : '';

      return `<tr data-id="${c.id}">
        <td>
          <div class="d-flex align-items-center gap-2">
            <div class="avatar-circle-sm" style="background:${color}">${initials}</div>
            <div>
              <div class="fw-semibold small">${name}${unread}</div>
              <div class="text-muted" style="font-size:.75rem">${phone}</div>
            </div>
          </div>
        </td>
        <td class="small text-truncate" style="max-width:220px">${msg}</td>
        <td>${statusBadge}</td>
        <td class="small text-muted">${wait}</td>
        <td class="small">${assigned}</td>
        <td class="text-end">
          <div class="d-flex gap-1 justify-content-end">
            <a href="${BASE}/chat/${c.id}" class="btn btn-sm btn-outline-primary" title="Abrir chat">
              <i class="bi bi-chat-text"></i>
            </a>
            <button class="btn btn-sm btn-primary btn-assign" data-id="${c.id}" title="Assumir">
              <i class="bi bi-person-check"></i>
            </button>
            <button class="btn btn-sm btn-outline-secondary btn-transfer" data-id="${c.id}" title="Transferir">
              <i class="bi bi-arrow-left-right"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger btn-finish" data-id="${c.id}" title="Finalizar">
              <i class="bi bi-x-circle"></i>
            </button>
          </div>
        </td>
      </tr>`;
    }).join('');
  }

  function esc(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(String(str)));
    return d.innerHTML;
  }

  // ── Actions ─────────────────────────────────────────────────────
  document.getElementById('queue-body').addEventListener('click', function (e) {
    const btn = e.target.closest('button');
    if (!btn) return;

    const id = btn.dataset.id;

    if (btn.classList.contains('btn-assign')) {
      btn.disabled = true;
      Api.post(BASE + '/queue/' + id + '/assign', {})
        .then(res => {
          if (res.success) { Toast.show(res.message, 'success'); load(); }
          else             { Toast.show(res.message, 'danger'); btn.disabled = false; }
        });
    }

    if (btn.classList.contains('btn-finish')) {
      if (!confirm('Finalizar esta conversa?')) return;
      btn.disabled = true;
      Api.post(BASE + '/queue/' + id + '/finish', {})
        .then(res => {
          if (res.success) { Toast.show(res.message, 'success'); load(); }
          else             { Toast.show(res.message, 'danger'); btn.disabled = false; }
        });
    }

    if (btn.classList.contains('btn-transfer')) {
      document.getElementById('transfer-chat-id').value = id;
      document.getElementById('transfer-agent').value   = '';
      document.getElementById('err-agent').textContent  = '';
      document.getElementById('transfer-agent').classList.remove('is-invalid');
      new bootstrap.Modal(document.getElementById('transferModal')).show();
    }
  });

  // Transfer confirm
  document.getElementById('btn-confirm-transfer').addEventListener('click', function () {
    const chatId  = document.getElementById('transfer-chat-id').value;
    const agentId = document.getElementById('transfer-agent').value;
    const spinner = document.getElementById('transfer-spinner');
    const errEl   = document.getElementById('err-agent');

    errEl.textContent = '';
    document.getElementById('transfer-agent').classList.remove('is-invalid');

    if (!agentId) {
      errEl.textContent = 'Selecione um atendente.';
      document.getElementById('transfer-agent').classList.add('is-invalid');
      errEl.style.display = 'block';
      return;
    }

    this.disabled = true;
    spinner.classList.remove('d-none');

    Api.post(BASE + '/queue/' + chatId + '/transfer', { agent_id: agentId })
      .then(res => {
        this.disabled = false;
        spinner.classList.add('d-none');
        if (res.success) {
          bootstrap.Modal.getInstance(document.getElementById('transferModal')).hide();
          Toast.show(res.message, 'success');
          load();
        } else {
          Toast.show(res.message, 'danger');
        }
      });
  });

  // Search
  let searchDebounce;
  document.getElementById('queue-search').addEventListener('input', function () {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(load, 400);
  });

  // Manual refresh
  document.getElementById('btn-refresh').addEventListener('click', load);

  // Auto-poll every 5s
  function startPoll() {
    pollTimer = setInterval(load, 5000);
  }

  // Init
  load();
  startPoll();
})();
</script>

<style>
.avatar-circle-sm {
  width: 36px; height: 36px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-weight: 700; font-size: .8rem;
  flex-shrink: 0;
}
</style>
<?php \Core\View::endSection() ?>
