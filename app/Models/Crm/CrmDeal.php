<?php

namespace App\Models\Crm;

use Core\Database;

class CrmDeal
{
    public static function find(int $id): ?array
    {
        return Database::getInstance()->selectOne(
            "SELECT d.*, s.name as stage_name, s.color as stage_color, s.is_won, s.is_lost,
                    p.name as pipeline_name,
                    u.name as assigned_name,
                    co.name as company_name, ct.name as contact_name
             FROM crm_deals d
             LEFT JOIN crm_stages    s  ON s.id  = d.stage_id
             LEFT JOIN crm_pipelines p  ON p.id  = d.pipeline_id
             LEFT JOIN users         u  ON u.id  = d.assigned_to
             LEFT JOIN crm_companies co ON co.id = d.company_id
             LEFT JOIN crm_contacts  ct ON ct.id = d.contact_id
             WHERE d.id = ? LIMIT 1",
            [$id]
        );
    }

    public static function getWithRelations(int $id): ?array
    {
        $deal = self::find($id);
        if (!$deal) return null;

        $db = Database::getInstance();
        $deal['activities'] = CrmActivity::byDeal($id);
        $deal['tasks']      = CrmTask::openByDeal($id);
        $deal['products']   = $db->select(
            "SELECT * FROM crm_deal_products WHERE deal_id = ? ORDER BY id ASC", [$id]
        );
        $deal['files'] = $db->select(
            "SELECT f.*, u.name as uploaded_by_name
             FROM crm_deal_files f
             LEFT JOIN users u ON u.id = f.uploaded_by
             WHERE f.deal_id = ? ORDER BY f.created_at DESC",
            [$id]
        );
        return $deal;
    }

    /**
     * Returns deals grouped by stage for kanban rendering.
     * $filters: ['assigned_to' => int, 'search' => string, 'date_from' => string, 'date_to' => string]
     */
    public static function kanban(int $pipelineId, array $filters = [], ?int $userId = null, bool $isAgent = false): array
    {
        $db     = Database::getInstance();
        $where  = "d.pipeline_id = ? AND d.status = 'open'";
        $params = [$pipelineId];

        if ($isAgent && $userId) {
            $where   .= " AND d.assigned_to = ?";
            $params[] = $userId;
        } elseif (!empty($filters['assigned_to'])) {
            $where   .= " AND d.assigned_to = ?";
            $params[] = (int)$filters['assigned_to'];
        }

        if (!empty($filters['search'])) {
            $where   .= " AND (d.title LIKE ? OR co.name LIKE ?)";
            $s        = '%' . $filters['search'] . '%';
            $params[] = $s;
            $params[] = $s;
        }

        if (!empty($filters['date_from'])) {
            $where   .= " AND DATE(d.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where   .= " AND DATE(d.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        return $db->select(
            "SELECT d.*, s.name as stage_name, s.color as stage_color,
                    u.name as assigned_name,
                    co.name as company_name
             FROM crm_deals d
             LEFT JOIN crm_stages    s  ON s.id  = d.stage_id
             LEFT JOIN users         u  ON u.id  = d.assigned_to
             LEFT JOIN crm_companies co ON co.id = d.company_id
             WHERE {$where}
             ORDER BY d.sort_order ASC, d.created_at DESC",
            $params
        );
    }

    public static function summary(int $pipelineId, array $filters = [], ?int $userId = null, bool $isAgent = false): array
    {
        $db     = Database::getInstance();
        $where  = "pipeline_id = ?";
        $params = [$pipelineId];

        if ($isAgent && $userId) {
            $where   .= " AND assigned_to = ?";
            $params[] = $userId;
        } elseif (!empty($filters['assigned_to'])) {
            $where   .= " AND assigned_to = ?";
            $params[] = (int)$filters['assigned_to'];
        }

        $rows = $db->select(
            "SELECT status, COUNT(*) as cnt, COALESCE(SUM(value),0) as total
             FROM crm_deals WHERE {$where} GROUP BY status",
            $params
        );

        $summary = ['open' => 0, 'won' => 0, 'lost' => 0, 'value_open' => 0.0, 'value_won' => 0.0];
        foreach ($rows as $r) {
            $summary[$r['status']]                          = (int)$r['cnt'];
            $summary['value_' . $r['status']] = (float)$r['total'];
        }

        // Overdue tasks count
        $overdueWhere = str_replace('pipeline_id', 'd.pipeline_id', $where);
        $summary['overdue_tasks'] = (int)($db->selectOne(
            "SELECT COUNT(*) as cnt FROM crm_tasks t
             JOIN crm_deals d ON d.id = t.deal_id
             WHERE {$overdueWhere} AND t.status = 'open' AND t.due_date < NOW()",
            $params
        )['cnt'] ?? 0);

        return $summary;
    }

    public static function create(array $data): int
    {
        return (int)Database::getInstance()->insert(
            "INSERT INTO crm_deals (pipeline_id, stage_id, title, value, status, origin,
              company_id, contact_id, assigned_to, expected_close_date, notes, sort_order, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, 'open', ?, ?, ?, ?, ?, ?, 0, ?, NOW(), NOW())",
            [
                $data['pipeline_id'], $data['stage_id'], $data['title'],
                (float)($data['value'] ?? 0), $data['origin'] ?? 'manual',
                $data['company_id']  ?? null, $data['contact_id'] ?? null,
                $data['assigned_to'] ?? null,
                $data['expected_close_date'] ?: null,
                $data['notes'] ?? null,
                $data['created_by'] ?? null,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        Database::getInstance()->update(
            "UPDATE crm_deals SET title=?, value=?, origin=?, company_id=?, contact_id=?,
              assigned_to=?, expected_close_date=?, notes=?, updated_at=NOW() WHERE id=?",
            [
                $data['title'], (float)($data['value'] ?? 0), $data['origin'] ?? 'manual',
                $data['company_id']  ?: null, $data['contact_id'] ?: null,
                $data['assigned_to'] ?: null,
                $data['expected_close_date'] ?: null,
                $data['notes'] ?? null, $id,
            ]
        );
    }

    public static function moveStage(int $id, int $stageId): ?array
    {
        $deal  = self::find($id);
        $stage = CrmStage::find($stageId);
        if (!$deal || !$stage) return null;

        Database::getInstance()->update(
            "UPDATE crm_deals SET stage_id = ?, updated_at = NOW() WHERE id = ?",
            [$stageId, $id]
        );
        return ['from' => $deal['stage_name'], 'to' => $stage['name']];
    }

    public static function win(int $id): void
    {
        $db    = Database::getInstance();
        $stage = $db->selectOne(
            "SELECT s.id FROM crm_stages s
             JOIN crm_deals d ON d.pipeline_id = s.pipeline_id
             WHERE d.id = ? AND s.is_won = 1 LIMIT 1",
            [$id]
        );
        $db->update(
            "UPDATE crm_deals SET status='won'" . ($stage ? ", stage_id={$stage['id']}" : "") . ", updated_at=NOW() WHERE id=?",
            [$id]
        );
    }

    public static function lose(int $id, string $reason = ''): void
    {
        $db    = Database::getInstance();
        $stage = $db->selectOne(
            "SELECT s.id FROM crm_stages s
             JOIN crm_deals d ON d.pipeline_id = s.pipeline_id
             WHERE d.id = ? AND s.is_lost = 1 LIMIT 1",
            [$id]
        );
        $db->update(
            "UPDATE crm_deals SET status='lost', lost_reason=?" . ($stage ? ", stage_id={$stage['id']}" : "") . ", updated_at=NOW() WHERE id=?",
            [$reason, $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::getInstance()->delete("DELETE FROM crm_deals WHERE id = ?", [$id]);
    }
}
