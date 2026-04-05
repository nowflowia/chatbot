<?php

namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use Core\Request;

class HomeController extends Controller
{
    public function index(Request $request): string
    {
        if (Auth::check()) {
            $this->redirect(url('admin/dashboard'));
        }
        $this->redirect(url('login'));
        return '';
    }
}
