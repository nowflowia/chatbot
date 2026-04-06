<?php

namespace App\Middlewares;

use Core\Auth;
use Core\Request;

class FeatureMiddleware
{
    private string $feature;

    public function __construct(string $feature = '')
    {
        $this->feature = $feature;
    }

    public function handle(Request $request): void
    {
        if (!Auth::hasFeature($this->feature)) {
            http_response_code(403);
            if ($request->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Módulo não disponível no seu plano.',
                    'data'    => null,
                    'errors'  => [],
                ]);
                exit;
            }
            if (file_exists(VIEW_PATH . '/errors/403.php')) {
                require VIEW_PATH . '/errors/403.php';
            } else {
                echo '<h1>403 — Módulo não disponível no seu plano.</h1>';
            }
            exit;
        }
    }
}
