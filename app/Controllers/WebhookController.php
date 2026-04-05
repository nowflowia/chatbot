<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use App\Models\WhatsappSetting;
use App\Models\WebhookLog;
use App\Services\MetaWhatsAppService;
use App\Services\FlowEngine;

class WebhookController extends Controller
{
    /**
     * GET /webhook — Meta webhook verification challenge.
     */
    public function verify(Request $request): void
    {
        $settings = WhatsappSetting::getActive();

        if (!$settings) {
            http_response_code(403);
            echo 'Webhook not configured.';
            exit;
        }

        $service   = new MetaWhatsAppService($settings);
        // PHP converts dots to underscores in $_GET keys: hub.mode -> hub_mode
        $challenge = $service->verifyWebhook([
            'hub_mode'         => $request->get('hub_mode',         ''),
            'hub_verify_token' => $request->get('hub_verify_token', ''),
            'hub_challenge'    => $request->get('hub_challenge',    ''),
        ]);

        if ($challenge !== false) {
            http_response_code(200);
            header('Content-Type: text/plain');
            echo $challenge;
            exit;
        }

        http_response_code(403);
        echo 'Forbidden';
        exit;
    }

    /**
     * POST /webhook — Receive events from Meta.
     */
    public function receive(Request $request): void
    {
        // ── Signature validation ──────────────────────────────────────
        $settings = WhatsappSetting::getActive();
        $appSecret = $settings['app_secret'] ?? '';

        if ($appSecret) {
            $rawBody   = file_get_contents('php://input');
            $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
            $expected  = 'sha256=' . hash_hmac('sha256', $rawBody, $appSecret);

            if (!hash_equals($expected, $signature)) {
                http_response_code(403);
                echo json_encode(['error' => 'Invalid signature']);
                exit;
            }
        }

        // Acknowledge immediately (Meta requires fast response)
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok']);

        // Process asynchronously (flush and continue)
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        $payload = $request->jsonBody();

        // Log raw event
        $this->logWebhookEvent($request, $payload);

        if (empty($payload)) {
            return;
        }

        if (!$settings) {
            return;
        }

        $service = new MetaWhatsAppService($settings);
        $events  = $service->parseWebhookPayload($payload);

        foreach ($events as $event) {
            try {
                $this->processEvent($event, $settings);
            } catch (\Throwable $e) {
                logger('Webhook event processing error: ' . $e->getMessage(), 'error');
            }
        }
    }

    /**
     * Process a single parsed event.
     */
    private function processEvent(array $event, array $settings): void
    {
        if ($event['type'] === 'status') {
            // Update message delivery/read status
            $this->handleStatusUpdate($event);
            return;
        }

        if ($event['type'] === 'message') {
            $this->handleIncomingMessage($event, $settings);
        }
    }

    /**
     * Handle incoming WhatsApp message.
     * Full logic implemented in ETAPA 11 — here we store the raw data.
     */
    private function handleIncomingMessage(array $event, array $settings): void
    {
        $db = \Core\Database::getInstance();

        $phone    = $event['from']         ?? '';
        $msgId    = $event['message_id']   ?? '';
        $msgType  = $event['message_type'] ?? 'text';
        $text     = $event['text']         ?? null;
        $name     = $event['profile_name'] ?? '';

        if (empty($phone)) return;

        // Upsert contact
        $contact = $db->selectOne("SELECT * FROM contacts WHERE phone = ? LIMIT 1", [$phone]);
        if (!$contact) {
            $contactId = $db->insert(
                "INSERT INTO contacts (phone, name, whatsapp_id, status, created_at, updated_at) VALUES (?, ?, ?, 'active', NOW(), NOW())",
                [$phone, $name ?: $phone, $phone]
            );
        } else {
            $contactId = $contact['id'];
            if ($name && (!$contact['name'] || $contact['name'] === $phone)) {
                $db->update("UPDATE contacts SET name = ?, updated_at = NOW() WHERE id = ?", [$name, $contactId]);
            }
        }

        // Find or create open chat
        $chat = $db->selectOne(
            "SELECT * FROM chats WHERE contact_id = ? AND status IN ('waiting','in_progress','bot') ORDER BY id DESC LIMIT 1",
            [$contactId]
        );

        if (!$chat) {
            $chatId = $db->insert(
                "INSERT INTO chats (contact_id, whatsapp_setting_id, status, is_bot_active, last_message_at, created_at, updated_at)
                 VALUES (?, ?, 'waiting', 1, NOW(), NOW(), NOW())",
                [$contactId, $settings['id']]
            );
        } else {
            $chatId = $chat['id'];
        }

        // Check duplicate message
        if ($msgId) {
            $exists = $db->selectOne("SELECT id FROM messages WHERE whatsapp_message_id = ? LIMIT 1", [$msgId]);
            if ($exists) return;
        }

        // Extract media info
        $mediaId       = null;
        $mediaUrl      = null;
        $mediaMime     = null;
        $mediaFilename = null;
        $mediaSize     = null;

        switch ($msgType) {
            case 'image':
                $mediaId   = $event['image']['id']       ?? null;
                $mediaMime = $event['image']['mime_type'] ?? 'image/jpeg';
                $content   = $event['image']['caption']  ?? '';
                break;
            case 'audio':
                $mediaId   = $event['audio']['id']       ?? null;
                $mediaMime = $event['audio']['mime_type'] ?? 'audio/ogg';
                $content   = '';
                break;
            case 'video':
                $mediaId   = $event['video']['id']       ?? null;
                $mediaMime = $event['video']['mime_type'] ?? 'video/mp4';
                $content   = $event['video']['caption']  ?? '';
                // GIFs chegam como video com animated=true
                if (!empty($event['video']['animated'])) {
                    $msgType = 'gif';
                }
                break;
            case 'document':
                $mediaId       = $event['document']['id']        ?? null;
                $mediaMime     = $event['document']['mime_type']  ?? 'application/octet-stream';
                $mediaFilename = $event['document']['filename']   ?? null;
                $content       = $mediaFilename ?? '[Documento]';
                break;
            case 'sticker':
                $mediaId   = $event['sticker']['id']       ?? null;
                $mediaMime = $event['sticker']['mime_type'] ?? 'image/webp';
                $content   = '[Figurinha]';
                break;
            case 'location':
                $lat     = $event['location']['latitude']  ?? '';
                $lng     = $event['location']['longitude'] ?? '';
                $content = '[Localização] ' . $lat . ',' . $lng;
                break;
            case 'text':
                $content = $text;
                break;
            case 'button':
                $content = $event['button']['text'] ?? '[Botão]';
                break;
            case 'interactive':
                $content = $event['interactive']['button_reply']['title']
                        ?? $event['interactive']['list_reply']['title']
                        ?? '[Interativo]';
                break;
            default:
                $content = '[' . strtoupper($msgType) . ']';
                break;
        }

        // Resolve media URL from Meta media ID
        if ($mediaId && $settings) {
            try {
                $service  = new MetaWhatsAppService($settings);
                $mediaInfo = $service->getMediaUrl($mediaId);
                if (!empty($mediaInfo['url'])) {
                    $mediaUrl  = $mediaInfo['url'];
                    $mediaSize = $mediaInfo['file_size'] ?? null;
                }
            } catch (\Throwable $e) {
                logger('Media URL resolve error: ' . $e->getMessage(), 'error');
            }
        }

        $db->insert(
            "INSERT INTO messages
             (chat_id, contact_id, whatsapp_message_id, direction, type, content, media_url, media_mime, media_filename, media_size, status, raw_payload, created_at, updated_at)
             VALUES (?, ?, ?, 'inbound', ?, ?, ?, ?, ?, ?, 'delivered', ?, NOW(), NOW())",
            [$chatId, $contactId, $msgId, $msgType, $content, $mediaUrl, $mediaMime, $mediaFilename, $mediaSize, json_encode($event['raw'])]
        );

        // Update chat
        $db->update(
            "UPDATE chats SET last_message = ?, last_message_at = NOW(), unread_count = unread_count + 1, updated_at = NOW() WHERE id = ?",
            [mb_substr($content ?? '', 0, 200), $chatId]
        );

        // Update webhook log as processed
        $db->update(
            "UPDATE webhook_logs SET status = 'processed', processed_at = NOW() WHERE message_id = ? AND status = 'received'",
            [$msgId]
        );

        logger("Incoming message from {$phone}: {$content}", 'info');

        // ── Flow engine ───────────────────────────────────────────────
        // Accept text, button reply, interactive list reply
        $engineText = null;
        if ($msgType === 'text' && $text) {
            $engineText = $text;
        } elseif (in_array($msgType, ['button', 'interactive'], true)) {
            $engineText = $event['button']['text']
                ?? $event['interactive']['button_reply']['title']
                ?? $event['interactive']['list_reply']['title']
                ?? null;
        }

        if ($engineText !== null) {
            try {
                $freshChat = $db->selectOne("SELECT * FROM chats WHERE id = ? LIMIT 1", [(int)$chatId]);
                if ($freshChat && $freshChat['is_bot_active']) {
                    $engine = new FlowEngine($settings);
                    $engine->processMessage($freshChat, $engineText);
                }
            } catch (\Throwable $e) {
                logger('FlowEngine error: ' . $e->getMessage(), 'error');
            }
        }
    }

    /**
     * Handle message status update (sent/delivered/read/failed).
     */
    private function handleStatusUpdate(array $event): void
    {
        $msgId  = $event['message_id'] ?? '';
        $status = $event['status']     ?? '';

        if (empty($msgId) || empty($status)) return;

        $statusMap = [
            'sent'      => 'sent',
            'delivered' => 'delivered',
            'read'      => 'read',
            'failed'    => 'failed',
        ];

        $dbStatus = $statusMap[$status] ?? null;
        if (!$dbStatus) return;

        $db     = \Core\Database::getInstance();
        $update = "UPDATE messages SET status = ?, updated_at = NOW()";
        $params = [$dbStatus];

        if ($status === 'delivered') { $update .= ', delivered_at = NOW()'; }
        if ($status === 'read')      { $update .= ', read_at = NOW()'; }

        $update .= ' WHERE whatsapp_message_id = ?';
        $params[] = $msgId;

        $db->update($update, $params);
    }

    /**
     * Log the raw webhook payload.
     */
    private function logWebhookEvent(Request $request, array $payload): void
    {
        try {
            $company = \App\Models\CompanySetting::get();
            if (isset($company['webhook_logging']) && !(bool)$company['webhook_logging']) {
                return; // logging disabled
            }
            $db = \Core\Database::getInstance();
            $db->insert(
                "INSERT INTO webhook_logs
                 (event_type, phone_number_id, direction, status, payload, ip_address, created_at, updated_at)
                 VALUES (?, ?, 'inbound', 'received', ?, ?, NOW(), NOW())",
                [
                    $payload['object'] ?? 'unknown',
                    $payload['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'] ?? null,
                    json_encode($payload),
                    $request->ip(),
                ]
            );
        } catch (\Throwable $e) {
            logger('Webhook log error: ' . $e->getMessage(), 'error');
        }
    }
}
