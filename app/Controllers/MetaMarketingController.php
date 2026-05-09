<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Auth;
use Core\Database;
use App\Services\MetaAdsService;
use App\Services\MetaAgentService;
use App\Services\ImageGenerationService;

class MetaMarketingController extends Controller
{
    private function requireMarketing(): void
    {
        if (!Auth::hasFeature('marketing')) { $this->redirect(url('admin/dashboard')); exit; }
    }

    // ── GET /admin/marketing/meta ────────────────────────────────────

    public function index(Request $request): string
    {
        $this->requireMarketing();

        $db        = Database::getInstance();
        $campaigns = $db->select(
            "SELECT * FROM meta_campaigns ORDER BY created_at DESC LIMIT 100"
        );
        $sessions  = $db->select(
            "SELECT id, title, status, created_at FROM meta_agent_sessions
             ORDER BY created_at DESC LIMIT 50"
        );

        return $this->view('marketing/meta/index', [
            'campaigns' => $campaigns,
            'sessions'  => $sessions,
        ]);
    }

    // ── GET /admin/marketing/meta/reports ───────────────────────────

    public function reports(Request $request): string
    {
        $this->requireMarketing();

        $db        = Database::getInstance();
        $campaigns = $db->select(
            "SELECT c.*, u.name as creator_name
             FROM meta_campaigns c
             LEFT JOIN users u ON u.id = c.created_by
             ORDER BY c.created_at DESC"
        );

        foreach ($campaigns as &$c) {
            $c['insights']       = !empty($c['insights'])       ? json_decode($c['insights'], true)       : null;
            $c['ad_copy']        = !empty($c['ad_copy'])        ? json_decode($c['ad_copy'], true)         : null;
            $c['target_audience']= !empty($c['target_audience'])? json_decode($c['target_audience'], true) : null;
            $c['platforms']      = !empty($c['platforms'])      ? json_decode($c['platforms'], true)       : [];
        }
        unset($c);

        return $this->view('marketing/meta/reports', ['campaigns' => $campaigns]);
    }

    // ── POST /admin/marketing/meta/agent/session ─────────────────────
    // Start a new agent session

    public function startSession(Request $request): void
    {
        $this->requireMarketing();

        $title  = trim((string) $request->post('title', 'Nova estratégia'));
        $db     = Database::getInstance();
        $userId = (int)(Auth::user()['id'] ?? 0);
        $ts     = now();

        $id = $db->insert(
            "INSERT INTO meta_agent_sessions (title, messages, status, created_by, created_at, updated_at)
             VALUES (?, ?, 'active', ?, ?, ?)",
            [$title, json_encode([]), $userId, $ts, $ts]
        );

        $this->jsonSuccess('Sessão iniciada.', ['session_id' => (int)$id, 'title' => $title]);
    }

    // ── POST /admin/marketing/meta/agent/{id}/chat ───────────────────
    // Send message to agent; returns response + proposed actions

    public function agentChat(Request $request, string $id): void
    {
        $this->requireMarketing();

        $db      = Database::getInstance();
        $session = $db->selectOne(
            "SELECT * FROM meta_agent_sessions WHERE id = ? LIMIT 1", [(int)$id]
        );

        if (!$session) { $this->jsonError('Sessão não encontrada.', [], 404); }
        if ($session['status'] !== 'active') { $this->jsonError('Sessão encerrada.', [], 422); }

        $userMsg  = trim((string) $request->post('message', ''));
        $briefRaw = $request->post('brief', []);

        if ($userMsg === '' && empty($briefRaw)) {
            $this->jsonError('Mensagem vazia.', [], 422);
        }

        $history = json_decode($session['messages'] ?? '[]', true) ?: [];

        // If a brief was submitted, build a structured prompt
        if (!empty($briefRaw) && $userMsg === '') {
            $agent   = new MetaAgentService();
            $userMsg = $agent->buildStrategyPrompt($briefRaw);
        }

        $agent    = new MetaAgentService();

        // Merge consecutive same-role messages before sending to Anthropic.
        // appendAgentMessage writes action results as 'user' role; if the user
        // then sends another message we'd have two consecutive 'user' entries
        // which the Anthropic API rejects. Merge them into one.
        $sanitizedHistory = [];
        foreach ($history as $msg) {
            $last = end($sanitizedHistory);
            if ($last && $last['role'] === $msg['role']) {
                $sanitizedHistory[count($sanitizedHistory) - 1]['content'] .= "\n\n" . $msg['content'];
            } else {
                $sanitizedHistory[] = $msg;
            }
        }

        $kbContext = $this->buildKnowledgeContext((int)$id);
        $response  = $agent->chat($sanitizedHistory, $userMsg, $kbContext);

        // Append to history (raw history, not sanitized — preserve all entries)
        $history[] = ['role' => 'user',      'content' => $userMsg];
        $history[] = ['role' => 'assistant', 'content' => $response['raw'] ?? $response['content'], 'actions' => $response['actions']];

        $db->update(
            "UPDATE meta_agent_sessions SET messages = ?, updated_at = ? WHERE id = ?",
            [json_encode($history), now(), (int)$id]
        );

        $this->jsonSuccess('OK', [
            'content' => $response['content'],
            'actions' => $response['actions'],
            'error'   => $response['error'] ?? false,
        ]);
    }

    // ── POST /admin/marketing/meta/agent/{id}/execute ────────────────
    // User approved an action — execute it via MetaAdsService

    public function executeAction(Request $request, string $id): void
    {
        $this->requireMarketing();

        $db       = Database::getInstance();
        $session  = $db->selectOne("SELECT * FROM meta_agent_sessions WHERE id = ? LIMIT 1", [(int)$id]);
        if (!$session) { $this->jsonError('Sessão não encontrada.', [], 404); }

        $type    = (string) $request->post('type', '');
        $dataRaw = $request->post('data', []);
        if (is_string($dataRaw)) {
            $dataRaw = json_decode($dataRaw, true) ?? [];
        }

        $service  = new MetaAdsService();
        $userId   = (int)(Auth::user()['id'] ?? 0);
        $result   = [];

        switch ($type) {
            case 'generate_image':
                $imgService = new ImageGenerationService();
                if (!$imgService->isConfigured()) {
                    $this->appendAgentMessage($db, (int)$id, '⚠️ OpenAI API Key não configurada. Vá em Administração → META → Geração de Imagens.');
                    $this->jsonError('OpenAI API Key não configurada.', [], 422);
                }
                $imgResult = $imgService->generate(
                    $dataRaw['prompt'] ?? '',
                    $dataRaw['size']   ?? '1024x1024'
                );
                if (isset($imgResult['error'])) {
                    $this->appendAgentMessage($db, (int)$id, '⚠️ Falha ao gerar imagem: ' . $imgResult['error']);
                    $this->jsonError($imgResult['error'], [], 422);
                }
                $imageUrls = $imgResult['images'] ?? [];
                $urlList   = implode("\n", $imageUrls);
                $this->appendAgentMessage($db, (int)$id,
                    "✅ Imagem gerada com sucesso! URL para usar no criativo:\n{$urlList}\n\n" .
                    "Agora posso criar o criativo do anúncio usando essa imagem. Deseja prosseguir?"
                );
                $this->jsonSuccess('Imagem gerada!', ['images' => $imageUrls]);
                return;

            case 'create_campaign':
                $result = $service->createCampaign($dataRaw);
                if (!isset($result['error']) && !empty($result['id'])) {
                    $this->saveCampaign($db, $dataRaw, $result['id'], null, null, $userId, (int)$id);
                }
                break;

            case 'create_adset':
                $result = $service->createAdSet($dataRaw);
                if (!isset($result['error']) && !empty($result['id'])) {
                    $this->updateCampaignAdset($db, $dataRaw, $result['id']);
                }
                break;

            case 'create_creative':
                $result = $service->createAdCreative($dataRaw);
                break;

            case 'create_ad':
                $result = $service->createAd($dataRaw);
                if (!isset($result['error']) && !empty($result['id'])) {
                    $this->updateCampaignAd($db, $dataRaw, $result['id']);
                }
                break;

            case 'activate_campaign':
                $metaId = $dataRaw['meta_campaign_id'] ?? $dataRaw['campaign_id'] ?? '';
                $result = $service->activateCampaign($metaId);
                if (!isset($result['error'])) {
                    $db->update(
                        "UPDATE meta_campaigns SET status='active', updated_at=? WHERE meta_campaign_id=?",
                        [now(), $metaId]
                    );
                }
                break;

            case 'pause_campaign':
                $metaId = $dataRaw['meta_campaign_id'] ?? $dataRaw['campaign_id'] ?? '';
                $result = $service->pauseCampaign($metaId);
                if (!isset($result['error'])) {
                    $db->update(
                        "UPDATE meta_campaigns SET status='paused', updated_at=? WHERE meta_campaign_id=?",
                        [now(), $metaId]
                    );
                }
                break;

            case 'fetch_insights':
                $metaId = $dataRaw['campaign_id'] ?? '';
                $result = $service->getCampaignInsights($metaId);
                if (!isset($result['error'])) {
                    $db->update(
                        "UPDATE meta_campaigns SET insights=?, insights_at=?, updated_at=? WHERE meta_campaign_id=?",
                        [json_encode($result['data'] ?? $result), now(), now(), $metaId]
                    );
                }
                break;

            case 'fetch_url':
                $url = trim((string)($dataRaw['url'] ?? ''));
                if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
                    $this->appendAgentMessage($db, (int)$id, '⚠️ URL inválida para fetch_url.');
                    $this->jsonError('URL inválida.', [], 422);
                }
                $fetched = $this->fetchUrlContent($url);
                if (isset($fetched['error'])) {
                    $this->appendAgentMessage($db, (int)$id, '⚠️ Falha ao buscar URL: ' . $fetched['error']);
                    $this->jsonError($fetched['error'], [], 422);
                }
                $this->appendAgentMessage($db, (int)$id,
                    "🌐 Conteúdo extraído de {$url}:\n\n" .
                    "Título: " . ($fetched['title'] ?? '(sem título)') . "\n\n" .
                    "Conteúdo (até 4000 chars):\n" . mb_substr($fetched['text'] ?? '', 0, 4000)
                );
                logger('MetaAgent: fetch_url ok — url=' . $url . ' chars=' . mb_strlen($fetched['text'] ?? ''));
                $this->jsonSuccess('Página extraída com sucesso!', ['title' => $fetched['title'] ?? '', 'chars' => mb_strlen($fetched['text'] ?? '')]);
                return;

            default:
                $this->jsonError("Tipo de ação desconhecido: {$type}", [], 422);
        }

        if (isset($result['error'])) {
            $errMsg = $result['error']['message'] ?? 'Erro na API Meta.';
            // Append error feedback to session history
            $this->appendAgentMessage($db, (int)$id, "⚠️ A ação `{$type}` falhou: {$errMsg}");
            $this->jsonError($errMsg);
        }

        // Append success feedback to session history
        $successMsg = "✅ Ação `{$type}` executada com sucesso. ID Meta: " . ($result['id'] ?? 'N/A');
        $this->appendAgentMessage($db, (int)$id, $successMsg);

        $this->jsonSuccess('Ação executada com sucesso!', ['meta_result' => $result]);
    }

    // ── GET /admin/marketing/meta/agent/{id} ─────────────────────────

    public function getSession(Request $request, string $id): void
    {
        $this->requireMarketing();

        $session = Database::getInstance()->selectOne(
            "SELECT * FROM meta_agent_sessions WHERE id = ? LIMIT 1", [(int)$id]
        );

        if (!$session) { $this->jsonError('Sessão não encontrada.', [], 404); }

        $session['messages'] = json_decode($session['messages'] ?? '[]', true) ?: [];

        $this->jsonSuccess('OK', ['session' => $session]);
    }

    // ── POST /admin/marketing/meta/campaigns/{id}/insights ───────────

    public function refreshInsights(Request $request, string $id): void
    {
        $this->requireMarketing();

        $db       = Database::getInstance();
        $campaign = $db->selectOne("SELECT * FROM meta_campaigns WHERE id = ? LIMIT 1", [(int)$id]);

        if (!$campaign || empty($campaign['meta_campaign_id'])) {
            $this->jsonError('Campanha não encontrada ou sem ID Meta.', [], 404);
        }

        $service  = new MetaAdsService();
        $insights = $service->getCampaignInsights($campaign['meta_campaign_id']);

        if (isset($insights['error'])) {
            $this->jsonError($insights['error']['message'] ?? 'Erro ao buscar insights.', [], 422);
        }

        $data = $insights['data'][0] ?? $insights;
        $db->update(
            "UPDATE meta_campaigns SET insights=?, insights_at=?, updated_at=? WHERE id=?",
            [json_encode($data), now(), now(), (int)$id]
        );

        $this->jsonSuccess('Insights atualizados.', ['insights' => $data]);
    }

    // ── Private helpers ───────────────────────────────────────────────

    private function saveCampaign(
        $db, array $data, string $metaCampaignId,
        ?string $metaAdsetId, ?string $metaAdId,
        int $userId, int $sessionId
    ): void {
        $ts = now();
        $db->insert(
            "INSERT INTO meta_campaigns
                (name, objective, platforms, status, budget_type, budget_amount,
                 start_date, end_date, target_audience, ad_copy, strategy_brief,
                 meta_campaign_id, meta_adset_id, meta_ad_id, created_by, created_at, updated_at)
             VALUES (?, ?, ?, 'active', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['name']        ?? 'Campanha Meta',
                $data['objective']   ?? 'OUTCOME_TRAFFIC',
                json_encode($data['platforms'] ?? ['facebook', 'instagram']),
                $data['budget_type'] ?? 'daily',
                $data['budget']      ?? null,
                $data['start_date']  ?? null,
                $data['end_date']    ?? null,
                isset($data['targeting']) ? json_encode($data['targeting']) : null,
                isset($data['ad_copy'])   ? json_encode($data['ad_copy'])   : null,
                $data['strategy']    ?? null,
                $metaCampaignId,
                $metaAdsetId,
                $metaAdId,
                $userId,
                $ts,
                $ts,
            ]
        );
    }

    private function updateCampaignAdset($db, array $data, string $metaAdsetId): void
    {
        $campaignMetaId = $data['campaign_id'] ?? '';
        if ($campaignMetaId) {
            $db->update(
                "UPDATE meta_campaigns SET meta_adset_id=?, updated_at=? WHERE meta_campaign_id=?",
                [$metaAdsetId, now(), $campaignMetaId]
            );
        }
    }

    private function updateCampaignAd($db, array $data, string $metaAdId): void
    {
        $adsetMetaId = $data['adset_id'] ?? '';
        if ($adsetMetaId) {
            $db->update(
                "UPDATE meta_campaigns SET meta_ad_id=?, status='active', updated_at=? WHERE meta_adset_id=?",
                [$metaAdId, now(), $adsetMetaId]
            );
        }
    }

    // ── Knowledge Base ──────────────────────────────────────────────

    public function listKnowledge(Request $request, string $id): void
    {
        $this->requireMarketing();
        $items = Database::getInstance()->select(
            "SELECT id, kind, label, source, mime_type, size_bytes, created_at,
                    CHAR_LENGTH(content) AS content_chars
             FROM meta_agent_knowledge WHERE session_id = ? ORDER BY id DESC",
            [(int)$id]
        );
        $this->jsonSuccess('OK', ['items' => $items]);
    }

    public function addKnowledgeUrl(Request $request, string $id): void
    {
        $this->requireMarketing();
        $url = trim((string)$request->post('url', ''));
        if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
            $this->jsonError('URL inválida.', [], 422);
        }
        $fetched = $this->fetchUrlContent($url);
        if (isset($fetched['error'])) {
            $this->jsonError('Falha ao buscar URL: ' . $fetched['error'], [], 422);
        }
        $db = Database::getInstance();
        $db->insert(
            "INSERT INTO meta_agent_knowledge (session_id, kind, label, source, content, created_at)
             VALUES (?, 'url', ?, ?, ?, ?)",
            [(int)$id, $fetched['title'] ?: parse_url($url, PHP_URL_HOST), $url, $fetched['text'] ?? '', now()]
        );
        $this->jsonSuccess('Página adicionada à base de conhecimento.');
    }

    public function addKnowledgeDocument(Request $request, string $id): void
    {
        $this->requireMarketing();
        $file = $_FILES['file'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->jsonError('Arquivo inválido.', [], 422);
        }

        $allowedExt = ['pdf', 'docx', 'md', 'txt', 'csv'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            $this->jsonError('Formato não suportado. Use: ' . implode(', ', $allowedExt), [], 422);
        }
        if (($file['size'] ?? 0) > 15 * 1024 * 1024) {
            $this->jsonError('Arquivo maior que 15 MB.', [], 422);
        }

        $uploadDir = ROOT_PATH . '/public/uploads/meta-knowledge/' . (int)$id;
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $safeName  = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
        $stored    = $uploadDir . '/' . uniqid() . '_' . $safeName . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $stored)) {
            $this->jsonError('Falha ao salvar arquivo.', [], 500);
        }

        $text = $this->extractDocumentText($stored, $ext);
        if (isset($text['error'])) {
            @unlink($stored);
            $this->jsonError($text['error'], [], 422);
        }

        $db = Database::getInstance();
        $db->insert(
            "INSERT INTO meta_agent_knowledge
                (session_id, kind, label, source, file_path, mime_type, size_bytes, content, created_at)
             VALUES (?, 'document', ?, ?, ?, ?, ?, ?, ?)",
            [(int)$id, $file['name'], $file['name'], $stored, $file['type'] ?? '', (int)$file['size'], $text['content'] ?? '', now()]
        );
        $this->jsonSuccess('Documento adicionado à base de conhecimento.');
    }

    public function deleteKnowledge(Request $request, string $id, string $kid): void
    {
        $this->requireMarketing();
        $db = Database::getInstance();
        $row = $db->selectOne("SELECT * FROM meta_agent_knowledge WHERE id=? AND session_id=?", [(int)$kid, (int)$id]);
        if (!$row) $this->jsonError('Item não encontrado.', [], 404);
        if (!empty($row['file_path']) && file_exists($row['file_path'])) @unlink($row['file_path']);
        $db->delete("DELETE FROM meta_agent_knowledge WHERE id=?", [(int)$kid]);
        $this->jsonSuccess('Item removido.');
    }

    private function extractDocumentText(string $path, string $ext): array
    {
        switch ($ext) {
            case 'txt':
            case 'md':
            case 'csv':
                $content = (string) @file_get_contents($path);
                return ['content' => mb_convert_encoding($content, 'UTF-8', 'auto')];

            case 'pdf':
                $bin = trim((string) @shell_exec('which pdftotext 2>/dev/null'));
                if ($bin === '') return ['error' => 'pdftotext não disponível no servidor. Instale poppler-utils ou converta o PDF para TXT/MD.'];
                $out = @shell_exec('pdftotext -layout ' . escapeshellarg($path) . ' - 2>/dev/null');
                if ($out === null || $out === '') return ['error' => 'Não foi possível extrair texto do PDF.'];
                return ['content' => $out];

            case 'docx':
                $zip = new \ZipArchive();
                if ($zip->open($path) !== true) return ['error' => 'DOCX inválido.'];
                $xml = $zip->getFromName('word/document.xml');
                $zip->close();
                if (!$xml) return ['error' => 'Não foi possível ler o documento.'];
                $text = strip_tags(str_replace(['</w:p>', '</w:tab>'], ["\n", "\t"], $xml));
                $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
                return ['content' => trim(preg_replace('/\s+\n/', "\n", $text))];
        }
        return ['error' => 'Formato não suportado.'];
    }

    private function buildKnowledgeContext(int $sessionId): string
    {
        $rows = Database::getInstance()->select(
            "SELECT kind, label, source, content FROM meta_agent_knowledge WHERE session_id = ? ORDER BY id ASC",
            [$sessionId]
        );
        if (empty($rows)) return '';

        $parts = ["════════════════════════════════════════", "BASE DE CONHECIMENTO DESTA CAMPANHA:", "════════════════════════════════════════"];
        foreach ($rows as $r) {
            $title = $r['label'] ?: ($r['source'] ?: ($r['kind'] === 'url' ? 'URL' : 'Documento'));
            $body  = mb_substr((string)$r['content'], 0, 6000); // cap per item
            $parts[] = "--- " . ($r['kind'] === 'url' ? '🌐 ' : '📄 ') . $title . " ---";
            if ($r['kind'] === 'url') $parts[] = "Fonte: " . $r['source'];
            $parts[] = $body;
            $parts[] = "";
        }
        return implode("\n", $parts);
    }

    private function fetchUrlContent(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; NowFlowMetaAgent/1.0)',
            CURLOPT_HTTPHEADER     => ['Accept: text/html,application/xhtml+xml'],
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err)               return ['error' => "cURL: {$err}"];
        if ($code >= 400)       return ['error' => "HTTP {$code}"];
        if (empty($body))       return ['error' => 'Resposta vazia.'];

        // Extract title
        $title = '';
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $body, $m)) {
            $title = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        // Strip script/style and convert to text
        $text = preg_replace('#<(script|style|noscript|iframe)[^>]*>.*?</\1>#is', ' ', $body);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return ['title' => $title, 'text' => $text];
    }

    private function appendAgentMessage($db, int $sessionId, string $content): void
    {
        $session  = $db->selectOne("SELECT messages FROM meta_agent_sessions WHERE id=? LIMIT 1", [$sessionId]);
        $history  = json_decode($session['messages'] ?? '[]', true) ?: [];
        // Must be 'user' role so Anthropic alternation rule is respected (assistant→user→assistant)
        $history[] = ['role' => 'user', 'content' => "[Sistema]: {$content}", 'actions' => [], 'system' => true];
        $db->update("UPDATE meta_agent_sessions SET messages=?, updated_at=? WHERE id=?",
            [json_encode($history), now(), $sessionId]);
    }
}
