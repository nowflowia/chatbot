<?php

namespace Core;

abstract class Migration
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    abstract public function up(): void;
    abstract public function down(): void;

    protected function createTable(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        $sql = $blueprint->toSQL();
        $this->db->statement($sql);
        echo "  Created table: {$table}\n";
    }

    protected function dropTable(string $table): void
    {
        $this->db->statement("DROP TABLE IF EXISTS `{$table}`");
        echo "  Dropped table: {$table}\n";
    }

    protected function addColumn(string $table, string $column, string $definition): void
    {
        $this->db->statement("ALTER TABLE `{$table}` ADD COLUMN {$column} {$definition}");
    }

    protected function dropColumn(string $table, string $column): void
    {
        $this->db->statement("ALTER TABLE `{$table}` DROP COLUMN `{$column}`");
    }

    protected function addIndex(string $table, string $column, string $indexName = ''): void
    {
        $name = $indexName ?: "idx_{$table}_{$column}";
        $this->db->statement("ALTER TABLE `{$table}` ADD INDEX `{$name}` (`{$column}`)");
    }
}

class Blueprint
{
    private string $table;
    private array $columns   = [];
    private array $indexes    = [];
    private array $foreignKeys = [];
    private string $engine   = 'InnoDB';
    private string $charset  = 'utf8mb4';
    private string $collation = 'utf8mb4_unicode_ci';

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function id(string $name = 'id'): static
    {
        $this->columns[] = "`{$name}` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY";
        return $this;
    }

    public function string(string $name, int $length = 255): static
    {
        $this->columns[] = "`{$name}` VARCHAR({$length})";
        return $this;
    }

    public function text(string $name): static
    {
        $this->columns[] = "`{$name}` TEXT";
        return $this;
    }

    public function longText(string $name): static
    {
        $this->columns[] = "`{$name}` LONGTEXT";
        return $this;
    }

    public function integer(string $name): static
    {
        $this->columns[] = "`{$name}` INT";
        return $this;
    }

    public function bigInteger(string $name): static
    {
        $this->columns[] = "`{$name}` BIGINT";
        return $this;
    }

    public function unsignedBigInteger(string $name): static
    {
        $this->columns[] = "`{$name}` BIGINT UNSIGNED";
        return $this;
    }

    public function boolean(string $name): static
    {
        $this->columns[] = "`{$name}` TINYINT(1)";
        return $this;
    }

    public function enum(string $name, array $values): static
    {
        $vals = implode("','", array_map('addslashes', $values));
        $this->columns[] = "`{$name}` ENUM('{$vals}')";
        return $this;
    }

    public function decimal(string $name, int $total = 8, int $places = 2): static
    {
        $this->columns[] = "`{$name}` DECIMAL({$total},{$places})";
        return $this;
    }

    public function date(string $name): static
    {
        $this->columns[] = "`{$name}` DATE";
        return $this;
    }

    public function dateTime(string $name): static
    {
        $this->columns[] = "`{$name}` DATETIME";
        return $this;
    }

    public function json(string $name): static
    {
        $this->columns[] = "`{$name}` JSON";
        return $this;
    }

    public function timestamp(string $name): static
    {
        $this->columns[] = "`{$name}` TIMESTAMP NULL DEFAULT NULL";
        return $this;
    }

    public function timestamps(): static
    {
        $this->columns[] = "`created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP";
        $this->columns[] = "`updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        return $this;
    }

    public function nullable(): static
    {
        $last = array_pop($this->columns);
        $this->columns[] = $last . ' NULL';
        return $this;
    }

    public function notNull(): static
    {
        $last = array_pop($this->columns);
        $this->columns[] = $last . ' NOT NULL';
        return $this;
    }

    public function default(mixed $value): static
    {
        $last = array_pop($this->columns);
        if ($value === null) {
            $this->columns[] = $last . ' DEFAULT NULL';
        } elseif (is_bool($value)) {
            $this->columns[] = $last . ' DEFAULT ' . ($value ? '1' : '0');
        } elseif (is_numeric($value)) {
            $this->columns[] = $last . " DEFAULT {$value}";
        } else {
            $this->columns[] = $last . " DEFAULT '{$value}'";
        }
        return $this;
    }

    public function unique(string $column, string $name = ''): static
    {
        $idxName = $name ?: "uniq_{$this->table}_{$column}";
        $this->indexes[] = "UNIQUE KEY `{$idxName}` (`{$column}`)";
        return $this;
    }

    public function index(string $column, string $name = ''): static
    {
        $idxName = $name ?: "idx_{$this->table}_{$column}";
        $this->indexes[] = "KEY `{$idxName}` (`{$column}`)";
        return $this;
    }

    public function foreign(string $column, string $refTable, string $refColumn = 'id', string $onDelete = 'CASCADE'): static
    {
        $fkName = "fk_{$this->table}_{$column}";
        $this->foreignKeys[] = "CONSTRAINT `{$fkName}` FOREIGN KEY (`{$column}`) REFERENCES `{$refTable}` (`{$refColumn}`) ON DELETE {$onDelete}";
        return $this;
    }

    public function toSQL(): string
    {
        $parts = array_merge($this->columns, $this->indexes, $this->foreignKeys);
        $defs  = implode(",\n  ", $parts);

        return "CREATE TABLE IF NOT EXISTS `{$this->table}` (\n  {$defs}\n) ENGINE={$this->engine} DEFAULT CHARSET={$this->charset} COLLATE={$this->collation}";
    }
}
