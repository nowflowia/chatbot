<?php

namespace App\Models;

use Core\Model;
use Core\Database;

class AiSetting extends Model
{
    protected static string $table = 'ai_settings';

    public const PROVIDERS = ['openai', 'anthropic'];

    public static function indexed(): array
    {
        $rows = Database::getInstance()->select(
            "SELECT * FROM ai_settings ORDER BY provider ASC"
        );
        $out = [];
        foreach ($rows as $row) {
            $out[$row['provider']] = $row;
        }
        return $out;
    }

    public static function get(string $provider): ?array
    {
        return Database::getInstance()->selectOne(
            "SELECT * FROM ai_settings WHERE provider=? LIMIT 1",
            [$provider]
        );
    }

    public static function save(string $provider, array $data): array
    {
        if (!in_array($provider, self::PROVIDERS, true)) {
            throw new \InvalidArgumentException("Provider inválido: {$provider}");
        }

        $db       = Database::getInstance();
        $existing = static::get($provider);

        $apiKey   = trim($data['api_key'] ?? '');
        $model    = trim($data['model'] ?? '');
        $isActive = !empty($data['is_active']) ? 1 : 0;
        $ts       = now();

        if ($existing) {
            // Preserva a chave existente se o input vier vazio (placeholder)
            if ($apiKey === '') {
                $apiKey = $existing['api_key'] ?? '';
            }

            $db->update(
                "UPDATE ai_settings
                 SET api_key=?, model=?, is_active=?, updated_at=?
                 WHERE id=?",
                [$apiKey, $model, $isActive, $ts, (int)$existing['id']]
            );
            return array_merge($existing, [
                'api_key'   => $apiKey,
                'model'     => $model,
                'is_active' => $isActive,
            ]);
        }

        $id = $db->insert(
            "INSERT INTO ai_settings (provider, api_key, model, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$provider, $apiKey, $model, $isActive, $ts, $ts]
        );

        return [
            'id'        => (int)$id,
            'provider'  => $provider,
            'api_key'   => $apiKey,
            'model'     => $model,
            'is_active' => $isActive,
        ];
    }

    public static function updateTestStatus(string $provider, bool $ok, ?string $error = null): void
    {
        Database::getInstance()->update(
            "UPDATE ai_settings SET last_tested_at=?, last_error=?, updated_at=? WHERE provider=?",
            [now(), $ok ? null : $error, now(), $provider]
        );
    }
}
