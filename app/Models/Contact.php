<?php

namespace App\Models;

use Core\Model;
use Core\Database;

class Contact extends Model
{
    protected static string $table = 'contacts';

    protected array $fillable = [
        'name',
        'phone',
        'email',
        'avatar_url',
        'notes',
        'last_seen_at',
        'extra_data',
    ];

    /**
     * Find a contact by phone number (exact match, digits only).
     */
    public static function findByPhone(string $phone): ?array
    {
        $digits = preg_replace('/\D/', '', $phone);
        return Database::getInstance()->selectOne(
            "SELECT * FROM contacts WHERE REGEXP_REPLACE(phone, '[^0-9]', '') = ? LIMIT 1",
            [$digits]
        );
    }

    /**
     * Find or create a contact by phone. If profile_name is provided, uses it as name on creation.
     */
    public static function findOrCreateByPhone(string $phone, string $profileName = ''): array
    {
        $db     = Database::getInstance();
        $digits = preg_replace('/\D/', '', $phone);

        $existing = $db->selectOne(
            "SELECT * FROM contacts WHERE REGEXP_REPLACE(phone, '[^0-9]', '') = ? LIMIT 1",
            [$digits]
        );

        if ($existing) {
            // Update name if we now have one and didn't before
            if ($profileName && (empty($existing['name']) || $existing['name'] === $existing['phone'])) {
                $db->update(
                    "UPDATE contacts SET name = ?, updated_at = ? WHERE id = ?",
                    [$profileName, now(), $existing['id']]
                );
                $existing['name'] = $profileName;
            }
            return $existing;
        }

        $name = $profileName ?: $phone;
        $id   = $db->insert(
            "INSERT INTO contacts (name, phone, created_at, updated_at) VALUES (?, ?, ?, ?)",
            [$name, $phone, now(), now()]
        );

        return $db->selectOne("SELECT * FROM contacts WHERE id = ? LIMIT 1", [(int)$id]);
    }

    /**
     * Update the last_seen_at timestamp for a contact.
     */
    public static function updateLastSeen(int $contactId): void
    {
        Database::getInstance()->update(
            "UPDATE contacts SET last_seen_at = ?, updated_at = ? WHERE id = ?",
            [now(), now(), $contactId]
        );
    }

    /**
     * Search contacts by name or phone with pagination.
     *
     * @return array{data: array, total: int, per_page: int, current_page: int, last_page: int}
     */
    public static function search(string $query, int $page = 1, int $perPage = 20): array
    {
        $db     = Database::getInstance();
        $offset = ($page - 1) * $perPage;
        $like   = '%' . $query . '%';

        $where    = "WHERE (name LIKE ? OR phone LIKE ? OR email LIKE ?)";
        $bindings = [$like, $like, $like];

        $total = (int)($db->selectOne(
            "SELECT COUNT(*) as cnt FROM contacts {$where}",
            $bindings
        )['cnt'] ?? 0);

        $data = $db->select(
            "SELECT * FROM contacts {$where} ORDER BY name ASC LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        return [
            'data'         => $data,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => max(1, (int)ceil($total / $perPage)),
        ];
    }
}
