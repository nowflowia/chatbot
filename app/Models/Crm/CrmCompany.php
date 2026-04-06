<?php

namespace App\Models\Crm;

use Core\Database;

class CrmCompany
{
    public static function find(int $id): ?array
    {
        return Database::getInstance()->selectOne(
            "SELECT * FROM crm_companies WHERE id = ? LIMIT 1", [$id]
        );
    }

    public static function allPaginated(int $page = 1, int $perPage = 20, string $search = ''): array
    {
        $db     = Database::getInstance();
        $where  = '1=1';
        $params = [];

        if ($search !== '') {
            $where   .= " AND (name LIKE ? OR fantasy_name LIKE ? OR cnpj LIKE ?)";
            $s        = "%{$search}%";
            $params[] = $s; $params[] = $s; $params[] = $s;
        }

        $total  = (int)($db->selectOne("SELECT COUNT(*) as cnt FROM crm_companies WHERE {$where}", $params)['cnt'] ?? 0);
        $offset = ($page - 1) * $perPage;

        $data = $db->select(
            "SELECT c.*, (SELECT COUNT(*) FROM crm_deals d WHERE d.company_id = c.id) as deal_count
             FROM crm_companies c WHERE {$where} ORDER BY c.name ASC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['data' => $data, 'total' => $total, 'per_page' => $perPage,
                'current_page' => $page, 'last_page' => max(1, (int)ceil($total / $perPage))];
    }

    public static function search(string $q, int $limit = 10): array
    {
        return Database::getInstance()->select(
            "SELECT id, name FROM crm_companies WHERE name LIKE ? OR fantasy_name LIKE ? LIMIT {$limit}",
            ["%{$q}%", "%{$q}%"]
        );
    }

    public static function create(array $data): int
    {
        return (int)Database::getInstance()->insert(
            "INSERT INTO crm_companies (name, fantasy_name, cnpj, email, phone, whatsapp, website, city, state, address, notes, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $data['name'], $data['fantasy_name'] ?? null, $data['cnpj'] ?? null,
                $data['email'] ?? null, $data['phone'] ?? null, $data['whatsapp'] ?? null,
                $data['website'] ?? null, $data['city'] ?? null, $data['state'] ?? null,
                $data['address'] ?? null, $data['notes'] ?? null, $data['created_by'] ?? null,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        Database::getInstance()->update(
            "UPDATE crm_companies SET name=?, fantasy_name=?, cnpj=?, email=?, phone=?, whatsapp=?, website=?, city=?, state=?, address=?, notes=?, updated_at=NOW() WHERE id=?",
            [
                $data['name'], $data['fantasy_name'] ?? null, $data['cnpj'] ?? null,
                $data['email'] ?? null, $data['phone'] ?? null, $data['whatsapp'] ?? null,
                $data['website'] ?? null, $data['city'] ?? null, $data['state'] ?? null,
                $data['address'] ?? null, $data['notes'] ?? null, $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::getInstance()->delete("DELETE FROM crm_companies WHERE id=?", [$id]);
    }
}
