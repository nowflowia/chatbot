<?php

class RolesSeeder
{
    public function run(): void
    {
        $db = \Core\Database::getInstance();

        $roles = [
            [
                'name'        => 'Administrador',
                'slug'        => 'admin',
                'description' => 'Acesso completo ao sistema.',
                'status'      => 'active',
            ],
            [
                'name'        => 'Supervisor',
                'slug'        => 'supervisor',
                'description' => 'Supervisiona atendentes e filas.',
                'status'      => 'active',
            ],
            [
                'name'        => 'Atendente',
                'slug'        => 'agent',
                'description' => 'Realiza atendimentos via chat.',
                'status'      => 'active',
            ],
        ];

        foreach ($roles as $role) {
            $existing = $db->selectOne("SELECT id FROM roles WHERE slug = ?", [$role['slug']]);
            if ($existing) {
                echo "  [SKIP] Role already exists: {$role['slug']}\n";
                continue;
            }
            $db->insert(
                "INSERT INTO roles (name, slug, description, status, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())",
                [$role['name'], $role['slug'], $role['description'], $role['status']]
            );
            echo "  [OK] Role created: {$role['slug']}\n";
        }
    }
}
