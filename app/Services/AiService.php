<?php

namespace App\Services;

class AiService
{
    /**
     * Catálogo de modelos sugeridos por provider.
     * O usuário pode digitar um modelo customizado se não estiver na lista.
     */
    public const MODELS = [
        'openai' => [
            'gpt-5'         => 'GPT-5',
            'gpt-5-mini'    => 'GPT-5 mini',
            'gpt-5-nano'    => 'GPT-5 nano',
            'gpt-4.1'       => 'GPT-4.1',
            'gpt-4.1-mini'  => 'GPT-4.1 mini',
            'gpt-4.1-nano'  => 'GPT-4.1 nano',
            'o4-mini'       => 'o4 mini',
            'o3'            => 'o3',
            'o3-mini'       => 'o3 mini',
        ],
        'anthropic' => [
            'claude-opus-4-7'    => 'Claude Opus 4.7',
            'claude-sonnet-4-6'  => 'Claude Sonnet 4.6',
            'claude-haiku-4-5'   => 'Claude Haiku 4.5',
            'claude-opus-4-5'    => 'Claude Opus 4.5',
            'claude-sonnet-4-5'  => 'Claude Sonnet 4.5',
        ],
    ];

    public const LABELS = [
        'openai'    => 'OpenAI',
        'anthropic' => 'Anthropic Claude',
    ];

    /**
     * Estilos de resposta selecionáveis na Persona.
     * Cada item: ['label' => string, 'desc' => string, 'instruction' => string]
     * `instruction` é injetada no system prompt.
     */
    public const STYLES = [
        'profissional' => [
            'label'       => 'Profissional',
            'desc'        => 'tom equilibrado, cordial e objetivo (padrão)',
            'instruction' => 'Use um tom profissional, equilibrado, cordial e objetivo. Evite gírias.',
        ],
        'amigavel' => [
            'label'       => 'Amigável',
            'desc'        => 'próximo, caloroso, usa "você" de forma relaxada',
            'instruction' => 'Use um tom amigável e caloroso, tratando o cliente por "você" de forma natural. Seja acolhedor sem perder a clareza.',
        ],
        'divertido' => [
            'label'       => 'Divertido',
            'desc'        => 'leve, bem-humorado, com emojis ocasionais',
            'instruction' => 'Use um tom leve e bem-humorado, com emojis ocasionais (não exagere). Mantenha a clareza da informação.',
        ],
        'empatico' => [
            'label'       => 'Empático',
            'desc'        => 'acolhedor, valida sentimentos antes de responder',
            'instruction' => 'Adote um tom empático e acolhedor. Valide os sentimentos do cliente antes de oferecer a solução.',
        ],
        'direto' => [
            'label'       => 'Direto',
            'desc'        => 'curto e ao ponto, sem rodeios',
            'instruction' => 'Seja direto e objetivo. Respostas curtas, sem rodeios nem cumprimentos longos.',
        ],
        'formal' => [
            'label'       => 'Formal',
            'desc'        => 'linguagem culta, tratamento cerimonioso',
            'instruction' => 'Use linguagem culta, tratamento cerimonioso ("o senhor"/"a senhora"). Mantenha postura formal.',
        ],
    ];

    /**
     * Testa a conexão chamando o endpoint /models de cada provedor.
     * Retorna ['ok' => bool, 'message' => string, 'data' => array]
     */
    public function testConnection(string $provider, string $apiKey, string $model = ''): array
    {
        if (trim($apiKey) === '') {
            return ['ok' => false, 'message' => 'API key vazia.', 'data' => []];
        }

        return match ($provider) {
            'openai'    => $this->testOpenAi($apiKey, $model),
            'anthropic' => $this->testAnthropic($apiKey, $model),
            default     => ['ok' => false, 'message' => 'Provider não suportado.', 'data' => []],
        };
    }

    private function testOpenAi(string $apiKey, string $model): array
    {
        $res = $this->httpRequest('GET', 'https://api.openai.com/v1/models', [
            "Authorization: Bearer {$apiKey}",
        ]);

        if ($res['code'] !== 200) {
            $msg = $this->extractError($res['body']) ?: "OpenAI retornou HTTP {$res['code']}";
            return ['ok' => false, 'message' => $msg, 'data' => []];
        }

        $body   = json_decode($res['body'], true) ?: [];
        $models = array_column($body['data'] ?? [], 'id');

        // Se o usuário escolheu um modelo, valida se está disponível
        if ($model !== '' && !in_array($model, $models, true)) {
            return [
                'ok'      => false,
                'message' => "Conexão OK, mas o modelo \"{$model}\" não está disponível para esta chave.",
                'data'    => ['models_count' => count($models)],
            ];
        }

        return [
            'ok'      => true,
            'message' => 'Conexão com OpenAI estabelecida com sucesso.',
            'data'    => ['models_count' => count($models)],
        ];
    }

    private function testAnthropic(string $apiKey, string $model): array
    {
        // A API da Anthropic não tem /models público estável — fazemos uma chamada
        // mínima ao /v1/messages com 1 token para validar a chave + o modelo.
        $payload = [
            'model'      => $model !== '' ? $model : 'claude-haiku-4-5',
            'max_tokens' => 1,
            'messages'   => [['role' => 'user', 'content' => 'ping']],
        ];

        $res = $this->httpRequest(
            'POST',
            'https://api.anthropic.com/v1/messages',
            [
                "x-api-key: {$apiKey}",
                'anthropic-version: 2023-06-01',
                'Content-Type: application/json',
            ],
            json_encode($payload)
        );

        if ($res['code'] === 200) {
            return [
                'ok'      => true,
                'message' => 'Conexão com Anthropic Claude estabelecida com sucesso.',
                'data'    => [],
            ];
        }

        $msg = $this->extractError($res['body']) ?: "Anthropic retornou HTTP {$res['code']}";
        return ['ok' => false, 'message' => $msg, 'data' => []];
    }

    private function httpRequest(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $resBody = curl_exec($ch);
        $code    = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err     = curl_error($ch);
        curl_close($ch);

        if ($resBody === false) {
            return ['code' => 0, 'body' => $err ?: 'Falha na conexão.'];
        }

        return ['code' => $code, 'body' => (string)$resBody];
    }

    private function extractError(string $body): ?string
    {
        $data = json_decode($body, true);
        if (!is_array($data)) {
            return null;
        }
        return $data['error']['message']
            ?? $data['error']['type']
            ?? $data['message']
            ?? null;
    }

    // ─────────────────────────────────────────────────────────────
    //  Chat completion + knowledge base context
    // ─────────────────────────────────────────────────────────────

    /**
     * Send a user message to the active AI provider with the knowledge
     * base as system context. Returns the assistant's reply text.
     *
     * @throws \RuntimeException when no provider is configured/active
     */
    public function ask(string $userMessage, ?string $systemContext = null): string
    {
        $row = \Core\Database::getInstance()->selectOne(
            "SELECT * FROM ai_settings WHERE is_active=1 ORDER BY id ASC LIMIT 1"
        );

        if (!$row) {
            throw new \RuntimeException('Nenhum provider de IA está ativo. Configure em Configurações → IA.');
        }
        if (empty($row['api_key']) || empty($row['model'])) {
            throw new \RuntimeException('API key ou modelo não configurado para o provider ativo.');
        }

        $system = $systemContext ?? $this->buildKnowledgeContext();

        return match ($row['provider']) {
            'openai'    => $this->chatOpenAi($row['api_key'], $row['model'], $system, $userMessage),
            'anthropic' => $this->chatAnthropic($row['api_key'], $row['model'], $system, $userMessage),
            default     => throw new \RuntimeException('Provider desconhecido: ' . $row['provider']),
        };
    }

    /**
     * Build the system context (Persona + Q&A + Docs + Sites).
     * Truncated to ~80k chars to stay within typical context windows.
     */
    public function buildKnowledgeContext(): string
    {
        $db    = \Core\Database::getInstance();
        $parts = [];

        // ── Persona ──
        $persona = $db->selectOne("SELECT prompt, style FROM ai_persona ORDER BY id ASC LIMIT 1");
        if ($persona && !empty($persona['prompt'])) {
            $parts[] = trim((string)$persona['prompt']);
        } else {
            $parts[] = 'Você é um assistente de atendimento. Responda de forma clara e objetiva.';
        }

        // ── Estilo de resposta ──
        $styleKey = $persona['style'] ?? 'profissional';
        $style    = self::STYLES[$styleKey] ?? self::STYLES['profissional'];
        $parts[]  = '## Estilo de resposta' . "\n" . $style['instruction'];

        // ── Q&A ──
        $qa = $db->select("SELECT question, answer FROM ai_knowledge_qa WHERE is_active=1 ORDER BY id ASC");
        if ($qa) {
            $faq = "## Perguntas frequentes (FAQ)\n";
            foreach ($qa as $r) {
                $faq .= "- P: " . trim((string)$r['question']) . "\n";
                $faq .= "  R: " . trim((string)$r['answer']) . "\n";
            }
            $parts[] = $faq;
        }

        // ── Documents (extracted text) ──
        $docs = $db->select(
            "SELECT original_name, extracted_text FROM ai_knowledge_docs
             WHERE is_active=1 AND extracted_text IS NOT NULL AND extracted_text<>''
             ORDER BY id ASC"
        );
        if ($docs) {
            $bloc = "## Documentos da empresa\n";
            foreach ($docs as $d) {
                $bloc .= "\n### {$d['original_name']}\n" . trim((string)$d['extracted_text']) . "\n";
            }
            $parts[] = $bloc;
        }

        // ── Sites ──
        $sites = $db->select(
            "SELECT url, title, content FROM ai_knowledge_sites
             WHERE is_active=1 AND content IS NOT NULL AND content<>''
             ORDER BY id ASC"
        );
        if ($sites) {
            $bloc = "## Conteúdo dos sites\n";
            foreach ($sites as $s) {
                $head = $s['title'] ? "{$s['title']} — {$s['url']}" : $s['url'];
                $bloc .= "\n### {$head}\n" . trim((string)$s['content']) . "\n";
            }
            $parts[] = $bloc;
        }

        $ctx = implode("\n\n", $parts);

        // Cap at ~80k chars (roughly 20k tokens) to be safe
        if (strlen($ctx) > 80000) {
            $ctx = substr($ctx, 0, 80000) . "\n\n[…contexto truncado…]";
        }

        return $ctx;
    }

    private function chatOpenAi(string $apiKey, string $model, string $system, string $user): string
    {
        $payload = [
            'model'    => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user',   'content' => $user],
            ],
        ];

        $res = $this->httpRequest(
            'POST',
            'https://api.openai.com/v1/chat/completions',
            [
                "Authorization: Bearer {$apiKey}",
                'Content-Type: application/json',
            ],
            json_encode($payload)
        );

        if ($res['code'] !== 200) {
            throw new \RuntimeException(
                $this->extractError($res['body']) ?: "OpenAI HTTP {$res['code']}"
            );
        }

        $data = json_decode($res['body'], true) ?: [];
        return (string)($data['choices'][0]['message']['content'] ?? '');
    }

    private function chatAnthropic(string $apiKey, string $model, string $system, string $user): string
    {
        $payload = [
            'model'      => $model,
            'max_tokens' => 2048,
            'system'     => $system,
            'messages'   => [['role' => 'user', 'content' => $user]],
        ];

        $res = $this->httpRequest(
            'POST',
            'https://api.anthropic.com/v1/messages',
            [
                "x-api-key: {$apiKey}",
                'anthropic-version: 2023-06-01',
                'Content-Type: application/json',
            ],
            json_encode($payload)
        );

        if ($res['code'] !== 200) {
            throw new \RuntimeException(
                $this->extractError($res['body']) ?: "Anthropic HTTP {$res['code']}"
            );
        }

        $data = json_decode($res['body'], true) ?: [];
        $out  = '';
        foreach (($data['content'] ?? []) as $block) {
            if (($block['type'] ?? '') === 'text') {
                $out .= $block['text'] ?? '';
            }
        }
        return $out;
    }

    // ─────────────────────────────────────────────────────────────
    //  Text extractors (used when uploading docs / adding URLs)
    // ─────────────────────────────────────────────────────────────

    public static function extractDocumentText(string $filePath, string $originalName): string
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        try {
            return match ($ext) {
                'md', 'markdown', 'txt' => self::readPlainFile($filePath),
                'docx'                  => self::readDocxFile($filePath),
                'pdf', 'doc'            => '[Conteúdo do arquivo ' . $originalName . ' não pôde ser extraído automaticamente. Considere convertê-lo para MD ou DOCX.]',
                default                 => '',
            };
        } catch (\Throwable $e) {
            return '';
        }
    }

    private static function readPlainFile(string $path): string
    {
        $text = @file_get_contents($path) ?: '';
        if (strlen($text) > 200000) {
            $text = substr($text, 0, 200000) . "\n[…truncado…]";
        }
        return $text;
    }

    private static function readDocxFile(string $path): string
    {
        if (!class_exists('ZipArchive')) {
            return '';
        }
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return '';
        }
        $xml = $zip->getFromName('word/document.xml') ?: '';
        $zip->close();

        if ($xml === '') return '';

        // Convert paragraph breaks before stripping tags
        $xml  = preg_replace('/<\/w:p>/', "\n", $xml);
        $xml  = preg_replace('/<w:tab[^>]*>/', "\t", $xml);
        $text = strip_tags($xml);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $text = preg_replace("/[ \t]+/", ' ', $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim((string)$text);
    }

    public static function fetchSiteContent(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 NowFlowBot/1.0',
            CURLOPT_HTTPHEADER     => ['Accept: text/html,application/xhtml+xml'],
        ]);
        $html = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 || !is_string($html) || $html === '') return '';

        // Strip script/style first
        $html = preg_replace('#<script\b[^>]*>.*?</script>#si', ' ', $html);
        $html = preg_replace('#<style\b[^>]*>.*?</style>#si', ' ', $html);
        $html = preg_replace('#<noscript\b[^>]*>.*?</noscript>#si', ' ', $html);

        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace("/[ \t]+/", ' ', $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        $text = trim((string)$text);

        if (strlen($text) > 100000) {
            $text = substr($text, 0, 100000) . "\n[…truncado…]";
        }
        return $text;
    }
}
