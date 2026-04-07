<?php

namespace App\Middlewares;

use Core\Request;
use Core\Database;

class ApiKeyMiddleware
{
    public function handle(Request $request): void
    {
        // Accept: Authorization: Bearer <key>  OR  X-API-Key: <key>
        $key = null;

        $auth = $request->header('Authorization', '');
        if (str_starts_with($auth, 'Bearer ')) {
            $key = trim(substr($auth, 7));
        }

        if (!$key) {
            $key = trim($request->header('X-Api-Key', ''));
        }

        if (!$key) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'API key não fornecida. Use o header Authorization: Bearer {key} ou X-API-Key: {key}', 'data' => null]);
            exit;
        }

        $db  = Database::getInstance();
        $row = $db->selectOne(
            "SELECT ak.*, u.id as user_id, u.name as user_name, u.email as user_email,
                    u.status as user_status, r.slug as role_slug
             FROM api_keys ak
             JOIN users u ON u.id = ak.user_id
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE ak.key = ? AND ak.is_active = 1 LIMIT 1",
            [$key]
        );

        if (!$row) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'API key inválida ou desativada.', 'data' => null]);
            exit;
        }

        if ($row['user_status'] !== 'active') {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Usuário inativo.', 'data' => null]);
            exit;
        }

        if (!empty($row['expires_at']) && strtotime($row['expires_at']) < time()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'API key expirada.', 'data' => null]);
            exit;
        }

        // Update last_used_at (non-blocking)
        try {
            $db->update("UPDATE api_keys SET last_used_at = NOW() WHERE id = ?", [$row['id']]);
        } catch (\Throwable $e) {}

        // Inject authenticated user into request context
        $_REQUEST['_api_user'] = [
            'id'        => (int)$row['user_id'],
            'name'      => $row['user_name'],
            'email'     => $row['user_email'],
            'role_slug' => $row['role_slug'],
            'api_key_id'=> (int)$row['id'],
        ];
    }
}
