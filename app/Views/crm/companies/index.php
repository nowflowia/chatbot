<?php \Core\View::section('title') ?>Empresas<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>CRM — Empresas<?php \Core\View::endSection() ?>
<?php \Core\View::section('content') ?>

<?php
$base    = url('admin');
$csrf    = \Core\CSRF::token();
$isAdmin = \Core\Auth::isSupervisorOrAdmin();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <form method="get" class="d-flex gap-2">
    <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar empresa…" value="<?= e($search) ?>" style="width:260px;">
    <button class="btn btn-sm btn-primary">Buscar</button>
    <?php if ($search): ?>
    <a href="<?= $base ?>/crm/companies" class="btn btn-sm btn-outline-secondary">Limpar</a>
    <?php endif; ?>
  </form>
  <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modal-company">
    <i class="bi bi-plus-lg me-1"></i>Nova empresa
  </button>
</div>

<div class="card p-0">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Nome</th>
          <th class="d-none d-md-table-cell">CNPJ</th>
          <th class="d-none d-md-table-cell">E-mail</th>
          <th class="d-none d-md-table-cell">Telefone</th>
          <th class="text-center">Negociações</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($companies)): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">Nenhuma empresa encontrada.</td></tr>
        <?php else: ?>
        <?php foreach ($companies as $c): ?>
        <tr>
          <td>
            <a href="<?= $base ?>/crm/companies/<?= $c['id'] ?>" class="fw-semibold text-decoration-none">
              <?= e($c['name']) ?>
            </a>
            <?php if (!empty($c['fantasy_name'])): ?>
            <div class="small text-muted"><?= e($c['fantasy_name']) ?></div>
            <?php endif; ?>
          </td>
          <td class="d-none d-md-table-cell small"><?= e($c['cnpj'] ?? '—') ?></td>
          <td class="d-none d-md-table-cell small"><?= e($c['email'] ?? '—') ?></td>
          <td class="d-none d-md-table-cell small"><?= e($c['phone'] ?? '—') ?></td>
          <td class="text-center"><span class="badge bg-secondary rounded-pill"><?= $c['deal_count'] ?? 0 ?></span></td>
          <td class="text-end">
            <a href="<?= $base ?>/crm/companies/<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary">
              <i class="bi bi-eye"></i>
            </a>
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

<!-- ── Modal: New Company ─── -->
<div class="modal fade" id="modal-company" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Nova Empresa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="form-company">
          <input type="hidden" name="_token" value="<?= $csrf ?>">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Razão Social <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Nome Fantasia</label>
              <input type="text" name="fantasy_name" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">CNPJ</label>
              <input type="text" name="cnpj" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">E-mail</label>
              <input type="email" name="email" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Telefone</label>
              <input type="text" name="phone" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">WhatsApp</label>
              <input type="text" name="whatsapp" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Website</label>
              <input type="text" name="website" class="form-control">
            </div>
            <div class="col-md-2">
              <label class="form-label">Estado</label>
              <input type="text" name="state" class="form-control" maxlength="2" placeholder="SP">
            </div>
            <div class="col-md-6">
              <label class="form-label">Cidade</label>
              <input type="text" name="city" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Endereço</label>
              <input type="text" name="address" class="form-control">
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
        <button type="button" class="btn btn-success" id="btn-save-company">Criar empresa</button>
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

document.getElementById('btn-save-company').addEventListener('click', async function () {
  const form = document.getElementById('form-company');
  if (!form.reportValidity()) return;
  this.disabled = true;
  const data = Object.fromEntries(new FormData(form));
  const r = await fetch(`${BASE}/crm/companies`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
    body: JSON.stringify(data)
  });
  const res = await r.json();
  if (res.success) {
    showToast('Empresa criada!');
    setTimeout(() => location.reload(), 700);
  } else {
    showToast(res.message || 'Erro ao criar.', 'danger');
    this.disabled = false;
  }
});
</script>
<?php \Core\View::endSection() ?>
