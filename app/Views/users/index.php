<?php \Core\View::section('title') ?>Usuários<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>Usuários<?php \Core\View::endSection() ?>

<?php \Core\View::section('content') ?>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-0 text-dark">Gerenciar Usuários</h5>
    <small class="text-muted">Total: <?= $pagination['total'] ?> usuário(s)</small>
  </div>
  <?php $atLimit = $maxUsers !== null && $activeCount >= $maxUsers; ?>
  <div class="d-flex align-items-center gap-3">
    <?php if ($maxUsers !== null): ?>
    <?php $pct = min(100, round($activeCount / $maxUsers * 100)); ?>
    <div style="min-width:200px;" id="license-counter">
      <div class="d-flex justify-content-between mb-1 small">
        <span class="text-muted fw-semibold">Usuários ativos</span>
        <span class="fw-bold license-count <?= $atLimit ? 'text-danger' : 'text-success' ?>">
          <?= $activeCount ?> / <?= $maxUsers ?>
        </span>
      </div>
      <div class="progress" style="height:8px;border-radius:4px;">
        <div class="progress-bar <?= $atLimit ? 'bg-danger' : ($pct >= 80 ? 'bg-warning' : 'bg-success') ?>"
             style="width:<?= $pct ?>%"></div>
      </div>
      <?php if ($atLimit): ?>
      <div class="license-sub text-danger small mt-1"><i class="bi bi-exclamation-triangle me-1"></i>Limite atingido</div>
      <?php else: ?>
      <div class="license-sub text-muted small mt-1"><?= $maxUsers - $activeCount ?> vaga(s) disponível(is)</div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <button class="btn btn-outline-secondary d-flex align-items-center gap-2"
            onclick="refreshLicense()" id="btn-refresh-license" title="Consultar API e atualizar limites da licença">
      <i class="bi bi-arrow-clockwise" id="refresh-icon"></i> Atualizar Licença
    </button>
    <button class="btn btn-primary d-flex align-items-center gap-2"
            onclick="openCreateModal()" id="btn-new-user"
            <?= $atLimit ? 'disabled title="Limite de usuários atingido"' : '' ?>>
      <i class="bi bi-person-plus-fill"></i> Novo Usuário
    </button>
  </div>
</div>

<?php if ($atLimit): ?>
<div id="license-alert-banner" class="alert alert-danger d-flex align-items-center gap-2 mb-4 small">
  <i class="bi bi-shield-lock-fill fs-5"></i>
  <div>
    <strong>Limite de licença atingido.</strong>
    Você possui <?= $maxUsers ?> usuário(s) ativo(s) permitido(s). Para adicionar mais, atualize sua licença em
    <a href="https://nowflow.com.br" target="_blank" class="fw-semibold">nowflow.com.br</a>.
  </div>
</div>
<?php endif; ?>

<!-- Search bar -->
<div class="card mb-3 p-3">
  <form method="GET" action="<?= url('admin/users') ?>" class="d-flex gap-2 align-items-center">
    <div class="input-group input-group-sm" style="max-width:320px;">
      <span class="input-group-text bg-light"><i class="bi bi-search text-muted"></i></span>
      <input type="text" name="search" class="form-control" placeholder="Buscar por nome ou e-mail…"
             value="<?= e($search) ?>">
    </div>
    <button type="submit" class="btn btn-sm btn-outline-primary">Buscar</button>
    <?php if ($search): ?>
      <a href="<?= url('admin/users') ?>" class="btn btn-sm btn-outline-secondary">Limpar</a>
    <?php endif; ?>
  </form>
</div>

<!-- Table -->
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="users-table">
      <thead class="table-light">
        <tr>
          <th class="ps-3" width="40">#</th>
          <th>Nome</th>
          <th>E-mail</th>
          <th>Perfil</th>
          <th>Status</th>
          <th>Criado em</th>
          <th class="text-end pe-3" width="120">Ações</th>
        </tr>
      </thead>
      <tbody id="users-tbody">
        <?php if (empty($users)): ?>
        <tr>
          <td colspan="7" class="text-center text-muted py-5">
            <i class="bi bi-people fs-2 d-block mb-2 opacity-50"></i>
            Nenhum usuário encontrado.
          </td>
        </tr>
        <?php else: ?>
        <?php foreach ($users as $u): ?>
        <tr id="row-<?= $u['id'] ?>">
          <td class="ps-3 text-muted small"><?= $u['id'] ?></td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="avatar-sm" style="width:34px;height:34px;background:<?= avatarColor($u['name']) ?>;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.78rem;font-weight:700;flex-shrink:0;">
                <?= e(strtoupper(substr($u['name'], 0, 1))) ?>
              </div>
              <div>
                <div class="fw-semibold text-dark small"><?= e($u['name']) ?></div>
                <?php if ($u['phone']): ?><div class="text-muted" style="font-size:.72rem;"><?= e($u['phone']) ?></div><?php endif; ?>
              </div>
            </div>
          </td>
          <td class="small text-muted"><?= e($u['email']) ?></td>
          <td>
            <span class="badge rounded-pill" style="background:<?= roleBadgeColor($u['role_slug'] ?? '') ?>;color:#fff;font-size:.72rem;">
              <?= e($u['role_name'] ?? 'Sem perfil') ?>
            </span>
          </td>
          <td><?= statusBadge($u['status']) ?></td>
          <td class="small text-muted"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
          <td class="text-end pe-3">
            <div class="dropdown">
              <button class="btn btn-sm btn-light" data-bs-toggle="dropdown" title="Ações">
                <i class="bi bi-three-dots-vertical"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li>
                  <button class="dropdown-item d-flex align-items-center gap-2"
                          onclick="openEditModal(<?= $u['id'] ?>)">
                    <i class="bi bi-pencil text-primary"></i> Editar
                  </button>
                </li>
                <li>
                  <button class="dropdown-item d-flex align-items-center gap-2"
                          onclick="resendInvite(<?= $u['id'] ?>, '<?= e($u['name']) ?>')">
                    <i class="bi bi-envelope text-info"></i> Reenviar convite
                  </button>
                </li>
                <?php if ($u['id'] != $currentUser['id']): ?>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <button class="dropdown-item d-flex align-items-center gap-2 text-danger"
                          onclick="confirmDelete(<?= $u['id'] ?>, '<?= e($u['name']) ?>')">
                    <i class="bi bi-trash"></i> Excluir
                  </button>
                </li>
                <?php endif; ?>
              </ul>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($pagination['last_page'] > 1): ?>
  <div class="card-footer d-flex align-items-center justify-content-between py-2 px-3">
    <small class="text-muted">
      Mostrando <?= $pagination['from'] ?? 1 ?>–<?= $pagination['to'] ?? count($users) ?> de <?= $pagination['total'] ?>
    </small>
    <nav>
      <ul class="pagination pagination-sm mb-0 gap-1">
        <?php for ($p = 1; $p <= $pagination['last_page']; $p++): ?>
        <li class="page-item <?= $p === $pagination['current_page'] ? 'active' : '' ?>">
          <a class="page-link rounded" href="<?= url('admin/users') ?>?page=<?= $p ?>&search=<?= urlencode($search) ?>">
            <?= $p ?>
          </a>
        </li>
        <?php endfor; ?>
      </ul>
    </nav>
  </div>
  <?php endif; ?>
</div>

<!-- ================================================================
     MODAL: Create / Edit User
================================================================ -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold" id="userModalLabel">Novo Usuário</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <div id="modal-alert"></div>
        <form id="user-form" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" id="user-id" name="user_id" value="">

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-semibold small">Nome completo <span class="text-danger">*</span></label>
              <input type="text" name="name" id="f-name" class="form-control"
                     placeholder="João da Silva" required>
              <div class="invalid-feedback" id="err-name"></div>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold small">E-mail <span class="text-danger">*</span></label>
              <input type="email" name="email" id="f-email" class="form-control"
                     placeholder="joao@empresa.com" required>
              <div class="invalid-feedback" id="err-email"></div>
            </div>

            <div class="col-sm-6">
              <label class="form-label fw-semibold small">Telefone</label>
              <input type="text" name="phone" id="f-phone" class="form-control"
                     placeholder="+55 11 99999-9999">
            </div>

            <div class="col-sm-6">
              <label class="form-label fw-semibold small">Perfil <span class="text-danger">*</span></label>
              <select name="role_id" id="f-role_id" class="form-select" required>
                <option value="">Selecione…</option>
                <?php foreach ($roles as $role): ?>
                <option value="<?= $role['id'] ?>"><?= e($role['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <div class="invalid-feedback" id="err-role_id"></div>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold small">Status <span class="text-danger">*</span></label>
              <select name="status" id="f-status" class="form-select" required>
                <option value="pending">Pendente (aguardando senha)</option>
                <option value="active">Ativo</option>
                <option value="inactive">Inativo</option>
              </select>
              <div class="invalid-feedback" id="err-status"></div>
            </div>

            <div class="col-12" id="invite-note">
              <div class="alert alert-info py-2 mb-0 d-flex align-items-start gap-2 small">
                <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
                Um e-mail com o link para criação de senha será enviado automaticamente.
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer border-top-0 pt-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary fw-semibold px-4" id="btn-save" onclick="saveUser()">
          <span class="spinner-border spinner-border-sm d-none me-2" id="save-spinner"></span>
          <span id="save-btn-text">Salvar</span>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ================================================================
     MODAL: Confirm Delete
================================================================ -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow">
      <div class="modal-body text-center p-4">
        <div style="width:56px;height:56px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto .75rem;">
          <i class="bi bi-trash-fill text-danger fs-5"></i>
        </div>
        <h6 class="fw-bold">Excluir usuário?</h6>
        <p class="text-muted small mb-0">Esta ação é irreversível.<br>
          <strong id="delete-user-name"></strong> será excluído.</p>
      </div>
      <div class="modal-footer border-0 pt-0 d-flex gap-2 justify-content-center">
        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger px-4 fw-semibold" id="btn-confirm-delete">
          <span class="spinner-border spinner-border-sm d-none me-1" id="delete-spinner"></span>
          Excluir
        </button>
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
const URLS = {
  store:          '<?= url('admin/users') ?>',
  show:           '<?= url('admin/users') ?>/',
  update:         '<?= url('admin/users') ?>/',
  delete:         '<?= url('admin/users') ?>/',
  invite:         '<?= url('admin/users') ?>/',
  refreshLicense: '<?= url('admin/users/refresh-license') ?>',
};

let userModal, deleteModal, currentDeleteId;

document.addEventListener('DOMContentLoaded', function () {
  userModal   = new bootstrap.Modal(document.getElementById('userModal'));
  deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
});

// ---- Open Create Modal ----
function openCreateModal() {
  resetForm();
  document.getElementById('userModalLabel').textContent = 'Novo Usuário';
  document.getElementById('save-btn-text').textContent  = 'Criar e enviar convite';
  document.getElementById('invite-note').style.display  = 'block';
  userModal.show();
}

// ---- Open Edit Modal ----
function openEditModal(id) {
  resetForm();
  document.getElementById('userModalLabel').textContent = 'Editar Usuário';
  document.getElementById('save-btn-text').textContent  = 'Salvar alterações';
  document.getElementById('invite-note').style.display  = 'none';
  document.getElementById('user-id').value = id;

  fetch(URLS.show + id, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json())
    .then(res => {
      if (!res.success) { Toast.show(res.message, 'error'); return; }
      const u = res.data;
      document.getElementById('f-name').value    = u.name    || '';
      document.getElementById('f-email').value   = u.email   || '';
      document.getElementById('f-phone').value   = u.phone   || '';
      document.getElementById('f-role_id').value = u.role_id || '';
      document.getElementById('f-status').value  = u.status  || 'active';
      userModal.show();
    });
}

// ---- Save (Create or Update) ----
function saveUser() {
  clearErrors();
  const id  = document.getElementById('user-id').value;
  const url = id ? URLS.update + id : URLS.store;

  const btn    = document.getElementById('btn-save');
  const spin   = document.getElementById('save-spinner');
  const btnTxt = document.getElementById('save-btn-text');

  btn.disabled = true;
  spin.classList.remove('d-none');
  const originalText = btnTxt.textContent;
  btnTxt.textContent = 'Salvando…';

  const fd = new FormData(document.getElementById('user-form'));

  fetch(url, {
    method: 'POST',
    body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false;
      spin.classList.add('d-none');
      btnTxt.textContent = originalText;

      if (res.success) {
        userModal.hide();
        Toast.show(res.message, 'success');
        setTimeout(() => location.reload(), 900);
      } else {
        document.getElementById('modal-alert').innerHTML =
          '<div class="alert alert-danger py-2 small d-flex gap-2"><i class="bi bi-exclamation-triangle-fill mt-1"></i><span>' + res.message + '</span></div>';
        if (res.errors) {
          Object.entries(res.errors).forEach(([field, msgs]) => {
            const el  = document.getElementById('err-' + field);
            const inp = document.getElementById('f-' + field);
            if (el)  { el.textContent = msgs[0]; el.style.display = 'block'; }
            if (inp) inp.classList.add('is-invalid');
          });
        }
      }
    })
    .catch(() => {
      btn.disabled = false;
      spin.classList.add('d-none');
      btnTxt.textContent = originalText;
      Toast.show('Erro de conexão.', 'error');
    });
}

// ---- Confirm Delete ----
function confirmDelete(id, name) {
  currentDeleteId = id;
  document.getElementById('delete-user-name').textContent = name;
  deleteModal.show();

  document.getElementById('btn-confirm-delete').onclick = function () {
    const btn  = this;
    const spin = document.getElementById('delete-spinner');
    btn.disabled = true;
    spin.classList.remove('d-none');

    const fd = new FormData();
    fd.append('_csrf_token', '<?= csrf_token() ?>');

    fetch(URLS.delete + id + '/delete', {
      method: 'POST',
      body: fd,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(r => r.json())
      .then(res => {
        btn.disabled = false;
        spin.classList.add('d-none');
        deleteModal.hide();

        if (res.success) {
          const row = document.getElementById('row-' + id);
          if (row) {
            row.style.transition = 'opacity .4s';
            row.style.opacity    = '0';
            setTimeout(() => row.remove(), 400);
          }
          Toast.show(res.message, 'success');
        } else {
          Toast.show(res.message, 'error');
        }
      })
      .catch(() => {
        btn.disabled = false;
        spin.classList.add('d-none');
        Toast.show('Erro de conexão.', 'error');
      });
  };
}

// ---- Resend Invite ----
function resendInvite(id, name) {
  if (!confirm('Reenviar convite por e-mail para ' + name + '?')) return;

  const fd = new FormData();
  fd.append('_csrf_token', '<?= csrf_token() ?>');

  fetch(URLS.invite + id + '/invite', {
    method: 'POST',
    body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
    .then(r => r.json())
    .then(res => Toast.show(res.message, res.success ? 'success' : 'error'))
    .catch(() => Toast.show('Erro de conexão.', 'error'));
}

// ---- Refresh License ----
function refreshLicense() {
  const btn  = document.getElementById('btn-refresh-license');
  const icon = document.getElementById('refresh-icon');
  btn.disabled = true;
  icon.style.animation = 'spin 1s linear infinite';
  icon.style.display = 'inline-block';

  const fd = new FormData();
  fd.append('_csrf_token', '<?= csrf_token() ?>');

  fetch(URLS.refreshLicense, {
    method: 'POST',
    body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false;
      icon.style.animation = '';

      if (!res.success) {
        Toast.show(res.message || 'Erro ao atualizar licença.', 'error');
        return;
      }

      const d = res.data;
      Toast.show('Licença atualizada.', 'success');

      // Update counter display
      const maxUsers    = d.max_users;
      const activeCount = d.active_count;
      const atLimit     = maxUsers !== null && activeCount >= maxUsers;

      // Update button state
      const btnNew = document.getElementById('btn-new-user');
      if (btnNew) {
        btnNew.disabled = atLimit;
        btnNew.title    = atLimit ? 'Limite de usuários atingido' : '';
      }

      // Update counter block
      const counterBlock = document.getElementById('license-counter');
      if (counterBlock && maxUsers) {
        const pct     = Math.min(100, Math.round(activeCount / maxUsers * 100));
        const color   = atLimit ? 'bg-danger' : (pct >= 80 ? 'bg-warning' : 'bg-success');
        const txtColor= atLimit ? 'text-danger' : 'text-success';
        const vagas   = maxUsers - activeCount;
        counterBlock.querySelector('.license-count').textContent = activeCount + ' / ' + maxUsers;
        counterBlock.querySelector('.license-count').className   = 'fw-bold license-count ' + txtColor;
        counterBlock.querySelector('.progress-bar').style.width  = pct + '%';
        counterBlock.querySelector('.progress-bar').className    = 'progress-bar ' + color;
        const sub = counterBlock.querySelector('.license-sub');
        if (atLimit) {
          sub.className   = 'text-danger small mt-1';
          sub.innerHTML   = '<i class="bi bi-exclamation-triangle me-1"></i>Limite atingido';
        } else {
          sub.className   = 'text-muted small mt-1';
          sub.textContent = vagas + ' vaga(s) disponível(is)';
        }

        // Show/hide alert banner
        const banner = document.getElementById('license-alert-banner');
        if (banner) banner.style.display = atLimit ? '' : 'none';
      }
    })
    .catch(() => {
      btn.disabled = false;
      icon.style.animation = '';
      Toast.show('Erro de conexão.', 'error');
    });
}

// ---- Helpers ----
function resetForm() {
  document.getElementById('user-form').reset();
  document.getElementById('user-id').value   = '';
  document.getElementById('modal-alert').innerHTML = '';
  clearErrors();
}

function clearErrors() {
  document.querySelectorAll('#user-form .is-invalid').forEach(el => el.classList.remove('is-invalid'));
  document.querySelectorAll('#user-form .invalid-feedback').forEach(el => { el.textContent = ''; el.style.display = 'none'; });
}
</script>
<?php \Core\View::endSection() ?>

<?php
// ---- View Helpers ----
function statusBadge(string $status): string {
  $map = [
    'active'   => ['bg' => '#dcfce7', 'color' => '#166534', 'icon' => 'check-circle-fill', 'label' => 'Ativo'],
    'inactive' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'icon' => 'x-circle-fill',     'label' => 'Inativo'],
    'pending'  => ['bg' => '#fef3c7', 'color' => '#92400e', 'icon' => 'clock-fill',         'label' => 'Pendente'],
  ];
  $s = $map[$status] ?? $map['inactive'];
  return "<span class=\"badge d-inline-flex align-items-center gap-1\" style=\"background:{$s['bg']};color:{$s['color']};font-size:.72rem;\">
    <i class=\"bi bi-{$s['icon']}\"></i>{$s['label']}</span>";
}

function roleBadgeColor(string $slug): string {
  return match($slug) {
    'admin'      => '#6366f1',
    'supervisor' => '#0ea5e9',
    'agent'      => '#10b981',
    default      => '#64748b',
  };
}

function avatarColor(string $name): string {
  $colors = ['#6366f1','#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#0ea5e9'];
  return $colors[ord($name[0]) % count($colors)];
}
?>
