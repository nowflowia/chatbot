<?php \Core\View::section('title') ?>CRM — Configurações<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>CRM — Pipelines e Etapas<?php \Core\View::endSection() ?>
<?php \Core\View::section('content') ?>

<?php
$base = url('admin');
$csrf = \Core\CSRF::token();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <a href="<?= $base ?>/crm" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Voltar
  </a>
  <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modal-pipeline">
    <i class="bi bi-plus-lg me-1"></i>Novo pipeline
  </button>
</div>

<?php if (empty($pipelines)): ?>
  <div class="alert alert-info">Nenhum pipeline criado ainda.</div>
<?php else: ?>
<?php foreach ($pipelines as $pl): ?>
<div class="card mb-4" id="pipeline-<?= $pl['id'] ?>">
  <div class="card-header d-flex align-items-center gap-3">
    <span class="fw-bold flex-grow-1"><?= e($pl['name']) ?></span>
    <?php if ($pl['is_default']): ?>
    <span class="badge text-bg-primary">Padrão</span>
    <?php endif; ?>
    <?php if (!$pl['is_active']): ?>
    <span class="badge text-bg-secondary">Inativo</span>
    <?php endif; ?>
    <span class="small text-muted"><?= $pl['deal_count'] ?> negociação(ões)</span>
    <button class="btn btn-sm btn-outline-primary btn-edit-pipeline"
            data-id="<?= $pl['id'] ?>"
            data-name="<?= e($pl['name']) ?>"
            data-description="<?= e($pl['description'] ?? '') ?>"
            data-is_default="<?= $pl['is_default'] ?>"
            data-is_active="<?= $pl['is_active'] ?>">
      <i class="bi bi-pencil"></i>
    </button>
    <button class="btn btn-sm btn-outline-danger btn-del-pipeline" data-id="<?= $pl['id'] ?>" data-name="<?= e($pl['name']) ?>">
      <i class="bi bi-trash"></i>
    </button>
  </div>
  <div class="card-body p-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h6 class="mb-0 small fw-semibold text-muted">ETAPAS</h6>
      <button class="btn btn-sm btn-outline-success btn-add-stage" data-pipeline-id="<?= $pl['id'] ?>">
        <i class="bi bi-plus"></i> Adicionar etapa
      </button>
    </div>

    <div class="stages-sortable d-flex flex-wrap gap-2" id="stages-<?= $pl['id'] ?>" data-pipeline-id="<?= $pl['id'] ?>">
      <?php foreach ($pl['stages'] as $s): ?>
      <div class="stage-item d-flex align-items-center gap-2 border rounded px-3 py-2 bg-white" data-stage-id="<?= $s['id'] ?>" style="cursor:grab;">
        <span class="stage-color-dot" style="width:12px;height:12px;border-radius:50%;background:<?= e($s['color']) ?>;display:inline-block;flex-shrink:0;"></span>
        <span class="small fw-semibold"><?= e($s['name']) ?></span>
        <?php if ($s['is_won']): ?><span class="badge text-bg-success ms-1" style="font-size:.65rem;">Ganho</span><?php endif; ?>
        <?php if ($s['is_lost']): ?><span class="badge text-bg-danger ms-1" style="font-size:.65rem;">Perdido</span><?php endif; ?>
        <button class="btn btn-link btn-sm p-0 ms-1 text-muted btn-edit-stage"
                data-id="<?= $s['id'] ?>"
                data-pipeline-id="<?= $pl['id'] ?>"
                data-name="<?= e($s['name']) ?>"
                data-color="<?= e($s['color']) ?>"
                data-is_won="<?= $s['is_won'] ?>"
                data-is_lost="<?= $s['is_lost'] ?>">
          <i class="bi bi-pencil-square small"></i>
        </button>
        <button class="btn btn-link btn-sm p-0 text-danger btn-del-stage" data-id="<?= $s['id'] ?>" data-name="<?= e($s['name']) ?>">
          <i class="bi bi-x-lg small"></i>
        </button>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- ── Modal: Pipeline ─── -->
<div class="modal fade" id="modal-pipeline" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="pipeline-modal-title">Novo Pipeline</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="form-pipeline">
          <input type="hidden" name="_token" value="<?= $csrf ?>">
          <input type="hidden" name="pipeline_id" id="pipeline-form-id" value="">
          <div class="mb-3">
            <label class="form-label">Nome <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" id="pipeline-form-name" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Descrição</label>
            <input type="text" name="description" class="form-control" id="pipeline-form-desc">
          </div>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input type="checkbox" name="is_default" value="1" class="form-check-input" id="pipeline-form-default">
              <label class="form-check-label small" for="pipeline-form-default">Pipeline padrão</label>
            </div>
            <div class="form-check">
              <input type="checkbox" name="is_active" value="1" class="form-check-input" id="pipeline-form-active" checked>
              <label class="form-check-label small" for="pipeline-form-active">Ativo</label>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btn-save-pipeline">Salvar</button>
      </div>
    </div>
  </div>
</div>

<!-- ── Modal: Stage ─── -->
<div class="modal fade" id="modal-stage" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="stage-modal-title">Nova Etapa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="form-stage">
          <input type="hidden" name="_token" value="<?= $csrf ?>">
          <input type="hidden" name="stage_id" id="stage-form-id" value="">
          <input type="hidden" name="pipeline_id" id="stage-form-pipeline-id" value="">
          <div class="mb-3">
            <label class="form-label">Nome <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" id="stage-form-name" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Cor</label>
            <div class="d-flex align-items-center gap-2">
              <input type="color" name="color" class="form-control form-control-color" id="stage-form-color" value="#6366f1" style="width:50px;">
              <div class="d-flex flex-wrap gap-1">
                <?php foreach (['#3b82f6','#8b5cf6','#f59e0b','#10b981','#ef4444','#0ea5e9','#f97316','#64748b','#14b8a6','#ec4899'] as $color): ?>
                <button type="button" class="btn-color-preset" data-color="<?= $color ?>"
                        style="width:22px;height:22px;border-radius:50%;background:<?= $color ?>;border:2px solid transparent;cursor:pointer;">
                </button>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input type="checkbox" name="is_won" value="1" class="form-check-input" id="stage-form-won">
              <label class="form-check-label small" for="stage-form-won">Etapa de Ganho</label>
            </div>
            <div class="form-check">
              <input type="checkbox" name="is_lost" value="1" class="form-check-input" id="stage-form-lost">
              <label class="form-check-label small" for="stage-form-lost">Etapa de Perda</label>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btn-save-stage">Salvar</button>
      </div>
    </div>
  </div>
</div>

<?php \Core\View::endSection() ?>
<?php \Core\View::section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
const BASE = '<?= $base ?>';
const CSRF = '<?= $csrf ?>';

function showToast(msg, type = 'success') {
  const id = 'toast-' + Date.now();
  const el = document.createElement('div');
  el.innerHTML = `<div id="${id}" class="toast align-items-center text-bg-${type} border-0 show">
    <div class="d-flex"><div class="toast-body">${msg}</div>
    <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>`;
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

/* ── Stage sortable ─── */
document.querySelectorAll('.stages-sortable').forEach(el => {
  Sortable.create(el, {
    animation: 150,
    onEnd() {
      const pipelineId = el.dataset.pipelineId;
      const ids = [...el.querySelectorAll('.stage-item')].map(s => s.dataset.stageId);
      apiPost(`${BASE}/crm/settings/stages/reorder`, { ids });
    }
  });
});

/* ── Color presets ─── */
document.querySelectorAll('.btn-color-preset').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('stage-form-color').value = btn.dataset.color;
  });
});

/* ── Pipeline CRUD ─── */
const pipelineModal = new bootstrap.Modal(document.getElementById('modal-pipeline'));

document.querySelectorAll('.btn-edit-pipeline').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('pipeline-modal-title').textContent = 'Editar Pipeline';
    document.getElementById('pipeline-form-id').value = btn.dataset.id;
    document.getElementById('pipeline-form-name').value = btn.dataset.name;
    document.getElementById('pipeline-form-desc').value = btn.dataset.description;
    document.getElementById('pipeline-form-default').checked = btn.dataset.is_default == '1';
    document.getElementById('pipeline-form-active').checked  = btn.dataset.is_active == '1';
    pipelineModal.show();
  });
});

document.getElementById('modal-pipeline').addEventListener('hidden.bs.modal', () => {
  document.getElementById('pipeline-modal-title').textContent = 'Novo Pipeline';
  document.getElementById('pipeline-form-id').value = '';
  document.getElementById('form-pipeline').reset();
  document.getElementById('pipeline-form-active').checked = true;
});

document.getElementById('btn-save-pipeline').addEventListener('click', async function () {
  const form = document.getElementById('form-pipeline');
  if (!form.reportValidity()) return;
  const data  = Object.fromEntries(new FormData(form));
  const id    = data.pipeline_id;
  const url   = id ? `${BASE}/crm/settings/pipelines/${id}` : `${BASE}/crm/settings/pipelines`;
  const res   = await apiPost(url, data);
  if (res.success) { showToast(id ? 'Pipeline atualizado!' : 'Pipeline criado!'); setTimeout(() => location.reload(), 700); }
  else showToast(res.message, 'danger');
});

document.querySelectorAll('.btn-del-pipeline').forEach(btn => {
  btn.addEventListener('click', async () => {
    if (!confirm(`Excluir pipeline "${btn.dataset.name}"?`)) return;
    const res = await apiPost(`${BASE}/crm/settings/pipelines/${btn.dataset.id}/delete`, {});
    if (res.success) { showToast('Pipeline excluído.'); document.getElementById(`pipeline-${btn.dataset.id}`)?.remove(); }
    else showToast(res.message, 'danger');
  });
});

/* ── Stage CRUD ─── */
const stageModal = new bootstrap.Modal(document.getElementById('modal-stage'));

document.querySelectorAll('.btn-add-stage').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('stage-modal-title').textContent = 'Nova Etapa';
    document.getElementById('stage-form-id').value = '';
    document.getElementById('stage-form-pipeline-id').value = btn.dataset.pipelineId;
    document.getElementById('form-stage').reset();
    document.getElementById('stage-form-color').value = '#6366f1';
    stageModal.show();
  });
});

document.querySelectorAll('.btn-edit-stage').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('stage-modal-title').textContent = 'Editar Etapa';
    document.getElementById('stage-form-id').value = btn.dataset.id;
    document.getElementById('stage-form-pipeline-id').value = btn.dataset.pipeline_id;
    document.getElementById('stage-form-name').value = btn.dataset.name;
    document.getElementById('stage-form-color').value = btn.dataset.color;
    document.getElementById('stage-form-won').checked  = btn.dataset.is_won == '1';
    document.getElementById('stage-form-lost').checked = btn.dataset.is_lost == '1';
    stageModal.show();
  });
});

document.getElementById('btn-save-stage').addEventListener('click', async function () {
  const form = document.getElementById('form-stage');
  if (!form.reportValidity()) return;
  const data = Object.fromEntries(new FormData(form));
  const id   = data.stage_id;
  const url  = id ? `${BASE}/crm/settings/stages/${id}` : `${BASE}/crm/settings/stages`;
  const res  = await apiPost(url, data);
  if (res.success) { showToast(id ? 'Etapa atualizada!' : 'Etapa criada!'); setTimeout(() => location.reload(), 700); }
  else showToast(res.message, 'danger');
});

document.querySelectorAll('.btn-del-stage').forEach(btn => {
  btn.addEventListener('click', async () => {
    if (!confirm(`Excluir etapa "${btn.dataset.name}"?`)) return;
    const res = await apiPost(`${BASE}/crm/settings/stages/${btn.dataset.id}/delete`, {});
    if (res.success) { showToast('Etapa excluída.'); btn.closest('.stage-item')?.remove(); }
    else showToast(res.message, 'danger');
  });
});
</script>
<?php \Core\View::endSection() ?>
