<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Auth;
use App\Models\MetaAdSetting;
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
        return $this->view('meta_admin/index', [
            'settings' => MetaAdSetting::getActive() ?? [],
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
