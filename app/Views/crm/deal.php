<?php \Core\View::section('title') ?>Negociação — <?= e($deal['title']) ?><?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>Detalhes da Negociação<?php \Core\View::endSection() ?>
<?php \Core\View::section('content') ?>

<?php
$fmt     = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
$base    = url('admin');
$csrf    = \Core\CSRF::token();
$isAdmin = \Core\Auth::isSupervisorOrAdmin();

$statusLabels = ['open' => ['label' => 'Em aberto', 'class' => 'text-bg-primary'],
                 'won'  => ['label' => 'Ganha',     'class' => 'text-bg-success'],
                 'lost' => ['label' => 'Perdida',    'class' => 'text-bg-danger']];
$originLabels = ['manual' => 'Manual', 'whatsapp' => 'WhatsApp', 'import' => 'Importação', 'api' => 'API'];
$status = $statusLabels[$deal['status']] ?? $statusLabels['open'];
?>

<!-- Breadcrumb + back -->
<div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
  <a href="<?= $base ?>/crm" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Kanban
  </a>
  <h5 class="mb-0 fw-bold text-truncate flex-grow-1"><?= e($deal['title']) ?></h5>
  <span class="badge rounded-pill <?= $status['class'] ?>"><?= $status['label'] ?></span>

  <?php if ($deal['status'] === 'open'): ?>
  <button class="btn btn-sm btn-success" id="btn-win"><i class="bi bi-trophy me-1"></i>Ganho</button>
  <button class="btn btn-sm btn-danger" id="btn-lose"><i class="bi bi-x-circle me-1"></i>Perdido</button>
  <?php endif; ?>

  <?php if ($isAdmin): ?>
  <button class="btn btn-sm btn-outline-danger" id="btn-delete-deal">
    <i class="bi bi-trash"></i>
  </button>
  <?php endif; ?>
</div>

<!-- Stage breadcrumb (clickable) -->
<?php if ($deal['status'] === 'open'): ?>
<div class="d-flex flex-wrap gap-1 mb-3">
  <?php foreach ($stages as $s): ?>
  <?php $active = $s['id'] == $deal['stage_id']; ?>
  <button type="button"
          class="btn btn-sm <?= $active ? 'btn-primary' : 'btn-outline-secondary' ?> btn-stage"
          data-stage-id="<?= $s['id'] ?>"
          style="<?= $active ? "background:{$s['color']};border-color:{$s['color']};" : '' ?>">
    <?= e($s['name']) ?>
  </button>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="row g-3">

  <!-- Left: data card -->
  <div class="col-lg-4">
    <div class="card p-3 mb-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-semibold mb-0">Dados</h6>
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-edit-deal">
          <i class="bi bi-pencil"></i> Editar
        </button>
      </div>
      <dl class="row g-1 small mb-0">
        <dt class="col-5 text-muted">Valor</dt>
        <dd class="col-7 fw-semibold"><?= $fmt($deal['value']) ?></dd>

        <dt class="col-5 text-muted">Pipeline</dt>
        <dd class="col-7"><?= e($deal['pipeline_name'] ?? '') ?></dd>

        <dt class="col-5 text-muted">Etapa</dt>
        <dd class="col-7">
          <span class="badge rounded-pill" style="background:<?= e($deal['stage_color'] ?? '#6366f1') ?>">
            <?= e($deal['stage_name'] ?? '') ?>
          </span>
        </dd>

        <dt class="col-5 text-muted">Origem</dt>
        <dd class="col-7"><?= $originLabels[$deal['origin']] ?? $deal['origin'] ?></dd>

        <?php if (!empty($deal['expected_close_date'])): ?>
        <dt class="col-5 text-muted">Previsão</dt>
        <dd class="col-7"><?= date('d/m/Y', strtotime($deal['expected_close_date'])) ?></dd>
        <?php endif; ?>

        <?php if (!empty($deal['assigned_name'])): ?>
        <dt class="col-5 text-muted">Responsável</dt>
        <dd class="col-7"><?= e($deal['assigned_name']) ?></dd>
        <?php endif; ?>

        <?php if (!empty($deal['company_name'])): ?>
        <dt class="col-5 text-muted">Empresa</dt>
        <dd class="col-7"><a href="<?= $base ?>/crm/companies/<?= $deal['company_id'] ?>"><?= e($deal['company_name']) ?></a></dd>
        <?php endif; ?>

        <?php if (!empty($deal['contact_name'])): ?>
        <dt class="col-5 text-muted">Contato</dt>
        <dd class="col-7"><?= e($deal['contact_name']) ?></dd>
        <?php endif; ?>

        <?php if (!empty($deal['lost_reason'])): ?>
        <dt class="col-5 text-muted">Motivo perda</dt>
        <dd class="col-7"><?= e($deal['lost_reason']) ?></dd>
        <?php endif; ?>

        <dt class="col-5 text-muted">Criado em</dt>
        <dd class="col-7"><?= date('d/m/Y H:i', strtotime($deal['created_at'])) ?></dd>
      </dl>
    </div>

    <!-- Tasks card -->
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-semibold mb-0">Tarefas</h6>
        <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modal-task">
          <i class="bi bi-plus"></i>
        </button>
      </div>
      <div id="task-list">
        <?php if (empty($deal['tasks'])): ?>
          <p class="text-muted small mb-0">Nenhuma tarefa.</p>
        <?php else: ?>
          <?php foreach ($deal['tasks'] as $task): ?>
          <div class="d-flex align-items-start gap-2 mb-2 task-item" data-task-id="<?= $task['id'] ?>">
            <button class="btn btn-sm btn-outline-success p-0 px-1 btn-task-done" data-task-id="<?= $task['id'] ?>">
              <i class="bi bi-check"></i>
            </button>
            <div class="flex-grow-1">
              <div class="small fw-semibold <?= $task['status'] === 'done' ? 'text-decoration-line-through text-muted' : '' ?>">
                <?= e($task['title']) ?>
              </div>
              <?php if (!empty($task['due_date'])): ?>
              <?php $late = strtotime($task['due_date']) < time() && $task['status'] !== 'done'; ?>
              <div class="small <?= $late ? 'text-danger' : 'text-muted' ?>">
                <?= date('d/m/Y H:i', strtotime($task['due_date'])) ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Right: tabs -->
  <div class="col-lg-8">
    <div class="card p-0">
      <ul class="nav nav-tabs nav-tabs-crm px-3 pt-2 border-0 gap-1" id="deal-tabs">
        <li class="nav-item">
          <button class="nav-link active small" data-bs-toggle="tab" data-bs-target="#tab-history">
            <i class="bi bi-clock-history me-1"></i>Histórico
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link small" data-bs-toggle="tab" data-bs-target="#tab-files">
            <i class="bi bi-paperclip me-1"></i>Arquivos
            <span class="badge text-bg-secondary rounded-pill ms-1"><?= count($deal['files']) ?></span>
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link small" data-bs-toggle="tab" data-bs-target="#tab-products">
            <i class="bi bi-box-seam me-1"></i>Produtos
            <span class="badge text-bg-secondary rounded-pill ms-1"><?= count($deal['products']) ?></span>
          </button>
        </li>
      </ul>

      <div class="tab-content p-3">

        <!-- History tab -->
        <div class="tab-pane fade show active" id="tab-history">
          <!-- Add note -->
          <div class="d-flex gap-2 mb-3">
            <textarea class="form-control form-control-sm" id="note-input" rows="2" placeholder="Adicionar anotação…"></textarea>
            <button class="btn btn-primary btn-sm align-self-end" id="btn-add-note" style="white-space:nowrap;">
              <i class="bi bi-send"></i>
            </button>
          </div>
          <!-- Activity timeline -->
          <div id="activity-list">
            <?php if (empty($deal['activities'])): ?>
              <p class="text-muted small">Nenhuma atividade ainda.</p>
            <?php else: ?>
              <?php foreach (array_reverse($deal['activities']) as $act): ?>
              <?php
                $icons = ['note' => 'bi-chat-left-text', 'stage_change' => 'bi-arrow-right-circle',
                          'status_change' => 'bi-flag', 'file' => 'bi-paperclip',
                          'task_done' => 'bi-check-circle', 'email' => 'bi-envelope',
                          'call' => 'bi-telephone', 'meeting' => 'bi-calendar-check'];
                $icon = $icons[$act['type']] ?? 'bi-circle';
              ?>
              <div class="d-flex gap-3 mb-3 activity-item">
                <div class="text-muted mt-1"><i class="bi <?= $icon ?> fs-5"></i></div>
                <div class="flex-grow-1">
                  <div class="small fw-semibold"><?= e($act['title']) ?></div>
                  <?php if (!empty($act['body'])): ?>
                  <div class="small text-muted mt-1" style="white-space:pre-wrap;"><?= e($act['body']) ?></div>
                  <?php endif; ?>
                  <div class="text-muted" style="font-size:.7rem;">
                    <?= e($act['user_name'] ?? 'Sistema') ?> · <?= date('d/m/Y H:i', strtotime($act['created_at'])) ?>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Files tab -->
        <div class="tab-pane fade" id="tab-files">
          <div class="mb-3">
            <label class="btn btn-outline-primary btn-sm">
              <i class="bi bi-upload me-1"></i>Enviar arquivo
              <input type="file" id="file-input" class="d-none">
            </label>
            <span class="text-muted small ms-2" id="file-upload-status"></span>
          </div>
          <div id="file-list">
            <?php if (empty($deal['files'])): ?>
              <p class="text-muted small">Nenhum arquivo enviado.</p>
            <?php else: ?>
            <div class="list-group list-group-flush">
              <?php foreach ($deal['files'] as $f): ?>
              <div class="list-group-item px-0 d-flex align-items-center gap-2" id="file-<?= $f['id'] ?>">
                <i class="bi bi-file-earmark fs-5 text-muted"></i>
                <div class="flex-grow-1">
                  <div class="small fw-semibold"><?= e($f['original_name']) ?></div>
                  <div class="text-muted" style="font-size:.7rem;">
                    <?= round($f['size'] / 1024) ?> KB · <?= e($f['uploaded_by_name'] ?? '') ?> · <?= date('d/m/Y', strtotime($f['created_at'])) ?>
                  </div>
                </div>
                <?php if ($isAdmin): ?>
                <button class="btn btn-sm btn-outline-danger p-1 btn-del-file" data-file-id="<?= $f['id'] ?>">
                  <i class="bi bi-trash"></i>
                </button>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Products tab -->
        <div class="tab-pane fade" id="tab-products">
          <?php if (empty($deal['products'])): ?>
            <p class="text-muted small">Nenhum produto vinculado.</p>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead class="table-light"><tr><th>Produto</th><th>Qtd</th><th>Unit.</th><th>Total</th></tr></thead>
              <tbody>
              <?php foreach ($deal['products'] as $p): ?>
              <tr>
                <td><?= e($p['name']) ?></td>
                <td><?= $p['quantity'] ?></td>
                <td><?= $fmt($p['unit_price']) ?></td>
                <td><?= $fmt($p['quantity'] * $p['unit_price']) ?></td>
              </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- ── Modal: Edit Deal ─────────────────────────────────── -->
<div class="modal fade" id="modal-edit-deal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Editar Negociação</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="form-edit-deal">
          <input type="hidden" name="_token" value="<?= $csrf ?>">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Título <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" value="<?= e($deal['title']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Valor (R$)</label>
              <input type="number" name="value" class="form-control" step="0.01" min="0" value="<?= $deal['value'] ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Origem</label>
              <select name="origin" class="form-select">
                <?php foreach (['manual','whatsapp','import','api'] as $o): ?>
                <option value="<?= $o ?>" <?= $deal['origin'] === $o ? 'selected' : '' ?>><?= ucfirst($o) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php if ($isAdmin && !empty($agents)): ?>
            <div class="col-md-6">
              <label class="form-label">Responsável</label>
              <select name="assigned_to" class="form-select">
                <option value="">Sem responsável</option>
                <?php foreach ($agents as $a): ?>
                <option value="<?= $a['id'] ?>" <?= $deal['assigned_to'] == $a['id'] ? 'selected' : '' ?>><?= e($a['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php endif; ?>
            <div class="col-md-6">
              <label class="form-label">Previsão de fechamento</label>
              <input type="date" name="expected_close_date" class="form-control" value="<?= e($deal['expected_close_date'] ?? '') ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Observações</label>
              <textarea name="notes" class="form-control" rows="3"><?= e($deal['notes'] ?? '') ?></textarea>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btn-save-edit">Salvar</button>
      </div>
    </div>
  </div>
</div>

<!-- ── Modal: Lose reason ─────────────────────────────── -->
<div class="modal fade" id="modal-lose" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Motivo da perda</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <textarea class="form-control" id="lose-reason" rows="3" placeholder="Descreva o motivo (opcional)…"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="btn-confirm-lose">Confirmar perda</button>
      </div>
    </div>
  </div>
</div>

<!-- ── Modal: New Task ─────────────────────────────────── -->
<div class="modal fade" id="modal-task" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Nova Tarefa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="form-task">
          <input type="hidden" name="_token" value="<?= $csrf ?>">
          <input type="hidden" name="deal_id" value="<?= $deal['id'] ?>">
          <div class="mb-3">
            <label class="form-label">Título <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Descrição</label>
            <textarea name="description" class="form-control" rows="2"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Prazo</label>
            <input type="datetime-local" name="due_date" class="form-control">
          </div>
          <?php if ($isAdmin && !empty($agents)): ?>
          <div class="mb-3">
            <label class="form-label">Responsável</label>
            <select name="assigned_to" class="form-select">
              <option value="">Sem responsável</option>
              <?php foreach ($agents as $a): ?>
              <option value="<?= $a['id'] ?>"><?= e($a['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btn-save-task">Criar tarefa</button>
      </div>
    </div>
  </div>
</div>

<?php \Core\View::endSection() ?>
<?php \Core\View::section('scripts') ?>
<script>
const BASE    = '<?= $base ?>';
const CSRF    = '<?= $csrf ?>';
const DEAL_ID = <?= $deal['id'] ?>;

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

async function apiPost(url, data) {
  const r = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
    body: JSON.stringify({ ...data, _token: CSRF })
  });
  return r.json();
}

/* Stage change */
document.querySelectorAll('.btn-stage').forEach(btn => {
  btn.addEventListener('click', async () => {
    const res = await apiPost(`${BASE}/crm/deals/${DEAL_ID}/move`, { stage_id: btn.dataset.stageId });
    if (res.success) { showToast('Etapa atualizada!'); location.reload(); }
    else showToast(res.message, 'danger');
  });
});

/* Win */
document.getElementById('btn-win')?.addEventListener('click', async () => {
  if (!confirm('Marcar como ganha?')) return;
  const res = await apiPost(`${BASE}/crm/deals/${DEAL_ID}/win`, {});
  if (res.success) { showToast('Negociação ganha!'); setTimeout(() => location.reload(), 800); }
  else showToast(res.message, 'danger');
});

/* Lose */
document.getElementById('btn-lose')?.addEventListener('click', () => {
  new bootstrap.Modal(document.getElementById('modal-lose')).show();
});
document.getElementById('btn-confirm-lose')?.addEventListener('click', async () => {
  const reason = document.getElementById('lose-reason').value;
  const res = await apiPost(`${BASE}/crm/deals/${DEAL_ID}/lose`, { lost_reason: reason });
  if (res.success) { showToast('Negociação marcada como perdida.', 'warning'); setTimeout(() => location.reload(), 800); }
  else showToast(res.message, 'danger');
});

/* Add note */
document.getElementById('btn-add-note').addEventListener('click', async () => {
  const body = document.getElementById('note-input').value.trim();
  if (!body) return;
  const res = await apiPost(`${BASE}/crm/deals/${DEAL_ID}/notes`, { body });
  if (res.success) {
    showToast('Anotação adicionada!');
    document.getElementById('note-input').value = '';
    location.reload();
  } else showToast(res.message, 'danger');
});

/* Edit deal */
document.getElementById('btn-save-edit').addEventListener('click', async () => {
  const data = Object.fromEntries(new FormData(document.getElementById('form-edit-deal')));
  const res = await apiPost(`${BASE}/crm/deals/${DEAL_ID}`, data);
  if (res.success) { showToast('Negociação atualizada!'); setTimeout(() => location.reload(), 600); }
  else showToast(res.message, 'danger');
});

/* Delete deal */
document.getElementById('btn-delete-deal')?.addEventListener('click', async () => {
  if (!confirm('Excluir esta negociação? Esta ação é irreversível.')) return;
  const res = await apiPost(`${BASE}/crm/deals/${DEAL_ID}/delete`, {});
  if (res.success) { showToast('Negociação excluída.'); setTimeout(() => location.href = `${BASE}/crm`, 800); }
  else showToast(res.message, 'danger');
});

/* Task done */
document.querySelectorAll('.btn-task-done').forEach(btn => {
  btn.addEventListener('click', async () => {
    const res = await apiPost(`${BASE}/crm/tasks/${btn.dataset.taskId}/done`, {});
    if (res.success) location.reload();
    else showToast(res.message, 'danger');
  });
});

/* New task */
document.getElementById('btn-save-task').addEventListener('click', async () => {
  const form = document.getElementById('form-task');
  if (!form.reportValidity()) return;
  const data = Object.fromEntries(new FormData(form));
  const res = await apiPost(`${BASE}/crm/tasks`, data);
  if (res.success) { showToast('Tarefa criada!'); location.reload(); }
  else showToast(res.message, 'danger');
});

/* File upload */
document.getElementById('file-input').addEventListener('change', async function () {
  if (!this.files[0]) return;
  const fd = new FormData();
  fd.append('file', this.files[0]);
  fd.append('_token', CSRF);
  document.getElementById('file-upload-status').textContent = 'Enviando…';
  const r = await fetch(`${BASE}/crm/deals/${DEAL_ID}/files`, { method: 'POST', body: fd });
  const res = await r.json();
  if (res.success) { showToast('Arquivo enviado!'); location.reload(); }
  else showToast(res.message, 'danger');
  document.getElementById('file-upload-status').textContent = '';
});

/* Delete file */
document.querySelectorAll('.btn-del-file').forEach(btn => {
  btn.addEventListener('click', async () => {
    if (!confirm('Excluir este arquivo?')) return;
    const res = await apiPost(`${BASE}/crm/deals/${DEAL_ID}/files/${btn.dataset.fileId}/delete`, {});
    if (res.success) { showToast('Arquivo excluído.'); document.getElementById('file-' + btn.dataset.fileId)?.remove(); }
    else showToast(res.message, 'danger');
  });
});
</script>
<?php \Core\View::endSection() ?>
