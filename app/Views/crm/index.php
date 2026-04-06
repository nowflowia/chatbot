<?php \Core\View::section('title') ?>CRM — Negociações<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>CRM — Kanban<?php \Core\View::endSection() ?>
<?php \Core\View::section('content') ?>

<?php
$fmt = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
$base = url('admin');
$csrf = \Core\CSRF::token();
$isAdmin = \Core\Auth::isSupervisorOrAdmin();
$userId  = \Core\Auth::id();
// Group deals by stage
$dealsByStage = [];
foreach ($deals as $d) {
    $dealsByStage[$d['stage_id']][] = $d;
}
?>

<!-- Top summary bar -->
<div class="row g-2 mb-3">
  <div class="col-6 col-md-2">
    <div class="card text-center py-2 px-1">
      <div class="fw-bold fs-5 text-primary"><?= $summary['open'] ?? 0 ?></div>
      <div class="small text-muted">Em aberto</div>
    </div>
  </div>
  <div class="col-6 col-md-2">
    <div class="card text-center py-2 px-1">
      <div class="fw-bold fs-5 text-success"><?= $summary['won'] ?? 0 ?></div>
      <div class="small text-muted">Ganhas</div>
    </div>
  </div>
  <div class="col-6 col-md-2">
    <div class="card text-center py-2 px-1">
      <div class="fw-bold fs-5 text-danger"><?= $summary['lost'] ?? 0 ?></div>
      <div class="small text-muted">Perdidas</div>
    </div>
  </div>
  <div class="col-6 col-md-2">
    <div class="card text-center py-2 px-1">
      <div class="fw-bold fs-5 text-info"><?= $fmt($summary['value_open'] ?? 0) ?></div>
      <div class="small text-muted">Valor em aberto</div>
    </div>
  </div>
  <div class="col-6 col-md-2">
    <div class="card text-center py-2 px-1">
      <div class="fw-bold fs-5 text-success"><?= $fmt($summary['value_won'] ?? 0) ?></div>
      <div class="small text-muted">Valor ganho</div>
    </div>
  </div>
  <div class="col-6 col-md-2">
    <div class="card text-center py-2 px-1">
      <div class="fw-bold fs-5 <?= ($summary['overdue_tasks'] ?? 0) > 0 ? 'text-warning' : 'text-muted' ?>"><?= $summary['overdue_tasks'] ?? 0 ?></div>
      <div class="small text-muted">Tarefas atrasadas</div>
    </div>
  </div>
</div>

<!-- Filters toolbar -->
<div class="card mb-3 p-2">
  <form method="get" class="row g-2 align-items-end">

    <!-- Pipeline selector -->
    <div class="col-auto">
      <label class="form-label small mb-1">Pipeline</label>
      <select name="pipeline" class="form-select form-select-sm" onchange="this.form.submit()">
        <?php foreach ($pipelines as $pl): ?>
          <option value="<?= $pl['id'] ?>" <?= $pl['id'] == $selectedPipelineId ? 'selected' : '' ?>>
            <?= e($pl['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <?php if ($isAdmin && !empty($agents)): ?>
    <div class="col-auto">
      <label class="form-label small mb-1">Responsável</label>
      <select name="assigned_to" class="form-select form-select-sm">
        <option value="">Todos</option>
        <?php foreach ($agents as $a): ?>
          <option value="<?= $a['id'] ?>" <?= ($filters['assigned_to'] ?? 0) == $a['id'] ? 'selected' : '' ?>>
            <?= e($a['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>

    <div class="col-auto">
      <label class="form-label small mb-1">De</label>
      <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($filters['date_from'] ?? '') ?>">
    </div>
    <div class="col-auto">
      <label class="form-label small mb-1">Até</label>
      <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($filters['date_to'] ?? '') ?>">
    </div>
    <div class="col">
      <label class="form-label small mb-1">Busca</label>
      <input type="text" name="search" class="form-control form-control-sm" placeholder="Título ou empresa…" value="<?= e($filters['search'] ?? '') ?>">
    </div>
    <div class="col-auto">
      <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
      <a href="<?= $base ?>/crm?pipeline=<?= $selectedPipelineId ?>" class="btn btn-sm btn-outline-secondary">Limpar</a>
    </div>

    <div class="col-auto ms-auto">
      <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modal-deal">
        <i class="bi bi-plus-lg"></i> Nova negociação
      </button>
      <?php if ($isAdmin): ?>
      <a href="<?= $base ?>/crm/settings" class="btn btn-sm btn-outline-secondary ms-1" title="Configurar pipelines">
        <i class="bi bi-gear"></i>
      </a>
      <?php endif; ?>
    </div>

  </form>
</div>

<!-- Kanban board -->
<?php if (empty($stages)): ?>
  <div class="alert alert-info">Nenhuma etapa configurada para este pipeline. <a href="<?= $base ?>/crm/settings">Configurar agora</a></div>
<?php else: ?>
<div class="kanban-board d-flex gap-3 pb-3" style="overflow-x:auto;min-height:60vh;">
  <?php foreach ($stages as $stage): ?>
  <?php
    $stageDeals = $dealsByStage[$stage['id']] ?? [];
    $stageTotal = array_sum(array_column($stageDeals, 'value'));
  ?>
  <div class="kanban-col flex-shrink-0" style="width:280px;" data-stage-id="<?= $stage['id'] ?>">

    <!-- Column header -->
    <div class="kanban-col-header d-flex align-items-center gap-2 mb-2 px-1">
      <span class="kanban-stage-dot" style="background:<?= e($stage['color']) ?>;width:10px;height:10px;border-radius:50%;display:inline-block;flex-shrink:0;"></span>
      <span class="fw-semibold small flex-grow-1 text-truncate"><?= e($stage['name']) ?></span>
      <span class="badge bg-secondary rounded-pill small"><?= count($stageDeals) ?></span>
    </div>
    <div class="text-muted small px-1 mb-2"><?= $fmt($stageTotal) ?></div>

    <!-- Cards container (sortable) -->
    <div class="kanban-cards" id="stage-<?= $stage['id'] ?>" data-stage-id="<?= $stage['id'] ?>">
      <?php foreach ($stageDeals as $deal): ?>
      <div class="kanban-card card mb-2 shadow-sm" data-deal-id="<?= $deal['id'] ?>" style="cursor:pointer;">
        <div class="card-body p-2">
          <a href="<?= $base ?>/crm/deals/<?= $deal['id'] ?>" class="text-decoration-none text-dark stretched-link">
            <div class="fw-semibold small text-truncate"><?= e($deal['title']) ?></div>
          </a>
          <?php if (!empty($deal['company_name'])): ?>
          <div class="text-muted small text-truncate mt-1"><i class="bi bi-building me-1"></i><?= e($deal['company_name']) ?></div>
          <?php endif; ?>
          <div class="d-flex align-items-center justify-content-between mt-2">
            <span class="small fw-semibold text-success"><?= $fmt($deal['value']) ?></span>
            <?php if (!empty($deal['assigned_name'])): ?>
            <span class="badge rounded-pill text-bg-light text-truncate" style="max-width:90px;font-size:.65rem;"><?= e($deal['assigned_name']) ?></span>
            <?php endif; ?>
          </div>
          <?php if (!empty($deal['expected_close_date'])): ?>
          <?php $late = strtotime($deal['expected_close_date']) < time(); ?>
          <div class="small mt-1 <?= $late ? 'text-danger' : 'text-muted' ?>">
            <i class="bi bi-calendar<?= $late ? '-x' : '' ?> me-1"></i><?= date('d/m/Y', strtotime($deal['expected_close_date'])) ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Add deal shortcut -->
    <button type="button" class="btn btn-sm btn-outline-secondary w-100 mt-1 btn-add-deal"
            data-stage-id="<?= $stage['id'] ?>" data-stage-name="<?= e($stage['name']) ?>">
      <i class="bi bi-plus"></i> Adicionar
    </button>

  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Modal: New Deal ──────────────────────────────────── -->
<div class="modal fade" id="modal-deal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Nova Negociação</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="form-deal">
          <input type="hidden" name="_token" value="<?= $csrf ?>">
          <input type="hidden" name="pipeline_id" value="<?= $selectedPipelineId ?>">
          <input type="hidden" name="stage_id" id="deal-stage-id" value="<?= $stages[0]['id'] ?? '' ?>">

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Título <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Etapa</label>
              <select name="stage_id" class="form-select" id="deal-stage-select">
                <?php foreach ($stages as $s): ?>
                <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Valor (R$)</label>
              <input type="number" name="value" class="form-control" step="0.01" min="0" value="0">
            </div>
            <?php if ($isAdmin && !empty($agents)): ?>
            <div class="col-md-6">
              <label class="form-label">Responsável</label>
              <select name="assigned_to" class="form-select">
                <option value="">Sem responsável</option>
                <?php foreach ($agents as $a): ?>
                <option value="<?= $a['id'] ?>"><?= e($a['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php endif; ?>
            <div class="col-md-6">
              <label class="form-label">Previsão de fechamento</label>
              <input type="date" name="expected_close_date" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Origem</label>
              <select name="origin" class="form-select">
                <option value="manual">Manual</option>
                <option value="whatsapp">WhatsApp</option>
                <option value="import">Importação</option>
                <option value="api">API</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Observações</label>
              <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btn-save-deal">
          <span class="spinner-border spinner-border-sm d-none me-1" id="deal-spinner"></span>
          Criar negociação
        </button>
      </div>
    </div>
  </div>
</div>

<style>
.kanban-board { user-select: none; }
.kanban-col { background: #f8fafc; border-radius: 10px; padding: 12px 8px; }
.kanban-card { transition: box-shadow .15s; border: 1px solid #e2e8f0; }
.kanban-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.12) !important; }
.kanban-card.sortable-ghost { opacity: .4; }
.kanban-card.sortable-chosen { box-shadow: 0 8px 20px rgba(0,0,0,.15) !important; }
</style>

<?php \Core\View::endSection() ?>
<?php \Core\View::section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
const BASE   = '<?= $base ?>';
const CSRF   = '<?= $csrf ?>';
const STAGES = <?= json_encode($stages) ?>;

/* ── Drag & Drop via SortableJS ─── */
document.querySelectorAll('.kanban-cards').forEach(el => {
  Sortable.create(el, {
    group: 'kanban',
    animation: 150,
    ghostClass: 'sortable-ghost',
    chosenClass: 'sortable-chosen',
    onEnd(evt) {
      const dealId  = evt.item.dataset.dealId;
      const stageId = evt.to.dataset.stageId;
      if (!dealId || !stageId) return;
      fetch(`${BASE}/crm/deals/${dealId}/move`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
        body: JSON.stringify({ stage_id: stageId, _token: CSRF })
      }).then(r => r.json()).then(data => {
        if (!data.success) {
          showToast(data.message || 'Erro ao mover.', 'danger');
          location.reload();
        }
      }).catch(() => location.reload());
    }
  });
});

/* ── Btn add deal with pre-selected stage ─── */
document.querySelectorAll('.btn-add-deal').forEach(btn => {
  btn.addEventListener('click', () => {
    const sid = btn.dataset.stageId;
    document.getElementById('deal-stage-select').value = sid;
    new bootstrap.Modal(document.getElementById('modal-deal')).show();
  });
});

/* ── Create deal ─── */
document.getElementById('btn-save-deal').addEventListener('click', function () {
  const form    = document.getElementById('form-deal');
  const spinner = document.getElementById('deal-spinner');
  if (!form.reportValidity()) return;

  const data = Object.fromEntries(new FormData(form));
  this.disabled = true;
  spinner.classList.remove('d-none');

  fetch(`${BASE}/crm/deals`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
    body: JSON.stringify(data)
  }).then(r => r.json()).then(res => {
    if (res.success) {
      showToast('Negociação criada!', 'success');
      setTimeout(() => location.reload(), 800);
    } else {
      showToast(res.message || 'Erro ao criar.', 'danger');
    }
  }).catch(() => showToast('Erro de rede.', 'danger'))
    .finally(() => { this.disabled = false; spinner.classList.add('d-none'); });
});

function showToast(msg, type = 'success') {
  const id = 'toast-' + Date.now();
  const el = document.createElement('div');
  el.innerHTML = `<div id="${id}" class="toast align-items-center text-bg-${type} border-0 show" role="alert">
    <div class="d-flex"><div class="toast-body">${msg}</div>
    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>
  </div>`;
  let c = document.getElementById('toast-container');
  if (!c) { c = document.createElement('div'); c.id='toast-container'; c.className='toast-container position-fixed bottom-0 end-0 p-3'; document.body.appendChild(c); }
  c.appendChild(el.firstElementChild);
  setTimeout(() => document.getElementById(id)?.remove(), 4000);
}
</script>
<?php \Core\View::endSection() ?>
