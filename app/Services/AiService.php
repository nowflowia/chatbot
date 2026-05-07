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
            'gpt-4o'           => 'GPT-4o',
            'gpt-4o-mini'      => 'GPT-4o mini',
            'gpt-4-turbo'      => 'GPT-4 Turbo',
            'gpt-4'            => 'GPT-4',
            'gpt-3.5-turbo'    => 'GPT-3.5 Turbo',
            'o1'               => 'o1',
            'o1-mini'          => 'o1 mini',
        ],
        'anthropic' => [
            'claude-opus-4-5'        => 'Claude Opus 4.5',
            'claude-sonnet-4-5'      => 'Claude Sonnet 4.5',
            'claude-haiku-4-5'       => 'Claude Haiku 4.5',
            'claude-3-5-sonnet-latest' => 'Claude 3.5 Sonnet',
            'claude-3-5-haiku-latest'  => 'Claude 3.5 Haiku',
        ],
    ];

    public const LABELS = [
        'openai'    => 'OpenAI',
        'anthropic' => 'Anthropic Claude',
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
}
