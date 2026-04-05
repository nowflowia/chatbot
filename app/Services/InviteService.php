<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserInvite;

class InviteService
{
    private MailService $mail;

    public function __construct()
    {
        $this->mail = new MailService();
    }

    /**
     * Creates a set-password invite and sends the email.
     */
    public function sendInvite(array $user): bool
    {
        $invite     = UserInvite::createForUser((int)$user['id'], 'set_password');
        $inviteUrl  = url('invite/' . $invite['token']);
        $html       = MailService::templateInvite($user['name'], $inviteUrl, $invite['expires_at']);
        $text       = "Olá {$user['name']},\n\nVocê foi convidado. Acesse: {$inviteUrl}\n\nLink válido até: " . date('d/m/Y H:i', strtotime($invite['expires_at']));

        return $this->mail->send(
            $user['email'],
            'Seu acesso ao ' . config('app.name'),
            $html,
            $text
        );
    }

    /**
     * Creates a reset-password token and sends the email.
     */
    public function sendPasswordReset(array $user): bool
    {
        $invite    = UserInvite::createForUser((int)$user['id'], 'reset_password');
        $resetUrl  = url('invite/' . $invite['token']);
        $html      = MailService::templateResetPassword($user['name'], $resetUrl, $invite['expires_at']);
        $text      = "Olá {$user['name']},\n\nRedefinição de senha: {$resetUrl}\n\nVálido até: " . date('d/m/Y H:i', strtotime($invite['expires_at']));

        return $this->mail->send(
            $user['email'],
            'Redefinição de senha — ' . config('app.name'),
            $html,
            $text
        );
    }

    /**
     * Validates a token and sets the user password.
     * Returns the user array on success or null on failure.
     */
    public function processSetPassword(string $token, string $password): ?array
    {
        $invite = UserInvite::findValidToken($token);
        if (!$invite) {
            return null;
        }

        User::updatePassword((int)$invite['user_id'], $password);
        UserInvite::markUsed($token);

        return User::findWithRole((int)$invite['user_id']);
    }

    public function findValidToken(string $token): ?array
    {
        return UserInvite::findValidToken($token);
    }
}
