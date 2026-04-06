<?php

namespace App\Models\Crm;

use Core\Database;

class CrmContact
{
    public static function find(int $id): ?array
    {
        return Database::getInstance()->selectOne(
            "SELECT c.*, co.name as company_name
             FROM crm_contacts c LEFT JOIN crm_companies co ON co.id = c.company_id
             WHERE c.id = ? LIMIT 1",
            [$id]
        );
    }

    public static function allPaginated(int $page = 1, int $perPage = 20, string $search = ''): array
    {
        $db     = Database::getInstance();
        $where  = '1=1';
        $params = [];

        if ($search !== '') {
            $where   .= " AND (c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
            $s        = "%{$search}%";
            $params[] = $s; $params[] = $s; $params[] = $s;
        }

        $total  = (int)($db->selectOne("SELECT COUNT(*) as cnt FROM crm_contacts c WHERE {$where}", $params)['cnt'] ?? 0);
        $offset = ($page - 1) * $perPage;

        $data = $db->select(
            "SELECT c.*, co.name as company_name
             FROM crm_contacts c
             LEFT JOIN crm_companies co ON co.id = c.company_id
             WHERE {$where} ORDER BY c.name ASC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['data' => $data, 'total' => $total, 'per_page' => $perPage,
                'current_page' => $page, 'last_page' => max(1, (int)ceil($total / $perPage))];
    }

    public static function byCompany(int $companyId): array
    {
        return Database::getInstance()->select(
            "SELECT * FROM crm_contacts WHERE company_id = ? ORDER BY name ASC", [$companyId]
        );
    }

    public static function search(string $q, int $limit = 10): array
    {
        return Database::getInstance()->select(
            "SELECT id, name, email FROM crm_contacts WHERE name LIKE ? OR email LIKE ? LIMIT {$limit}",
            ["%{$q}%", "%{$q}%"]
        );
    }

    public static function create(array $data): int
    {
        return (int)Database::getInstance()->insert(
            "INSERT INTO crm_contacts (company_id, name, role_title, email, phone, whatsapp, linkedin, notes, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $data['company_id'] ?: null, $data['name'], $data['role_title'] ?? null,
                $data['email'] ?? null, $data['phone'] ?? null, $data['whatsapp'] ?? null,
                $data['linkedin'] ?? null, $data['notes'] ?? null, $data['created_by'] ?? null,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        Database::getInstance()->update(
            "UPDATE crm_contacts SET company_id=?, name=?, role_title=?, email=?, phone=?, whatsapp=?, linkedin=?, notes=?, updated_at=NOW() WHERE id=?",
            [
                $data['company_id'] ?: null, $data['name'], $data['role_title'] ?? null,
                $data['email'] ?? null, $data['phone'] ?? null, $data['whatsapp'] ?? null,
                $data['linkedin'] ?? null, $data['notes'] ?? null, $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::getInstance()->delete("DELETE FROM crm_contacts WHERE id=?", [$id]);
    }
}
