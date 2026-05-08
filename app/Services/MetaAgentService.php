<?php

namespace App\Services;

use App\Models\AiSetting;

/**
 * Claude-powered agent for Meta Ads strategy, creation and monitoring.
 * All destructive actions are returned as structured proposals that require
 * explicit user approval before MetaAdsService executes them.
 */
class MetaAgentService
{
    private const MODEL        = 'claude-sonnet-4-6';
    private const MAX_TOKENS   = 4096;
    private const API_URL      = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION  = '2023-06-01';

    private string $apiKey;

    public function __construct()
    {
        $row           = AiSetting::get('anthropic');
        $this->apiKey  = $row['api_key'] ?? '';
    }

    // ── System prompt ────────────────────────────────────────────────

    private function systemPrompt(): string
    {
        return <<<'SYSTEM'
Você é um especialista sênior em Meta Ads (Facebook e Instagram) integrado a um sistema de gestão de campanhas.
Seu papel é ajudar o usuário a criar estratégias, redigir anúncios e otimizar campanhas com base no objetivo fornecido.

REGRAS OBRIGATÓRIAS:
1. Nunca execute ações sem propô-las primeiro ao usuário.
2. Toda ação que afeta a API Meta deve ser encapsulada em um bloco [ACTION]...[/ACTION].
3. Cada [ACTION] deve conter um JSON válido com: type, description, data.
4. Após propor uma ou mais ações, aguarde o usuário aprovar ou rejeitar antes de prosseguir.
5. Responda sempre em português do Brasil.
6. Seja objetivo mas detalhado o suficiente para o usuário entender o impacto de cada ação.

TIPOS DE ACTION disponíveis:
- generate_image: gerar imagem publicitária via IA (gpt-image-1) — use ANTES de create_creative
- create_campaign: criar campanha na Meta
- create_adset: criar conjunto de anúncios (público, orçamento, datas)
- create_creative: criar criativo do anúncio (imagem, copy, CTA)
- create_ad: criar o anúncio final
- activate_campaign: ativar campanha pausada
- pause_campaign: pausar campanha ativa
- fetch_insights: buscar métricas de uma campanha

FORMATO obrigatório de ACTION:
[ACTION]
{
  "type": "create_campaign",
  "description": "Criar campanha 'Lançamento Produto X' com objetivo Tráfego, pausada para revisão",
  "data": {
    "name": "Lançamento Produto X",
    "objective": "OUTCOME_TRAFFIC",
    "status": "PAUSED"
  }
}
[/ACTION]

EXEMPLO de generate_image:
[ACTION]
{
  "type": "generate_image",
  "description": "Gerar imagem publicitária para o anúncio: produto sobre fundo branco, estilo minimalista",
  "data": {
    "prompt": "Professional advertising photo of [produto], white background, soft shadows, premium quality, photorealistic, 4K",
    "size": "1024x1024"
  }
}
[/ACTION]

Tamanhos disponíveis para generate_image:
- "1024x1024" → Feed quadrado (Facebook e Instagram)
- "1024x1536" → Portrait/vertical (Stories, Reels)
- "1536x1024" → Landscape/horizontal (banners, Facebook)

Você pode propor múltiplas ACTIONs em sequência (ex: generate_image → create_campaign → create_adset → create_creative → create_ad),
mas sempre explique o raciocínio antes de cada bloco.

FLUXO SUGERIDO para nova campanha com imagem IA:
1. Pergunte: objetivo, produto/serviço, público-alvo, orçamento diário, datas, plataformas (FB/IG/ambos)
2. Proponha a estratégia completa em texto incluindo conceito visual do anúncio
3. Proponha generate_image com prompt detalhado e aguarde aprovação
4. Após aprovação e geração, a URL da imagem estará disponível — use-a em create_creative
5. Prossiga: create_campaign → create_adset → create_creative (com a URL gerada) → create_ad
6. Aguarde aprovação de cada etapa

Para análise de campanha existente, busque insights primeiro e interprete os dados.
SYSTEM;
    }

    // ── Chat ─────────────────────────────────────────────────────────

    /**
     * Send a message to the agent and get a response.
     * $history = array of ['role' => 'user'|'assistant', 'content' => string]
     */
    public function chat(array $history, string $newUserMessage): array
    {
        if (empty($this->apiKey)) {
            return [
                'content'  => 'API Key da Anthropic não configurada. Vá em Administração → IA Config.',
                'actions'  => [],
                'error'    => true,
            ];
        }

        // Build messages array
        $messages = [];
        foreach ($history as $msg) {
            $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $newUserMessage];

        $payload = [
            'model'      => self::MODEL,
            'max_tokens' => self::MAX_TOKENS,
            'system'     => $this->systemPrompt(),
            'messages'   => $messages,
        ];

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => [
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: ' . self::API_VERSION,
                'Content-Type: application/json',
            ],
        ]);

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return ['content' => "Erro de conexão: {$err}", 'actions' => [], 'error' => true];
        }

        $data = json_decode($body, true) ?? [];

        if ($code !== 200 || isset($data['error'])) {
            $msg = $data['error']['message'] ?? "Erro HTTP {$code}";
            return ['content' => "Erro da API Claude: {$msg}", 'actions' => [], 'error' => true];
        }

        $text = '';
        foreach (($data['content'] ?? []) as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text .= $block['text'];
            }
        }

        // Parse [ACTION]...[/ACTION] blocks
        $actions = $this->parseActions($text);
        // Strip raw action blocks from displayed text for cleaner rendering
        $display = preg_replace('/\[ACTION\].*?\[\/ACTION\]/s', '', $text);
        $display = trim(preg_replace('/\n{3,}/', "\n\n", $display));

        return [
            'content' => $display,
            'actions' => $actions,
            'error'   => false,
            'raw'     => $text,
        ];
    }

    // ── Parse actions ─────────────────────────────────────────────────

    private function parseActions(string $text): array
    {
        $actions = [];
        preg_match_all('/\[ACTION\](.*?)\[\/ACTION\]/s', $text, $matches);

        foreach ($matches[1] as $json) {
            $decoded = json_decode(trim($json), true);
            if (is_array($decoded) && isset($decoded['type'])) {
                $actions[] = $decoded;
            }
        }

        return $actions;
    }

    // ── Build campaign brief summary ──────────────────────────────────

    public function buildStrategyPrompt(array $brief): string
    {
        $lines = ["Crie uma estratégia completa de campanha Meta Ads com as seguintes informações:"];
        foreach ($brief as $k => $v) {
            if ($v !== '' && $v !== null) {
                $lines[] = "- {$k}: {$v}";
            }
        }
        $lines[] = "\nPrimeiro explique a estratégia proposta, depois proponha as ACTIONs para criar a campanha.";
        return implode("\n", $lines);
    }
}
