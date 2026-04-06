<?php
$currentUri  = $_SERVER['REQUEST_URI'] ?? '/';
$authUser    = \Core\Auth::user();
$appName     = config('app.name', 'ChatBot');
try { $__company = \App\Models\CompanySetting::get(); } catch (\Throwable $e) { $__company = null; }
$__logo = $__company['logo_path'] ?? '';

function isActive(string $segment): string {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    return str_contains($uri, $segment) ? ' active' : '';
}

$avatarColors = ['#6366f1','#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#0ea5e9'];
$avatarBg     = $avatarColors[ord(($authUser['name'] ?? 'U')[0]) % count($avatarColors)];
?>
<!-- Sidebar overlay (mobile) -->
<div id="sidebar-overlay" class="sidebar-overlay"></div>

<nav id="sidebar">
  <!-- Brand -->
  <a href="<?= url('admin/dashboard') ?>" class="sidebar-brand">
    <?php if (!empty($__logo)): ?>
      <img src="<?= url($__logo) ?>" alt="<?= e($appName) ?>"
           style="max-height:36px;max-width:140px;object-fit:contain;">
    <?php else: ?>
      <div class="brand-icon"><i class="bi bi-chat-dots-fill"></i></div>
      <span class="brand-name"><?= e($appName) ?></span>
    <?php endif; ?>
  </a>

  <!-- Nav -->
  <div class="sidebar-nav">

    <div class="nav-section-title">Principal</div>
    <ul class="nav flex-column">
      <li>
        <a href="<?= url('admin/dashboard') ?>" class="nav-link<?= isActive('dashboard') ?>">
          <i class="bi bi-speedometer2"></i>
          <span class="link-label">Dashboard</span>
        </a>
      </li>
      <li>
        <a href="<?= url('admin/chat') ?>" class="nav-link<?= isActive('/chat') ?>">
          <i class="bi bi-chat-text-fill"></i>
          <span class="link-label">Atendimento</span>
        </a>
      </li>
      <li>
        <a href="<?= url('admin/queue') ?>" class="nav-link<?= isActive('queue') ?>">
          <i class="bi bi-people-fill"></i>
          <span class="link-label">Fila</span>
        </a>
      </li>
    </ul>

    <div class="nav-section-title">Automação</div>
    <ul class="nav flex-column">
      <li>
        <a href="<?= url('admin/flows') ?>" class="nav-link<?= isActive('flow') ?>">
          <i class="bi bi-diagram-3-fill"></i>
          <span class="link-label">Fluxos</span>
        </a>
      </li>
    </ul>

    <div class="nav-section-title">Administração</div>
    <ul class="nav flex-column">
      <li>
        <a href="<?= url('admin/users') ?>" class="nav-link<?= isActive('users') ?>">
          <i class="bi bi-people"></i>
          <span class="link-label">Usuários</span>
        </a>
      </li>
      <li>
        <a href="<?= url('admin/webhook-logs') ?>" class="nav-link<?= isActive('webhook-logs') ?>">
          <i class="bi bi-broadcast"></i>
          <span class="link-label">Logs Webhook</span>
        </a>
      </li>
      <li>
        <a href="<?= url('admin/settings') ?>" class="nav-link<?= isActive('settings') ?>">
          <i class="bi bi-gear-fill"></i>
          <span class="link-label">Configurações</span>
        </a>
      </li>
      <li>
        <a href="<?= url('admin/system-update') ?>" class="nav-link<?= isActive('system-update') ?>">
          <i class="bi bi-cloud-arrow-down-fill"></i>
          <span class="link-label">Atualização</span>
        </a>
      </li>
    </ul>

  </div><!-- /sidebar-nav -->

  <!-- Version -->
  <div class="px-3 py-1" style="font-size:.65rem;color:#475569;text-align:center;">
    v1.0.0 — NowFlow &copy; <?= date('Y') ?>
  </div>

  <!-- Footer / User -->
  <div class="sidebar-footer">
    <div class="dropdown">
      <a href="#" class="sidebar-user" data-bs-toggle="dropdown">
        <div class="user-avatar" style="background:<?= $avatarBg ?>">
          <?= strtoupper(substr($authUser['name'] ?? 'U', 0, 1)) ?>
        </div>
        <div class="user-info">
          <div class="user-name"><?= e($authUser['name'] ?? 'Usuário') ?></div>
          <div class="user-role"><?= e($authUser['role_name'] ?? '') ?></div>
        </div>
      </a>
      <ul class="dropdown-menu shadow-sm border-0 mb-1" style="min-width:180px;">
        <li><h6 class="dropdown-header text-xs"><?= e($authUser['email'] ?? '') ?></h6></li>
        <li><hr class="dropdown-divider my-1"></li>
        <li>
          <a class="dropdown-item text-danger d-flex align-items-center gap-2 small" href="<?= url('logout') ?>">
            <i class="bi bi-box-arrow-right"></i> Sair
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>
