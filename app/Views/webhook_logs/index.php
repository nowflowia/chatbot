<?php \Core\View::section('title') ?>Logs Webhook<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>Logs de Webhook<?php \Core\View::endSection() ?>
<?php \Core\View::section('content') ?>

<!-- Filters -->
<form class="d-flex flex-wrap gap-2 mb-3" method="get">
  <input type="search" name="search" value="<?= e($search) ?>"
         class="form-control form-control-sm" placeholder="Número, message_id…" style="width:220px">
  <select name="status" class="form-select form-select-sm" style="width:150px">
    <option value="">Todos status</option>
    <?php foreach (['received','processed','failed','ignored'] as $s): ?>
    <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-sm btn-outline-secondary">Filtrar</button>
  <a href="<?= url('admin/webhook-logs') ?>" class="btn btn-sm btn-outline-secondary">Limpar filtro</a>

  <div class="ms-auto d-flex gap-2">
    <!-- Toggle logging -->
    <button type="button" class="btn btn-sm <?= $loggingEnabled ? 'btn-success' : 'btn-outline-secondary' ?>"
            id="btn-toggle-logging" title="<?= $loggingEnabled ? 'Log habilitado' : 'Log desabilitado' ?>">
      <span class="spinner-border spinner-border-sm d-none me-1" id="toggle-spin"></span>
      <i class="bi <?= $loggingEnabled ? 'bi-record-circle-fill' : 'bi-record-circle' ?> me-1" id="toggle-icon"></i>
      <span id="toggle-label"><?= $loggingEnabled ? 'Log ativo' : 'Log inativo' ?></span>
    </button>
    <!-- Clear -->
    <button type="button" class="btn btn-sm btn-outline-danger" id="btn-clear-logs">
      <i class="bi bi-trash me-1"></i>Limpar logs
    </button>
  </div>
</form>

<div class="d-flex align-items-center mb-2 small text-muted">
  <span><?= number_format($total) ?> registro(s)</span>
</div>

<div class="card p-0 overflow-hidden">
  <div class="table-responsive">
    <table class="table table-sm table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Data</th>
          <th>Tipo</th>
          <th>De</th>
          <th>Message ID</th>
          <th>Status</th>
          <th>IP</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($logs)): ?>
        <tr><td colspan="8" class="text-center py-5 text-muted">Nenhum log encontrado.</td></tr>
        <?php else: ?>
        <?php foreach ($logs as $log): ?>
        <tr>
          <td class="text-muted small"><?= (int)$log['id'] ?></td>
          <td class="small text-nowrap">
            <?= $log['created_at'] ? date('d/m/y H:i:s', strtotime($log['created_at'])) : '—' ?>
          </td>
          <td><span class="badge bg-secondary-subtle text-secondary"><?= e($log['event_type'] ?? '—') ?></span></td>
          <td class="small"><?= e($log['from_number'] ?? '—') ?></td>
          <td class="small text-truncate" style="max-width:180px">
            <?= e($log['message_id'] ?? '—') ?>
          </td>
          <td>
            <?php
            $badgeMap = [
              'received'  => 'bg-warning-subtle text-warning',
              'processed' => 'bg-success-subtle text-success',
              'failed'    => 'bg-danger-subtle text-danger',
              'ignored'   => 'bg-secondary-subtle text-secondary',
            ];
            $cls = $badgeMap[$log['status']] ?? 'bg-secondary-subtle text-secondary';
            ?>
            <span class="badge <?= $cls ?>"><?= e($log['status']) ?></span>
          </td>
          <td class="small text-muted"><?= e($log['ip_address'] ?? '—') ?></td>
          <td>
            <button class="btn btn-sm btn-link p-0 btn-view-log" data-id="<?= (int)$log['id'] ?>">
              <i class="bi bi-eye"></i>
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Pagination -->
<?php if ($lastPage > 1): ?>
<nav class="mt-3">
  <ul class="pagination pagination-sm justify-content-center mb-0">
    <?php for ($p = 1; $p <= min($lastPage, 20); $p++): ?>
    <li class="page-item <?= $p == $page ? 'active' : '' ?>">
      <a class="page-link" href="?page=<?= $p ?>&status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>">
        <?= $p ?>
      </a>
    </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>

<!-- Log detail modal -->
<div class="modal fade" id="logModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">Payload do Log <span id="log-id-label" class="text-muted"></span></h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <pre id="log-payload" class="p-3 rounded" style="background:#1e1e2e;color:#cdd6f4;font-size:.78rem;max-height:500px;overflow:auto;white-space:pre-wrap;word-break:break-all"></pre>
      </div>
    </div>
  </div>
</div>

<!-- Clear modal -->
<div class="modal fade" id="clearModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">Limpar logs</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label form-label-sm fw-semibold d-block mb-2">O que deseja remover?</label>
          <div class="form-check mb-1">
            <input class="form-check-input" type="radio" name="clear_mode" id="mode-all" value="all" checked>
            <label class="form-check-label" for="mode-all">Todos os logs</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="clear_mode" id="mode-days" value="days">
            <label class="form-check-label" for="mode-days">Logs com mais de</label>
          </div>
        </div>
        <div class="input-group input-group-sm" id="days-input-group" style="display:none!important">
          <input type="number" id="clear-days" class="form-control" value="7" min="1">
          <span class="input-group-text">dias</span>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-sm btn-danger" id="btn-confirm-clear">
          <span class="spinner-border spinner-border-sm d-none me-1" id="clear-spin"></span>
          Remover
        </button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  'use strict';
  const BASE = '<?= url('admin') ?>';

  // View log payload
  document.querySelectorAll('.btn-view-log').forEach(btn => {
    btn.addEventListener('click', function () {
      const id = this.dataset.id;
      document.getElementById('log-payload').textContent = 'Carregando…';
      document.getElementById('log-id-label').textContent = '#' + id;
      new bootstrap.Modal(document.getElementById('logModal')).show();
      Api.get(BASE + '/webhook-logs/' + id).then(res => {
        if (res.success) {
          let payload = res.data.log.payload;
          try { payload = JSON.stringify(JSON.parse(payload), null, 2); } catch(e) {}
          document.getElementById('log-payload').textContent = payload || '(vazio)';
        }
      });
    });
  });

  // Toggle logging
  document.getElementById('btn-toggle-logging').addEventListener('click', function () {
    const btn   = this;
    const spin  = document.getElementById('toggle-spin');
    const icon  = document.getElementById('toggle-icon');
    const label = document.getElementById('toggle-label');
    btn.disabled = true;
    spin.classList.remove('d-none');

    Api.post(BASE + '/webhook-logs/toggle-logging', {}).then(res => {
      btn.disabled = false;
      spin.classList.add('d-none');
      if (res.success) {
        const enabled = res.data.enabled;
        btn.className  = 'btn btn-sm ' + (enabled ? 'btn-success' : 'btn-outline-secondary');
        icon.className = 'bi ' + (enabled ? 'bi-record-circle-fill' : 'bi-record-circle') + ' me-1';
        label.textContent = enabled ? 'Log ativo' : 'Log inativo';
        btn.title = enabled ? 'Log habilitado' : 'Log desabilitado';
        Toast.show(res.message, 'success');
      } else {
        Toast.show(res.message, 'danger');
      }
    });
  });

  // Clear logs — show/hide days input based on radio
  document.querySelectorAll('[name="clear_mode"]').forEach(r => {
    r.addEventListener('change', function () {
      const grp = document.getElementById('days-input-group');
      grp.style.setProperty('display', this.value === 'days' ? 'flex' : 'none', 'important');
    });
  });

  document.getElementById('btn-clear-logs').addEventListener('click', () => {
    new bootstrap.Modal(document.getElementById('clearModal')).show();
  });

  document.getElementById('btn-confirm-clear').addEventListener('click', function () {
    const mode = document.querySelector('[name="clear_mode"]:checked').value;
    const days = mode === 'days' ? parseInt(document.getElementById('clear-days').value, 10) : 0;
    const btn  = this;
    const spin = document.getElementById('clear-spin');
    btn.disabled = true;
    spin.classList.remove('d-none');

    Api.post(BASE + '/webhook-logs/clear', { days }).then(res => {
      btn.disabled = false;
      spin.classList.add('d-none');
      bootstrap.Modal.getInstance(document.getElementById('clearModal')).hide();
      Toast.show(res.message, res.success ? 'success' : 'danger');
      if (res.success) setTimeout(() => window.location.reload(), 800);
    });
  });
})();
</script>
<?php \Core\View::endSection() ?>
