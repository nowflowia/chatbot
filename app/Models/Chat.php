<?php

namespace App\Models;

use Core\Model;
use Core\Database;

class Chat extends Model
{
    protected static string $table = 'chats';

    protected array $fillable = [
        'contact_id',
        'assigned_to',
        'flow_id',
        'whatsapp_setting_id',
        'status',
        'last_message',
        'last_message_at',
        'unread_count',
        'is_bot_active',
    ];

    /**
     * Find a single chat with its contact data joined.
     */
    public static function findWithContact(int $id): ?array
    {
        return Database::getInstance()->selectOne(
            "SELECT c.*,
                    ct.name  AS contact_name,
                    ct.phone AS contact_phone,
                    ct.email AS contact_email,
                    ct.avatar AS contact_avatar,
                    ct.last_seen_at AS contact_last_seen,
                    u.name AS assigned_user_name
             FROM chats c
             LEFT JOIN contacts ct ON ct.id = c.contact_id
             LEFT JOIN users u     ON u.id  = c.assigned_to
             WHERE c.id = ? LIMIT 1",
            [$id]
        );
    }

    /**
     * List chats with contact info, optional filters and pagination.
     *
     * @return array{data: array, total: int, per_page: int, current_page: int, last_page: int}
     */
    public static function allWithContacts(int $page = 1, int $perPage = 30, string $status = '', string $search = ''): array
    {
        $db     = Database::getInstance();
        $offset = ($page - 1) * $perPage;

        $conditions = [];
        $bindings   = [];

        if ($status !== '' && $status !== 'all') {
            $conditions[] = "c.status = ?";
            $bindings[]   = $status;
        }

        if ($search !== '') {
            $like           = '%' . $search . '%';
            $conditions[]   = "(ct.name LIKE ? OR ct.phone LIKE ?)";
            $bindings[]     = $like;
            $bindings[]     = $like;
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $total = (int)($db->selectOne(
            "SELECT COUNT(*) as cnt
             FROM chats c
             LEFT JOIN contacts ct ON ct.id = c.contact_id
             {$where}",
            $bindings
        )['cnt'] ?? 0);

        $data = $db->select(
            "SELECT c.*,
                    ct.name       AS contact_name,
                    ct.phone      AS contact_phone,
                    ct.email      AS contact_email,
                    ct.avatar AS contact_avatar,
                    u.name        AS assigned_user_name
             FROM chats c
             LEFT JOIN contacts ct ON ct.id = c.contact_id
             LEFT JOIN users u     ON u.id  = c.assigned_to
             {$where}
             ORDER BY c.last_message_at DESC, c.updated_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        return [
            'data'         => $data,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => max(1, (int)ceil($total / $perPage)),
        ];
    }

    /**
     * Return counts grouped by status.
     * Returns assoc array: ['waiting' => N, 'in_progress' => N, 'finished' => N, 'bot' => N, 'all' => N]
     */
    public static function countByStatus(): array
    {
        $rows = Database::getInstance()->select(
            "SELECT status, COUNT(*) as cnt FROM chats GROUP BY status"
        );

        $counts = ['waiting' => 0, 'in_progress' => 0, 'finished' => 0, 'bot' => 0, 'all' => 0];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int)$row['cnt'];
            $counts['all']         += (int)$row['cnt'];
        }

        return $counts;
    }

    /**
     * Get messages for a chat with optional cursor-based pagination.
     * Returns messages in ascending order (oldest first) for display.
     *
     * @param int      $chatId
     * @param int      $limit
     * @param int|null $beforeId  Load messages with id < beforeId (for infinite scroll upward)
     */
    public static function getMessages(int $chatId, int $limit = 40, ?int $beforeId = null): array
    {
        $db       = Database::getInstance();
        $bindings = [$chatId];
        $extra    = '';

        if ($beforeId !== null) {
            $extra      = "AND m.id < ?";
            $bindings[] = $beforeId;
        }

        // Fetch last N messages in DESC, then reverse for chronological display
        $rows = $db->select(
            "SELECT m.*,
                    u.name AS sender_name
             FROM messages m
             LEFT JOIN users u ON u.id = m.user_id
             WHERE m.chat_id = ? {$extra}
             ORDER BY m.id DESC
             LIMIT {$limit}",
            $bindings
        );

        return array_reverse($rows);
    }

    /**
     * Reset unread_count to 0 for a chat.
     */
    public static function markAsRead(int $chatId): void
    {
        Database::getInstance()->update(
            "UPDATE chats SET unread_count = 0, updated_at = ? WHERE id = ?",
            [now(), $chatId]
        );
    }

    /**
     * Update last_message and last_message_at for a chat.
     */
    public static function updateLastMessage(int $chatId, string $text): void
    {
        Database::getInstance()->update(
            "UPDATE chats SET last_message = ?, last_message_at = ?, updated_at = ? WHERE id = ?",
            [mb_substr($text, 0, 255), now(), now(), $chatId]
        );
    }

    /**
     * Increment unread_count by 1 for a chat.
     */
    public static function incrementUnread(int $chatId): void
    {
        Database::getInstance()->update(
            "UPDATE chats SET unread_count = unread_count + 1, updated_at = ? WHERE id = ?",
            [now(), $chatId]
        );
    }

    /**
     * Find the most recent open chat for a contact.
     */
    public static function findOpenByContact(int $contactId): ?array
    {
        return Database::getInstance()->selectOne(
            "SELECT * FROM chats
             WHERE contact_id = ? AND status IN ('waiting', 'in_progress', 'bot')
             ORDER BY created_at DESC LIMIT 1",
            [$contactId]
        );
    }

    /**
     * Create a new chat for a contact.
     */
    public static function openForContact(int $contactId, ?int $whatsappSettingId = null): array
    {
        $db = Database::getInstance();
        $id = $db->insert(
            "INSERT INTO chats (contact_id, whatsapp_setting_id, status, unread_count, is_bot_active, created_at, updated_at)
             VALUES (?, ?, 'waiting', 0, 1, ?, ?)",
            [$contactId, $whatsappSettingId, now(), now()]
        );
        return $db->selectOne("SELECT * FROM chats WHERE id = ? LIMIT 1", [(int)$id]);
    }
}
