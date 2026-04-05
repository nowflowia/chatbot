<?php

namespace App\Middlewares;

use Core\Auth;
use Core\Request;

class AuthMiddleware
{
    public function handle(Request $request): void
    {
        if (Auth::guest()) {
            if ($request->isAjax()) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Não autenticado.', 'data' => null, 'errors' => []]);
                exit;
            }
            header('Location: ' . url('login'));
            exit;
        }
    }
}
