<?php

namespace App\Models;

use Core\Model;
use Core\Database;

class WhatsappTemplate extends Model
{
    protected static string $table = 'whatsapp_templates';

    /**
     * Return all templates, optionally filtered by category.
     */
    public static function all(string $category = ''): array
    {
        $db = Database::getInstance();

        if ($category !== '') {
            return $db->select(
                "SELECT * FROM whatsapp_templates WHERE category = ? ORDER BY created_at DESC",
                [$category]
            );
        }

        return $db->select(
            "SELECT * FROM whatsapp_templates ORDER BY created_at DESC"
        );
    }

    /**
     * Find a single template by ID.
     */
    public static function find(int|string $id): ?array
    {
        return Database::getInstance()->selectOne(
            "SELECT * FROM whatsapp_templates WHERE id = ? LIMIT 1",
            [$id]
        );
    }

    /**
     * Insert a new template and return its new ID.
     */
    public static function create(array $data): string|false
    {
        $db = Database::getInstance();
        $ts = now();

        // Extract variables placeholders from body (e.g. {{1}}, {{2}})
        $variables = [];
        if (!empty($data['body_text'])) {
            preg_match_all('/\{\{(\d+)\}\}/', $data['body_text'], $matches);
            if (!empty($matches[1])) {
                $vars = array_unique($matches[1]);
                sort($vars, SORT_NUMERIC);
                $variables = array_values($vars);
            }
        }

        return $db->insert(
            "INSERT INTO whatsapp_templates
                (name, category, language, header_type, header_text, body_text,
                 footer_text, buttons, variables, status, meta_template_id,
                 rejection_reason, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['name']              ?? '',
                $data['category']          ?? 'marketing',
                $data['language']          ?? 'pt_BR',
                $data['header_type']       ?? 'none',
                $data['header_text']       ?? null,
                $data['body_text']         ?? '',
                $data['footer_text']       ?? null,
                isset($data['buttons'])    ? json_encode($data['buttons'])   : null,
                !empty($variables)         ? json_encode($variables)         : null,
                $data['status']            ?? 'draft',
                $data['meta_template_id']  ?? null,
                $data['rejection_reason']  ?? null,
                $ts,
                $ts,
            ]
        );
    }

    /**
     * Update an existing template by ID.
     */
    public static function update(int|string $id, array $data): int
    {
        $db = Database::getInstance();
        $ts = now();

        // Re-extract variables if body is being updated
        if (isset($data['body_text'])) {
            $variables = [];
            preg_match_all('/\{\{(\d+)\}\}/', $data['body_text'], $matches);
            if (!empty($matches[1])) {
                $vars = array_unique($matches[1]);
                sort($vars, SORT_NUMERIC);
                $variables = array_values($vars);
            }
            $data['variables'] = !empty($variables) ? json_encode($variables) : null;
        }

        // Build SET clause dynamically from provided fields
        $allowed = [
            'name', 'category', 'language', 'header_type', 'header_text',
            'body_text', 'footer_text', 'buttons', 'variables',
            'status', 'meta_template_id', 'rejection_reason',
        ];

        $sets     = [];
        $bindings = [];

        foreach ($allowed as $col) {
            if (!array_key_exists($col, $data)) {
                continue;
            }
            $sets[]     = "`{$col}` = ?";
            $val        = $data[$col];
            // JSON-encode array values
            if (is_array($val)) {
                $val = json_encode($val);
            }
            $bindings[] = $val;
        }

        if (empty($sets)) {
            return 0;
        }

        $sets[]     = "`updated_at` = ?";
        $bindings[] = $ts;
        $bindings[] = $id;

        return $db->update(
            "UPDATE whatsapp_templates SET " . implode(', ', $sets) . " WHERE id = ?",
            $bindings
        );
    }

    /**
     * Delete a template by ID.
     */
    public static function delete(int|string $id): int
    {
        return Database::getInstance()->delete(
            "DELETE FROM whatsapp_templates WHERE id = ?",
            [$id]
        );
    }
}
