<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Auth;
use App\Models\MetaAdSetting;
use App\Models\AiSetting;
use App\Services\MetaAdsService;

class MetaAdminController extends Controller
{
    private function requireAdmin(): void
    {
        if (!Auth::isAdmin()) { $this->redirect(url('admin/dashboard')); exit; }
    }

    // ── GET /admin/meta ──────────────────────────────────────────────

    public function index(Request $request): string
    {
        $this->requireAdmin();
        $ai     = AiSetting::get('anthropic');
        $openai = AiSetting::get('openai');
        return $this->view('meta_admin/index', [
            'settings'       => MetaAdSetting::getActive() ?? [],
            'aiConfigured'   => !empty($ai['api_key']),
            'openaiKey'      => !empty($openai['api_key']) ? substr($openai['api_key'], 0, 7) . '••••••••••••' : '',
            'openaiOk'       => !empty($openai['api_key']),
            'openaiModel'    => $openai['model'] ?? 'gpt-image-1',
            'aiModels'       => \App\Services\MetaAgentService::MODELS,
            'imageModels'    => \App\Services\ImageGenerationService::MODELS,
        ]);
    }

    // ── POST /admin/meta/settings ────────────────────────────────────

    public function saveSettings(Request $request): void
    {
        $this->requireAdmin();

        MetaAdSetting::saveSettings([
            'app_id'             => trim((string) $request->post('app_id', '')),
            'app_secret'         => trim((string) $request->post('app_secret', '')),
            'access_token'       => trim((string) $request->post('access_token', '')),
            'ad_account_id'      => trim((string) $request->post('ad_account_id', '')),
            'business_id'        => trim((string) $request->post('business_id', '')),
            'page_id'            => trim((string) $request->post('page_id', '')),
            'instagram_actor_id' => trim((string) $request->post('instagram_actor_id', '')),
            'api_version'        => trim((string) $request->post('api_version', 'v21.0')),
        ]);

        $this->jsonSuccess('Configurações salvas!');
    }

    // ── POST /admin/meta/ai-model ────────────────────────────────────

    public function saveAiModel(Request $request): void
    {
        $this->requireAdmin();
        $model = trim((string) $request->post('ai_model', 'claude-sonnet-4-6'));
        if (!array_key_exists($model, \App\Services\MetaAgentService::MODELS)) {
            $this->jsonError('Modelo inválido.', [], 422);
        }
        $existing = MetaAdSetting::getActive();
        if ($existing) {
            MetaAdSetting::saveSettings(array_merge($existing, ['ai_model' => $model]));
        }
        $this->jsonSuccess('Modelo salvo: ' . \App\Services\MetaAgentService::MODELS[$model]);
    }

    // ── POST /admin/meta/persona ────────────────────────────────────

    public function savePersona(Request $request): void
    {
        $this->requireAdmin();
        $persona  = (string) $request->post('agent_persona', '');
        $existing = MetaAdSetting::getActive();
        if ($existing) {
            MetaAdSetting::saveSettings(array_merge($existing, ['agent_persona' => $persona]));
        }
        $this->jsonSuccess($persona === '' ? 'Persona removida.' : 'Persona salva!');
    }

    // ── POST /admin/meta/openai-key ──────────────────────────────

    public function saveOpenAiKey(Request $request): void
    {
        $this->requireAdmin();
        $key   = trim((string) $request->post('api_key', ''));
        $model = trim((string) $request->post('image_model', 'gpt-image-1'));

        if (!empty($key) && str_starts_with($key, 'sk-ant-')) {
            $this->jsonError('Chave inválida: essa é uma chave Anthropic (sk-ant-...). Informe a chave OpenAI, obtida em platform.openai.com → API keys.', [], 422);
        }

        if (!array_key_exists($model, \App\Services\ImageGenerationService::MODELS)) {
            $model = 'gpt-image-1';
        }
        AiSetting::save('openai', ['api_key' => $key, 'model' => $model, 'is_active' => 1]);
        $this->jsonSuccess('Configurações de imagem salvas!');
    }

    // ── GET /admin/meta/logs ─────────────────────────────────────────

    public function logs(Request $request): void
    {
        $this->requireAdmin();
        $date    = $request->get('date', date('Y-m-d'));
        $logFile = storage_path('logs/' . basename($date) . '.log');
        $lines   = [];
        if (file_exists($logFile)) {
            $all   = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            $lines = array_slice(array_reverse($all), 0, 200);
        }
        $this->jsonSuccess('OK', ['lines' => $lines, 'date' => $date]);
    }

    // ── POST /admin/meta/logs/clear ──────────────────────────────────

    public function clearLogs(Request $request): void
    {
        $this->requireAdmin();
        $date    = $request->post('date', date('Y-m-d'));
        $logFile = storage_path('logs/' . basename($date) . '.log');
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }
        $this->jsonSuccess('Log limpo.');
    }

    // ── POST /admin/meta/test ────────────────────────────────────────

    public function testConnection(Request $request): void
    {
        $this->requireAdmin();

        $service = new MetaAdsService();
        $result  = $service->testConnection();

        $s = MetaAdSetting::getActive();
        if ($s) {
            MetaAdSetting::updateStatus(
                (int) $s['id'],
                $result['ok'] ? 'active' : 'error',
                $result['ok'] ? null : $result['message']
            );
        }

        if ($result['ok']) {
            $this->jsonSuccess($result['message'], ['data' => $result['data'] ?? []]);
        } else {
            $this->jsonError($result['message'], [], 422);
        }
    }
}
