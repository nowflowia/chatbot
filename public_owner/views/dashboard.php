<?php
ownerAuth();
$db = ownerDb();

$stats = [
    'total'     => (int)$db->query("SELECT COUNT(*) FROM licenses")->fetchColumn(),
    'active'    => (int)$db->query("SELECT COUNT(*) FROM licenses WHERE status='active'")->fetchColumn(),
    'trial'     => (int)$db->query("SELECT COUNT(*) FROM licenses WHERE status='trial'")->fetchColumn(),
    'suspended' => (int)$db->query("SELECT COUNT(*) FROM licenses WHERE status='suspended'")->fetchColumn(),
    'expired'   => (int)$db->query("SELECT COUNT(*) FROM licenses WHERE status='expired'")->fetchColumn(),
    'checks_today' => (int)$db->query("SELECT COUNT(*) FROM license_logs WHERE DATE(checked_at)=CURDATE()")->fetchColumn(),
];

$recent = $db->query(
    "SELECT ll.*, l.plan FROM license_logs ll
     LEFT JOIN licenses l ON l.domain = ll.domain
     ORDER BY ll.id DESC LIMIT 20"
)->fetchAll();

layoutStart();
?>
<div class="page-title"><i class="bi bi-grid me-2"></i>Dashboard</div>

<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['label'=>'Total de licenças', 'value'=>$stats['total'],     'icon'=>'key',           'color'=>'primary'],
    ['label'=>'Ativas',            'value'=>$stats['active'],    'icon'=>'check-circle',  'color'=>'success'],
    ['label'=>'Trial',             'value'=>$stats['trial'],     'icon'=>'clock-history', 'color'=>'warning'],
    ['label'=>'Suspensas',         'value'=>$stats['suspended'], 'icon'=>'pause-circle',  'color'=>'secondary'],
    ['label'=>'Expiradas',         'value'=>$stats['expired'],   'icon'=>'x-circle',      'color'=>'danger'],
    ['label'=>'Verificações hoje', 'value'=>$stats['checks_today'], 'icon'=>'activity',   'color'=>'info'],
  ];
  foreach ($cards as $c): ?>
  <div class="col-md-4 col-xl-2">
    <div class="stat-card text-center">
      <i class="bi bi-<?= $c['icon'] ?> text-<?= $c['color'] ?> fs-4 mb-1 d-block"></i>
      <div class="fw-bold fs-4"><?= $c['value'] ?></div>
      <div class="text-muted small"><?= $c['label'] ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white fw-semibold"><i class="bi bi-journal-text me-2"></i>Verificações recentes</div>
  <div class="table-responsive">
    <table class="table table-sm table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr><th>Data</th><th>Domínio</th><th>IP</th><th>Status</th><th>Plano</th></tr>
      </thead>
      <tbody>
        <?php if (empty($recent)): ?>
        <tr><td colspan="5" class="text-center text-muted py-4">Nenhuma verificação ainda.</td></tr>
        <?php else: foreach ($recent as $r): ?>
        <?php
          $cls = match($r['status']) {
            'active','valid' => 'success', 'trial' => 'warning',
            'not_found','expired','suspended' => 'danger', default => 'secondary'
          };
        ?>
        <tr>
          <td class="small text-nowrap"><?= date('d/m/y H:i', strtotime($r['checked_at'])) ?></td>
          <td class="small fw-semibold"><?= e($r['domain']) ?></td>
          <td class="small text-muted"><?= e($r['ip_address']) ?></td>
          <td><span class="badge bg-<?= $cls ?>-subtle text-<?= $cls ?>"><?= e($r['status']) ?></span></td>
          <td class="small"><?= e($r['plan'] ?? '—') ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php layoutEnd('Dashboard', 'dashboard'); ?>
