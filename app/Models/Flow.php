<?php

namespace App\Models;

use Core\Model;
use Core\Database;

class Flow extends Model
{
    protected static string $table = 'flows';

    protected array $fillable = [
        'name', 'slug', 'description', 'trigger',
        'trigger_keywords', 'is_active', 'is_default', 'created_by',
    ];

    // ----------------------------------------------------------------

    public static function allPaginated(int $page = 1, int $perPage = 20, string $search = ''): array
    {
        $db     = Database::getInstance();
        $where  = '1=1';
        $params = [];

        if ($search !== '') {
            $where   .= " AND (name LIKE ? OR description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $total  = (int)($db->selectOne("SELECT COUNT(*) as cnt FROM flows WHERE {$where}", $params)['cnt'] ?? 0);
        $offset = ($page - 1) * $perPage;

        $data = $db->select(
            "SELECT f.*, u.name AS creator_name,
                    (SELECT COUNT(*) FROM flow_nodes fn WHERE fn.flow_id = f.id) AS node_count
             FROM flows f
             LEFT JOIN users u ON u.id = f.created_by
             WHERE {$where}
             ORDER BY f.updated_at DESC
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

    public static function generateSlug(string $name): string
    {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $slug = trim($slug, '-');
        $base = $slug;
        $i    = 1;

        while (Database::getInstance()->selectOne("SELECT id FROM flows WHERE slug = ? LIMIT 1", [$slug])) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    public static function getWithNodes(int $id): ?array
    {
        $db   = Database::getInstance();
        $flow = $db->selectOne("SELECT * FROM flows WHERE id = ? LIMIT 1", [$id]);
        if (!$flow) return null;

        $flow['nodes']       = $db->select(
            "SELECT * FROM flow_nodes WHERE flow_id = ? ORDER BY id ASC", [$id]
        );
        $flow['connections'] = $db->select(
            "SELECT * FROM flow_connections WHERE flow_id = ? ORDER BY sort_order ASC", [$id]
        );

        // Decode config_json for each node
        foreach ($flow['nodes'] as &$node) {
            $node['config'] = $node['config_json']
                ? json_decode($node['config_json'], true)
                : [];
        }
        unset($node);

        return $flow;
    }

    public static function saveBuilder(int $flowId, array $nodes, array $connections): void
    {
        $db = Database::getInstance();

        $db->transaction(function ($db) use ($flowId, $nodes, $connections) {
            $db->delete("DELETE FROM flow_connections WHERE flow_id = ?", [$flowId]);
            $db->delete("DELETE FROM flow_nodes      WHERE flow_id = ?", [$flowId]);

            $nodeIdMap = []; // temp_id => real db id

            foreach ($nodes as $n) {
                $config = isset($n['config']) ? json_encode($n['config'], JSON_UNESCAPED_UNICODE) : null;
                $realId = $db->insert(
                    "INSERT INTO flow_nodes (flow_id, type, title, config_json, pos_x, pos_y, is_start, status, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)",
                    [
                        $flowId,
                        $n['type']    ?? 'message',
                        $n['title']   ?? 'Bloco',
                        $config,
                        (int)($n['pos_x'] ?? 100),
                        (int)($n['pos_y'] ?? 100),
                        (int)($n['is_start'] ?? 0),
                        now(), now(),
                    ]
                );
                $nodeIdMap[$n['id']] = (int)$realId;
            }

            foreach ($connections as $i => $c) {
                $src = $nodeIdMap[$c['source_node_id']] ?? null;
                $tgt = $nodeIdMap[$c['target_node_id']] ?? null;
                if (!$src || !$tgt) continue;

                $db->insert(
                    "INSERT INTO flow_connections (flow_id, source_node_id, target_node_id, source_port, target_port, label, condition_value, sort_order, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $flowId, $src, $tgt,
                        $c['source_port']      ?? 'output',
                        $c['target_port']      ?? 'input',
                        $c['label']            ?? null,
                        $c['condition_value']  ?? null,
                        (int)($c['sort_order'] ?? $i),
                        now(), now(),
                    ]
                );
            }

            $db->update(
                "UPDATE flows SET updated_at = ? WHERE id = ?",
                [now(), $flowId]
            );
        });
    }
}
