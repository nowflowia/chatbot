<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Auth;
use App\Models\InstagramSetting;
use App\Services\InstagramService;

class InstagramAdminController extends Controller
{
    private function requireAdmin(): void
    {
        if (!Auth::isAdmin()) {
            $this->redirect(url('admin/dashboard'));
            exit;
        }
    }

    // ── GET /admin/instagram ─────────────────────────────────────────

    public function index(Request $request): string
    {
        $this->requireAdmin();

        $settings   = InstagramSetting::getActive() ?? [];
        $oauthError = \Core\Session::getFlash('ig_oauth_error');
        $oauthOk    = \Core\Session::getFlash('ig_oauth_ok');

        return $this->view('instagram_admin/index', [
            'settings'   => $settings,
            'oauthError' => $oauthError,
            'oauthOk'    => $oauthOk,
        ]);
    }

    // ── POST /admin/instagram/settings ───────────────────────────────

    public function saveSettings(Request $request): void
    {
        $this->requireAdmin();

        $data = [
            'app_id'               => trim((string) $request->post('app_id', '')),
            'app_secret'           => trim((string) $request->post('app_secret', '')),
            'access_token'         => trim((string) $request->post('access_token', '')),
            'instagram_account_id' => trim((string) $request->post('instagram_account_id', '')),
            'page_id'              => trim((string) $request->post('page_id', '')),
            'webhook_verify_token' => trim((string) $request->post('webhook_verify_token', '')),
            'api_version'          => trim((string) $request->post('api_version', 'v21.0')),
        ];

        InstagramSetting::saveSettings($data);

        $this->jsonSuccess('Configurações salvas com sucesso!');
    }

    // ── POST /admin/instagram/test ───────────────────────────────────

    public function testConnection(Request $request): void
    {
        $this->requireAdmin();

        $service = new InstagramService();
        $result  = $service->testConnection();

        $settings = InstagramSetting::getActive();
        if ($settings) {
            InstagramSetting::updateStatus(
                (int)$settings['id'],
                $result['ok'] ? 'active' : 'error',
                $result['ok'] ? null : $result['message']
            );
        }

        if ($result['ok']) {
            $this->jsonSuccess($result['message'], ['data' => $result['data']]);
        } else {
            $this->jsonError($result['message'], [], 422);
        }
    }

    // ── GET /admin/instagram/oauth/start ────────────────────────────

    public function oauthStart(Request $request): void
    {
        $this->requireAdmin();

        $service     = new InstagramService();
        $redirectUri = url('admin/instagram/oauth/callback');
        $oauthUrl    = $service->buildOAuthUrl($redirectUri);

        if (empty($service->getSettings()['app_id'])) {
            \Core\Session::flash('ig_oauth_error', 'Configure o App ID antes de iniciar o OAuth.');
            $this->redirect(url('admin/instagram'));
            exit;
        }

        header('Location: ' . $oauthUrl);
        exit;
    }

    // ── GET /admin/instagram/oauth/callback ──────────────────────────

    public function oauthCallback(Request $request): void
    {
        $this->requireAdmin();

        $code  = $request->get('code', '');
        $error = $request->get('error', '');

        if ($error || !$code) {
            $desc = $request->get('error_description', 'Autorização negada.');
            \Core\Session::flash('ig_oauth_error', $desc);
            $this->redirect(url('admin/instagram'));
            exit;
        }

        $service     = new InstagramService();
        $redirectUri = url('admin/instagram/oauth/callback');

        // Exchange code for short-lived token
        $tokenData = $service->exchangeToken($code, $redirectUri);

        if (empty($tokenData['access_token'])) {
            \Core\Session::flash('ig_oauth_error', 'Falha ao obter token: ' . ($tokenData['error']['message'] ?? 'Erro desconhecido.'));
            $this->redirect(url('admin/instagram'));
            exit;
        }

        // Get long-lived token
        $llData = $service->getLongLivedToken($tokenData['access_token']);
        $longToken = $llData['access_token'] ?? $tokenData['access_token'];

        // Get pages and Instagram accounts linked
        $pages = $service->getPagesWithInstagram($longToken);

        $igAccountId = '';
        $pageId      = '';
        foreach ($pages as $page) {
            if (!empty($page['instagram_business_account']['id'])) {
                $igAccountId = $page['instagram_business_account']['id'];
                $pageId      = $page['id'];
                break;
            }
        }

        InstagramSetting::saveSettings([
            'access_token'         => $longToken,
            'instagram_account_id' => $igAccountId,
            'page_id'              => $pageId,
        ]);

        \Core\Session::flash('ig_oauth_ok', 'Conta conectada com sucesso!' . ($igAccountId ? " Instagram ID: {$igAccountId}" : ' (nenhuma conta de negócios encontrada — configure manualmente.)'));
        $this->redirect(url('admin/instagram'));
        exit;
    }
}
