<?php

namespace App\Middlewares;

use Core\Auth;
use Core\Request;

class GuestMiddleware
{
    public function handle(Request $request): void
    {
        if (Auth::check()) {
            header('Location: ' . url('admin/dashboard'));
            exit;
        }
    }
}
