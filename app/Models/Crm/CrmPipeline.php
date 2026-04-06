<?php

namespace App\Models\Crm;

use Core\Database;

class CrmPipeline
{
    public static function allActive(): array
    {
        return Database::getInstance()->select(
            "SELECT p.*, COUNT(s.id) as stage_count
             FROM crm_pipelines p
             LEFT JOIN crm_stages s ON s.pipeline_id = p.id
             WHERE p.is_active = 1
             GROUP BY p.id
             ORDER BY p.sort_order ASC, p.id ASC"
        );
    }

    public static function find(int $id): ?array
    {
        return Database::getInstance()->selectOne(
            "SELECT * FROM crm_pipelines WHERE id = ? LIMIT 1", [$id]
        );
    }

    public static function findDefault(): ?array
    {
        return Database::getInstance()->selectOne(
            "SELECT * FROM crm_pipelines WHERE is_default = 1 AND is_active = 1 LIMIT 1"
        ) ?? Database::getInstance()->selectOne(
            "SELECT * FROM crm_pipelines WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 1"
        );
    }

    public static function getWithStages(int $id): ?array
    {
        $pipeline = self::find($id);
        if (!$pipeline) return null;

        $pipeline['stages'] = Database::getInstance()->select(
            "SELECT * FROM crm_stages WHERE pipeline_id = ? ORDER BY sort_order ASC, id ASC", [$id]
        );
        return $pipeline;
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();
        if (!empty($data['is_default'])) {
            $db->update("UPDATE crm_pipelines SET is_default = 0");
        }
        return (int)$db->insert(
            "INSERT INTO crm_pipelines (name, slug, description, is_default, is_active, sort_order, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $data['name'], $data['slug'], $data['description'] ?? null,
                (int)($data['is_default'] ?? 0), (int)($data['is_active'] ?? 1),
                (int)($data['sort_order'] ?? 0), $data['created_by'] ?? null,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        $db = Database::getInstance();
        if (!empty($data['is_default'])) {
            $db->update("UPDATE crm_pipelines SET is_default = 0 WHERE id != ?", [$id]);
        }
        $db->update(
            "UPDATE crm_pipelines SET name=?, slug=?, description=?, is_default=?, is_active=?, sort_order=?, updated_at=NOW() WHERE id=?",
            [
                $data['name'], $data['slug'], $data['description'] ?? null,
                (int)($data['is_default'] ?? 0), (int)($data['is_active'] ?? 1),
                (int)($data['sort_order'] ?? 0), $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::getInstance()->delete("DELETE FROM crm_pipelines WHERE id = ?", [$id]);
    }

    public static function generateSlug(string $name): string
    {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $slug = trim($slug, '-');
        $base = $slug;
        $i    = 1;
        while (Database::getInstance()->selectOne("SELECT id FROM crm_pipelines WHERE slug = ? LIMIT 1", [$slug])) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
