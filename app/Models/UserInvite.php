<?php

namespace App\Models;

use Core\Model;
use Core\Database;

class UserInvite extends Model
{
    protected static string $table = 'user_invites';

    protected array $fillable = [
        'user_id',
        'token',
        'type',
        'status',
        'expires_at',
    ];

    public static function createForUser(int $userId, string $type = 'set_password'): array
    {
        $db = Database::getInstance();

        // Expire previous tokens of same type
        $db->update(
            "UPDATE user_invites SET status = 'expired', updated_at = NOW()
             WHERE user_id = ? AND type = ? AND status = 'pending'",
            [$userId, $type]
        );

        $token     = generate_token(64);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+48 hours'));

        $id = $db->insert(
            "INSERT INTO user_invites (user_id, token, type, status, expires_at, created_at, updated_at)
             VALUES (?, ?, ?, 'pending', ?, NOW(), NOW())",
            [$userId, $token, $type, $expiresAt]
        );

        return [
            'id'         => $id,
            'user_id'    => $userId,
            'token'      => $token,
            'type'       => $type,
            'expires_at' => $expiresAt,
        ];
    }

    public static function findValidToken(string $token): ?array
    {
        return Database::getInstance()->selectOne(
            "SELECT i.*, u.email, u.name as user_name
             FROM user_invites i
             JOIN users u ON u.id = i.user_id
             WHERE i.token = ?
               AND i.status = 'pending'
               AND i.expires_at > NOW()
             LIMIT 1",
            [$token]
        );
    }

    public static function markUsed(string $token): void
    {
        Database::getInstance()->update(
            "UPDATE user_invites SET status = 'used', used_at = NOW(), updated_at = NOW() WHERE token = ?",
            [$token]
        );
    }

    public static function expireOldTokens(): void
    {
        Database::getInstance()->update(
            "UPDATE user_invites SET status = 'expired', updated_at = NOW()
             WHERE status = 'pending' AND expires_at < NOW()"
        );
    }
}
