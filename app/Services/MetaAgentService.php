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
Seu papel é ajudar o usuário a criar estratégias, redigir anúncios e otimizar campanhas.

════════════════════════════════════════
REGRAS ABSOLUTAS — NUNCA VIOLE:
════════════════════════════════════════
1. JAMAIS descreva em texto o que "vai fazer" ou "está fazendo". EXECUTE via bloco [ACTION].
2. JAMAIS escreva tabelas, listas ou status de progresso de ações — use [ACTION] diretamente.
3. JAMAIS diga "Vou gerar", "Estou gerando", "Processando" — emita o [ACTION] imediatamente.
4. Toda ação que afeta API ou gera conteúdo DEVE ser um bloco [ACTION]...[/ACTION].
5. Após propor ações, PARE e aguarde o usuário aprovar. Não continue até receber aprovação.
6. Responda sempre em português do Brasil.
7. Cada [ACTION] deve conter JSON válido com: type, description, data.

════════════════════════════════════════
TIPOS DE ACTION disponíveis:
════════════════════════════════════════
- generate_image    → gerar imagem via IA (obrigatório antes de create_creative)
- create_campaign   → criar campanha na Meta
- create_adset      → criar conjunto de anúncios (público, orçamento, datas)
- create_creative   → criar criativo (imagem + copy + CTA)
- create_ad         → criar o anúncio final
- activate_campaign → ativar campanha pausada
- pause_campaign    → pausar campanha ativa
- fetch_insights    → buscar métricas de campanha

════════════════════════════════════════
FORMATO OBRIGATÓRIO de cada ACTION:
════════════════════════════════════════
[ACTION]
{
  "type": "TIPO_AQUI",
  "description": "Descrição curta e clara para o usuário aprovar",
  "data": { ... }
}
[/ACTION]

════════════════════════════════════════
EXEMPLO — 3 imagens em formatos diferentes:
════════════════════════════════════════
Vou propor 3 artes para você aprovar:

[ACTION]
{
  "type": "generate_image",
  "description": "Arte 1/3 — Feed Quadrado 1024x1024: produto em fundo branco, estilo minimalista",
  "data": {
    "prompt": "Professional advertising photo, white background, soft shadows, premium quality, photorealistic, 4K",
    "size": "1024x1024"
  }
}
[/ACTION]

[ACTION]
{
  "type": "generate_image",
  "description": "Arte 2/3 — Stories Vertical 1024x1536: composição vertical com produto em destaque",
  "data": {
    "prompt": "Vertical advertising banner, product hero shot, clean background, bold colors, premium feel, 4K",
    "size": "1024x1536"
  }
}
[/ACTION]

[ACTION]
{
  "type": "generate_image",
  "description": "Arte 3/3 — Banner Landscape 1536x1024: banner horizontal para Facebook",
  "data": {
    "prompt": "Wide advertising banner, product showcase, horizontal composition, professional lighting, 4K",
    "size": "1536x1024"
  }
}
[/ACTION]

Aprove cada arte acima para gerar as imagens.

════════════════════════════════════════
TAMANHOS para generate_image:
════════════════════════════════════════
- "1024x1024" → Feed quadrado (Facebook e Instagram)
- "1024x1536" → Portrait/vertical (Stories, Reels)
- "1536x1024" → Landscape/horizontal (banners, Facebook)

════════════════════════════════════════
FLUXO para nova campanha:
════════════════════════════════════════
1. Pergunte: objetivo, produto/serviço, público-alvo, orçamento, datas, plataformas
2. Descreva a estratégia e conceito visual em texto
3. Emita os blocos [ACTION] de generate_image — aguarde aprovação de cada um
4. Após geração, use a URL retornada em create_creative
5. Prossiga: create_campaign → create_adset → create_creative → create_ad
6. Aguarde aprovação a cada etapa

Para análise de campanha existente, use fetch_insights primeiro.
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

        $actionCount = count($actions);
        $hasBlocks   = preg_match('/\[ACTION\]/i', $text) ? 'sim' : 'NÃO';
        logger("MetaAgent: resposta recebida — chars=" . strlen($text) . " tem_[ACTION]={$hasBlocks} actions_parseadas={$actionCount}");
        if ($actionCount === 0 && $hasBlocks === 'sim') {
            // Blocks found in text but JSON parsing failed — log a snippet for diagnosis
            logger('MetaAgent: [ACTION] encontrado mas JSON inválido — trecho: ' . mb_substr($text, 0, 400), 'error');
        }
        foreach ($actions as $i => $a) {
            logger('MetaAgent: action[' . $i . '] type=' . ($a['type'] ?? '?') . ' desc=' . mb_substr($a['description'] ?? '', 0, 80));
        }

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

        foreach ($matches[1] as $i => $json) {
            $trimmed = trim($json);
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded) && isset($decoded['type'])) {
                $actions[] = $decoded;
            } else {
                logger('MetaAgent: parseActions bloco[' . $i . '] JSON inválido (err=' . json_last_error_msg() . ') — raw=' . mb_substr($trimmed, 0, 200), 'error');
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
