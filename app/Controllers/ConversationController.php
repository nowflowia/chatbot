<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Auth;
use Core\Database;
use App\Models\WhatsappTemplate;
use App\Models\Contact;
use App\Models\Chat;
use App\Models\Message;
use App\Services\MetaWhatsAppService;

class ConversationController extends Controller
{
    private function requireSupervisorOrAdmin(): void
    {
        if (!Auth::isSupervisorOrAdmin()) {
            $this->redirect(url('admin/dashboard'));
            exit;
        }
    }

    // ── GET /admin/conversations/active ─────────────────────────────

    public function active(Request $request): string
    {
        $this->requireSupervisorOrAdmin();

        $db       = Database::getInstance();
        $category = $request->get('category', '');

        $conditions = ["c.conversation_category IS NOT NULL"];
        $bindings   = [];

        if ($category !== '' && in_array($category, ['marketing', 'utility', 'service', 'authentication'], true)) {
            $conditions[] = "c.conversation_category = ?";
            $bindings[]   = $category;
        }

        $where = 'WHERE ' . implode(' AND ', $conditions);

        $conversations = $db->select(
            "SELECT c.*,
                    ct.name  AS contact_name,
                    ct.phone AS contact_phone,
                    t.name   AS template_name
             FROM chats c
             LEFT JOIN contacts ct ON ct.id = c.contact_id
             LEFT JOIN whatsapp_templates t ON t.name = c.last_message
             {$where}
             ORDER BY c.last_message_at DESC, c.updated_at DESC
             LIMIT 200",
            $bindings
        );

        // Load approved templates for the modal
        $approvedTemplates = $db->select(
            "SELECT id, name, category, language, body_text, variables FROM whatsapp_templates
             WHERE status = 'approved'
             ORDER BY category ASC, name ASC"
        );

        // Decode variables JSON
        foreach ($approvedTemplates as &$tpl) {
            $tpl['variables_decoded'] = !empty($tpl['variables'])
                ? json_decode($tpl['variables'], true)
                : [];
        }
        unset($tpl);

        return $this->view('conversations/active', [
            'conversations'     => $conversations,
            'approvedTemplates' => $approvedTemplates,
            'activeCategory'    => $category,
        ]);
    }

    // ── POST /admin/conversations/send ───────────────────────────────

    public function send(Request $request): void
    {
        $this->requireSupervisorOrAdmin();

        $templateId  = (int) $request->post('template_id', 0);
        $rawContacts = $request->post('contact_ids', []);
        $variables   = $request->post('variables', []);

        // contact_ids can be a single value or an array
        if (!is_array($rawContacts)) {
            $rawContacts = [$rawContacts];
        }
        $contactIds = array_filter(array_map('intval', $rawContacts));

        if ($templateId === 0) {
            $this->jsonError('Selecione um template.', [], 422);
        }
        if (empty($contactIds)) {
            $this->jsonError('Selecione pelo menos um contato.', [], 422);
        }

        $template = WhatsappTemplate::find($templateId);
        if (!$template) {
            $this->jsonError('Template não encontrado.', [], 404);
        }
        if ($template['status'] !== 'approved') {
            $this->jsonError('Apenas templates aprovados podem ser enviados.', [], 422);
        }

        $db               = Database::getInstance();
        $currentUser      = Auth::user();
        $userId           = (int) ($currentUser['id'] ?? 0);
        $service          = new MetaWhatsAppService();
        $waSetting        = $db->selectOne("SELECT id FROM whatsapp_settings ORDER BY id ASC LIMIT 1");
        $waSettingId      = $waSetting ? (int) $waSetting['id'] : null;

        // Replace variables in body text for the stored message preview
        $bodyText = $template['body_text'];
        if (!empty($variables) && is_array($variables)) {
            foreach ($variables as $idx => $val) {
                $placeholder = '{{' . (int) $idx . '}}';
                $bodyText    = str_replace($placeholder, (string) $val, $bodyText);
            }
        }

        // Build components array for the template send call
        $components = [];
        if (!empty($variables)) {
            $params = [];
            foreach ($variables as $val) {
                $params[] = ['type' => 'text', 'text' => (string) $val];
            }
            if (!empty($params)) {
                $components[] = ['type' => 'body', 'parameters' => $params];
            }
        }

        $sent   = 0;
        $errors = [];

        foreach ($contactIds as $contactId) {
            $contact = $db->selectOne(
                "SELECT * FROM contacts WHERE id = ? LIMIT 1",
                [$contactId]
            );

            if (!$contact || empty($contact['phone'])) {
                $errors[] = "Contato ID {$contactId} não encontrado ou sem telefone.";
                continue;
            }

            try {
                // Send via Meta API
                $apiResponse = $service->sendTemplate(
                    $contact['phone'],
                    $template['name'],
                    $template['language'],
                    $components
                );

                $waMessageId = $apiResponse['messages'][0]['id'] ?? null;

                // Find or create an active chat tagged with this category
                $chat = $db->selectOne(
                    "SELECT * FROM chats
                     WHERE contact_id = ?
                       AND conversation_category = ?
                       AND status IN ('waiting','in_progress','bot')
                     ORDER BY created_at DESC LIMIT 1",
                    [$contactId, $template['category']]
                );

                if (!$chat) {
                    $chatId = $db->insert(
                        "INSERT INTO chats
                            (contact_id, whatsapp_setting_id, status, conversation_category,
                             last_message, last_message_at, unread_count, is_bot_active,
                             created_at, updated_at)
                         VALUES (?, ?, 'in_progress', ?, ?, ?, 0, 0, ?, ?)",
                        [
                            $contactId,
                            $waSettingId,
                            $template['category'],
                            mb_substr($bodyText, 0, 255),
                            now(),
                            now(),
                            now(),
                        ]
                    );
                    $chatId = (int) $chatId;
                } else {
                    $chatId = (int) $chat['id'];
                    $db->update(
                        "UPDATE chats SET last_message = ?, last_message_at = ?,
                                          conversation_category = ?, updated_at = ?
                         WHERE id = ?",
                        [mb_substr($bodyText, 0, 255), now(), $template['category'], now(), $chatId]
                    );
                }

                // Store outbound message
                $db->insert(
                    "INSERT INTO messages
                        (chat_id, user_id, whatsapp_message_id, direction, type,
                         content, status, created_at, updated_at)
                     VALUES (?, ?, ?, 'outbound', 'template', ?, 'sent', ?, ?)",
                    [$chatId, $userId, $waMessageId, $bodyText, now(), now()]
                );

                // Update contact last_seen
                Contact::updateLastSeen($contactId);

                $sent++;
            } catch (\Throwable $e) {
                $errors[] = "Contato ID {$contactId}: " . $e->getMessage();
            }
        }

        $message = "Mensagem enviada para {$sent} contato(s).";
        if (!empty($errors)) {
            $message .= ' Erros: ' . implode('; ', $errors);
        }

        $this->jsonSuccess($message, ['sent' => $sent, 'errors' => $errors]);
    }

    // ── POST /admin/conversations/search-contact ─────────────────────

    public function searchContact(Request $request): void
    {
        $this->requireSupervisorOrAdmin();

        $q = trim((string) $request->post('q', $request->get('q', '')));

        if (strlen($q) < 2) {
            $this->jsonSuccess('OK', ['contacts' => []]);
        }

        $result   = Contact::search($q, 1, 15);
        $contacts = array_map(fn($c) => [
            'id'    => $c['id'],
            'name'  => $c['name'],
            'phone' => $c['phone'],
            'email' => $c['email'] ?? '',
        ], $result['data']);

        $this->jsonSuccess('OK', ['contacts' => $contacts]);
    }
}
