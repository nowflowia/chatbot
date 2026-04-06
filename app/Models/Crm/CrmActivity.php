<?php

namespace App\Models\Crm;

use Core\Database;

class CrmActivity
{
    public static function log(int $dealId, int $userId, string $type, string $title, ?string $body = null, ?array $meta = null): int
    {
        return (int)Database::getInstance()->insert(
            "INSERT INTO crm_activities (deal_id, user_id, type, title, body, meta, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())",
            [$dealId, $userId, $type, $title, $body, $meta ? json_encode($meta) : null]
        );
    }

    public static function byDeal(int $dealId): array
    {
        return Database::getInstance()->select(
            "SELECT a.*, u.name as user_name
             FROM crm_activities a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.deal_id = ?
             ORDER BY a.created_at DESC",
            [$dealId]
        );
    }
}
