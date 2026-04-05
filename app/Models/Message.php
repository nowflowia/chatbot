<?php

namespace App\Models;

use Core\Model;
use Core\Database;

class Message extends Model
{
    protected static string $table = 'messages';

    protected array $fillable = [
        'chat_id',
        'contact_id',
        'user_id',
        'whatsapp_message_id',
        'direction',
        'type',
        'content',
        'media_url',
        'status',
        'raw_payload',
    ];

    /**
     * Get messages for a chat with optional cursor pagination.
     * Returns messages in ascending (chronological) order.
     *
     * @param int      $chatId
     * @param int      $limit
     * @param int|null $beforeId  Return messages with id < beforeId
     */
    public static function findByChatId(int $chatId, int $limit = 40, ?int $beforeId = null): array
    {
        $db       = Database::getInstance();
        $bindings = [$chatId];
        $cursor   = '';

        if ($beforeId !== null) {
            $cursor     = "AND m.id < ?";
            $bindings[] = $beforeId;
        }

        $rows = $db->select(
            "SELECT m.*,
                    u.name AS sender_name
             FROM messages m
             LEFT JOIN users u ON u.id = m.user_id
             WHERE m.chat_id = ? {$cursor}
             ORDER BY m.id DESC
             LIMIT {$limit}",
            $bindings
        );

        return array_reverse($rows);
    }

    /**
     * Get the last message of a chat.
     */
    public static function getLastMessage(int $chatId): ?array
    {
        return Database::getInstance()->selectOne(
            "SELECT * FROM messages WHERE chat_id = ? ORDER BY id DESC LIMIT 1",
            [$chatId]
        );
    }

    /**
     * Create an inbound message (received from WhatsApp contact).
     */
    public static function createInbound(
        int     $chatId,
        int     $contactId,
        string  $content,
        string  $type = 'text',
        ?string $whatsappMessageId = null,
        ?string $mediaUrl = null,
        ?string $rawPayload = null
    ): array {
        $db = Database::getInstance();

        $id = $db->insert(
            "INSERT INTO messages
                (chat_id, contact_id, whatsapp_message_id, direction, type, content, media_url, status, raw_payload, created_at, updated_at)
             VALUES (?, ?, ?, 'inbound', ?, ?, ?, 'received', ?, ?, ?)",
            [
                $chatId,
                $contactId,
                $whatsappMessageId,
                $type,
                $content,
                $mediaUrl,
                $rawPayload,
                now(),
                now(),
            ]
        );

        return $db->selectOne("SELECT * FROM messages WHERE id = ? LIMIT 1", [(int)$id]);
    }

    /**
     * Create an outbound message (sent by an agent / system).
     */
    public static function createOutbound(
        int     $chatId,
        int     $userId,
        string  $content,
        string  $type = 'text',
        ?string $whatsappMessageId = null,
        ?string $mediaUrl = null,
        string  $status = 'sent'
    ): array {
        $db = Database::getInstance();

        $id = $db->insert(
            "INSERT INTO messages
                (chat_id, user_id, whatsapp_message_id, direction, type, content, media_url, status, created_at, updated_at)
             VALUES (?, ?, ?, 'outbound', ?, ?, ?, ?, ?, ?)",
            [
                $chatId,
                $userId,
                $whatsappMessageId,
                $type,
                $content,
                $mediaUrl,
                $status,
                now(),
                now(),
            ]
        );

        return $db->selectOne("SELECT * FROM messages WHERE id = ? LIMIT 1", [(int)$id]);
    }

    /**
     * Update the delivery/read status of a message by its WhatsApp message ID.
     */
    public static function updateStatusByWhatsappId(string $whatsappMessageId, string $status): void
    {
        Database::getInstance()->update(
            "UPDATE messages SET status = ?, updated_at = ? WHERE whatsapp_message_id = ?",
            [$status, now(), $whatsappMessageId]
        );
    }

    /**
     * Count unread inbound messages for a chat.
     */
    public static function countUnread(int $chatId): int
    {
        $result = Database::getInstance()->selectOne(
            "SELECT COUNT(*) as cnt FROM messages WHERE chat_id = ? AND direction = 'inbound' AND status != 'read'",
            [$chatId]
        );
        return (int)($result['cnt'] ?? 0);
    }
}
