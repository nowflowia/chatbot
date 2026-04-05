<?php $authUser = \Core\Auth::user(); ?>
<header id="topbar">
  <!-- Toggle -->
  <button id="sidebar-toggle" class="topbar-toggle" title="Recolher menu">
    <i class="bi bi-list"></i>
  </button>

  <!-- Page title -->
  <div class="topbar-title"><?= \Core\View::yield('page-title', 'Dashboard') ?></div>

  <!-- Spacer -->
  <div class="flex-grow-1"></div>

  <!-- Right side -->
  <div class="d-flex align-items-center gap-2">

    <!-- API Status indicator -->
    <div id="api-status-indicator" class="d-flex align-items-center gap-1 px-2 py-1 rounded-2"
         style="background:#f1f5f9;font-size:.72rem;color:#64748b;">
      <span class="rounded-circle d-inline-block" id="api-status-dot"
            style="width:7px;height:7px;background:#94a3b8;flex-shrink:0;"></span>
      <span id="api-status-text">WhatsApp</span>
    </div>

    <!-- User dropdown -->
    <div class="dropdown">
      <button class="btn btn-sm btn-light d-flex align-items-center gap-2 py-1 px-2 rounded-3"
              data-bs-toggle="dropdown">
        <div style="width:28px;height:28px;background:#3b82f6;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.7rem;font-weight:700;">
          <?= strtoupper(substr($authUser['name'] ?? 'U', 0, 1)) ?>
        </div>
        <span class="d-none d-md-inline text-sm fw-semibold text-dark">
          <?= e(explode(' ', $authUser['name'] ?? 'Usuário')[0]) ?>
        </span>
        <i class="bi bi-chevron-down text-xs text-muted"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-1" style="min-width:200px;">
        <li>
          <div class="px-3 py-2">
            <div class="fw-semibold text-dark small"><?= e($authUser['name'] ?? '') ?></div>
            <div class="text-muted text-xs"><?= e($authUser['email'] ?? '') ?></div>
            <span class="badge rounded-pill mt-1" style="font-size:.65rem;background:#e0e7ff;color:#4338ca;">
              <?= e($authUser['role_name'] ?? '') ?>
            </span>
          </div>
        </li>
        <li><hr class="dropdown-divider my-1"></li>
        <li>
          <a class="dropdown-item d-flex align-items-center gap-2 small text-danger" href="<?= url('logout') ?>">
            <i class="bi bi-box-arrow-right"></i> Sair
          </a>
        </li>
      </ul>
    </div>

  </div>
</header>
