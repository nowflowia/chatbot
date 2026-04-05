<?php

namespace App\Models;

use Core\Model;
use Core\Database;

class WhatsappSetting extends Model
{
    protected static string $table = 'whatsapp_settings';

    protected array $fillable = [
        'name',
        'access_token',
        'verify_token',
        'phone_number_id',
        'business_account_id',
        'app_id',
        'app_secret',
        'webhook_url',
        'api_version',
        'status',
        'last_error',
    ];

    /**
     * Returns the first (and usually only) active configuration.
     */
    public static function getActive(): ?array
    {
        return Database::getInstance()->selectOne(
            "SELECT * FROM whatsapp_settings ORDER BY id ASC LIMIT 1"
        );
    }

    /**
     * Creates or updates the first configuration row.
     */
    public static function saveSettings(array $data): array
    {
        $db       = Database::getInstance();
        $existing = static::getActive();

        $payload = [
            'name'                => $data['name']                ?? 'Principal',
            'access_token'        => $data['access_token']        ?? null,
            'verify_token'        => $data['verify_token']        ?? null,
            'phone_number_id'     => $data['phone_number_id']     ?? null,
            'business_account_id' => $data['business_account_id'] ?? null,
            'app_id'              => $data['app_id']              ?? null,
            'app_secret'          => $data['app_secret']          ?? null,
            'webhook_url'         => $data['webhook_url']         ?? null,
            'api_version'         => $data['api_version']         ?? 'v18.0',
            'updated_at'          => now(),
        ];

        if ($existing) {
            $sets = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($payload)));
            $db->update(
                "UPDATE whatsapp_settings SET {$sets} WHERE id = ?",
                [...array_values($payload), $existing['id']]
            );
            return array_merge($existing, $payload);
        }

        $payload['status']     = 'inactive';
        $payload['created_at'] = now();
        $id = $db->insert(
            "INSERT INTO whatsapp_settings (" . implode(',', array_map(fn($k) => "`{$k}`", array_keys($payload))) . ")
             VALUES (" . implode(',', array_fill(0, count($payload), '?')) . ")",
            array_values($payload)
        );
        return static::find((int)$id) ?? $payload;
    }

    public static function updateStatus(int $id, string $status, ?string $error = null): void
    {
        Database::getInstance()->update(
            "UPDATE whatsapp_settings SET status = ?, last_error = ?, last_tested_at = NOW(), updated_at = NOW() WHERE id = ?",
            [$status, $error, $id]
        );
    }
}
