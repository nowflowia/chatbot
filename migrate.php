#!/usr/bin/env php
<?php

declare(strict_types=1);

define('ROOT_PATH', __DIR__);
define('APP_PATH', ROOT_PATH . '/app');
define('CORE_PATH', ROOT_PATH . '/core');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('VIEW_PATH', APP_PATH . '/Views');

if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
} else {
    require_once ROOT_PATH . '/bootstrap/autoload.php';
}

// Load .env
$envFile = ROOT_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        $_ENV[$key] = $value; $_SERVER[$key] = $value; putenv("{$key}={$value}");
    }
}

date_default_timezone_set(env('TIMEZONE', 'America/Sao_Paulo'));

$command = $argv[1] ?? 'help';

// Ensure migrations table exists
function ensureMigrationsTable(): void {
    \Core\Database::getInstance()->statement("
        CREATE TABLE IF NOT EXISTS `migrations` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(255) NOT NULL,
            `batch` INT NOT NULL DEFAULT 1,
            `ran_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function getRanMigrations(): array {
    return array_column(\Core\Database::getInstance()->select("SELECT migration FROM migrations ORDER BY id ASC"), 'migration');
}

function getMigrationFiles(): array {
    $files = glob(ROOT_PATH . '/database/migrations/*.php') ?: [];
    sort($files);
    return $files;
}

function getMigrationClass(string $file): string {
    $name  = pathinfo($file, PATHINFO_FILENAME);
    // Pattern: 2024_01_01_000001_create_something_table
    // Skip first 4 segments (year_month_day_seq), rest is the class name
    $parts = explode('_', $name);
    if (count($parts) > 4) {
        $classParts = array_slice($parts, 4);
    } else {
        $classParts = $parts;
    }
    // Convert snake_case to PascalCase
    return implode('', array_map('ucfirst', $classParts));
}

switch ($command) {
    case 'make':
        $name = $argv[2] ?? null;
        if (!$name) { echo "Usage: php migrate.php make <name>\n"; exit(1); }
        $timestamp = date('Y_m_d_His');
        $filename  = $timestamp . '_' . $name . '.php';
        $class     = str_replace('_', '', ucwords($name, '_'));
        $path      = ROOT_PATH . '/database/migrations/' . $filename;
        file_put_contents($path, <<<PHP
<?php

use Core\\Migration;
use Core\\Blueprint;

class {$class} extends Migration
{
    public function up(): void
    {
        \$this->createTable('table_name', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name')->notNull();
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        \$this->dropTable('table_name');
    }
}
PHP);
        echo "Created: database/migrations/{$filename}\n";
        break;

    case 'up':
        ensureMigrationsTable();
        $ran   = getRanMigrations();
        $files = getMigrationFiles();
        $batch = (int)(\Core\Database::getInstance()->selectOne("SELECT MAX(batch) as b FROM migrations")['b'] ?? 0) + 1;
        $count = 0;
        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            if (in_array($name, $ran)) continue;
            require_once $file;
            $class = getMigrationClass($file);
            if (!class_exists($class)) { echo "  [SKIP] Class not found: {$class} in {$name}\n"; continue; }
            echo "Running: {$name}\n";
            (new $class())->up();
            \Core\Database::getInstance()->insert(
                "INSERT INTO migrations (migration, batch) VALUES (?, ?)", [$name, $batch]
            );
            $count++;
        }
        echo $count > 0 ? "\n{$count} migration(s) ran successfully.\n" : "Nothing to migrate.\n";
        break;

    case 'down':
        ensureMigrationsTable();
        $batch = (int)(\Core\Database::getInstance()->selectOne("SELECT MAX(batch) as b FROM migrations")['b'] ?? 0);
        if ($batch === 0) { echo "Nothing to rollback.\n"; break; }
        $ran  = \Core\Database::getInstance()->select("SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC", [$batch]);
        $count = 0;
        foreach ($ran as $row) {
            $name = $row['migration'];
            $file = ROOT_PATH . '/database/migrations/' . $name . '.php';
            if (!file_exists($file)) { echo "  [SKIP] File not found: {$name}\n"; continue; }
            require_once $file;
            $class = getMigrationClass($file);
            echo "Rolling back: {$name}\n";
            (new $class())->down();
            \Core\Database::getInstance()->delete("DELETE FROM migrations WHERE migration = ?", [$name]);
            $count++;
        }
        echo "{$count} migration(s) rolled back.\n";
        break;

    case 'status':
        ensureMigrationsTable();
        $ran   = getRanMigrations();
        $files = getMigrationFiles();
        echo str_pad('Migration', 60) . "Status\n";
        echo str_repeat('-', 70) . "\n";
        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $status = in_array($name, $ran) ? "\033[32mRan\033[0m" : "\033[33mPending\033[0m";
            echo str_pad($name, 60) . $status . "\n";
        }
        break;

    case 'fresh':
        echo "Dropping all tables and re-running migrations...\n";
        $tables = \Core\Database::getInstance()->select("SHOW TABLES");
        \Core\Database::getInstance()->statement("SET FOREIGN_KEY_CHECKS=0");
        foreach ($tables as $row) {
            $table = array_values($row)[0];
            \Core\Database::getInstance()->statement("DROP TABLE IF EXISTS `{$table}`");
            echo "  Dropped: {$table}\n";
        }
        \Core\Database::getInstance()->statement("SET FOREIGN_KEY_CHECKS=1");
        $argv[1] = 'up';
        include __FILE__;
        break;

    default:
        echo <<<HELP
ChatBot Migration Tool
Usage:
  php migrate.php make <name>    Create a new migration
  php migrate.php up             Run all pending migrations
  php migrate.php down           Rollback last batch
  php migrate.php status         Show migration status
  php migrate.php fresh          Drop all and re-run
HELP;
        break;
}
