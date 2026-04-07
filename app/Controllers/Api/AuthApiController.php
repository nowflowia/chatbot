<?php

namespace App\Controllers\Api;

use Core\Controller;
use Core\Request;
use Core\Database;

class AuthApiController extends Controller
{
    /**
     * POST /api/v1/auth/login
     * Authenticate with email + password, returns an API key.
     */
    public function login(Request $request): void
    {
        $email    = trim($request->input('email', ''));
        $password = $request->input('password', '');

        if (!$email || !$password) {
            $this->jsonError('E-mail e senha são obrigatórios.', [], 422);
        }

        $db   = Database::getInstance();
        $user = $db->selectOne(
            "SELECT u.*, r.slug as role_slug
             FROM users u LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.email = ? AND u.status = 'active' LIMIT 1",
            [$email]
        );

        if (!$user || !password_verify($password, $user['password'])) {
            $this->jsonError('Credenciais inválidas.', [], 401);
        }

        // Return existing active key or create a new one
        $existing = $db->selectOne(
            "SELECT * FROM api_keys WHERE user_id = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > CURDATE()) ORDER BY id DESC LIMIT 1",
            [(int)$user['id']]
        );

        if ($existing) {
            $apiKey = $existing['key'];
        } else {
            $apiKey = bin2hex(random_bytes(40)); // 80 hex chars
            $db->insert(
                "INSERT INTO api_keys (user_id, name, `key`, is_active, created_at, updated_at) VALUES (?, ?, ?, 1, NOW(), NOW())",
                [(int)$user['id'], 'Login automático', $apiKey]
            );
        }

        $this->jsonSuccess('Autenticado com sucesso.', [
            'token'      => $apiKey,
            'token_type' => 'Bearer',
            'user'       => [
                'id'    => (int)$user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role_slug'],
            ],
        ]);
    }

    /**
     * GET /api/v1/auth/me
     * Returns the authenticated user info.
     */
    public function me(Request $request): void
    {
        $user = $_REQUEST['_api_user'] ?? null;
        if (!$user) {
            $this->jsonError('Não autenticado.', [], 401);
        }

        $this->jsonSuccess('OK', ['user' => $user]);
    }
}
