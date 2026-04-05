<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Validator;
use Core\Auth;
use Core\View;
use App\Models\User;
use App\Models\Role;
use App\Services\InviteService;
use App\Services\LicenseService;

class UserController extends Controller
{
    // ---------------------------------------------------------------
    // GET /admin/users
    // ---------------------------------------------------------------
    public function index(Request $request): string
    {
        $page    = max(1, (int)$request->get('page', 1));
        $search  = trim($request->get('search', ''));
        $perPage = 15;

        $result = User::allWithRole($page, $perPage, $search);
        $roles  = Role::allActive();

        return $this->view('users/index', [
            'users'       => $result['data'],
            'pagination'  => $result,
            'roles'       => $roles,
            'search'      => $search,
            'currentUser' => Auth::user(),
        ]);
    }

    // ---------------------------------------------------------------
    // POST /admin/users  — create
    // ---------------------------------------------------------------
    public function store(Request $request): void
    {
        $this->requireAjax();

        // ── License check ─────────────────────────────────────────
        $license  = LicenseService::check();
        $maxUsers = $license['max_users'] ?? 0;
        if ($license['valid'] && $maxUsers > 0 && $maxUsers < PHP_INT_MAX) {
            $currentCount = User::countAll();
            if ($currentCount >= $maxUsers) {
                $this->jsonError(
                    "Limite de {$maxUsers} usuário(s) atingido. Atualize sua licença para adicionar mais.",
                    [], 403
                );
            }
        } elseif (!$license['valid']) {
            $this->jsonError('Licença inválida ou expirada. Contate o suporte.', [], 403);
        }

        $data = $request->only(['name', 'email', 'role_id', 'phone', 'status']);

        $validator = new Validator($data, [
            'name'    => 'required|min:2|max:150',
            'email'   => 'required|email|unique:users,email',
            'role_id' => 'required|integer',
            'status'  => 'required|in:active,inactive,pending',
        ]);

        if ($validator->fails()) {
            $this->jsonError('Verifique os campos informados.', $validator->errors(), 422);
        }

        $payload = [
            'name'    => $data['name'],
            'email'   => strtolower(trim($data['email'])),
            'role_id' => (int)$data['role_id'],
            'phone'   => $data['phone'] ?? null,
            'status'  => $data['status'] ?? 'pending',
        ];

        $id   = User::create($payload);
        $user = User::findWithRole((int)$id);

        // Send invite email
        $sent = false;
        try {
            $sent = (new InviteService())->sendInvite($user);
        } catch (\Throwable $e) {
            logger('Invite send error: ' . $e->getMessage(), 'error');
        }

        $this->jsonSuccess(
            'Usuário criado.' . ($sent ? ' Convite enviado por e-mail.' : ' Não foi possível enviar o e-mail de convite.'),
            User::hide($user)
        );
    }

    // ---------------------------------------------------------------
    // GET /admin/users/{id}  — fetch for edit modal
    // ---------------------------------------------------------------
    public function show(Request $request, string $id): void
    {
        $this->requireAjax();
        $user = User::findWithRole((int)$id);
        if (!$user) {
            $this->jsonError('Usuário não encontrado.', [], 404);
        }
        $this->jsonSuccess('OK', User::hide($user));
    }

    // ---------------------------------------------------------------
    // POST /admin/users/{id}  — update
    // ---------------------------------------------------------------
    public function update(Request $request, string $id): void
    {
        $this->requireAjax();

        $user = User::find((int)$id);
        if (!$user) {
            $this->jsonError('Usuário não encontrado.', [], 404);
        }

        $data = $request->only(['name', 'email', 'role_id', 'phone', 'status']);

        $validator = new Validator($data, [
            'name'    => 'required|min:2|max:150',
            'email'   => 'required|email|unique:users,email,' . $id,
            'role_id' => 'required|integer',
            'status'  => 'required|in:active,inactive,pending',
        ]);

        if ($validator->fails()) {
            $this->jsonError('Verifique os campos informados.', $validator->errors(), 422);
        }

        // Prevent self-demotion
        if ((int)$id === (int)Auth::id() && (int)$data['role_id'] !== (int)$user['role_id']) {
            $this->jsonError('Você não pode alterar seu próprio perfil.', [], 403);
        }

        User::update((int)$id, [
            'name'    => $data['name'],
            'email'   => strtolower(trim($data['email'])),
            'role_id' => (int)$data['role_id'],
            'phone'   => $data['phone'] ?? null,
            'status'  => $data['status'],
        ]);

        // Refresh session if editing self
        if ((int)$id === (int)Auth::id()) {
            Auth::refreshUser();
        }

        $updated = User::findWithRole((int)$id);
        $this->jsonSuccess('Usuário atualizado com sucesso.', User::hide($updated));
    }

    // ---------------------------------------------------------------
    // POST /admin/users/{id}/delete
    // ---------------------------------------------------------------
    public function destroy(Request $request, string $id): void
    {
        $this->requireAjax();

        if ((int)$id === (int)Auth::id()) {
            $this->jsonError('Você não pode excluir sua própria conta.', [], 403);
        }

        $user = User::find((int)$id);
        if (!$user) {
            $this->jsonError('Usuário não encontrado.', [], 404);
        }

        User::delete((int)$id);
        $this->jsonSuccess('Usuário excluído com sucesso.');
    }

    // ---------------------------------------------------------------
    // POST /admin/users/{id}/invite  — resend invite
    // ---------------------------------------------------------------
    public function resendInvite(Request $request, string $id): void
    {
        $this->requireAjax();

        $user = User::findWithRole((int)$id);
        if (!$user) {
            $this->jsonError('Usuário não encontrado.', [], 404);
        }

        try {
            $sent = (new InviteService())->sendInvite($user);
        } catch (\Throwable $e) {
            logger('Resend invite error: ' . $e->getMessage(), 'error');
            $sent = false;
        }

        if ($sent) {
            $this->jsonSuccess('Convite reenviado com sucesso para ' . $user['email'] . '.');
        } else {
            $this->jsonError('Não foi possível enviar o e-mail. Verifique as configurações de e-mail.', [], 500);
        }
    }

    // ---------------------------------------------------------------
}
