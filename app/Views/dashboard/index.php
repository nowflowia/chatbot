<?php \Core\View::section('title') ?>Dashboard<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>Dashboard<?php \Core\View::endSection() ?>

<?php \Core\View::section('content') ?>

<?php $user = \Core\Auth::user(); ?>

<!-- Welcome -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold text-dark mb-0">
      Olá, <?= e(explode(' ', $user['name'] ?? 'Usuário')[0]) ?>! 👋
    </h5>
    <p class="text-muted small mb-0"><?= date('l, d \d\e F \d\e Y') ?></p>
  </div>

  <?php if (!empty($waSettings)): ?>
  <div class="d-flex align-items-center gap-2 px-3 py-2 rounded-3"
       style="background:<?= $waSettings['status'] === 'active' ? '#dcfce7' : '#fee2e2' ?>;font-size:.8rem;">
    <span class="rounded-circle" style="width:8px;height:8px;background:<?= $waSettings['status'] === 'active' ? '#16a34a' : '#dc2626' ?>;display:inline-block;"></span>
    <span style="color:<?= $waSettings['status'] === 'active' ? '#166534' : '#991b1b' ?>;font-weight:600;">
      WhatsApp <?= $waSettings['status'] === 'active' ? 'Conectado' : 'Desconectado' ?>
    </span>
  </div>
  <?php else: ?>
  <a href="<?= url('admin/settings') ?>" class="btn btn-sm btn-warning fw-semibold">
    <i class="bi bi-gear-fill me-1"></i> Configurar WhatsApp
  </a>
  <?php endif; ?>
</div>

<!-- ---- Stat Cards ---- -->
<div class="row g-3 mb-4">

  <div class="col-6 col-xl-3">
    <div class="card">
      <div class="stat-card">
        <div class="stat-icon" style="background:#dbeafe;">
          <i class="bi bi-chat-dots-fill" style="color:#2563eb;"></i>
        </div>
        <div>
          <div class="stat-value"><?= number_format($chatsToday) ?></div>
          <div class="stat-label">Chats hoje</div>
          <?php if ($chatsInProgress > 0): ?>
          <div class="stat-change" style="color:#16a34a;">
            <i class="bi bi-circle-fill" style="font-size:.45rem;"></i>
            <?= $chatsInProgress ?> em andamento
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-6 col-xl-3">
    <div class="card">
      <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;">
          <i class="bi bi-hourglass-split" style="color:#d97706;"></i>
        </div>
        <div>
          <div class="stat-value"><?= number_format($chatsWaiting) ?></div>
          <div class="stat-label">Na fila</div>
          <?php if ($chatsWaiting > 0): ?>
          <div class="stat-change" style="color:#d97706;">
            <i class="bi bi-exclamation-circle-fill" style="font-size:.7rem;"></i>
            Aguardando
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-6 col-xl-3">
    <div class="card">
      <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7;">
          <i class="bi bi-person-lines-fill" style="color:#16a34a;"></i>
        </div>
        <div>
          <div class="stat-value"><?= number_format($totalContacts) ?></div>
          <div class="stat-label">Contatos</div>
          <div class="stat-change text-muted">Total cadastrado</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-6 col-xl-3">
    <div class="card">
      <div class="stat-card">
        <div class="stat-icon" style="background:#f3e8ff;">
          <i class="bi bi-diagram-3-fill" style="color:#7c3aed;"></i>
        </div>
        <div>
          <div class="stat-value"><?= number_format($totalFlows) ?></div>
          <div class="stat-label">Fluxos ativos</div>
          <div class="stat-change text-muted"><?= number_format($messagesToday) ?> msg hoje</div>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- ---- Second row ---- -->
<div class="row g-3 mb-4">

  <!-- Resumo do dia -->
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-bar-chart-fill text-primary"></i> Resumo do dia
      </div>
      <div class="card-body p-0">
        <ul class="list-group list-group-flush">
          <li class="list-group-item d-flex align-items-center justify-content-between py-3 px-4">
            <div class="d-flex align-items-center gap-2 small">
              <span class="rounded-circle" style="width:10px;height:10px;background:#f59e0b;display:inline-block;flex-shrink:0;"></span>
              Na fila
            </div>
            <span class="fw-bold text-dark"><?= $chatsWaiting ?></span>
          </li>
          <li class="list-group-item d-flex align-items-center justify-content-between py-3 px-4">
            <div class="d-flex align-items-center gap-2 small">
              <span class="rounded-circle" style="width:10px;height:10px;background:#3b82f6;display:inline-block;flex-shrink:0;"></span>
              Em atendimento
            </div>
            <span class="fw-bold text-dark"><?= $chatsInProgress ?></span>
          </li>
          <li class="list-group-item d-flex align-items-center justify-content-between py-3 px-4">
            <div class="d-flex align-items-center gap-2 small">
              <span class="rounded-circle" style="width:10px;height:10px;background:#10b981;display:inline-block;flex-shrink:0;"></span>
              Finalizados hoje
            </div>
            <span class="fw-bold text-dark"><?= $chatsFinished ?></span>
          </li>
          <li class="list-group-item d-flex align-items-center justify-content-between py-3 px-4">
            <div class="d-flex align-items-center gap-2 small">
              <span class="rounded-circle" style="width:10px;height:10px;background:#8b5cf6;display:inline-block;flex-shrink:0;"></span>
              Usuários ativos
            </div>
            <span class="fw-bold text-dark"><?= $totalUsers ?></span>
          </li>
        </ul>
      </div>
      <div class="card-footer px-4 py-2">
        <a href="<?= url('admin/queue') ?>" class="small text-primary text-decoration-none">
          Ver fila <i class="bi bi-arrow-right"></i>
        </a>
      </div>
    </div>
  </div>

  <!-- Últimos usuários -->
  <div class="col-md-8">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-people-fill text-primary"></i> Usuários recentes
        </div>
        <a href="<?= url('admin/users') ?>" class="btn btn-sm btn-outline-primary">Ver todos</a>
      </div>
      <div class="table-responsive">
        <table class="table mb-0">
          <thead>
            <tr>
              <th class="ps-4">Nome</th>
              <th>Perfil</th>
              <th>Status</th>
              <th class="pe-4">Criado</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recentUsers)): ?>
            <tr><td colspan="4" class="empty-state py-4">
              <i class="bi bi-people"></i><p>Nenhum usuário ainda.</p>
            </td></tr>
            <?php else: ?>
            <?php
            $uc = ['#6366f1','#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6'];
            foreach ($recentUsers as $u):
              $bg = $uc[ord($u['name'][0]) % count($uc)];
            ?>
            <tr>
              <td class="ps-4">
                <div class="d-flex align-items-center gap-2">
                  <div style="width:30px;height:30px;background:<?= $bg ?>;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.7rem;font-weight:700;flex-shrink:0;">
                    <?= strtoupper(substr($u['name'],0,1)) ?>
                  </div>
                  <div>
                    <div class="fw-semibold small text-dark"><?= e($u['name']) ?></div>
                    <div class="text-xs text-muted"><?= e($u['email']) ?></div>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge rounded-pill text-xs" style="background:<?= roleBgColor($u['role_slug'] ?? '') ?>;color:#fff;">
                  <?= e($u['role_name'] ?? '-') ?>
                </span>
              </td>
              <td><?= dashStatusBadge($u['status']) ?></td>
              <td class="pe-4 text-xs text-muted"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<!-- ---- Gráfico semanal ---- -->
<div class="card mb-4">
  <div class="card-header d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
      <i class="bi bi-bar-chart-line-fill text-primary"></i> Conversas — últimos 7 dias
    </div>
    <span class="small text-muted"><?= number_format($messagesTotal) ?> mensagens no total</span>
  </div>
  <div class="card-body" style="height:180px;padding:12px 20px 0;">
    <canvas id="weeklyChart"></canvas>
  </div>
</div>

<!-- ---- Últimos chats ---- -->
<div class="card">
  <div class="card-header d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
      <i class="bi bi-chat-left-text-fill text-primary"></i> Últimas conversas
    </div>
    <a href="<?= url('admin/chat') ?>" class="btn btn-sm btn-outline-primary">Ver atendimento</a>
  </div>
  <div class="table-responsive">
    <table class="table mb-0">
      <thead>
        <tr>
          <th class="ps-4">Contato</th>
          <th>Status</th>
          <th>Atendente</th>
          <th>Última mensagem</th>
          <th class="pe-4">Atualizado</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recentChats)): ?>
        <tr><td colspan="5">
          <div class="empty-state">
            <i class="bi bi-chat-dots"></i>
            <p>Nenhuma conversa ainda. Configure a API do WhatsApp para começar a receber mensagens.</p>
            <a href="<?= url('admin/settings') ?>" class="btn btn-sm btn-primary mt-2">
              <i class="bi bi-gear me-1"></i>Configurar WhatsApp
            </a>
          </div>
        </td></tr>
        <?php else: ?>
        <?php foreach ($recentChats as $chat): ?>
        <tr>
          <td class="ps-4">
            <div class="fw-semibold small"><?= e($chat['contact_name'] ?: $chat['contact_phone'] ?: 'Desconhecido') ?></div>
            <?php if ($chat['contact_phone']): ?>
            <div class="text-xs text-muted"><?= e($chat['contact_phone']) ?></div>
            <?php endif; ?>
          </td>
          <td><?= chatStatusBadge($chat['status']) ?></td>
          <td class="small text-muted"><?= e($chat['agent_name'] ?: '—') ?></td>
          <td class="small text-muted" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            <?= e($chat['last_message'] ?: '—') ?>
          </td>
          <td class="pe-4 text-xs text-muted">
            <?= $chat['last_message_at'] ? date('d/m H:i', strtotime($chat['last_message_at'])) : date('d/m H:i', strtotime($chat['updated_at'])) ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php \Core\View::endSection() ?>

<?php \Core\View::section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
  // API status badge
  var dot  = document.getElementById('api-status-dot');
  var text = document.getElementById('api-status-text');
  if (dot && text) {
    <?php if (!empty($waSettings) && $waSettings['status'] === 'active'): ?>
    dot.style.background = '#16a34a';
    text.textContent     = 'WhatsApp OK';
    text.style.color     = '#16a34a';
    <?php elseif (!empty($waSettings)): ?>
    dot.style.background = '#dc2626';
    text.textContent     = 'Desconectado';
    text.style.color     = '#dc2626';
    <?php else: ?>
    dot.style.background = '#94a3b8';
    text.textContent     = 'Não configurado';
    <?php endif; ?>
  }

  // Weekly chart
  var ctx = document.getElementById('weeklyChart');
  if (ctx) {
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels:   <?= json_encode($weeklyLabels) ?>,
        datasets: [{
          label:           'Conversas',
          data:            <?= json_encode($weeklyData) ?>,
          backgroundColor: 'rgba(59,130,246,.20)',
          borderColor:     '#3b82f6',
          borderWidth:     2,
          borderRadius:    6,
          hoverBackgroundColor: 'rgba(59,130,246,.35)',
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { stepSize: 1, font: { size: 11 } },
            grid:  { color: 'rgba(0,0,0,.05)' },
          },
          x: {
            ticks: { font: { size: 11 } },
            grid:  { display: false },
          },
        },
      },
    });
  }
})();
</script>
<?php \Core\View::endSection() ?>

<?php
function chatStatusBadge(string $status): string {
  $map = [
    'waiting'     => ['#fef3c7','#92400e','clock-fill',        'Na fila'],
    'in_progress' => ['#dbeafe','#1e40af','circle-fill',       'Em atendimento'],
    'finished'    => ['#dcfce7','#166534','check-circle-fill', 'Finalizado'],
    'bot'         => ['#f3e8ff','#6d28d9','robot',             'Bot'],
  ];
  $s = $map[$status] ?? $map['waiting'];
  return "<span class=\"badge d-inline-flex align-items-center gap-1 text-xs\" style=\"background:{$s[0]};color:{$s[1]};\">
    <i class=\"bi bi-{$s[2]}\"></i>{$s[3]}</span>";
}
function dashStatusBadge(string $status): string {
  $map = [
    'active'   => ['#dcfce7','#166534','Ativo'],
    'inactive' => ['#fee2e2','#991b1b','Inativo'],
    'pending'  => ['#fef3c7','#92400e','Pendente'],
  ];
  $s = $map[$status] ?? $map['inactive'];
  return "<span class=\"badge text-xs\" style=\"background:{$s[0]};color:{$s[1]};\">{$s[2]}</span>";
}
function roleBgColor(string $slug): string {
  return match($slug) { 'admin' => '#6366f1', 'supervisor' => '#0ea5e9', 'agent' => '#10b981', default => '#64748b' };
}
?>
