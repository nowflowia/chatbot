<?php

/**
 * Fallback PSR-4 autoloader — used when Composer vendor/ is not available.
 * When Composer IS available, public/index.php loads vendor/autoload.php instead.
 */
spl_autoload_register(function (string $class): void {
    $prefixMap = [
        'Core\\'  => ROOT_PATH . '/core/',
        'App\\'   => ROOT_PATH . '/app/',
    ];

    foreach ($prefixMap as $prefix => $basePath) {
        if (!str_starts_with($class, $prefix)) continue;
        $relative = substr($class, strlen($prefix));
        $file     = $basePath . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Also load helpers
require_once ROOT_PATH . '/core/Helpers.php';
