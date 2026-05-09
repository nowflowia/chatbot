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
    private const DEFAULT_MODEL = 'claude-sonnet-4-6';
    private const MAX_TOKENS    = 4096;
    private const API_URL       = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION   = '2023-06-01';

    public const MODELS = [
        'claude-opus-4-7'           => 'Claude Opus 4.7 — Mais inteligente',
        'claude-sonnet-4-6'         => 'Claude Sonnet 4.6 — Recomendado',
        'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5 — Mais rápido',
    ];

    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $row           = AiSetting::get('anthropic');
        $this->apiKey  = $row['api_key'] ?? '';

        $metaSettings  = \App\Models\MetaAdSetting::getActive();
        $this->model   = $metaSettings['ai_model'] ?? self::DEFAULT_MODEL;
        if (!array_key_exists($this->model, self::MODELS)) {
            $this->model = self::DEFAULT_MODEL;
        }
    }

    // ── System prompt ────────────────────────────────────────────────

    private function buildSystem(): string
    {
        $base    = $this->systemPrompt();
        $persona = trim((string)(\App\Models\MetaAdSetting::getActive()['agent_persona'] ?? ''));
        if ($persona === '') return $base;
        return $base . "\n\n════════════════════════════════════════\nPERSONA / INSTRUÇÕES ADICIONAIS DO CLIENTE:\n════════════════════════════════════════\n" . $persona;
    }

    private function systemPrompt(): string
    {
        return <<<'SYSTEM'
Você é um especialista sênior em Meta Ads (Facebook e Instagram) integrado a um sistema de gestão de campanhas.
Seu único papel é ajudar o usuário a criar estratégias de anúncios, redigir copies, gerar imagens publicitárias e criar campanhas na Meta.

════════════════════════════════════════
ESCOPO — O QUE VOCÊ FAZ:
════════════════════════════════════════
✅ Criar e otimizar campanhas de anúncios no Facebook e Instagram
✅ Propor estratégias de público-alvo, orçamento e objetivos
✅ Redigir copies publicitários (headlines, textos, CTAs)
✅ Gerar imagens para anúncios via IA
✅ Analisar métricas e insights de campanhas existentes
✅ Configurar conjuntos de anúncios e criativos

════════════════════════════════════════
FORA DO ESCOPO — O QUE VOCÊ NÃO FAZ:
════════════════════════════════════════
❌ NÃO tente diagnosticar ou resolver problemas técnicos do sistema
❌ NÃO monitore logs, erros de API ou status de servidores
❌ NÃO tente depurar integrações, chaves de API ou configurações técnicas
❌ NÃO sugira verificar logs, consoles ou dashboards técnicos
❌ NÃO saia do tema de campanhas e publicidade — redirecione o usuário para o suporte técnico se necessário

Se o usuário mencionar problemas técnicos (erros, logs, falhas de API, etc.), responda apenas:
"Isso é um problema técnico fora do meu escopo. Entre em contato com o suporte técnico para resolver essa questão. Posso ajudá-lo com estratégias e criação de campanhas Meta Ads!"

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
REGRAS DE TARGETING (público-alvo):
════════════════════════════════════════
✅ USE: geo_locations (countries, regions, cities), age_min, age_max, genders (1=masc, 2=fem), publisher_platforms
❌ NÃO USE: interests, behaviors, detailed_targeting com strings de texto.
   Esses campos exigem IDs numéricos do taxonomy do Facebook que você NÃO tem acesso.
   Se quiser segmentar por interesse, descreva no nome/copy do anúncio, não no targeting.

EXEMPLO targeting VÁLIDO:
{
  "geo_locations": { "countries": ["BR"] },
  "age_min": 25,
  "age_max": 55,
  "genders": [1, 2],
  "publisher_platforms": ["facebook", "instagram"]
}

════════════════════════════════════════
USO DE IDs RETORNADOS POR AÇÕES ANTERIORES:
════════════════════════════════════════
Após uma ação ser executada, o sistema responde com "[Sistema]: ✅ Ação ... ID Meta: 123456789".
Esse ID DEVE ser usado nas próximas ações:
- create_adset → "campaign_id": "<ID retornado por create_campaign>"
- create_ad    → "adset_id": "<ID retornado por create_adset>", "creative_id": "<ID retornado por create_creative>"
NÃO emita create_adset antes de receber confirmação do create_campaign anterior.

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
- fetch_url         → ler o conteúdo de uma página web (landing page do cliente, site do concorrente, artigo, etc.)
                      Útil para entender produto, tom de voz, público, ofertas. data: { "url": "https://..." }
                      Após o sistema retornar o conteúdo, use-o para refinar copy, headline, targeting e estratégia.

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
    public function chat(array $history, string $newUserMessage, string $extraContext = ''): array
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
            'model'      => $this->model,
            'max_tokens' => self::MAX_TOKENS,
            'system'     => $this->buildSystem() . ($extraContext !== '' ? "\n\n" . $extraContext : ''),
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
            // Strip markdown code fences the AI sometimes wraps around JSON
            $trimmed = preg_replace('/^```(?:json)?\s*/i', '', $trimmed);
            $trimmed = preg_replace('/\s*```$/', '', $trimmed);
            $trimmed = trim($trimmed);
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
