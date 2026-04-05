<?php
ownerAuth();
$db = ownerDb();

// ── Actions ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $action = $_POST['_action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $id         = (int)($_POST['id'] ?? 0);
        $domain     = strtolower(trim($_POST['domain'] ?? ''));
        $domain     = preg_replace('#^(https?://)?(www\.)?#', '', $domain);
        $domain     = strtok($domain, '/');
        $plan       = $_POST['plan']       ?? 'basic';
        $max_users  = max(1,  (int)($_POST['max_users']  ?? 3));
        $max_flows  = max(1,  (int)($_POST['max_flows']  ?? 10));
        $status     = $_POST['status']     ?? 'trial';
        $expires_at = $_POST['expires_at'] ?: null;
        $notes      = trim($_POST['notes'] ?? '');
        $features   = json_encode(array_filter(array_map('trim', explode(',', $_POST['features'] ?? ''))));

        if (!$domain) { flash('error', 'Domínio obrigatório.'); header('Location: ?page=licenses'); exit; }

        if ($action === 'create') {
            $key = bin2hex(random_bytes(24)); // 48-char random key
            $db->prepare("INSERT INTO licenses
                (domain, secret_key, plan, max_users, max_flows, status, expires_at, features, notes, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())")
               ->execute([$domain, $key, $plan, $max_users, $max_flows, $status, $expires_at, $features, $notes]);
            flash('success', "Licença para {$domain} criada. Chave: {$key}");
        } else {
            $db->prepare("UPDATE licenses SET domain=?, plan=?, max_users=?, max_flows=?, status=?,
                expires_at=?, features=?, notes=?, updated_at=NOW() WHERE id=?")
               ->execute([$domain, $plan, $max_users, $max_flows, $status, $expires_at, $features, $notes, $id]);
            flash('success', "Licença #{$id} atualizada.");
        }
        header('Location: ?page=licenses'); exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare("DELETE FROM licenses WHERE id=?")->execute([$id]);
        flash('success', 'Licença removida.');
        header('Location: ?page=licenses'); exit;
    }

    if ($action === 'regenerate_key') {
        $id  = (int)($_POST['id'] ?? 0);
        $key = bin2hex(random_bytes(24));
        $db->prepare("UPDATE licenses SET secret_key=?, updated_at=NOW() WHERE id=?")->execute([$key, $id]);
        flash('success', "Nova chave gerada: {$key}");
        header('Location: ?page=licenses'); exit;
    }
}

// ── List ─────────────────────────────────────────────────────────
$edit = null;
if (isset($_GET['edit'])) {
    $edit = $db->prepare("SELECT * FROM licenses WHERE id=? LIMIT 1");
    $edit->execute([(int)$_GET['edit']]);
    $edit = $edit->fetch();
}

$licenses = $db->query("SELECT * FROM licenses ORDER BY id DESC")->fetchAll();

$statusColors = [
    'active'    => 'success',
    'trial'     => 'warning',
    'suspended' => 'secondary',
    'expired'   => 'danger',
];

layoutStart();
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <div class="page-title mb-0"><i class="bi bi-key me-2"></i>Licenças</div>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#licenseModal">
    <i class="bi bi-plus-lg me-1"></i> Nova licença
  </button>
</div>

<!-- Licenses table -->
<div class="card border-0 shadow-sm mb-4">
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>#</th><th>Domínio</th><th>Plano</th><th>Usuários</th><th>Fluxos</th>
          <th>Status</th><th>Expira em</th><th>Chave secreta</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($licenses)): ?>
        <tr><td colspan="9" class="text-center text-muted py-5">Nenhuma licença cadastrada.</td></tr>
        <?php else: foreach ($licenses as $lic): $cls = $statusColors[$lic['status']] ?? 'secondary'; ?>
        <tr>
          <td class="text-muted small"><?= $lic['id'] ?></td>
          <td class="fw-semibold small"><?= e($lic['domain']) ?></td>
          <td><span class="badge bg-primary-subtle text-primary"><?= e($lic['plan']) ?></span></td>
          <td class="text-center"><?= $lic['max_users'] ?></td>
          <td class="text-center"><?= $lic['max_flows'] ?></td>
          <td><span class="badge bg-<?= $cls ?>-subtle text-<?= $cls ?>"><?= $lic['status'] ?></span></td>
          <td class="small"><?= $lic['expires_at'] ? date('d/m/Y', strtotime($lic['expires_at'])) : '∞' ?></td>
          <td>
            <div class="input-group input-group-sm" style="width:200px">
              <input type="text" class="form-control font-monospace" value="<?= e($lic['secret_key']) ?>"
                     id="key-<?= $lic['id'] ?>" readonly style="font-size:.72rem">
              <button class="btn btn-outline-secondary" type="button"
                      onclick="copyKey('key-<?= $lic['id'] ?>')" title="Copiar">
                <i class="bi bi-clipboard"></i>
              </button>
            </div>
          </td>
          <td>
            <div class="d-flex gap-1">
              <a href="?page=licenses&edit=<?= $lic['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                <i class="bi bi-pencil"></i>
              </a>
              <!-- Regenerate key -->
              <form method="post" onsubmit="return confirm('Gerar nova chave? A antiga será invalidada!')">
                <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
                <input type="hidden" name="_action" value="regenerate_key">
                <input type="hidden" name="id" value="<?= $lic['id'] ?>">
                <button class="btn btn-sm btn-outline-warning" title="Gerar nova chave"><i class="bi bi-arrow-clockwise"></i></button>
              </form>
              <!-- Delete -->
              <form method="post" onsubmit="return confirm('Remover licença de <?= e($lic['domain']) ?>?')">
                <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
                <input type="hidden" name="_action" value="delete">
                <input type="hidden" name="id" value="<?= $lic['id'] ?>">
                <button class="btn btn-sm btn-outline-danger" title="Remover"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Create modal -->
<div class="modal fade" id="licenseModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
        <input type="hidden" name="_action" value="create">
        <div class="modal-header">
          <h6 class="modal-title fw-bold">Nova Licença</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <?php include __DIR__ . '/license_fields.php'; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary btn-sm">Criar licença</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit modal (shown when ?edit=ID) -->
<?php if ($edit): ?>
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
        <input type="hidden" name="_action" value="update">
        <input type="hidden" name="id" value="<?= $edit['id'] ?>">
        <div class="modal-header">
          <h6 class="modal-title fw-bold">Editar Licença #<?= $edit['id'] ?></h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <?php include __DIR__ . '/license_fields.php'; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary btn-sm">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  new bootstrap.Modal(document.getElementById('editModal')).show();
});
</script>
<?php endif; ?>

<script>
function copyKey(id) {
  var el = document.getElementById(id);
  navigator.clipboard.writeText(el.value).then(function () {
    var btn = el.nextElementSibling;
    btn.innerHTML = '<i class="bi bi-check2 text-success"></i>';
    setTimeout(function () { btn.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 2000);
  });
}
</script>
<?php layoutEnd('Licenças', 'licenses'); ?>
