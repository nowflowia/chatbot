<?php

class AdminUserSeeder
{
    public function run(): void
    {
        $db = \Core\Database::getInstance();

        $email = 'admin@chatbot.local';
        $existing = $db->selectOne("SELECT id FROM users WHERE email = ?", [$email]);

        if ($existing) {
            echo "  [SKIP] Admin user already exists: {$email}\n";
            return;
        }

        // Get admin role id
        $role = $db->selectOne("SELECT id FROM roles WHERE slug = 'admin' LIMIT 1");
        if (!$role) {
            echo "  [ERROR] Admin role not found. Run RolesSeeder first.\n";
            return;
        }

        $password = password_hash('Admin@1234', PASSWORD_BCRYPT, ['cost' => 12]);

        $db->insert(
            "INSERT INTO users (role_id, name, email, password, status, created_at, updated_at) VALUES (?, ?, ?, ?, 'active', NOW(), NOW())",
            [$role['id'], 'Administrador', $email, $password]
        );

        echo "  [OK] Admin user created:\n";
        echo "       E-mail: {$email}\n";
        echo "       Senha:  Admin@1234\n";
        echo "  [!] Altere a senha após o primeiro login!\n";
    }
}
