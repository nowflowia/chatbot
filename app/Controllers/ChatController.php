<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Auth;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Contact;
use App\Services\MetaWhatsAppService;

class ChatController extends Controller
{
    // ----------------------------------------------------------------
    // Page views
    // ----------------------------------------------------------------

    /**
     * Main chat interface — no specific chat selected.
     */
    public function index(Request $request): string
    {
        $counts = Chat::countByStatus();
        $user   = Auth::user();

        return $this->view('chat/index', [
            'counts'     => $counts,
            'activeChat' => null,
            'user'       => $user,
        ]);
    }

    /**
     * Main chat interface with a specific chat pre-opened.
     */
    public function show(Request $request, string $id): string
    {
        $chat = Chat::findWithContact((int)$id);

        if (!$chat) {
            $this->flash('error', 'Conversa não encontrada.');
            $this->redirect(url('admin/chat'));
        }

        // Mark chat as read when opened
        Chat::markAsRead((int)$id);

        $counts = Chat::countByStatus();
        $user   = Auth::user();

        return $this->view('chat/index', [
            'counts'     => $counts,
            'activeChat' => $chat,
            'user'       => $user,
        ]);
    }

    // ----------------------------------------------------------------
    // AJAX — chat list
    // ----------------------------------------------------------------

    /**
     * GET /admin/chat/list/active
     * Returns list of chats with contact info for polling.
     */
    public function getActiveChats(Request $request): void
    {
        $status = trim($request->get('status', 'all'));
        $search = trim($request->get('search', ''));
        $page   = max(1, (int)$request->get('page', 1));

        $result = Chat::allWithContacts($page, 30, $status, $search);
        $counts = Chat::countByStatus();

        $chats = array_map(function (array $chat) {
            return $this->formatChatForList($chat);
        }, $result['data']);

        $this->jsonSuccess('OK', [
            'chats'       => $chats,
            'counts'      => $counts,
            'total'       => $result['total'],
            'last_page'   => $result['last_page'],
            'current_page'=> $result['current_page'],
        ]);
    }

    // ----------------------------------------------------------------
    // AJAX — single chat data
    // ----------------------------------------------------------------

    /**
     * GET /admin/chat/{id}/data
     * Returns full data for a single chat.
     */
    public function getSingleChat(Request $request, string $id): void
    {
        $chat = Chat::findWithContact((int)$id);

        if (!$chat) {
            $this->jsonError('Conversa não encontrada.', [], 404);
        }

        $this->jsonSuccess('OK', ['chat' => $this->formatChatForList($chat)]);
    }

    // ----------------------------------------------------------------
    // AJAX — messages
    // ----------------------------------------------------------------

    /**
     * GET /admin/chat/{id}/messages
     * Returns paginated messages. Supports ?before_id=X for infinite scroll upward.
     */
    public function getMessages(Request $request, string $id): void
    {
        $chat = Chat::find((int)$id);

        if (!$chat) {
            $this->jsonError('Conversa não encontrada.', [], 404);
        }

        $beforeId = $request->get('before_id') ? (int)$request->get('before_id') : null;
        $limit    = min(60, max(10, (int)$request->get('limit', 40)));

        $messages = Message::findByChatId((int)$id, $limit, $beforeId);

        // Mark chat as read when agent opens/polls messages
        if ($beforeId === null) {
            Chat::markAsRead((int)$id);
        }

        $formatted = array_map([$this, 'formatMessage'], $messages);

        $hasMore = count($messages) >= $limit;

        $this->jsonSuccess('OK', [
            'messages' => $formatted,
            'has_more' => $hasMore,
            'chat_id'  => (int)$id,
        ]);
    }

    // ----------------------------------------------------------------
    // AJAX — send message
    // ----------------------------------------------------------------

    /**
     * POST /admin/chat/{id}/message
     * Send a text message to the contact via WhatsApp.
     */
    public function sendMessage(Request $request, string $id): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->jsonError('Não autorizado.', [], 401);
        }

        $chat = Chat::findWithContact((int)$id);
        if (!$chat) {
            $this->jsonError('Conversa não encontrada.', [], 404);
        }

        $content = trim($request->post('content', ''));
        if ($content === '') {
            $this->jsonError('Mensagem não pode ser vazia.', ['content' => ['Campo obrigatório.']]);
        }

        if ($chat['status'] === 'finished') {
            $this->jsonError('Esta conversa já foi finalizada.');
        }

        // Send via Meta WhatsApp API
        $whatsappMsgId = null;
        try {
            $service  = new MetaWhatsAppService();
            $phone    = $chat['contact_phone'] ?? '';
            $apiResult = $service->sendText($phone, $content);

            if (isset($apiResult['messages'][0]['id'])) {
                $whatsappMsgId = $apiResult['messages'][0]['id'];
                $msgStatus     = 'sent';
            } elseif (isset($apiResult['error'])) {
                $errMsg = $apiResult['error']['message'] ?? 'Erro ao enviar via WhatsApp.';
                $this->jsonError($errMsg);
            } else {
                $msgStatus = 'sent';
            }
        } catch (\Throwable $e) {
            logger('ChatController@sendMessage error: ' . $e->getMessage(), 'error');
            $this->jsonError('Erro ao enviar mensagem: ' . $e->getMessage());
        }

        // Save message to database
        $message = Message::createOutbound(
            chatId:           (int)$id,
            userId:           (int)$user['id'],
            content:          $content,
            type:             'text',
            whatsappMessageId: $whatsappMsgId,
            mediaUrl:         null,
            status:           $msgStatus ?? 'sent'
        );

        // Update chat's last message
        Chat::updateLastMessage((int)$id, $content);

        // If chat was in 'waiting', move to 'in_progress' and assign if not already assigned
        if ($chat['status'] === 'waiting' || $chat['status'] === 'bot') {
            $updateData = ['status' => 'in_progress', 'updated_at' => now()];
            if (empty($chat['assigned_to'])) {
                $updateData['assigned_to'] = $user['id'];
            }
            \Core\Database::getInstance()->update(
                "UPDATE chats SET status = ?, assigned_to = COALESCE(assigned_to, ?), updated_at = ? WHERE id = ?",
                [$updateData['status'], $user['id'], now(), (int)$id]
            );
        }

        $this->jsonSuccess('Mensagem enviada.', ['message' => $this->formatMessage($message)]);
    }

    // ----------------------------------------------------------------
    // AJAX — assign chat
    // ----------------------------------------------------------------

    /**
     * POST /admin/chat/{id}/assign
     * Assign the chat to the currently logged-in user.
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

        $this->jsonSuccess('Atendimento assumido.', ['chat' => $this->formatChatForList($updated)]);
    }

    // ----------------------------------------------------------------
    // AJAX — finish chat
    // ----------------------------------------------------------------

    /**
     * POST /admin/chat/{id}/finish
     * Mark the chat as finished.
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

        $updated = Chat::findWithContact((int)$id);

        $this->jsonSuccess('Conversa finalizada.', ['chat' => $this->formatChatForList($updated)]);
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    /**
     * Format a chat row for the list/polling response.
     */
    private function formatChatForList(array $chat): array
    {
        return [
            'id'                 => (int)$chat['id'],
            'status'             => $chat['status'],
            'last_message'       => $chat['last_message'] ?? '',
            'last_message_at'    => $chat['last_message_at'] ?? $chat['updated_at'] ?? '',
            'last_message_human' => $this->humanTime($chat['last_message_at'] ?? $chat['updated_at'] ?? ''),
            'unread_count'       => (int)($chat['unread_count'] ?? 0),
            'is_bot_active'      => (bool)($chat['is_bot_active'] ?? false),
            'assigned_to'        => $chat['assigned_to'] ? (int)$chat['assigned_to'] : null,
            'assigned_user_name' => $chat['assigned_user_name'] ?? null,
            'contact' => [
                'id'        => (int)$chat['contact_id'],
                'name'      => $chat['contact_name']   ?? 'Desconhecido',
                'phone'     => $chat['contact_phone']  ?? '',
                'email'     => $chat['contact_email']  ?? '',
                'avatar'    => $chat['contact_avatar'] ?? null,
                'initials'  => $this->initials($chat['contact_name'] ?? ''),
                'color'     => $this->avatarColor($chat['contact_id'] ?? 0),
                'last_seen' => $chat['contact_last_seen'] ?? null,
            ],
        ];
    }

    /**
     * Format a message row for the API response.
     */
    private function formatMessage(array $msg): array
    {
        return [
            'id'             => (int)$msg['id'],
            'chat_id'        => (int)$msg['chat_id'],
            'direction'      => $msg['direction'],
            'type'           => $msg['type'] ?? 'text',
            'content'        => $msg['content'] ?? '',
            'media_url'      => $msg['media_url'] ?? null,
            'media_mime'     => $msg['media_mime'] ?? null,
            'media_filename' => $msg['media_filename'] ?? null,
            'status'         => $msg['status'] ?? 'sent',
            'sender_name'    => $msg['sender_name'] ?? null,
            'user_id'        => $msg['user_id'] ? (int)$msg['user_id'] : null,
            'contact_id'     => $msg['contact_id'] ? (int)$msg['contact_id'] : null,
            'created_at'     => $msg['created_at'] ?? '',
            'time_human'     => $this->humanTime($msg['created_at'] ?? ''),
            'whatsapp_id'    => $msg['whatsapp_message_id'] ?? null,
        ];
    }

    /**
     * Generate human-readable relative time.
     */
    private function humanTime(string $datetime): string
    {
        if (!$datetime) return '';
        try {
            $ts   = strtotime($datetime);
            $diff = time() - $ts;

            if ($diff < 60)      return 'agora';
            if ($diff < 3600)    return (int)($diff / 60) . 'min';
            if ($diff < 86400)   return date('H:i', $ts);
            if ($diff < 604800)  return date('d/m', $ts);
            return date('d/m/y', $ts);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Return up to 2 initials from a name string.
     */
    private function initials(string $name): string
    {
        $words = array_filter(explode(' ', trim($name)));
        if (empty($words)) return '?';
        $parts = array_values($words);
        if (count($parts) === 1) return mb_strtoupper(mb_substr($parts[0], 0, 2));
        return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1));
    }

    /**
     * Deterministic avatar background color based on contact id.
     */
    private function avatarColor(int $contactId): string
    {
        $colors = ['#6366f1','#8b5cf6','#ec4899','#f59e0b','#10b981','#3b82f6','#ef4444','#14b8a6'];
        return $colors[$contactId % count($colors)];
    }
}
