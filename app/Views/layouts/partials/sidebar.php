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

function isGroupOpen(array $segments): bool {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    foreach ($segments as $s) {
        if (str_contains($uri, $s)) return true;
    }
    return false;
}

$avatarColors = ['#6366f1','#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#0ea5e9'];
$avatarBg     = $avatarColors[ord(($authUser['name'] ?? 'U')[0]) % count($avatarColors)];

$chatOpen  = isGroupOpen(['/chat', 'queue', 'flow']);
$crmOpen   = isGroupOpen(['/crm']);
$adminOpen = isGroupOpen(['users', 'webhook-logs', 'settings']);
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

    <!-- Dashboard -->
    <ul class="nav flex-column mb-1">
      <li>
        <a href="<?= url('admin/dashboard') ?>" class="nav-link<?= isActive('dashboard') ?>">
          <i class="bi bi-speedometer2"></i>
          <span class="link-label">Dashboard</span>
        </a>
      </li>
    </ul>

    <!-- ── Chat ────────────────────────────────── -->
    <div class="nav-group">
      <button class="nav-group-toggle<?= $chatOpen ? ' open' : '' ?>"
              data-target="group-chat" type="button">
        <span class="d-flex align-items-center gap-2">
          <i class="bi bi-chat-dots-fill"></i>
          <span class="link-label">Chat</span>
        </span>
        <i class="bi bi-chevron-right nav-group-arrow"></i>
      </button>
      <ul class="nav flex-column nav-group-items<?= $chatOpen ? ' show' : '' ?>" id="group-chat">
        <li>
          <a href="<?= url('admin/chat') ?>" class="nav-link nav-sub<?= isActive('/chat') ?>">
            <i class="bi bi-chat-text-fill"></i>
            <span class="link-label">Atendimento</span>
          </a>
        </li>
        <li>
          <a href="<?= url('admin/queue') ?>" class="nav-link nav-sub<?= isActive('queue') ?>">
            <i class="bi bi-people-fill"></i>
            <span class="link-label">Fila</span>
          </a>
        </li>
        <li>
          <a href="<?= url('admin/flows') ?>" class="nav-link nav-sub<?= isActive('flow') ?>">
            <i class="bi bi-diagram-3-fill"></i>
            <span class="link-label">Fluxos</span>
          </a>
        </li>
      </ul>
    </div>

    <!-- ── CRM ─────────────────────────────────── -->
    <?php if (\Core\Auth::hasFeature('crm')): ?>
    <div class="nav-group">
      <button class="nav-group-toggle<?= $crmOpen ? ' open' : '' ?>"
              data-target="group-crm" type="button">
        <span class="d-flex align-items-center gap-2">
          <i class="bi bi-graph-up-arrow"></i>
          <span class="link-label">CRM</span>
        </span>
        <i class="bi bi-chevron-right nav-group-arrow"></i>
      </button>
      <ul class="nav flex-column nav-group-items<?= $crmOpen ? ' show' : '' ?>" id="group-crm">
        <li>
          <a href="<?= url('admin/crm') ?>" class="nav-link nav-sub<?= isActive('/crm') && !isActive('crm/companies') && !isActive('crm/contacts') && !isActive('crm/settings') ? ' active' : '' ?>">
            <i class="bi bi-layout-three-columns"></i>
            <span class="link-label">Negociações</span>
          </a>
        </li>
        <li>
          <a href="<?= url('admin/crm/companies') ?>" class="nav-link nav-sub<?= isActive('crm/companies') ?>">
            <i class="bi bi-building"></i>
            <span class="link-label">Empresas</span>
          </a>
        </li>
        <li>
          <a href="<?= url('admin/crm/contacts') ?>" class="nav-link nav-sub<?= isActive('crm/contacts') ?>">
            <i class="bi bi-person-lines-fill"></i>
            <span class="link-label">Contatos</span>
          </a>
        </li>
        <?php if (\Core\Auth::isSupervisorOrAdmin()): ?>
        <li>
          <a href="<?= url('admin/crm/settings') ?>" class="nav-link nav-sub<?= isActive('crm/settings') ?>">
            <i class="bi bi-sliders"></i>
            <span class="link-label">Configurar</span>
          </a>
        </li>
        <?php endif; ?>
      </ul>
    </div>
    <?php endif; ?>

    <!-- ── Administração ───────────────────────── -->
    <div class="nav-group">
      <button class="nav-group-toggle<?= $adminOpen ? ' open' : '' ?>"
              data-target="group-admin" type="button">
        <span class="d-flex align-items-center gap-2">
          <i class="bi bi-shield-lock-fill"></i>
          <span class="link-label">Administração</span>
        </span>
        <i class="bi bi-chevron-right nav-group-arrow"></i>
      </button>
      <ul class="nav flex-column nav-group-items<?= $adminOpen ? ' show' : '' ?>" id="group-admin">
        <li>
          <a href="<?= url('admin/users') ?>" class="nav-link nav-sub<?= isActive('users') ?>">
            <i class="bi bi-people"></i>
            <span class="link-label">Usuários</span>
          </a>
        </li>
        <li>
          <a href="<?= url('admin/webhook-logs') ?>" class="nav-link nav-sub<?= isActive('webhook-logs') ?>">
            <i class="bi bi-broadcast"></i>
            <span class="link-label">Logs Webhook</span>
          </a>
        </li>
        <?php if (\Core\Auth::isAdmin()): ?>
        <li>
          <a href="<?= url('admin/settings') ?>" class="nav-link nav-sub<?= isActive('settings') ?>">
            <i class="bi bi-gear-fill"></i>
            <span class="link-label">Configurações</span>
          </a>
        </li>
        <?php endif; ?>
      </ul>
    </div>

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

<style>
/* ── Nav groups ─────────────────────────────────────── */
.nav-group { margin-bottom: 2px; }

.nav-group-toggle {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 9px 16px;
  background: none;
  border: none;
  color: #94a3b8;
  font-size: .825rem;
  font-weight: 600;
  letter-spacing: .02em;
  cursor: pointer;
  border-radius: 8px;
  transition: background .15s, color .15s;
  text-align: left;
}
.nav-group-toggle:hover,
.nav-group-toggle.open {
  background: rgba(99,102,241,.08);
  color: #e2e8f0;
}
.nav-group-toggle.open .nav-group-arrow {
  transform: rotate(90deg);
}
.nav-group-arrow {
  font-size: .7rem;
  transition: transform .2s;
  flex-shrink: 0;
}

.nav-group-items {
  display: none;
  padding-left: 8px;
  margin-bottom: 2px;
}
.nav-group-items.show { display: flex; }

.nav-link.nav-sub {
  font-size: .8rem;
  padding: 7px 12px;
  border-radius: 6px;
}
.nav-link.nav-sub::before {
  content: '';
  display: inline-block;
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: currentColor;
  margin-right: 10px;
  opacity: .4;
  flex-shrink: 0;
}
.nav-link.nav-sub.active::before { opacity: 1; background: #6366f1; }
</style>

<script>
document.querySelectorAll('.nav-group-toggle').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var target = document.getElementById(this.dataset.target);
    var isOpen = target.classList.contains('show');
    // Close all
    document.querySelectorAll('.nav-group-items').forEach(function (el) {
      el.classList.remove('show');
    });
    document.querySelectorAll('.nav-group-toggle').forEach(function (el) {
      el.classList.remove('open');
    });
    // Open clicked if it was closed
    if (!isOpen) {
      target.classList.add('show');
      this.classList.add('open');
    }
  });
});
</script>
