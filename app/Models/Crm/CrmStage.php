<?php

namespace App\Models\Crm;

use Core\Database;

class CrmStage
{
    public static function find(int $id): ?array
    {
        return Database::getInstance()->selectOne(
            "SELECT * FROM crm_stages WHERE id = ? LIMIT 1", [$id]
        );
    }

    public static function byPipeline(int $pipelineId): array
    {
        return Database::getInstance()->select(
            "SELECT * FROM crm_stages WHERE pipeline_id = ? ORDER BY sort_order ASC, id ASC",
            [$pipelineId]
        );
    }

    public static function create(array $data): int
    {
        return (int)Database::getInstance()->insert(
            "INSERT INTO crm_stages (pipeline_id, name, color, sort_order, is_won, is_lost, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $data['pipeline_id'], $data['name'],
                $data['color'] ?? '#6366f1', (int)($data['sort_order'] ?? 0),
                (int)($data['is_won'] ?? 0), (int)($data['is_lost'] ?? 0),
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        Database::getInstance()->update(
            "UPDATE crm_stages SET name=?, color=?, sort_order=?, is_won=?, is_lost=?, updated_at=NOW() WHERE id=?",
            [
                $data['name'], $data['color'] ?? '#6366f1',
                (int)($data['sort_order'] ?? 0),
                (int)($data['is_won'] ?? 0), (int)($data['is_lost'] ?? 0),
                $id,
            ]
        );
    }

    public static function reorder(array $ids): void
    {
        $db = Database::getInstance();
        foreach ($ids as $i => $id) {
            $db->update("UPDATE crm_stages SET sort_order = ? WHERE id = ?", [$i, (int)$id]);
        }
    }

    public static function delete(int $id): void
    {
        Database::getInstance()->delete("DELETE FROM crm_stages WHERE id = ?", [$id]);
    }
}
