<?php

namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use Core\Request;
use Core\Validator;
use Core\View;
use Core\Session;
use App\Models\User;
use App\Services\InviteService;

class AuthController extends Controller
{
    // ---------------------------------------------------------------
    // Login
    // ---------------------------------------------------------------

    public function showLogin(Request $request): string
    {
        $company = \App\Models\CompanySetting::get();
        return View::render('auth/login', [
            'appName'    => config('app.name'),
            'logoPath'   => $company['logo_path'] ?? '',
            'iconPath'   => $company['icon_path'] ?? '',
        ], null);
    }

    public function login(Request $request): void
    {
        // Always respond as JSON for POST requests
        if (!$request->isPost()) {
            $this->redirect(url('login'));
        }

        $data = $request->only(['email', 'password']);

        $validator = new Validator($data, [
            'email'    => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            $this->jsonError('Verifique os campos informados.', $validator->errors(), 422);
        }

        $email    = strtolower(trim($data['email']));
        $password = $data['password'];

        if (!Auth::attempt($email, $password)) {
            $this->jsonError('E-mail ou senha inválidos.', [], 401);
        }

        // Update last login (non-critical)
        try { User::updateLastLogin((int)Auth::id(), $request->ip()); } catch (\Throwable $e) {}

        $this->jsonSuccess('Login realizado com sucesso.', [
            'redirect' => url('admin/dashboard'),
        ]);
    }

    // ---------------------------------------------------------------
    // Logout
    // ---------------------------------------------------------------

    public function logout(Request $request): void
    {
        Auth::logout();
        $this->redirect(url('login'));
    }

    // ---------------------------------------------------------------
    // Set Password via Invite
    // ---------------------------------------------------------------

    public function showSetPassword(Request $request, string $token): string
    {
        $invite = (new InviteService())->findValidToken($token);

        if (!$invite) {
            return View::render('auth/invite-expired', [], null);
        }

        return View::render('auth/set-password', [
            'token'    => $token,
            'invite'   => $invite,
            'appName'  => config('app.name'),
        ], null);
    }

    public function setPassword(Request $request, string $token): void
    {
        if (!$request->isAjax()) {
            $this->redirect(url('login'));
        }

        $data = $request->only(['password', 'password_confirmation']);

        $validator = new Validator($data, [
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        if ($validator->fails()) {
            $this->jsonError('Verifique os campos.', $validator->errors(), 422);
        }

        $service = new InviteService();
        $user    = $service->processSetPassword($token, $data['password']);

        if (!$user) {
            $this->jsonError('Token inválido ou expirado. Solicite um novo convite.', [], 410);
        }

        // Auto-login
        Auth::login($user);
        User::updateLastLogin((int)Auth::id(), $request->ip());

        $this->jsonSuccess('Senha criada com sucesso!', [
            'redirect' => url('admin/dashboard'),
        ]);
    }

    // ---------------------------------------------------------------
    // Forgot Password
    // ---------------------------------------------------------------

    public function showForgotPassword(Request $request): string
    {
        return View::render('auth/forgot-password', [
            'appName' => config('app.name'),
        ], null);
    }

    public function forgotPassword(Request $request): void
    {
        if (!$request->isAjax()) {
            $this->redirect(url('forgot-password'));
        }

        $data      = $request->only(['email']);
        $validator = new Validator($data, ['email' => 'required|email']);

        if ($validator->fails()) {
            $this->jsonError('Informe um e-mail válido.', $validator->errors(), 422);
        }

        $user = User::findByEmail(strtolower(trim($data['email'])));

        // Always return success to avoid email enumeration
        if ($user && $user['status'] !== 'inactive') {
            try {
                (new InviteService())->sendPasswordReset($user);
            } catch (\Throwable $e) {
                logger('Forgot password mail error: ' . $e->getMessage(), 'error');
            }
        }

        $this->jsonSuccess('Se este e-mail estiver cadastrado, você receberá as instruções em breve.');
    }
}
