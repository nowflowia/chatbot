<?php

namespace App\Services;

use App\Models\WhatsappSetting;

class MetaWhatsAppService
{
    private string $baseUrl    = 'https://graph.facebook.com';
    private string $apiVersion;
    private string $accessToken;
    private string $phoneNumberId;
    private array  $settings;

    public function __construct(?array $settings = null)
    {
        $this->settings      = $settings ?? WhatsappSetting::getActive() ?? [];
        $this->apiVersion    = $this->settings['api_version']    ?? 'v25.0';
        $this->accessToken   = $this->settings['access_token']   ?? '';
        $this->phoneNumberId = $this->settings['phone_number_id'] ?? '';
    }

    // ----------------------------------------------------------------
    // Send Messages
    // ----------------------------------------------------------------

    /**
     * Send a plain text message.
     */
    public function sendText(string $to, string $message): array
    {
        return $this->request('POST', "/{$this->phoneNumberId}/messages", [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->normalizePhone($to),
            'type'              => 'text',
            'text'              => [
                'preview_url' => false,
                'body'        => $message,
            ],
        ]);
    }

    /**
     * Send an image message with optional caption.
     */
    public function sendImage(string $to, string $imageUrl, string $caption = ''): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->normalizePhone($to),
            'type'              => 'image',
            'image'             => ['link' => $imageUrl],
        ];
        if ($caption) $payload['image']['caption'] = $caption;
        return $this->request('POST', "/{$this->phoneNumberId}/messages", $payload);
    }

    /**
     * Send a document message.
     */
    public function sendDocument(string $to, string $docUrl, string $filename = '', string $caption = ''): array
    {
        $doc = ['link' => $docUrl];
        if ($filename) $doc['filename'] = $filename;
        if ($caption)  $doc['caption']  = $caption;
        return $this->request('POST', "/{$this->phoneNumberId}/messages", [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->normalizePhone($to),
            'type'              => 'document',
            'document'          => $doc,
        ]);
    }

    /**
     * Send an audio message.
     */
    public function sendAudio(string $to, string $audioUrl): array
    {
        return $this->request('POST', "/{$this->phoneNumberId}/messages", [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->normalizePhone($to),
            'type'              => 'audio',
            'audio'             => ['link' => $audioUrl],
        ]);
    }

    /**
     * Send an interactive button message.
     */
    public function sendButtons(string $to, string $bodyText, array $buttons, string $headerText = ''): array
    {
        $btns = array_map(fn($b, $i) => [
            'type'  => 'reply',
            'reply' => ['id' => $b['id'] ?? "btn_{$i}", 'title' => substr($b['title'] ?? '', 0, 20)],
        ], $buttons, array_keys($buttons));

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->normalizePhone($to),
            'type'              => 'interactive',
            'interactive'       => [
                'type' => 'button',
                'body' => ['text' => $bodyText],
                'action' => ['buttons' => array_slice($btns, 0, 3)],
            ],
        ];
        if ($headerText) {
            $payload['interactive']['header'] = ['type' => 'text', 'text' => $headerText];
        }
        return $this->request('POST', "/{$this->phoneNumberId}/messages", $payload);
    }

    /**
     * Send an interactive list message.
     */
    public function sendList(string $to, string $bodyText, string $buttonLabel, array $sections): array
    {
        return $this->request('POST', "/{$this->phoneNumberId}/messages", [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->normalizePhone($to),
            'type'              => 'interactive',
            'interactive'       => [
                'type'   => 'list',
                'body'   => ['text' => $bodyText],
                'action' => [
                    'button'   => substr($buttonLabel, 0, 20),
                    'sections' => $sections,
                ],
            ],
        ]);
    }

    /**
     * Mark a message as read.
     */
    public function markAsRead(string $messageId): array
    {
        return $this->request('POST', "/{$this->phoneNumberId}/messages", [
            'messaging_product' => 'whatsapp',
            'status'            => 'read',
            'message_id'        => $messageId,
        ]);
    }

    /**
     * Send a template message.
     */
    public function sendTemplate(string $to, string $templateName, string $languageCode = 'pt_BR', array $components = []): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->normalizePhone($to),
            'type'              => 'template',
            'template'          => [
                'name'     => $templateName,
                'language' => ['code' => $languageCode],
            ],
        ];
        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }
        return $this->request('POST', "/{$this->phoneNumberId}/messages", $payload);
    }

    // ----------------------------------------------------------------
    // Utility / Info
    // ----------------------------------------------------------------

    /**
     * Resolve a media ID to a downloadable URL.
     * Returns ['url' => '...', 'mime_type' => '...', 'file_size' => N]
     */
    public function getMediaUrl(string $mediaId): array
    {
        return $this->request('GET', "/{$mediaId}?fields=url,mime_type,file_size,sha256");
    }

    /**
     * Get phone number info — used to test connection.
     */
    public function getPhoneNumberInfo(): array
    {
        return $this->request('GET', "/{$this->phoneNumberId}?fields=id,display_phone_number,verified_name,quality_rating,platform_type,status");
    }

    /**
     * Get Business Account info.
     */
    public function getBusinessAccountInfo(): array
    {
        $waba = $this->settings['business_account_id'] ?? '';
        return $this->request('GET', "/{$waba}?fields=id,name,timezone_id,message_template_namespace");
    }

    /**
     * List message templates.
     */
    public function getTemplates(): array
    {
        $waba = $this->settings['business_account_id'] ?? '';
        return $this->request('GET', "/{$waba}/message_templates?fields=name,status,language,category&limit=50");
    }

    /**
     * Test the connection — returns ['ok' => bool, 'message' => string, 'data' => array].
     */
    public function testConnection(): array
    {
        if (empty($this->accessToken)) {
            return ['ok' => false, 'message' => 'Access Token não configurado.', 'data' => []];
        }
        if (empty($this->phoneNumberId)) {
            return ['ok' => false, 'message' => 'Phone Number ID não configurado.', 'data' => []];
        }

        $result = $this->getPhoneNumberInfo();

        if (isset($result['error'])) {
            $msg = $result['error']['message'] ?? 'Erro desconhecido da API Meta.';
            return ['ok' => false, 'message' => $msg, 'data' => $result];
        }

        if (isset($result['id'])) {
            $phone   = $result['display_phone_number'] ?? '';
            $name    = $result['verified_name']        ?? '';
            $quality = $result['quality_rating']       ?? '';
            return [
                'ok'      => true,
                'message' => "Conectado! Número: {$phone} ({$name}) — Qualidade: {$quality}",
                'data'    => $result,
            ];
        }

        return ['ok' => false, 'message' => 'Resposta inesperada da API Meta.', 'data' => $result];
    }

    // ----------------------------------------------------------------
    // Webhook
    // ----------------------------------------------------------------

    /**
     * Validate webhook verification request from Meta.
     */
    public function verifyWebhook(array $params): string|false
    {
        $mode      = $params['hub_mode']        ?? '';
        $token     = $params['hub_verify_token'] ?? '';
        $challenge = $params['hub_challenge']    ?? '';

        $expectedToken = $this->settings['verify_token'] ?? '';

        if ($mode === 'subscribe' && hash_equals($expectedToken, $token)) {
            return $challenge;
        }
        return false;
    }

    /**
     * Parse an incoming webhook payload into a normalized array.
     */
    public function parseWebhookPayload(array $payload): array
    {
        $results = [];

        $entries = $payload['entry'] ?? [];
        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];
            foreach ($changes as $change) {
                $value = $change['value'] ?? [];

                // Messages
                $messages = $value['messages'] ?? [];
                foreach ($messages as $msg) {
                    $results[] = [
                        'type'             => 'message',
                        'phone_number_id'  => $value['metadata']['phone_number_id'] ?? '',
                        'from'             => $msg['from'] ?? '',
                        'message_id'       => $msg['id']   ?? '',
                        'timestamp'        => $msg['timestamp'] ?? '',
                        'message_type'     => $msg['type'] ?? 'text',
                        'text'             => $msg['text']['body'] ?? null,
                        'image'            => $msg['image']    ?? null,
                        'audio'            => $msg['audio']    ?? null,
                        'document'         => $msg['document'] ?? null,
                        'video'            => $msg['video']    ?? null,
                        'sticker'          => $msg['sticker']  ?? null,
                        'location'         => $msg['location'] ?? null,
                        'interactive'      => $msg['interactive'] ?? null,
                        'button'           => $msg['button'] ?? null,
                        'contacts_data'    => $msg['contacts'] ?? null,
                        'context'          => $msg['context']  ?? null,
                        'profile_name'     => $value['contacts'][0]['profile']['name'] ?? '',
                        'raw'              => $msg,
                    ];
                }

                // Status updates
                $statuses = $value['statuses'] ?? [];
                foreach ($statuses as $status) {
                    $results[] = [
                        'type'        => 'status',
                        'message_id'  => $status['id']        ?? '',
                        'status'      => $status['status']    ?? '',
                        'recipient'   => $status['recipient_phone_number'] ?? '',
                        'timestamp'   => $status['timestamp'] ?? '',
                        'raw'         => $status,
                    ];
                }
            }
        }

        return $results;
    }

    // ----------------------------------------------------------------
    // HTTP Client
    // ----------------------------------------------------------------

    private function request(string $method, string $endpoint, array $payload = []): array
    {
        $url = $this->baseUrl . '/' . $this->apiVersion . $endpoint;

        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            logger("Meta API cURL error: {$error}", 'error');
            return ['error' => ['message' => "cURL error: {$error}", 'code' => 0]];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            logger("Meta API invalid response (HTTP {$httpCode}): {$response}", 'error');
            return ['error' => ['message' => "Invalid API response (HTTP {$httpCode})", 'code' => $httpCode]];
        }

        if ($httpCode >= 400 && isset($decoded['error'])) {
            logger("Meta API error (HTTP {$httpCode}): " . json_encode($decoded['error']), 'error');
        }

        return $decoded;
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    /**
     * Normalize phone to E.164 format (digits only, with country code).
     */
    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        // Add Brazil code if missing
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = '55' . substr($digits, 1);
        } elseif (strlen($digits) === 11 || strlen($digits) === 10) {
            $digits = '55' . $digits;
        }
        return $digits;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }
}
