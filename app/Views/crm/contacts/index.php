<?php \Core\View::section('title') ?>Contatos<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>CRM — Contatos<?php \Core\View::endSection() ?>
<?php \Core\View::section('content') ?>

<?php
$base    = url('admin');
$csrf    = \Core\CSRF::token();
$isAdmin = \Core\Auth::isSupervisorOrAdmin();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <form method="get" class="d-flex gap-2">
    <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar contato…" value="<?= e($search) ?>" style="width:260px;">
    <button class="btn btn-sm btn-primary">Buscar</button>
    <?php if ($search): ?>
    <a href="<?= $base ?>/crm/contacts" class="btn btn-sm btn-outline-secondary">Limpar</a>
    <?php endif; ?>
  </form>
  <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modal-contact">
    <i class="bi bi-plus-lg me-1"></i>Novo contato
  </button>
</div>

<div class="card p-0">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Nome</th>
          <th class="d-none d-md-table-cell">Empresa</th>
          <th class="d-none d-md-table-cell">Cargo</th>
          <th class="d-none d-md-table-cell">E-mail</th>
          <th class="d-none d-md-table-cell">Telefone</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($contacts)): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">Nenhum contato encontrado.</td></tr>
        <?php else: ?>
        <?php foreach ($contacts as $ct): ?>
        <tr>
          <td class="fw-semibold small"><?= e($ct['name']) ?></td>
          <td class="d-none d-md-table-cell small">
            <?php if (!empty($ct['company_name'])): ?>
            <a href="<?= $base ?>/crm/companies/<?= $ct['company_id'] ?>" class="text-decoration-none"><?= e($ct['company_name']) ?></a>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td class="d-none d-md-table-cell small text-muted"><?= e($ct['role_title'] ?? '—') ?></td>
          <td class="d-none d-md-table-cell small"><?= e($ct['email'] ?? '—') ?></td>
          <td class="d-none d-md-table-cell small"><?= e($ct['phone'] ?? '—') ?></td>
          <td class="text-end">
            <?php if ($isAdmin): ?>
            <button class="btn btn-sm btn-outline-danger btn-del-contact" data-id="<?= $ct['id'] ?>">
              <i class="bi bi-trash"></i>
            </button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Pagination -->
<?php if ($pagination['last_page'] > 1): ?>
<nav class="mt-3">
  <ul class="pagination pagination-sm justify-content-center mb-0">
    <?php for ($p = 1; $p <= $pagination['last_page']; $p++): ?>
    <li class="page-item <?= $p == $pagination['current_page'] ? 'active' : '' ?>">
      <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>"><?= $p ?></a>
    </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>

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
          <div class="mb-3">
            <label class="form-label">Nome <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Empresa</label>
            <input type="text" name="company_search" class="form-control" placeholder="Buscar empresa…" autocomplete="off" id="company-search">
            <input type="hidden" name="company_id" id="company-id">
            <div id="company-suggestions" class="list-group mt-1 position-absolute" style="z-index:1000;width:calc(100% - 3rem);display:none;"></div>
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

document.getElementById('btn-save-contact').addEventListener('click', async function () {
  const form = document.getElementById('form-contact');
  if (!form.reportValidity()) return;
  this.disabled = true;
  const data = Object.fromEntries(new FormData(form));
  const r = await fetch(`${BASE}/crm/contacts`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
    body: JSON.stringify({ ...data, _token: CSRF })
  });
  const res = await r.json();
  if (res.success) { showToast('Contato criado!'); setTimeout(() => location.reload(), 700); }
  else { showToast(res.message, 'danger'); this.disabled = false; }
});

document.querySelectorAll('.btn-del-contact').forEach(btn => {
  btn.addEventListener('click', async () => {
    if (!confirm('Excluir este contato?')) return;
    const r = await fetch(`${BASE}/crm/contacts/${btn.dataset.id}/delete`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
      body: JSON.stringify({ _token: CSRF })
    });
    const res = await r.json();
    if (res.success) { showToast('Contato excluído.'); btn.closest('tr').remove(); }
    else showToast(res.message, 'danger');
  });
});
</script>
<?php \Core\View::endSection() ?>
