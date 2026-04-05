<?php
ownerAuth();
$db = ownerDb();

// Clear logs action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'clear_logs') {
    csrfCheck();
    $days = max(1, (int)($_POST['days'] ?? 30));
    $db->prepare("DELETE FROM license_logs WHERE checked_at < DATE_SUB(NOW(), INTERVAL ? DAY)")->execute([$days]);
    flash('success', "Logs com mais de {$days} dias removidos.");
    header('Location: ?page=logs'); exit;
}

$domain = trim($_GET['domain'] ?? '');
$status = trim($_GET['status'] ?? '');

$where  = '1=1';
$params = [];
if ($domain) { $where .= ' AND domain LIKE ?'; $params[] = "%{$domain}%"; }
if ($status) { $where .= ' AND status = ?'; $params[] = $status; }

$total = (int)$db->prepare("SELECT COUNT(*) FROM license_logs WHERE {$where}")->execute($params) ? 0 : 0;
$stmt  = $db->prepare("SELECT * FROM license_logs WHERE {$where} ORDER BY id DESC LIMIT 200");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$statusColors = [
    'active'=>'success','valid'=>'success','trial'=>'warning',
    'not_found'=>'danger','expired'=>'danger','suspended'=>'secondary',
];

layoutStart();
?>
<div class="page-title"><i class="bi bi-journal-text me-2"></i>Logs de Verificação</div>

<!-- Filters -->
<form class="d-flex flex-wrap gap-2 mb-3" method="get">
  <input type="hidden" name="page" value="logs">
  <input type="text" name="domain" class="form-control form-control-sm" style="width:200px"
         placeholder="Filtrar domínio" value="<?= e($domain) ?>">
  <select name="status" class="form-select form-select-sm" style="width:160px">
    <option value="">Todos status</option>
    <?php foreach (['active','trial','not_found','expired','suspended'] as $s): ?>
    <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-sm btn-outline-secondary">Filtrar</button>
  <a href="?page=logs" class="btn btn-sm btn-outline-secondary">Limpar</a>

  <!-- Clear old logs -->
  <form method="post" class="d-flex gap-2 ms-auto" onsubmit="return confirm('Remover logs antigos?')">
    <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
    <input type="hidden" name="_action" value="clear_logs">
    <div class="input-group input-group-sm">
      <input type="number" name="days" class="form-control" value="30" min="1" style="width:70px">
      <span class="input-group-text">dias</span>
    </div>
    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Limpar antigos</button>
  </form>
</form>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
      <thead class="table-light">
        <tr><th>Data</th><th>Domínio</th><th>IP</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php if (empty($logs)): ?>
        <tr><td colspan="4" class="text-center text-muted py-5">Nenhum log encontrado.</td></tr>
        <?php else: foreach ($logs as $l):
          $cls = $statusColors[$l['status']] ?? 'secondary'; ?>
        <tr>
          <td class="small text-nowrap"><?= date('d/m/y H:i:s', strtotime($l['checked_at'])) ?></td>
          <td class="small fw-semibold"><?= e($l['domain']) ?></td>
          <td class="small text-muted"><?= e($l['ip_address']) ?></td>
          <td><span class="badge bg-<?= $cls ?>-subtle text-<?= $cls ?>"><?= e($l['status']) ?></span></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php layoutEnd('Logs', 'logs'); ?>
