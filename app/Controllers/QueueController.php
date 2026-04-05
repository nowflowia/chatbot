<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Auth;
use App\Models\Chat;
use App\Models\User;

class QueueController extends Controller
{
    // ----------------------------------------------------------------
    // Page view
    // ----------------------------------------------------------------

    public function index(Request $request): string
    {
        $user   = Auth::user();
        $agents = User::allAgents();

        return $this->view('queue/index', [
            'user'   => $user,
            'agents' => $agents,
        ]);
    }

    // ----------------------------------------------------------------
    // AJAX — assign to self
    // ----------------------------------------------------------------

    /**
     * POST /admin/queue/{id}/assign
     * Assign waiting/bot chat to the logged-in user.
     */
    public function assign(Request $request, string $id): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->jsonError('Não autorizado.', [], 401);
        }

        $chat = Chat::find((int)$id);
        if (!$chat) {
            $this->jsonError('Conversa não encontrada.', [], 404);
        }

        if ($chat['status'] === 'finished') {
            $this->jsonError('Esta conversa já foi finalizada.');
        }

        \Core\Database::getInstance()->update(
            "UPDATE chats SET assigned_to = ?, status = 'in_progress', is_bot_active = 0, updated_at = ? WHERE id = ?",
            [$user['id'], now(), (int)$id]
        );

        $updated = Chat::findWithContact((int)$id);

        $this->jsonSuccess('Atendimento assumido.', ['chat' => $this->formatChat($updated)]);
    }

    // ----------------------------------------------------------------
    // AJAX — transfer to another agent
    // ----------------------------------------------------------------

    /**
     * POST /admin/queue/{id}/transfer
     * Transfer chat to a specific agent.
     */
    public function transfer(Request $request, string $id): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->jsonError('Não autorizado.', [], 401);
        }

        $chat = Chat::find((int)$id);
        if (!$chat) {
            $this->jsonError('Conversa não encontrada.', [], 404);
        }

        if ($chat['status'] === 'finished') {
            $this->jsonError('Esta conversa já foi finalizada.');
        }

        $agentId = (int)$request->post('agent_id', 0);
        if (!$agentId) {
            $this->jsonError('Selecione um atendente.', ['agent_id' => ['Campo obrigatório.']]);
        }

        $agent = User::find($agentId);
        if (!$agent) {
            $this->jsonError('Atendente não encontrado.', [], 404);
        }

        \Core\Database::getInstance()->update(
            "UPDATE chats SET assigned_to = ?, status = 'in_progress', is_bot_active = 0, updated_at = ? WHERE id = ?",
            [$agentId, now(), (int)$id]
        );

        $updated = Chat::findWithContact((int)$id);

        $this->jsonSuccess('Conversa transferida para ' . $agent['name'] . '.', ['chat' => $this->formatChat($updated)]);
    }

    // ----------------------------------------------------------------
    // AJAX — finish
    // ----------------------------------------------------------------

    /**
     * POST /admin/queue/{id}/finish
     * Mark chat as finished directly from queue.
     */
    public function finish(Request $request, string $id): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->jsonError('Não autorizado.', [], 401);
        }

        $chat = Chat::find((int)$id);
        if (!$chat) {
            $this->jsonError('Conversa não encontrada.', [], 404);
        }

        if ($chat['status'] === 'finished') {
            $this->jsonError('Esta conversa já está finalizada.');
        }

        \Core\Database::getInstance()->update(
            "UPDATE chats SET status = 'finished', is_bot_active = 0, updated_at = ? WHERE id = ?",
            [now(), (int)$id]
        );

        $this->jsonSuccess('Conversa finalizada.');
    }

    // ----------------------------------------------------------------
    // AJAX — queue list (polling)
    // ----------------------------------------------------------------

    /**
     * GET /admin/queue/list
     * Returns waiting + bot chats for queue display.
     */
    public function getList(Request $request): void
    {
        $search = trim($request->get('search', ''));
        $page   = max(1, (int)$request->get('page', 1));

        $result = Chat::allWithContacts($page, 50, 'waiting', $search);
        $bot    = Chat::allWithContacts(1, 50, 'bot', $search);

        // Merge waiting + bot
        $all = array_merge($result['data'], $bot['data']);
        usort($all, fn($a, $b) => strtotime($a['created_at']) - strtotime($b['created_at']));

        $counts = Chat::countByStatus();

        $formatted = array_map([$this, 'formatChat'], $all);

        $this->jsonSuccess('OK', [
            'chats'  => $formatted,
            'counts' => $counts,
            'total'  => count($formatted),
        ]);
    }

    // ----------------------------------------------------------------
    // Helper
    // ----------------------------------------------------------------

    private function formatChat(array $chat): array
    {
        $colors = ['#6366f1','#8b5cf6','#ec4899','#f59e0b','#10b981','#3b82f6','#ef4444','#14b8a6'];

        return [
            'id'                 => (int)$chat['id'],
            'status'             => $chat['status'],
            'last_message'       => $chat['last_message'] ?? '',
            'last_message_at'    => $chat['last_message_at'] ?? $chat['updated_at'] ?? '',
            'last_message_human' => $this->humanTime($chat['last_message_at'] ?? $chat['updated_at'] ?? ''),
            'wait_time'          => $this->humanTime($chat['created_at'] ?? ''),
            'created_at'         => $chat['created_at'] ?? '',
            'unread_count'       => (int)($chat['unread_count'] ?? 0),
            'is_bot_active'      => (bool)($chat['is_bot_active'] ?? false),
            'assigned_to'        => $chat['assigned_to'] ? (int)$chat['assigned_to'] : null,
            'assigned_user_name' => $chat['assigned_user_name'] ?? null,
            'contact' => [
                'id'       => (int)$chat['contact_id'],
                'name'     => $chat['contact_name']  ?? 'Desconhecido',
                'phone'    => $chat['contact_phone'] ?? '',
                'avatar'   => $chat['contact_avatar'] ?? null,
                'initials' => $this->initials($chat['contact_name'] ?? ''),
                'color'    => $colors[$chat['contact_id'] % count($colors)],
            ],
        ];
    }

    private function humanTime(string $datetime): string
    {
        if (!$datetime) return '';
        try {
            $ts   = strtotime($datetime);
            $diff = time() - $ts;
            if ($diff < 60)     return 'agora';
            if ($diff < 3600)   return (int)($diff / 60) . 'min';
            if ($diff < 86400)  return date('H:i', $ts);
            if ($diff < 604800) return date('d/m', $ts);
            return date('d/m/y', $ts);
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function initials(string $name): string
    {
        $words = array_filter(explode(' ', trim($name)));
        if (empty($words)) return '?';
        $parts = array_values($words);
        if (count($parts) === 1) return mb_strtoupper(mb_substr($parts[0], 0, 2));
        return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1));
    }
}
