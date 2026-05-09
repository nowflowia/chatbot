<?php

namespace App\Models;

use Core\Model;
use Core\Database;

class MetaAdSetting extends Model
{
    protected static string $table = 'meta_ad_settings';

    public static function getActive(): ?array
    {
        return Database::getInstance()->selectOne(
            "SELECT * FROM meta_ad_settings ORDER BY id ASC LIMIT 1"
        );
    }

    public static function saveSettings(array $data): array
    {
        $db       = Database::getInstance();
        $existing = static::getActive();

        $payload = [
            'app_id'              => $data['app_id']              ?? null,
            'app_secret'          => $data['app_secret']          ?? null,
            'access_token'        => $data['access_token']        ?? null,
            'ad_account_id'       => $data['ad_account_id']       ?? null,
            'business_id'         => $data['business_id']         ?? null,
            'page_id'             => $data['page_id']             ?? null,
            'instagram_actor_id'  => $data['instagram_actor_id']  ?? null,
            'api_version'         => $data['api_version']         ?? 'v21.0',
            'ai_model'            => $data['ai_model']            ?? 'claude-sonnet-4-6',
            'agent_persona'       => $data['agent_persona']       ?? null,
            'updated_at'          => now(),
        ];

        if ($existing) {
            $sets = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($payload)));
            $db->update(
                "UPDATE meta_ad_settings SET {$sets} WHERE id = ?",
                [...array_values($payload), $existing['id']]
            );
            return array_merge($existing, $payload);
        }

        $payload['status']     = 'inactive';
        $payload['created_at'] = now();
        $id = $db->insert(
            "INSERT INTO meta_ad_settings (" . implode(',', array_map(fn($k) => "`{$k}`", array_keys($payload))) . ")
             VALUES (" . implode(',', array_fill(0, count($payload), '?')) . ")",
            array_values($payload)
        );
        return static::find((int)$id) ?? $payload;
    }

    public static function updateStatus(int $id, string $status, ?string $error = null): void
    {
        Database::getInstance()->update(
            "UPDATE meta_ad_settings SET status=?, last_error=?, last_tested_at=NOW(), updated_at=NOW() WHERE id=?",
            [$status, $error, $id]
        );
    }
}
