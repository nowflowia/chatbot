<?php

namespace App\Models\Crm;

use Core\Database;

class CrmTask
{
    public static function find(int $id): ?array
    {
        return Database::getInstance()->selectOne(
            "SELECT t.*, u.name as assigned_name
             FROM crm_tasks t LEFT JOIN users u ON u.id = t.assigned_to
             WHERE t.id = ? LIMIT 1",
            [$id]
        );
    }

    public static function openByDeal(int $dealId): array
    {
        return Database::getInstance()->select(
            "SELECT t.*, u.name as assigned_name
             FROM crm_tasks t LEFT JOIN users u ON u.id = t.assigned_to
             WHERE t.deal_id = ? AND t.status != 'cancelled'
             ORDER BY t.due_date ASC, t.id ASC",
            [$dealId]
        );
    }

    public static function overdueForUser(int $userId): array
    {
        return Database::getInstance()->select(
            "SELECT t.*, d.title as deal_title
             FROM crm_tasks t LEFT JOIN crm_deals d ON d.id = t.deal_id
             WHERE t.assigned_to = ? AND t.status = 'open' AND t.due_date < NOW()
             ORDER BY t.due_date ASC",
            [$userId]
        );
    }

    public static function create(array $data): int
    {
        return (int)Database::getInstance()->insert(
            "INSERT INTO crm_tasks (deal_id, contact_id, title, description, due_date, status, assigned_to, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, 'open', ?, ?, NOW(), NOW())",
            [
                $data['deal_id'] ?? null, $data['contact_id'] ?? null,
                $data['title'], $data['description'] ?? null,
                $data['due_date'] ?: null, $data['assigned_to'] ?? null,
                $data['created_by'] ?? null,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        Database::getInstance()->update(
            "UPDATE crm_tasks SET title=?, description=?, due_date=?, assigned_to=?, updated_at=NOW() WHERE id=?",
            [$data['title'], $data['description'] ?? null, $data['due_date'] ?: null, $data['assigned_to'] ?: null, $id]
        );
    }

    public static function done(int $id): void
    {
        Database::getInstance()->update(
            "UPDATE crm_tasks SET status='done', updated_at=NOW() WHERE id=?", [$id]
        );
    }

    public static function delete(int $id): void
    {
        Database::getInstance()->delete("DELETE FROM crm_tasks WHERE id=?", [$id]);
    }
}
