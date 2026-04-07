<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Auth;
use Core\Database;

class ApiDocsController extends Controller
{
    public function index(Request $request): string
    {
        $user    = Auth::user();
        $db      = Database::getInstance();
        $apiKeys = $db->select(
            "SELECT * FROM api_keys WHERE user_id=? ORDER BY id DESC",
            [(int)$user['id']]
        );

        return $this->view('api-docs/index', [
            'apiKeys' => $apiKeys,
            'baseUrl' => url('api/v1'),
        ]);
    }
}
