<?php

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

// Redirect to installer if not installed yet
if (!file_exists(ROOT_PATH . '/storage/.installed') && !file_exists(ROOT_PATH . '/.env')) {
    header('Location: /install/');
    exit;
}
define('APP_PATH', ROOT_PATH . '/app');
define('CORE_PATH', ROOT_PATH . '/core');
define('PUBLIC_PATH', __DIR__);
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('VIEW_PATH', APP_PATH . '/Views');
define('START_TIME', microtime(true));

if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
} else {
    require_once ROOT_PATH . '/bootstrap/autoload.php';
}

require_once ROOT_PATH . '/bootstrap/app.php';
