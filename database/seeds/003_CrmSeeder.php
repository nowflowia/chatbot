<?php

class CrmSeeder
{
    public function run(): void
    {
        $db = \Core\Database::getInstance();

        // Check if tables exist
        if (!$db->tableExists('crm_pipelines')) {
            echo "  [SKIP] crm_pipelines table not found. Run migrations first.\n";
            return;
        }

        // Skip if pipeline already exists
        $existing = $db->selectOne("SELECT id FROM crm_pipelines WHERE slug = 'default' LIMIT 1");
        if ($existing) {
            echo "  [SKIP] Default CRM pipeline already exists.\n";
            return;
        }

        // Create default pipeline
        $pipelineId = $db->insert(
            "INSERT INTO crm_pipelines (name, slug, description, is_default, is_active, sort_order, created_at, updated_at)
             VALUES (?, ?, ?, 1, 1, 0, NOW(), NOW())",
            ['Pipeline padrão', 'default', 'Pipeline de vendas padrão do sistema.']
        );

        echo "  [OK] Default pipeline created (ID: {$pipelineId})\n";

        // Create default stages
        $stages = [
            ['Novo lead',    '#3b82f6', 0, 0, 0],
            ['Qualificação', '#8b5cf6', 1, 0, 0],
            ['Proposta',     '#f59e0b', 2, 0, 0],
            ['Ganho',        '#10b981', 3, 1, 0],
            ['Perdido',      '#ef4444', 4, 0, 1],
        ];

        foreach ($stages as [$name, $color, $sort, $isWon, $isLost]) {
            $db->insert(
                "INSERT INTO crm_stages (pipeline_id, name, color, sort_order, is_won, is_lost, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [$pipelineId, $name, $color, $sort, $isWon, $isLost]
            );
            echo "  [OK] Stage created: {$name}\n";
        }
    }
}
