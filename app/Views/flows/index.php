<?php \Core\View::section('title') ?>Fluxos<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>Fluxos de Atendimento<?php \Core\View::endSection() ?>
<?php \Core\View::section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <form class="d-flex gap-2" method="get">
    <input type="search" name="search" value="<?= e($search) ?>"
           class="form-control form-control-sm" placeholder="Buscar fluxo…" style="width:220px">
    <button class="btn btn-sm btn-outline-secondary">Buscar</button>
  </form>
  <button class="btn btn-primary btn-sm" id="btn-new-flow">
    <i class="bi bi-plus-lg me-1"></i>Novo Fluxo
  </button>
</div>

<?php if (empty($flows)): ?>
<div class="card p-5 text-center text-muted">
  <i class="bi bi-diagram-3 fs-1 mb-2 d-block text-primary opacity-40"></i>
  <p class="mb-0">Nenhum fluxo criado ainda.</p>
</div>
<?php else: ?>
<div class="row g-3">
  <?php foreach ($flows as $f): ?>
  <div class="col-12 col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body d-flex flex-column gap-1">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <h6 class="mb-0 fw-semibold"><?= e($f['name']) ?></h6>
            <div class="text-muted small mt-1"><?= e($f['description'] ?? '') ?></div>
          </div>
          <?php if ($f['is_active']): ?>
            <span class="badge bg-success-subtle text-success">Ativo</span>
          <?php else: ?>
            <span class="badge bg-secondary-subtle text-secondary">Inativo</span>
          <?php endif; ?>
        </div>
        <div class="d-flex gap-3 mt-2 small text-muted">
          <span><i class="bi bi-diagram-2 me-1"></i><?= (int)$f['node_count'] ?> blocos</span>
          <span><i class="bi bi-lightning me-1"></i><?= e($f['trigger']) ?></span>
        </div>
      </div>
      <div class="card-footer bg-transparent d-flex gap-2">
        <a href="<?= url('admin/flows/' . $f['id'] . '/edit') ?>" class="btn btn-sm btn-primary">
          <i class="bi bi-pencil me-1"></i>Editar
        </a>
        <button class="btn btn-sm btn-outline-danger btn-delete-flow"
                data-id="<?= (int)$f['id'] ?>" data-name="<?= e($f['name']) ?>">
          <i class="bi bi-trash"></i>
        </button>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Pagination -->
<?php if ($meta['last_page'] > 1): ?>
<nav class="mt-4">
  <ul class="pagination pagination-sm justify-content-center mb-0">
    <?php for ($p = 1; $p <= $meta['last_page']; $p++): ?>
    <li class="page-item <?= $p == $meta['current_page'] ? 'active' : '' ?>">
      <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>"><?= $p ?></a>
    </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>

<!-- New flow modal -->
<div class="modal fade" id="newFlowModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">Novo Fluxo</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label form-label-sm fw-semibold">Nome <span class="text-danger">*</span></label>
          <input type="text" id="nf-name" class="form-control form-control-sm" placeholder="Ex: Boas-vindas">
          <div class="invalid-feedback" id="err-nf-name"></div>
        </div>
        <div class="mb-3">
          <label class="form-label form-label-sm fw-semibold">Gatilho</label>
          <select id="nf-trigger" class="form-select form-select-sm">
            <option value="keyword">Palavra-chave</option>
            <option value="start">Início (primeira mensagem)</option>
            <option value="always">Sempre</option>
            <option value="manual">Manual</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label form-label-sm fw-semibold">Descrição</label>
          <textarea id="nf-desc" class="form-control form-control-sm" rows="2"
                    placeholder="Opcional…"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-sm btn-primary" id="btn-save-new-flow">
          <span class="spinner-border spinner-border-sm d-none me-1" id="nf-spinner"></span>
          Criar e Editar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  'use strict';
  const BASE = '<?= url('admin') ?>';

  document.getElementById('btn-new-flow').addEventListener('click', () => {
    document.getElementById('nf-name').value    = '';
    document.getElementById('nf-trigger').value = 'keyword';
    document.getElementById('nf-desc').value    = '';
    document.getElementById('nf-name').classList.remove('is-invalid');
    new bootstrap.Modal(document.getElementById('newFlowModal')).show();
    setTimeout(() => document.getElementById('nf-name').focus(), 300);
  });

  document.getElementById('btn-save-new-flow').addEventListener('click', function () {
    const name    = document.getElementById('nf-name').value.trim();
    const trigger = document.getElementById('nf-trigger').value;
    const desc    = document.getElementById('nf-desc').value.trim();
    const spinner = document.getElementById('nf-spinner');
    const errEl   = document.getElementById('err-nf-name');

    errEl.textContent = '';
    document.getElementById('nf-name').classList.remove('is-invalid');

    if (!name) {
      errEl.textContent = 'Nome é obrigatório.';
      document.getElementById('nf-name').classList.add('is-invalid');
      errEl.style.display = 'block';
      return;
    }

    this.disabled = true;
    spinner.classList.remove('d-none');

    Api.post(BASE + '/flows', { name, trigger, description: desc })
      .then(res => {
        this.disabled = false;
        spinner.classList.add('d-none');
        if (res.success && res.data.redirect) {
          window.location.href = res.data.redirect;
        } else {
          Toast.show(res.message || 'Erro ao criar fluxo.', 'danger');
        }
      });
  });

  document.querySelectorAll('.btn-delete-flow').forEach(btn => {
    btn.addEventListener('click', function () {
      const id   = this.dataset.id;
      const name = this.dataset.name;
      if (!confirm('Excluir o fluxo "' + name + '"? Esta ação não pode ser desfeita.')) return;
      this.disabled = true;
      Api.post(BASE + '/flows/' + id + '/delete', {})
        .then(res => {
          if (res.success) {
            Toast.show(res.message, 'success');
            setTimeout(() => window.location.reload(), 800);
          } else {
            Toast.show(res.message, 'danger');
            this.disabled = false;
          }
        });
    });
  });
})();
</script>
<?php \Core\View::endSection() ?>
