<?php

namespace Core;

abstract class Model
{
    protected static string $table    = '';
    protected static string $primaryKey = 'id';
    protected array $fillable         = [];
    protected array $hidden           = [];
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function getTable(): string
    {
        if (static::$table !== '') {
            return static::$table;
        }
        $class = (new \ReflectionClass(static::class))->getShortName();
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class)) . 's';
    }

    public static function find(int|string $id): ?array
    {
        $db    = Database::getInstance();
        $table = static::getTable();
        $pk    = static::$primaryKey;
        return $db->selectOne("SELECT * FROM `{$table}` WHERE `{$pk}` = ? LIMIT 1", [$id]);
    }

    public static function findOrFail(int|string $id): array
    {
        $result = static::find($id);
        if ($result === null) {
            throw new \RuntimeException(static::getTable() . " record {$id} not found");
        }
        return $result;
    }

    public static function all(string $orderBy = ''): array
    {
        $db    = Database::getInstance();
        $table = static::getTable();
        $sql   = "SELECT * FROM `{$table}`";
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        return $db->select($sql);
    }

    public static function where(string $column, mixed $value, string $operator = '='): array
    {
        $db    = Database::getInstance();
        $table = static::getTable();
        return $db->select("SELECT * FROM `{$table}` WHERE `{$column}` {$operator} ?", [$value]);
    }

    public static function findWhere(string $column, mixed $value): ?array
    {
        $db    = Database::getInstance();
        $table = static::getTable();
        return $db->selectOne("SELECT * FROM `{$table}` WHERE `{$column}` = ? LIMIT 1", [$value]);
    }

    public static function create(array $data): string|false
    {
        $db       = Database::getInstance();
        $table    = static::getTable();
        $instance = new static();
        $data     = $instance->filterFillable($data);
        $data     = $instance->addTimestamps($data, true);

        $columns  = implode('`, `', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        return $db->insert(
            "INSERT INTO `{$table}` (`{$columns}`) VALUES ({$placeholders})",
            array_values($data)
        );
    }

    public static function update(int|string $id, array $data): int
    {
        $db       = Database::getInstance();
        $table    = static::getTable();
        $pk       = static::$primaryKey;
        $instance = new static();
        $data     = $instance->filterFillable($data);
        $data     = $instance->addTimestamps($data, false);

        $sets = implode(' = ?, ', array_map(fn($k) => "`{$k}`", array_keys($data))) . ' = ?';

        return $db->update(
            "UPDATE `{$table}` SET {$sets} WHERE `{$pk}` = ?",
            [...array_values($data), $id]
        );
    }

    public static function delete(int|string $id): int
    {
        $db    = Database::getInstance();
        $table = static::getTable();
        $pk    = static::$primaryKey;
        return $db->delete("DELETE FROM `{$table}` WHERE `{$pk}` = ?", [$id]);
    }

    public static function count(string $where = '', array $bindings = []): int
    {
        $db    = Database::getInstance();
        $table = static::getTable();
        $sql   = "SELECT COUNT(*) as cnt FROM `{$table}`";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        $result = $db->selectOne($sql, $bindings);
        return (int)($result['cnt'] ?? 0);
    }

    public static function paginate(int $page, int $perPage, string $where = '', array $bindings = [], string $orderBy = 'id DESC'): array
    {
        $db     = Database::getInstance();
        $table  = static::getTable();
        $offset = ($page - 1) * $perPage;
        $total  = static::count($where, $bindings);

        $sql = "SELECT * FROM `{$table}`";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        $sql .= " ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}";

        $data = $db->select($sql, $bindings);

        return [
            'data'         => $data,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => max(1, (int)ceil($total / $perPage)),
            'from'         => $offset + 1,
            'to'           => min($offset + $perPage, $total),
        ];
    }

    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }
        return array_intersect_key($data, array_flip($this->fillable));
    }

    protected function addTimestamps(array $data, bool $withCreated = false): array
    {
        $data['updated_at'] = now();
        if ($withCreated && !isset($data['created_at'])) {
            $data['created_at'] = now();
        }
        return $data;
    }

    public static function raw(string $sql, array $bindings = []): array
    {
        return Database::getInstance()->select($sql, $bindings);
    }

    public static function rawOne(string $sql, array $bindings = []): ?array
    {
        return Database::getInstance()->selectOne($sql, $bindings);
    }
}
