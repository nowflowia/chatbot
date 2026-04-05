<?php
/**
 * License database configuration.
 * Used by public_api/ and public_owner/ only.
 * This database is managed exclusively by the software owner.
 */
return [
    'host'     => $_ENV['LICENSE_DB_HOST']      ?? getenv('LICENSE_DB_HOST')      ?: 'localhost',
    'database' => $_ENV['LICENSE_DB_DATABASE']  ?? getenv('LICENSE_DB_DATABASE')  ?: 'chatbot_licenses',
    'username' => $_ENV['LICENSE_DB_USERNAME']  ?? getenv('LICENSE_DB_USERNAME')  ?: 'root',
    'password' => $_ENV['LICENSE_DB_PASSWORD']  ?? getenv('LICENSE_DB_PASSWORD')  ?: '',
    'charset'  => 'utf8mb4',
];
