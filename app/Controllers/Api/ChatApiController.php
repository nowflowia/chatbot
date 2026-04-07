<?php

namespace App\Controllers\Api;

use Core\Controller;
use Core\Request;
use Core\Database;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Contact;
use App\Services\MetaWhatsAppService;

class ChatApiController extends Controller
{
    private function apiUser(): array
    {
        $user = $_REQUEST['_api_user'] ?? null;
        if (!$user) {
            $this->jsonError('Não autenticado.', [], 401);
        }
        return $user;
    }

    private function isAgent(): bool
    {
        return ($this->apiUser()['role_slug'] ?? '') === 'agent';
    }

    private function isSupervisorOrAdmin(): bool
    {
        $role = $this->apiUser()['role_slug'] ?? '';
        return in_array($role, ['admin', 'supervisor'], true);
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /api/v1/chats
    // ─────────────────────────────────────────────────────────────────
    public function index(Request $request): void
    {
        $user   = $this->apiUser();
        $status = trim($request->get('status', 'all'));
        $search = trim($request->get('search', ''));
        $page   = max(1, (int)$request->get('page', 1));
        $limit  = min(50, max(1, (int)$request->get('limit', 20)));

        $db    = Database::getInstance();
        $where = '1=1';
        $params = [];

        if ($status !== 'all' && in_array($status, ['waiting','in_progress','finished','bot'], true)) {
            $where   .= " AND c.status = ?";
            $params[] = $status;
        }

        if ($this->isAgent()) {
            $where   .= " AND c.assigned_to = ?";
            $params[] = $user['id'];
        }

        if ($search !== '') {
            $where   .= " AND (ct.name LIKE ? OR ct.phone LIKE ?)";
            $s        = "%{$search}%";
            $params[] = $s;
            $params[] = $s;
        }

        $total  = (int)($db->selectOne("SELECT COUNT(*) as cnt FROM chats c LEFT JOIN contacts ct ON ct.id = c.contact_id WHERE {$where}", $params)['cnt'] ?? 0);
        $offset = ($page - 1) * $limit;

        $rows = $db->select(
            "SELECT c.*, ct.name as contact_name, ct.phone as contact_phone, ct.avatar as contact_avatar,
                    u.name as agent_name
             FROM chats c
             LEFT JOIN contacts ct ON ct.id = c.contact_id
             LEFT JOIN users u ON u.id = c.assigned_to
             WHERE {$where}
             ORDER BY c.last_message_at DESC LIMIT {$limit} OFFSET {$offset}",
            $params
        );

        $this->jsonSuccess('OK', [
            'data'         => array_map([$this, 'formatChat'], $rows),
            'total'        => $total,
            'per_page'     => $limit,
            'current_page' => $page,
            'last_page'    => max(1, (int)ceil($total / $limit)),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /api/v1/chats/{id}
    // ─────────────────────────────────────────────────────────────────
    public function show(Request $request, string $id): void
    {
        $user = $this->apiUser();
        $chat = $this->findChat((int)$id);

        if ($this->isAgent() && (int)($chat['assigned_to'] ?? 0) !== (int)$user['id']) {
            $this->jsonError('Sem permissão para acessar esta conversa.', [], 403);
        }

        $this->jsonSuccess('OK', ['chat' => $this->formatChat($chat)]);
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /api/v1/chats/{id}/messages
    // ─────────────────────────────────────────────────────────────────
    public function messages(Request $request, string $id): void
    {
        $this->findChat((int)$id); // ensures exists

        $beforeId = $request->get('before_id') ? (int)$request->get('before_id') : null;
        $limit    = min(100, max(10, (int)$request->get('limit', 40)));
        $messages = Message::findByChatId((int)$id, $limit, $beforeId);

        $this->jsonSuccess('OK', [
            'messages' => array_map([$this, 'formatMessage'], $messages),
            'has_more' => count($messages) >= $limit,
            'chat_id'  => (int)$id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /api/v1/chats/{id}/messages
    // ─────────────────────────────────────────────────────────────────
    public function sendMessage(Request $request, string $id): void
    {
        $user    = $this->apiUser();
        $chat    = $this->findChat((int)$id);
        $content = trim($request->input('content', ''));

        if ($content === '') {
            $this->jsonError('O campo content é obrigatório.', [], 422);
        }

        if ($chat['status'] === 'finished') {
            $this->jsonError('Esta conversa já está finalizada.', [], 409);
        }

        $whatsappMsgId = null;
        $msgStatus     = 'sent';

        try {
            $service   = new MetaWhatsAppService();
            $apiResult = $service->sendText($chat['contact_phone'] ?? '', $content);
            $whatsappMsgId = $apiResult['messages'][0]['id'] ?? null;
            if (isset($apiResult['error'])) {
                $this->jsonError($apiResult['error']['message'] ?? 'Erro ao enviar.', [], 502);
            }
        } catch (\Throwable $e) {
            $this->jsonError('Erro ao enviar mensagem: ' . $e->getMessage(), [], 502);
        }

        $message = Message::createOutbound(
            chatId:            (int)$id,
            userId:            (int)$user['id'],
            content:           $content,
            type:              'text',
            whatsappMessageId: $whatsappMsgId,
            mediaUrl:          null,
            status:            $msgStatus
        );

        Chat::updateLastMessage((int)$id, $content);

        if (in_array($chat['status'], ['waiting', 'bot'], true)) {
            Database::getInstance()->update(
                "UPDATE chats SET status='in_progress', assigned_to=COALESCE(assigned_to,?), updated_at=NOW() WHERE id=?",
                [$user['id'], (int)$id]
            );
        }

        $this->jsonSuccess('Mensagem enviada.', ['message' => $this->formatMessage($message)], 201);
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /api/v1/chats/{id}/assign
    // ─────────────────────────────────────────────────────────────────
    public function assign(Request $request, string $id): void
    {
        $user = $this->apiUser();
        $chat = $this->findChat((int)$id);

        if ($chat['status'] === 'finished') {
            $this->jsonError('Conversa já finalizada.', [], 409);
        }

        $assignTo = $this->isSupervisorOrAdmin() && $request->input('user_id')
            ? (int)$request->input('user_id')
            : (int)$user['id'];

        Database::getInstance()->update(
            "UPDATE chats SET assigned_to=?, status='in_progress', is_bot_active=0, updated_at=NOW() WHERE id=?",
            [$assignTo, (int)$id]
        );

        $this->jsonSuccess('Atribuído com sucesso.', ['chat' => $this->formatChat($this->findChat((int)$id))]);
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /api/v1/chats/{id}/finish
    // ─────────────────────────────────────────────────────────────────
    public function finish(Request $request, string $id): void
    {
        $user = $this->apiUser();
        $chat = $this->findChat((int)$id);

        if ($chat['status'] === 'finished') {
            $this->jsonError('Conversa já está finalizada.', [], 409);
        }

        $notes = trim($request->input('notes', ''));

        Database::getInstance()->update(
            "UPDATE chats SET status='finished', finished_at=NOW(), finished_by=?, finish_notes=?, updated_at=NOW() WHERE id=?",
            [$user['id'], $notes ?: null, (int)$id]
        );

        $this->jsonSuccess('Conversa finalizada.', ['chat' => $this->formatChat($this->findChat((int)$id))]);
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /api/v1/contacts
    // ─────────────────────────────────────────────────────────────────
    public function contacts(Request $request): void
    {
        $search = trim($request->get('search', ''));
        $page   = max(1, (int)$request->get('page', 1));
        $limit  = min(50, max(1, (int)$request->get('limit', 20)));

        $db     = Database::getInstance();
        $where  = '1=1';
        $params = [];

        if ($search !== '') {
            $where   .= " AND (name LIKE ? OR phone LIKE ? OR email LIKE ?)";
            $s        = "%{$search}%";
            $params[] = $s; $params[] = $s; $params[] = $s;
        }

        $total  = (int)($db->selectOne("SELECT COUNT(*) as cnt FROM contacts WHERE {$where}", $params)['cnt'] ?? 0);
        $offset = ($page - 1) * $limit;
        $rows   = $db->select("SELECT id,name,phone,email,avatar,status,last_seen_at,created_at FROM contacts WHERE {$where} ORDER BY name ASC LIMIT {$limit} OFFSET {$offset}", $params);

        $this->jsonSuccess('OK', [
            'data'         => $rows,
            'total'        => $total,
            'per_page'     => $limit,
            'current_page' => $page,
            'last_page'    => max(1, (int)ceil($total / $limit)),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /api/v1/contacts/{id}
    // ─────────────────────────────────────────────────────────────────
    public function contact(Request $request, string $id): void
    {
        $row = Database::getInstance()->selectOne(
            "SELECT id,name,phone,email,avatar,status,notes,last_seen_at,created_at FROM contacts WHERE id=? LIMIT 1",
            [(int)$id]
        );
        if (!$row) {
            $this->jsonError('Contato não encontrado.', [], 404);
        }
        $this->jsonSuccess('OK', ['contact' => $row]);
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────
    private function findChat(int $id): array
    {
        $chat = Database::getInstance()->selectOne(
            "SELECT c.*, ct.name as contact_name, ct.phone as contact_phone, ct.avatar as contact_avatar,
                    u.name as agent_name
             FROM chats c
             LEFT JOIN contacts ct ON ct.id = c.contact_id
             LEFT JOIN users u ON u.id = c.assigned_to
             WHERE c.id = ? LIMIT 1",
            [$id]
        );
        if (!$chat) {
            $this->jsonError('Conversa não encontrada.', [], 404);
        }
        return $chat;
    }

    private function formatChat(array $chat): array
    {
        return [
            'id'              => (int)$chat['id'],
            'status'          => $chat['status'],
            'channel'         => $chat['channel'],
            'protocol'        => $chat['protocol'],
            'last_message'    => $chat['last_message'],
            'last_message_at' => $chat['last_message_at'],
            'unread_count'    => (int)$chat['unread_count'],
            'is_bot_active'   => (bool)$chat['is_bot_active'],
            'assigned_to'     => $chat['assigned_to'] ? (int)$chat['assigned_to'] : null,
            'agent_name'      => $chat['agent_name'] ?? null,
            'started_at'      => $chat['started_at'],
            'finished_at'     => $chat['finished_at'],
            'created_at'      => $chat['created_at'],
            'contact'         => [
                'name'   => $chat['contact_name'],
                'phone'  => $chat['contact_phone'],
                'avatar' => $chat['contact_avatar'],
            ],
        ];
    }

    private function formatMessage(array $msg): array
    {
        return [
            'id'         => (int)$msg['id'],
            'chat_id'    => (int)$msg['chat_id'],
            'direction'  => $msg['direction'],
            'type'       => $msg['type'],
            'content'    => $msg['content'],
            'status'     => $msg['status'],
            'media_url'  => $msg['media_url'] ?? null,
            'created_at' => $msg['created_at'],
        ];
    }
}
