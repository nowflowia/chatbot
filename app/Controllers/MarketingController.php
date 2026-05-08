<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Auth;
use Core\Database;
use App\Models\WhatsappTemplate;
use App\Models\Contact;
use App\Services\MetaWhatsAppService;

class MarketingController extends Controller
{
    private function requireMarketing(): void
    {
        if (!Auth::hasFeature('marketing')) {
            $this->redirect(url('admin/dashboard'));
            exit;
        }
    }

    // ── GET /admin/marketing ─────────────────────────────────────────

    public function index(Request $request): string
    {
        $this->requireMarketing();

        $db = Database::getInstance();

        $campaigns = $db->select(
            "SELECT mc.*, ml.name AS list_name, wt.name AS template_name
             FROM marketing_campaigns mc
             LEFT JOIN marketing_lists ml ON ml.id = mc.list_id
             LEFT JOIN whatsapp_templates wt ON wt.id = mc.whatsapp_template_id
             ORDER BY mc.created_at DESC
             LIMIT 100"
        );

        $lists = $db->select(
            "SELECT ml.*, COUNT(mlc.contact_id) AS contact_count
             FROM marketing_lists ml
             LEFT JOIN marketing_list_contacts mlc ON mlc.list_id = ml.id
             GROUP BY ml.id
             ORDER BY ml.name ASC"
        );

        $approvedTemplates = $db->select(
            "SELECT id, name, category, language, body_text, variables
             FROM whatsapp_templates
             WHERE status = 'approved'
             ORDER BY name ASC"
        );

        foreach ($approvedTemplates as &$tpl) {
            $tpl['variables_decoded'] = !empty($tpl['variables'])
                ? json_decode($tpl['variables'], true) : [];
        }
        unset($tpl);

        return $this->view('marketing/index', [
            'campaigns'         => $campaigns,
            'lists'             => $lists,
            'approvedTemplates' => $approvedTemplates,
            'activeTab'         => $request->get('tab', 'campaigns'),
        ]);
    }

    // ── POST /admin/marketing/lists ──────────────────────────────────

    public function storeList(Request $request): void
    {
        $this->requireMarketing();

        $name = trim((string) $request->post('name', ''));
        $desc = trim((string) $request->post('description', ''));

        if ($name === '') {
            $this->jsonError('O nome da lista é obrigatório.', [], 422);
        }

        $db = Database::getInstance();
        $ts = now();
        $id = $db->insert(
            "INSERT INTO marketing_lists (name, description, created_at, updated_at) VALUES (?, ?, ?, ?)",
            [$name, $desc ?: null, $ts, $ts]
        );

        $list = $db->selectOne("SELECT *, 0 AS contact_count FROM marketing_lists WHERE id = ? LIMIT 1", [(int)$id]);

        $this->jsonSuccess('Lista criada com sucesso!', ['list' => $list]);
    }

    // ── POST /admin/marketing/lists/{id}/delete ──────────────────────

    public function destroyList(Request $request, string $id): void
    {
        $this->requireMarketing();

        $db = Database::getInstance();
        $db->delete("DELETE FROM marketing_list_contacts WHERE list_id = ?", [(int)$id]);
        $db->delete("DELETE FROM marketing_lists WHERE id = ?", [(int)$id]);

        $this->jsonSuccess('Lista excluída.');
    }

    // ── POST /admin/marketing/lists/{id}/contacts ────────────────────
    // Add contacts to a list (from CSV import or manual search)

    public function addContacts(Request $request, string $listId): void
    {
        $this->requireMarketing();

        $listId = (int) $listId;
        $db     = Database::getInstance();

        $list = $db->selectOne("SELECT id FROM marketing_lists WHERE id = ? LIMIT 1", [$listId]);
        if (!$list) {
            $this->jsonError('Lista não encontrada.', [], 404);
        }

        // Accept JSON array of contact IDs or CSV file
        $contactIds = $request->post('contact_ids', []);
        if (!is_array($contactIds)) {
            $contactIds = array_filter(array_map('intval', explode(',', (string)$contactIds)));
        } else {
            $contactIds = array_filter(array_map('intval', $contactIds));
        }

        // CSV import
        $file = $_FILES['csv_file'] ?? null;
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $handle = fopen($file['tmp_name'], 'r');
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") rewind($handle);

            $firstLine = fgets($handle);
            rewind($handle);
            if ($bom === "\xEF\xBB\xBF") fread($handle, 3);
            $delimiter = str_contains($firstLine, ';') ? ';' : ',';

            $header = fgetcsv($handle, 0, $delimiter);
            $header = array_map(fn($h) => strtolower(trim($h)), $header ?? []);
            $phoneIdx = array_search('telefone', $header) !== false
                ? array_search('telefone', $header)
                : array_search('phone', $header);

            $added = 0;
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $phone = $phoneIdx !== false ? preg_replace('/\D/', '', $row[$phoneIdx] ?? '') : '';
                if (!$phone) continue;
                $contact = $db->selectOne(
                    "SELECT id FROM contacts WHERE REGEXP_REPLACE(phone, '[^0-9]', '') = ? LIMIT 1",
                    [$phone]
                );
                if ($contact) {
                    $contactIds[] = (int)$contact['id'];
                    $added++;
                }
            }
            fclose($handle);
        }

        if (empty($contactIds)) {
            $this->jsonError('Nenhum contato válido informado.', [], 422);
        }

        $ts    = now();
        $added = 0;
        foreach (array_unique($contactIds) as $cid) {
            $exists = $db->selectOne(
                "SELECT 1 FROM marketing_list_contacts WHERE list_id = ? AND contact_id = ? LIMIT 1",
                [$listId, $cid]
            );
            if (!$exists) {
                $db->insert(
                    "INSERT INTO marketing_list_contacts (list_id, contact_id, added_at) VALUES (?, ?, ?)",
                    [$listId, $cid, $ts]
                );
                $added++;
            }
        }

        $total = (int)($db->selectOne(
            "SELECT COUNT(*) AS c FROM marketing_list_contacts WHERE list_id = ?", [$listId]
        )['c'] ?? 0);

        $this->jsonSuccess("{$added} contato(s) adicionado(s) à lista.", ['added' => $added, 'total' => $total]);
    }

    // ── GET /admin/marketing/lists/{id}/contacts ─────────────────────

    public function listContacts(Request $request, string $id): void
    {
        $this->requireMarketing();

        $db = Database::getInstance();
        $contacts = $db->select(
            "SELECT c.id, c.name, c.phone, c.email
             FROM contacts c
             INNER JOIN marketing_list_contacts mlc ON mlc.contact_id = c.id
             WHERE mlc.list_id = ?
             ORDER BY c.name ASC
             LIMIT 500",
            [(int)$id]
        );

        $this->jsonSuccess('OK', ['contacts' => $contacts]);
    }

    // ── POST /admin/marketing/campaigns ─────────────────────────────

    public function storeCampaign(Request $request): void
    {
        $this->requireMarketing();

        $name       = trim((string) $request->post('name', ''));
        $templateId = (int) $request->post('template_id', 0);
        $listId     = (int) $request->post('list_id', 0);
        $variables  = $request->post('variables', []);

        if ($name === '') {
            $this->jsonError('O nome da campanha é obrigatório.', [], 422);
        }
        if ($templateId === 0) {
            $this->jsonError('Selecione um template.', [], 422);
        }
        if ($listId === 0) {
            $this->jsonError('Selecione uma lista de contatos.', [], 422);
        }

        $template = WhatsappTemplate::find($templateId);
        if (!$template || $template['status'] !== 'approved') {
            $this->jsonError('Template não encontrado ou não aprovado.', [], 422);
        }

        $db   = Database::getInstance();
        $ts   = now();
        $user = Auth::user();

        $total = (int)($db->selectOne(
            "SELECT COUNT(*) AS c FROM marketing_list_contacts WHERE list_id = ?", [$listId]
        )['c'] ?? 0);

        $id = $db->insert(
            "INSERT INTO marketing_campaigns
                (name, whatsapp_template_id, list_id, variables, status,
                 total_contacts, sent_count, failed_count, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, 'draft', ?, 0, 0, ?, ?, ?)",
            [
                $name,
                $templateId,
                $listId,
                !empty($variables) ? json_encode($variables) : null,
                $total,
                (int)($user['id'] ?? 0),
                $ts,
                $ts,
            ]
        );

        $campaign = $db->selectOne(
            "SELECT mc.*, ml.name AS list_name, wt.name AS template_name
             FROM marketing_campaigns mc
             LEFT JOIN marketing_lists ml ON ml.id = mc.list_id
             LEFT JOIN whatsapp_templates wt ON wt.id = mc.whatsapp_template_id
             WHERE mc.id = ? LIMIT 1",
            [(int)$id]
        );

        $this->jsonSuccess('Campanha criada!', ['campaign' => $campaign]);
    }

    // ── POST /admin/marketing/campaigns/{id}/send ────────────────────

    public function sendCampaign(Request $request, string $id): void
    {
        $this->requireMarketing();

        $db       = Database::getInstance();
        $campaign = $db->selectOne(
            "SELECT mc.*, wt.name AS template_name, wt.language, wt.body_text
             FROM marketing_campaigns mc
             LEFT JOIN whatsapp_templates wt ON wt.id = mc.whatsapp_template_id
             WHERE mc.id = ? LIMIT 1",
            [(int)$id]
        );

        if (!$campaign) {
            $this->jsonError('Campanha não encontrada.', [], 404);
        }
        if (!in_array($campaign['status'], ['draft', 'scheduled'], true)) {
            $this->jsonError('Esta campanha não pode ser enviada novamente.', [], 422);
        }

        $contacts = $db->select(
            "SELECT c.id, c.phone FROM contacts c
             INNER JOIN marketing_list_contacts mlc ON mlc.contact_id = c.id
             WHERE mlc.list_id = ? AND c.phone IS NOT NULL AND c.phone != ''",
            [(int)$campaign['list_id']]
        );

        if (empty($contacts)) {
            $this->jsonError('A lista não possui contatos com telefone.', [], 422);
        }

        $variables = !empty($campaign['variables'])
            ? json_decode($campaign['variables'], true) : [];

        $components = [];
        if (!empty($variables)) {
            $params = [];
            foreach ($variables as $val) {
                $params[] = ['type' => 'text', 'text' => (string)$val];
            }
            if ($params) {
                $components[] = ['type' => 'body', 'parameters' => $params];
            }
        }

        $service  = new MetaWhatsAppService();
        $userId   = (int)(Auth::user()['id'] ?? 0);
        $waSetting = $db->selectOne("SELECT id FROM whatsapp_settings ORDER BY id ASC LIMIT 1");
        $waSettingId = $waSetting ? (int)$waSetting['id'] : null;

        $bodyText = $campaign['body_text'] ?? '';
        if (!empty($variables)) {
            foreach ($variables as $idx => $val) {
                $bodyText = str_replace('{{' . (int)$idx . '}}', (string)$val, $bodyText);
            }
        }

        $now      = now();
        $sent     = 0;
        $failed   = 0;
        $errors   = [];

        $db->update(
            "UPDATE marketing_campaigns SET status = 'sending', started_at = ?, updated_at = ? WHERE id = ?",
            [$now, $now, (int)$id]
        );

        foreach ($contacts as $contact) {
            try {
                $apiResp = $service->sendTemplate(
                    $contact['phone'],
                    $campaign['template_name'],
                    $campaign['language'],
                    $components
                );

                $waMessageId = $apiResp['messages'][0]['id'] ?? null;

                // Find or create chat
                $chat = $db->selectOne(
                    "SELECT id FROM chats
                     WHERE contact_id = ? AND conversation_category = 'marketing'
                       AND status IN ('waiting','in_progress','bot')
                     ORDER BY created_at DESC LIMIT 1",
                    [(int)$contact['id']]
                );

                if (!$chat) {
                    $chatId = (int)$db->insert(
                        "INSERT INTO chats
                            (contact_id, whatsapp_setting_id, status, conversation_category,
                             last_message, last_message_at, unread_count, is_bot_active, created_at, updated_at)
                         VALUES (?, ?, 'in_progress', 'marketing', ?, ?, 0, 0, ?, ?)",
                        [(int)$contact['id'], $waSettingId, mb_substr($bodyText, 0, 255), $now, $now, $now]
                    );
                } else {
                    $chatId = (int)$chat['id'];
                    $db->update(
                        "UPDATE chats SET last_message = ?, last_message_at = ?, updated_at = ? WHERE id = ?",
                        [mb_substr($bodyText, 0, 255), $now, $now, $chatId]
                    );
                }

                $db->insert(
                    "INSERT INTO messages
                        (chat_id, user_id, whatsapp_message_id, direction, type, content, status, created_at, updated_at)
                     VALUES (?, ?, ?, 'outbound', 'template', ?, 'sent', ?, ?)",
                    [$chatId, $userId, $waMessageId, $bodyText, $now, $now]
                );

                $sent++;
            } catch (\Throwable $e) {
                $errors[] = "Contato {$contact['phone']}: " . $e->getMessage();
                $failed++;
            }
        }

        $finishedAt = now();
        $db->update(
            "UPDATE marketing_campaigns
             SET status = 'sent', finished_at = ?, sent_count = ?, failed_count = ?,
                 total_contacts = ?, updated_at = ?
             WHERE id = ?",
            [$finishedAt, $sent, $failed, count($contacts), $finishedAt, (int)$id]
        );

        $this->jsonSuccess(
            "Campanha enviada: {$sent} entregue(s), {$failed} falha(s).",
            ['sent' => $sent, 'failed' => $failed, 'errors' => array_slice($errors, 0, 10)]
        );
    }

    // ── POST /admin/marketing/campaigns/{id}/delete ──────────────────

    public function destroyCampaign(Request $request, string $id): void
    {
        $this->requireMarketing();

        $db = Database::getInstance();
        $campaign = $db->selectOne("SELECT status FROM marketing_campaigns WHERE id = ? LIMIT 1", [(int)$id]);

        if (!$campaign) {
            $this->jsonError('Campanha não encontrada.', [], 404);
        }
        if ($campaign['status'] === 'sending') {
            $this->jsonError('Não é possível excluir uma campanha em andamento.', [], 422);
        }

        $db->delete("DELETE FROM marketing_campaigns WHERE id = ?", [(int)$id]);

        $this->jsonSuccess('Campanha excluída.');
    }

    // ── POST /admin/marketing/contacts/search ────────────────────────

    public function searchContact(Request $request): void
    {
        $this->requireMarketing();

        $q = trim((string) $request->post('q', $request->get('q', '')));
        if (strlen($q) < 2) {
            $this->jsonSuccess('OK', ['contacts' => []]);
            return;
        }

        $result   = Contact::search($q, 1, 15);
        $contacts = array_map(fn($c) => [
            'id'    => $c['id'],
            'name'  => $c['name'],
            'phone' => $c['phone'],
        ], $result['data']);

        $this->jsonSuccess('OK', ['contacts' => $contacts]);
    }
}
