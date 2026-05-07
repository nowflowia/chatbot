<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Auth;
use Core\Database;
use App\Services\LicenseService;

class LicenseController extends Controller
{
    private function requireAdmin(): void
    {
        if (!Auth::isAdmin()) {
            $this->redirect(url('admin/dashboard'));
            exit;
        }
    }

    public function index(Request $request): string
    {
        $this->requireAdmin();

        $apiUrl    = (string)(config('app.license_api_url', env('LICENSE_API_URL', '')) ?: '');
        $key       = (string)(config('app.license_key',     env('LICENSE_KEY', ''))     ?: '');
        $configDom = parse_url((string)config('app.url', ''), PHP_URL_HOST) ?: ($_SERVER['HTTP_HOST'] ?? 'localhost');

        $license = LicenseService::check();

        // Cache row info
        $cacheRow = null;
        try {
            $cacheRow = Database::getInstance()->selectOne(
                "SELECT cache_key, checked_at, updated_at FROM license_cache WHERE cache_key='license_data' LIMIT 1"
            );
        } catch (\Throwable $e) {}

        return $this->view('license/index', [
            'license'    => $license,
            'apiUrl'     => $apiUrl,
            'keyMasked'  => $this->maskKey($key),
            'keyLength'  => strlen($key),
            'configDom'  => $configDom,
            'cacheRow'   => $cacheRow,
            'cacheTtl'   => 3600,
        ]);
    }

    public function refresh(Request $request): void
    {
        $this->requireAdmin();
        try {
            LicenseService::clearCache();
            $license = LicenseService::check();
            $this->jsonSuccess('Licença atualizada da API!', ['license' => $license]);
        } catch (\Throwable $e) {
            $this->jsonError('Erro ao atualizar: ' . $e->getMessage());
        }
    }

    public function probe(Request $request): void
    {
        $this->requireAdmin();
        $r       = LicenseService::probeApi();
        $bodyTxt = $r['body'] !== null ? substr($r['body'], 0, 4000) : null;

        $parsed  = null;
        if (is_string($r['body'])) {
            $tmp = json_decode($r['body'], true);
            if (is_array($tmp)) $parsed = $tmp;
        }

        $this->jsonSuccess('Diagnóstico concluído.', [
            'url'         => $r['url'] ?? '',
            'code'        => (int)($r['code'] ?? 0),
            'duration_ms' => (int)($r['duration_ms'] ?? 0),
            'error'       => $r['error'] ?? null,
            'body'        => $bodyTxt,
            'parsed'      => $parsed,
        ]);
    }

    private function maskKey(string $key): string
    {
        if ($key === '') return '';
        if (strlen($key) <= 8) return str_repeat('•', strlen($key));
        return substr($key, 0, 4) . str_repeat('•', max(8, strlen($key) - 8)) . substr($key, -4);
    }
}
