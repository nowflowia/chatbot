<?php \Core\View::section('title') ?>Empresa — <?= e($company['name']) ?><?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>Empresa<?php \Core\View::endSection() ?>
<?php \Core\View::section('content') ?>

<?php
$base    = url('admin');
$csrf    = \Core\CSRF::token();
$isAdmin = \Core\Auth::isSupervisorOrAdmin();
$fmt     = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
$statusLabels = ['open' => ['label' => 'Em aberto', 'class' => 'text-bg-primary'],
                 'won'  => ['label' => 'Ganha', 'class' => 'text-bg-success'],
                 'lost' => ['label' => 'Perdida', 'class' => 'text-bg-danger']];
?>

<div class="d-flex align-items-center gap-3 mb-3">
  <a href="<?= $base ?>/crm/companies" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Empresas
  </a>
  <h5 class="mb-0 fw-bold flex-grow-1"><?= e($company['name']) ?></h5>
  <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-edit-company">
    <i class="bi bi-pencil me-1"></i>Editar
  </button>
  <?php if ($isAdmin): ?>
  <button class="btn btn-sm btn-outline-danger" id="btn-delete-company">
    <i class="bi bi-trash"></i>
  </button>
  <?php endif; ?>
</div>

<div class="row g-3">
  <!-- Left: data -->
  <div class="col-lg-4">
    <div class="card p-3">
      <h6 class="fw-semibold mb-3">Dados da empresa</h6>
      <dl class="row g-1 small mb-0">
        <?php if (!empty($company['fantasy_name'])): ?>
        <dt class="col-5 text-muted">Fantasia</dt><dd class="col-7"><?= e($company['fantasy_name']) ?></dd>
        <?php endif; ?>
        <?php if (!empty($company['cnpj'])): ?>
        <dt class="col-5 text-muted">CNPJ</dt><dd class="col-7"><?= e($company['cnpj']) ?></dd>
        <?php endif; ?>
        <?php if (!empty($company['email'])): ?>
        <dt class="col-5 text-muted">E-mail</dt><dd class="col-7"><a href="mailto:<?= e($company['email']) ?>"><?= e($company['email']) ?></a></dd>
        <?php endif; ?>
        <?php if (!empty($company['phone'])): ?>
        <dt class="col-5 text-muted">Telefone</dt><dd class="col-7"><?= e($company['phone']) ?></dd>
        <?php endif; ?>
        <?php if (!empty($company['whatsapp'])): ?>
        <dt class="col-5 text-muted">WhatsApp</dt><dd class="col-7"><?= e($company['whatsapp']) ?></dd>
        <?php endif; ?>
        <?php if (!empty($company['website'])): ?>
        <dt class="col-5 text-muted">Website</dt><dd class="col-7"><a href="<?= e($company['website']) ?>" target="_blank"><?= e($company['website']) ?></a></dd>
        <?php endif; ?>
        <?php if (!empty($company['city'])): ?>
        <dt class="col-5 text-muted">Cidade</dt><dd class="col-7"><?= e($company['city']) ?><?= !empty($company['state']) ? '/' . e($company['state']) : '' ?></dd>
        <?php endif; ?>
        <?php if (!empty($company['notes'])): ?>
        <dt class="col-5 text-muted">Obs.</dt><dd class="col-7"><?= nl2br(e($company['notes'])) ?></dd>
        <?php endif; ?>
      </dl>
    </div>
  </div>

  <!-- Right: deals + contacts -->
  <div class="col-lg-8">
    <!-- Contacts -->
    <div class="card p-3 mb-3">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-semibold mb-0">Contatos (<?= count($contacts) ?>)</h6>
        <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modal-contact">
          <i class="bi bi-plus"></i>
        </button>
      </div>
      <?php if (empty($contacts)): ?>
        <p class="text-muted small mb-0">Nenhum contato vinculado.</p>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead class="table-light"><tr><th>Nome</th><th>Cargo</th><th>E-mail</th><th>Telefone</th></tr></thead>
          <tbody>
          <?php foreach ($contacts as $ct): ?>
          <tr>
            <td class="small"><?= e($ct['name']) ?></td>
            <td class="small text-muted"><?= e($ct['role_title'] ?? '') ?></td>
            <td class="small"><?= e($ct['email'] ?? '') ?></td>
            <td class="small"><?= e($ct['phone'] ?? '') ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- Deals -->
    <div class="card p-3">
      <h6 class="fw-semibold mb-2">Negociações (<?= count($deals) ?>)</h6>
      <?php if (empty($deals)): ?>
        <p class="text-muted small mb-0">Nenhuma negociação vinculada.</p>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead class="table-light"><tr><th>Título</th><th>Etapa</th><th>Valor</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach ($deals as $d): ?>
          <?php $st = $statusLabels[$d['status']] ?? $statusLabels['open']; ?>
          <tr>
            <td><a href="<?= $base ?>/crm/deals/<?= $d['id'] ?>" class="text-decoration-none small"><?= e($d['title']) ?></a></td>
            <td>
              <span class="badge rounded-pill" style="background:<?= e($d['stage_color'] ?? '#6366f1') ?>;font-size:.7rem;">
                <?= e($d['stage_name'] ?? '') ?>
              </span>
            </td>
            <td class="small"><?= $fmt($d['value']) ?></td>
            <td><span class="badge rounded-pill <?= $st['class'] ?>" style="font-size:.7rem;"><?= $st['label'] ?></span></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ── Modal: Edit Company ─── -->
<div class="modal fade" id="modal-edit-company" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Editar Empresa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="form-edit-company">
          <input type="hidden" name="_token" value="<?= $csrf ?>">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Razão Social <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" value="<?= e($company['name']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Nome Fantasia</label>
              <input type="text" name="fantasy_name" class="form-control" value="<?= e($company['fantasy_name'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">CNPJ</label>
              <input type="text" name="cnpj" class="form-control" value="<?= e($company['cnpj'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">E-mail</label>
              <input type="email" name="email" class="form-control" value="<?= e($company['email'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Telefone</label>
              <input type="text" name="phone" class="form-control" value="<?= e($company['phone'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">WhatsApp</label>
              <input type="text" name="whatsapp" class="form-control" value="<?= e($company['whatsapp'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Website</label>
              <input type="text" name="website" class="form-control" value="<?= e($company['website'] ?? '') ?>">
            </div>
            <div class="col-md-2">
              <label class="form-label">Estado</label>
              <input type="text" name="state" class="form-control" maxlength="2" value="<?= e($company['state'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Cidade</label>
              <input type="text" name="city" class="form-control" value="<?= e($company['city'] ?? '') ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Endereço</label>
              <input type="text" name="address" class="form-control" value="<?= e($company['address'] ?? '') ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Observações</label>
              <textarea name="notes" class="form-control" rows="2"><?= e($company['notes'] ?? '') ?></textarea>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btn-save-edit-company">Salvar</button>
      </div>
    </div>
  </div>
</div>

<!-- ── Modal: New Contact ─── -->
<div class="modal fade" id="modal-contact" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Novo Contato</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="form-contact">
          <input type="hidden" name="_token" value="<?= $csrf ?>">
          <input type="hidden" name="company_id" value="<?= $company['id'] ?>">
          <div class="mb-3">
            <label class="form-label">Nome <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Cargo</label>
            <input type="text" name="role_title" class="form-control">
          </div>
          <div class="row g-2">
            <div class="col-6">
              <label class="form-label">E-mail</label>
              <input type="email" name="email" class="form-control">
            </div>
            <div class="col-6">
              <label class="form-label">Telefone</label>
              <input type="text" name="phone" class="form-control">
            </div>
            <div class="col-6">
              <label class="form-label">WhatsApp</label>
              <input type="text" name="whatsapp" class="form-control">
            </div>
            <div class="col-6">
              <label class="form-label">LinkedIn</label>
              <input type="text" name="linkedin" class="form-control">
            </div>
          </div>
          <div class="mt-2">
            <label class="form-label">Observações</label>
            <textarea name="notes" class="form-control" rows="2"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btn-save-contact">Criar contato</button>
      </div>
    </div>
  </div>
</div>

<?php \Core\View::endSection() ?>
<?php \Core\View::section('scripts') ?>
<script>
const BASE       = '<?= $base ?>';
const CSRF       = '<?= $csrf ?>';
const COMPANY_ID = <?= $company['id'] ?>;

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

document.getElementById('btn-save-edit-company').addEventListener('click', async function () {
  const form = document.getElementById('form-edit-company');
  if (!form.reportValidity()) return;
  const res = await apiPost(`${BASE}/crm/companies/${COMPANY_ID}`, Object.fromEntries(new FormData(form)));
  if (res.success) { showToast('Empresa atualizada!'); setTimeout(() => location.reload(), 600); }
  else showToast(res.message, 'danger');
});

document.getElementById('btn-delete-company')?.addEventListener('click', async () => {
  if (!confirm('Excluir esta empresa?')) return;
  const res = await apiPost(`${BASE}/crm/companies/${COMPANY_ID}/delete`, {});
  if (res.success) { showToast('Empresa excluída.'); setTimeout(() => location.href = `${BASE}/crm/companies`, 800); }
  else showToast(res.message, 'danger');
});

document.getElementById('btn-save-contact').addEventListener('click', async function () {
  const form = document.getElementById('form-contact');
  if (!form.reportValidity()) return;
  this.disabled = true;
  const res = await apiPost(`${BASE}/crm/contacts`, Object.fromEntries(new FormData(form)));
  if (res.success) { showToast('Contato criado!'); setTimeout(() => location.reload(), 700); }
  else { showToast(res.message, 'danger'); this.disabled = false; }
});
</script>
<?php \Core\View::endSection() ?>
