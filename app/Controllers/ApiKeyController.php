<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Auth;
use Core\Database;

class ApiKeyController extends Controller
{
    public function store(Request $request): void
    {
        $user = Auth::user();
        $name = trim($request->input('name', ''));

        if (!$name) {
            $this->jsonError('Informe um nome para a chave.');
        }

        $key = bin2hex(random_bytes(40)); // 80 hex chars
        $expiresAt = $request->input('expires_at') ?: null;

        $db = Database::getInstance();
        $id = (int)$db->insert(
            "INSERT INTO api_keys (user_id, name, `key`, expires_at, is_active, created_at, updated_at) VALUES (?,?,?,?,1,NOW(),NOW())",
            [(int)$user['id'], $name, $key, $expiresAt]
        );

        $this->jsonSuccess('Chave criada com sucesso.', [
            'api_key' => [
                'id'         => $id,
                'name'       => $name,
                'key'        => $key,
                'expires_at' => $expiresAt,
                'created_at' => now(),
            ]
        ]);
    }

    public function destroy(Request $request, string $id): void
    {
        $user = Auth::user();
        $db   = Database::getInstance();

        $key = $db->selectOne("SELECT * FROM api_keys WHERE id=? LIMIT 1", [(int)$id]);
        if (!$key) {
            $this->jsonError('Chave não encontrada.', [], 404);
        }

        // Only owner or admin can delete
        if ((int)$key['user_id'] !== (int)$user['id'] && !Auth::isAdmin()) {
            $this->jsonError('Sem permissão.', [], 403);
        }

        $db->delete("DELETE FROM api_keys WHERE id=?", [(int)$id]);
        $this->jsonSuccess('Chave removida.');
    }

    public function toggle(Request $request, string $id): void
    {
        $user = Auth::user();
        $db   = Database::getInstance();

        $key = $db->selectOne("SELECT * FROM api_keys WHERE id=? LIMIT 1", [(int)$id]);
        if (!$key) {
            $this->jsonError('Chave não encontrada.', [], 404);
        }

        if ((int)$key['user_id'] !== (int)$user['id'] && !Auth::isAdmin()) {
            $this->jsonError('Sem permissão.', [], 403);
        }

        $newStatus = $key['is_active'] ? 0 : 1;
        $db->update("UPDATE api_keys SET is_active=?, updated_at=NOW() WHERE id=?", [$newStatus, (int)$id]);

        $this->jsonSuccess($newStatus ? 'Chave ativada.' : 'Chave desativada.', ['is_active' => (bool)$newStatus]);
    }
}
