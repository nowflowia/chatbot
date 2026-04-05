<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= csrf_token() ?>">
  <title><?= \Core\View::yield('title', config('app.name')) ?> | <?= e(config('app.name')) ?></title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- App CSS -->
  <link href="<?= url('assets/css/app.css') ?>" rel="stylesheet">

  <?= \Core\View::yield('styles') ?>
<?php
try {
    $__cs = \App\Models\CompanySetting::get();
    if (!empty($__cs['custom_css']) || !empty($__cs['primary_color'])) {
        $__color = htmlspecialchars($__cs['primary_color'] ?? '#3b82f6', ENT_QUOTES);
        echo "<style>\n";
        echo ":root { --bs-primary: {$__color}; --bs-btn-bg: {$__color}; }\n";
        if (!empty($__cs['custom_css'])) {
            echo $__cs['custom_css'] . "\n";
        }
        echo "</style>\n";
    }
} catch (\Throwable $e) { /* table may not exist yet */ }
?>
</head>
<body>

  <?php \Core\View::include('layouts/partials/sidebar') ?>
  <?php \Core\View::include('layouts/partials/topbar') ?>

  <!-- Main Content -->
  <main id="main-content">
    <?= \Core\View::yield('content') ?>
  </main>

  <!-- Toasts -->
  <?php \Core\View::include('layouts/partials/toasts') ?>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- App JS -->
  <script src="<?= url('assets/js/app.js') ?>"></script>

  <?= \Core\View::yield('scripts') ?>

</body>
</html>
