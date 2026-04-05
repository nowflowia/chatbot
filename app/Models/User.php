<?php

namespace App\Models;

use Core\Model;
use Core\Database;

class User extends Model
{
    protected static string $table = 'users';

    protected array $fillable = [
        'role_id',
        'name',
        'email',
        'password',
        'avatar',
        'phone',
        'status',
    ];

    protected array $hidden = ['password'];

    public static function findByEmail(string $email): ?array
    {
        return static::findWhere('email', $email);
    }

    public static function findWithRole(int $id): ?array
    {
        return Database::getInstance()->selectOne(
            "SELECT u.*, r.name as role_name, r.slug as role_slug
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.id = ? LIMIT 1",
            [$id]
        );
    }

    public static function count(): int
    {
        return (int)(Database::getInstance()->selectOne("SELECT COUNT(*) AS cnt FROM users")['cnt'] ?? 0);
    }

    public static function allWithRole(int $page = 1, int $perPage = 20, string $search = ''): array
    {
        $db     = Database::getInstance();
        $where  = '1=1';
        $params = [];

        if ($search !== '') {
            $where   .= " AND (u.name LIKE ? OR u.email LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $total  = (int)($db->selectOne(
            "SELECT COUNT(*) as cnt FROM users u WHERE {$where}", $params
        )['cnt'] ?? 0);

        $offset = ($page - 1) * $perPage;
        $data   = $db->select(
            "SELECT u.*, r.name as role_name, r.slug as role_slug
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE {$where}
             ORDER BY u.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [
            'data'         => $data,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => max(1, (int)ceil($total / $perPage)),
        ];
    }

    public static function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public static function updateLastLogin(int $id, string $ip): void
    {
        Database::getInstance()->update(
            "UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?",
            [$ip, $id]
        );
    }

    public static function updatePassword(int $id, string $plain): void
    {
        Database::getInstance()->update(
            "UPDATE users SET password = ?, status = 'active', updated_at = NOW() WHERE id = ?",
            [static::hashPassword($plain), $id]
        );
    }

    public static function emailExists(string $email, ?int $exceptId = null): bool
    {
        $db  = Database::getInstance();
        $sql = "SELECT COUNT(*) as cnt FROM users WHERE email = ?";
        $params = [$email];
        if ($exceptId) {
            $sql .= " AND id != ?";
            $params[] = $exceptId;
        }
        return (int)($db->selectOne($sql, $params)['cnt'] ?? 0) > 0;
    }

    /**
     * Return all active agents/supervisors/admins for transfer dropdown.
     */
    public static function allAgents(): array
    {
        return Database::getInstance()->select(
            "SELECT u.id, u.name, u.email, r.slug as role_slug
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.status = 'active'
             ORDER BY u.name ASC"
        );
    }

    public static function hide(array $user): array
    {
        unset($user['password']);
        return $user;
    }
}
