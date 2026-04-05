<?php
/**
 * Owner panel configuration.
 * Change OWNER_PASSWORD before deploying!
 */
if (!defined('OWNER_ROOT')) define('OWNER_ROOT', dirname(__DIR__));

// Owner panel credentials (stored as bcrypt hash)
// To generate: php -r "echo password_hash('yourpassword', PASSWORD_DEFAULT);"
define('OWNER_USERNAME', $_ENV['OWNER_USERNAME'] ?? getenv('OWNER_USERNAME') ?: 'admin');
define('OWNER_PASSWORD_HASH', $_ENV['OWNER_PASSWORD_HASH'] ?? getenv('OWNER_PASSWORD_HASH')
    ?: '$2y$12$Y88u9dR9PCw5JTLvJTVr5uhyXgmNb4rSv4v/Ien/DxZD0ULFRdnS6'); // REPLACE THIS

define('SESSION_NAME', 'owner_session');
define('APP_NAME', 'License Manager');
