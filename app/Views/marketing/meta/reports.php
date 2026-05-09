<?php \Core\View::section('title') ?>Relatórios META<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>Relatórios META Ads<?php \Core\View::endSection() ?>
<?php \Core\View::section('content') ?>
<?php
$statusMap = [
    'active'           => ['bg' => '#dcfce7', 'color' => '#166534', 'dot' => '#16a34a', 'label' => 'Ativa'],
    'paused'           => ['bg' => '#fef9c3', 'color' => '#854d0e', 'dot' => '#ca8a04', 'label' => 'Pausada'],
    'draft'            => ['bg' => '#f1f5f9', 'color' => '#475569', 'dot' => '#94a3b8', 'label' => 'Rascunho'],
    'pending_approval' => ['bg' => '#fff7ed', 'color' => '#9a3412', 'dot' => '#ea580c', 'label' => 'Aguardando'],
    'completed'        => ['bg' => '#eff6ff', 'color' => '#1e40af', 'dot' => '#3b82f6', 'label' => 'Concluída'],
    'cancelled'        => ['bg' => '#fef2f2', 'color' => '#991b1b', 'dot' => '#dc2626', 'label' => 'Cancelada'],
];
$objectiveMap = [
    'OUTCOME_TRAFFIC'     => 'Tráfego',
    'OUTCOME_AWARENESS'   => 'Reconhecimento',
    'OUTCOME_ENGAGEMENT'  => 'Engajamento',
    'OUTCOME_LEADS'       => 'Leads',
    'OUTCOME_SALES'       => 'Vendas',
    'OUTCOME_APP_PROMOTION'=> 'App',
];
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-0">Relatórios de Campanhas</h5>
    <small class="text-muted">Desempenho e criativos das campanhas META Ads</small>
  </div>
  <a href="<?= url('admin/marketing/meta') ?>" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-robot me-1"></i>Agente META
  </a>
</div>

<?php if (empty($campaigns)): ?>
<div class="card border-0 shadow-sm">
  <div class="card-body text-center py-5">
    <i class="bi bi-bar-chart-line text-muted" style="font-size:3rem;"></i>
    <p class="text-muted mt-3 mb-0">Nenhuma campanha encontrada.<br>Use o Agente META para criar sua primeira campanha.</p>
    <a href="<?= url('admin/marketing/meta') ?>" class="btn btn-primary mt-3">
      <i class="bi bi-robot me-1"></i>Abrir Agente META
    </a>
  </div>
</div>
<?php else: ?>

<div class="row g-4">
<?php foreach ($campaigns as $c):
  $ins    = $c['insights'] ?? [];
  $copy   = $c['ad_copy']  ?? [];
  $st     = $statusMap[$c['status']] ?? $statusMap['draft'];
  $obj    = $objectiveMap[$c['objective']] ?? $c['objective'];

  // Metrics
  $impressions = number_format((int)($ins['impressions'] ?? 0));
  $reach       = number_format((int)($ins['reach']       ?? 0));
  $clicks      = number_format((int)($ins['clicks']      ?? 0));
  $spend       = 'R$ ' . number_format((float)($ins['spend'] ?? 0), 2, ',', '.');
  $cpm         = 'R$ ' . number_format((float)($ins['cpm']   ?? 0), 2, ',', '.');
  $cpc         = 'R$ ' . number_format((float)($ins['cpc']   ?? 0), 2, ',', '.');
  $ctr         = number_format((float)($ins['ctr'] ?? 0), 2) . '%';

  // Engagement from actions array
  $likes = $comments = $saves = $shares = $engagements = 0;
  foreach ($ins['actions'] ?? [] as $act) {
    $v = (int)($act['value'] ?? 0);
    match ($act['action_type'] ?? '') {
        'like', 'post_reaction'         => $likes      += $v,
        'comment'                       => $comments   += $v,
        'post_save'                     => $saves      += $v,
        'post'                          => $shares     += $v,
        'post_engagement', 'page_engagement' => $engagements += $v,
        default                         => null,
    };
  }
  $engRate = ($ins['reach'] ?? 0) > 0
    ? number_format(($engagements / $ins['reach']) * 100, 2) . '%'
    : '—';

  // Image
  $imageUrl = $copy['image_url'] ?? null;
  $headline  = $copy['headline'] ?? $c['name'];
  $body      = $copy['body']     ?? $c['strategy_brief'] ?? '';
?>
<div class="col-xl-4 col-lg-6">
  <div class="card border-0 shadow-sm h-100" style="overflow:hidden;">

    <!-- Creative image -->
    <?php if ($imageUrl): ?>
    <div style="position:relative;height:220px;overflow:hidden;background:#0f172a;">
      <img src="<?= e($imageUrl) ?>" alt="Criativo"
           style="width:100%;height:100%;object-fit:cover;opacity:.9;">
      <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.7) 0%,transparent 60%);"></div>
      <div style="position:absolute;bottom:12px;left:14px;right:14px;">
        <div class="fw-bold text-white" style="font-size:1rem;line-height:1.3;text-shadow:0 1px 4px rgba(0,0,0,.6);">
          <?= e($headline) ?>
        </div>
      </div>
      <?php if (!empty($c['meta_campaign_id'])): ?>
      <div style="position:absolute;top:10px;right:10px;">
        <span class="badge" style="background:rgba(0,0,0,.55);color:#fff;font-size:.65rem;backdrop-filter:blur(4px);">
          <i class="bi bi-meta me-1" style="color:#1877f2;"></i>Live
        </span>
      </div>
      <?php endif; ?>
    </div>
    <?php else: ?>
    <div style="height:120px;background:linear-gradient(135deg,#1877f2,#42b883);display:flex;align-items:center;justify-content:center;">
      <i class="bi bi-megaphone-fill text-white" style="font-size:2.5rem;opacity:.5;"></i>
    </div>
    <?php endif; ?>

    <div class="card-body p-0">

      <!-- Header -->
      <div class="px-3 pt-3 pb-2 border-bottom">
        <div class="d-flex align-items-start justify-content-between gap-2">
          <div class="flex-grow-1 min-w-0">
            <div class="fw-semibold text-truncate" style="font-size:.92rem;"><?= e($c['name']) ?></div>
            <div class="text-muted" style="font-size:.72rem;"><?= e($obj) ?> &middot; <?= date('d/m/Y', strtotime($c['created_at'])) ?></div>
          </div>
          <span class="badge rounded-pill flex-shrink-0"
                style="background:<?= $st['bg'] ?>;color:<?= $st['color'] ?>;font-size:.65rem;font-weight:600;">
            <span style="width:6px;height:6px;background:<?= $st['dot'] ?>;border-radius:50%;display:inline-block;margin-right:4px;"></span>
            <?= $st['label'] ?>
          </span>
        </div>

        <!-- Platforms -->
        <?php $plats = $c['platforms'] ?? []; if ($plats): ?>
        <div class="mt-1 d-flex gap-1 flex-wrap">
          <?php foreach ($plats as $p): ?>
          <span class="badge rounded-pill" style="background:#eff6ff;color:#1e40af;font-size:.63rem;">
            <?= $p === 'instagram' ? '<i class="bi bi-instagram me-1" style="color:#e1306c;"></i>Instagram' : '<i class="bi bi-facebook me-1" style="color:#1877f2;"></i>Facebook' ?>
          </span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Metrics grid -->
      <div class="px-3 py-2">
        <?php if (!empty($ins)): ?>
        <div class="row g-2 mb-2">

          <div class="col-6">
            <div class="rounded-3 p-2" style="background:#f0fdf4;">
              <div class="d-flex align-items-center gap-1 mb-1">
                <i class="bi bi-people-fill" style="color:#16a34a;font-size:.8rem;"></i>
                <span class="text-muted" style="font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.03em;">Alcance</span>
              </div>
              <div class="fw-bold" style="font-size:1.05rem;color:#15803d;"><?= $reach ?></div>
            </div>
          </div>

          <div class="col-6">
            <div class="rounded-3 p-2" style="background:#eff6ff;">
              <div class="d-flex align-items-center gap-1 mb-1">
                <i class="bi bi-eye-fill" style="color:#1d4ed8;font-size:.8rem;"></i>
                <span class="text-muted" style="font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.03em;">Impressões</span>
              </div>
              <div class="fw-bold" style="font-size:1.05rem;color:#1d4ed8;"><?= $impressions ?></div>
            </div>
          </div>

          <div class="col-6">
            <div class="rounded-3 p-2" style="background:#fdf4ff;">
              <div class="d-flex align-items-center gap-1 mb-1">
                <i class="bi bi-hand-index-thumb-fill" style="color:#7e22ce;font-size:.8rem;"></i>
                <span class="text-muted" style="font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.03em;">Cliques</span>
              </div>
              <div class="fw-bold" style="font-size:1.05rem;color:#7e22ce;"><?= $clicks ?></div>
            </div>
          </div>

          <div class="col-6">
            <div class="rounded-3 p-2" style="background:#fff7ed;">
              <div class="d-flex align-items-center gap-1 mb-1">
                <i class="bi bi-graph-up-arrow" style="color:#ea580c;font-size:.8rem;"></i>
                <span class="text-muted" style="font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.03em;">CTR</span>
              </div>
              <div class="fw-bold" style="font-size:1.05rem;color:#ea580c;"><?= $ctr ?></div>
            </div>
          </div>

        </div>

        <!-- Engagement row -->
        <div class="d-flex gap-2 mb-2">
          <div class="flex-fill rounded-3 p-2 text-center" style="background:#fff1f2;">
            <i class="bi bi-heart-fill" style="color:#e11d48;font-size:.85rem;"></i>
            <div class="fw-semibold mt-1" style="font-size:.88rem;color:#be123c;"><?= number_format($likes) ?></div>
            <div style="font-size:.62rem;color:#9f1239;font-weight:600;">Curtidas</div>
          </div>
          <div class="flex-fill rounded-3 p-2 text-center" style="background:#f0f9ff;">
            <i class="bi bi-chat-fill" style="color:#0284c7;font-size:.85rem;"></i>
            <div class="fw-semibold mt-1" style="font-size:.88rem;color:#0369a1;"><?= number_format($comments) ?></div>
            <div style="font-size:.62rem;color:#075985;font-weight:600;">Comentários</div>
          </div>
          <div class="flex-fill rounded-3 p-2 text-center" style="background:#f0fdf4;">
            <i class="bi bi-bookmark-fill" style="color:#16a34a;font-size:.85rem;"></i>
            <div class="fw-semibold mt-1" style="font-size:.88rem;color:#15803d;"><?= number_format($saves) ?></div>
            <div style="font-size:.62rem;color:#166534;font-weight:600;">Salvos</div>
          </div>
          <div class="flex-fill rounded-3 p-2 text-center" style="background:#fefce8;">
            <i class="bi bi-share-fill" style="color:#ca8a04;font-size:.85rem;"></i>
            <div class="fw-semibold mt-1" style="font-size:.88rem;color:#a16207;"><?= number_format($shares) ?></div>
            <div style="font-size:.62rem;color:#854d0e;font-weight:600;">Shares</div>
          </div>
        </div>

        <!-- Cost row -->
        <div class="d-flex gap-2 pb-1">
          <div class="flex-fill rounded-3 px-2 py-1" style="background:#f8fafc;border:1px solid #e2e8f0;">
            <div style="font-size:.63rem;color:#64748b;font-weight:600;">💰 Gasto</div>
            <div class="fw-bold" style="font-size:.9rem;color:#0f172a;"><?= $spend ?></div>
          </div>
          <div class="flex-fill rounded-3 px-2 py-1" style="background:#f8fafc;border:1px solid #e2e8f0;">
            <div style="font-size:.63rem;color:#64748b;font-weight:600;">📊 CPM</div>
            <div class="fw-bold" style="font-size:.9rem;color:#0f172a;"><?= $cpm ?></div>
          </div>
          <div class="flex-fill rounded-3 px-2 py-1" style="background:#f8fafc;border:1px solid #e2e8f0;">
            <div style="font-size:.63rem;color:#64748b;font-weight:600;">🖱 CPC</div>
            <div class="fw-bold" style="font-size:.9rem;color:#0f172a;"><?= $cpc ?></div>
          </div>
          <div class="flex-fill rounded-3 px-2 py-1" style="background:#f8fafc;border:1px solid #e2e8f0;">
            <div style="font-size:.63rem;color:#64748b;font-weight:600;">💡 Eng.</div>
            <div class="fw-bold" style="font-size:.9rem;color:#0f172a;"><?= $engRate ?></div>
          </div>
        </div>

        <!-- Last updated -->
        <?php if (!empty($c['insights_at'])): ?>
        <div class="text-end mt-1">
          <span class="text-muted" style="font-size:.65rem;">
            <i class="bi bi-clock me-1"></i>Atualizado <?= date('d/m H:i', strtotime($c['insights_at'])) ?>
          </span>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="text-center py-3">
          <i class="bi bi-bar-chart text-muted" style="font-size:1.8rem;opacity:.4;"></i>
          <p class="text-muted small mt-2 mb-0">Sem métricas ainda</p>
          <?php if (!empty($c['meta_campaign_id'])): ?>
          <button class="btn btn-sm btn-outline-primary mt-2"
                  onclick="refreshInsights(<?= $c['id'] ?>, this)">
            <i class="bi bi-arrow-clockwise me-1"></i>Buscar métricas
          </button>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Footer -->
      <?php if (!empty($c['meta_campaign_id'])): ?>
      <div class="px-3 pb-3 d-flex gap-2">
        <button class="btn btn-sm btn-outline-primary flex-fill"
                onclick="refreshInsights(<?= $c['id'] ?>, this)">
          <i class="bi bi-arrow-clockwise me-1"></i>Atualizar
        </button>
        <?php if ($imageUrl): ?>
        <a href="<?= e($imageUrl) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-box-arrow-up-right"></i>
        </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>
<?php endforeach; ?>
</div>

<?php endif; ?>

<?php \Core\View::endSection() ?>
<?php \Core\View::section('scripts') ?>
<script>
const REPORTS = { refresh: '<?= url('admin/marketing/meta/campaigns') ?>/' };

function refreshInsights(id, btn) {
  const orig = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
  fetch(REPORTS.refresh + id + '/refresh-insights', {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: (() => { const f = new FormData(); f.append('_csrf_token', '<?= csrf_token() ?>'); return f; })()
  })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false;
      btn.innerHTML = orig;
      if (res.success) { Toast.show('Métricas atualizadas!', 'success'); setTimeout(() => location.reload(), 800); }
      else Toast.show(res.message || 'Erro ao atualizar.', 'error');
    })
    .catch(() => { btn.disabled = false; btn.innerHTML = orig; Toast.show('Erro de conexão.', 'error'); });
}
</script>
<?php \Core\View::endSection() ?>
