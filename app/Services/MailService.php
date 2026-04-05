<?php

namespace App\Services;

class MailService
{
    private string $host;
    private int    $port;
    private string $username;
    private string $password;
    private string $encryption;
    private string $fromAddress;
    private string $fromName;
    private bool   $debug;

    public function __construct(?array $settings = null)
    {
        // Priority: passed settings → DB → config fallback
        if (!$settings) {
            try {
                $settings = \App\Models\MailSetting::get();
            } catch (\Throwable $e) {
                $settings = null;
            }
        }

        $this->host        = $settings['host']         ?? config('app.mail.host', 'localhost');
        $this->port        = (int)($settings['port']   ?? config('app.mail.port', 587));
        $this->username    = $settings['username']      ?? config('app.mail.username', '');
        $this->password    = $settings['password']      ?? config('app.mail.password', '');
        $this->encryption  = $settings['encryption']   ?? config('app.mail.encryption', 'tls');
        $this->fromAddress = $settings['from_address'] ?? config('app.mail.from.address', 'noreply@localhost');
        $this->fromName    = $settings['from_name']    ?? config('app.mail.from.name', 'ChatBot');
        $this->debug       = config('app.debug', false);
    }

    public function send(string $to, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        // If no SMTP configured, use PHP mail()
        if (empty($this->username) || $this->username === 'null') {
            return $this->sendWithPhpMail($to, $subject, $htmlBody, $textBody);
        }
        return $this->sendWithSmtp($to, $subject, $htmlBody, $textBody);
    }

    private function sendWithPhpMail(string $to, string $subject, string $html, string $text): bool
    {
        $boundary = md5(uniqid(random_int(0, PHP_INT_MAX), true));
        $headers  = [
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            'From: ' . $this->fromName . ' <' . $this->fromAddress . '>',
            'Reply-To: ' . $this->fromAddress,
            'X-Mailer: ChatBot/1.0',
        ];

        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $body .= ($text ?: strip_tags($html)) . "\r\n\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $body .= $html . "\r\n\r\n";
        $body .= "--{$boundary}--";

        $result = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));

        if (!$result) {
            logger("Mail failed to {$to}: " . (error_get_last()['message'] ?? 'unknown'), 'error');
        }

        return $result;
    }

    private function sendWithSmtp(string $to, string $subject, string $html, string $text): bool
    {
        try {
            $boundary = md5(uniqid(random_int(0, PHP_INT_MAX), true));

            $prefix = match($this->encryption) {
                'ssl'   => 'ssl://',
                default => '',
            };

            $socket = @fsockopen($prefix . $this->host, $this->port, $errno, $errstr, 10);
            if (!$socket) {
                throw new \RuntimeException("SMTP connect failed: {$errstr} ({$errno})");
            }

            stream_set_timeout($socket, 15);

            $this->smtpRead($socket); // 220

            $domain = parse_url(config('app.url', 'localhost'), PHP_URL_HOST) ?? 'localhost';
            $this->smtpSend($socket, "EHLO {$domain}");
            $resp = $this->smtpRead($socket);

            if ($this->encryption === 'tls') {
                $this->smtpSend($socket, "STARTTLS");
                $this->smtpRead($socket); // 220
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $this->smtpSend($socket, "EHLO {$domain}");
                $this->smtpRead($socket);
            }

            $this->smtpSend($socket, "AUTH LOGIN");
            $this->smtpRead($socket); // 334
            $this->smtpSend($socket, base64_encode($this->username));
            $this->smtpRead($socket); // 334
            $this->smtpSend($socket, base64_encode($this->password));
            $authResp = $this->smtpRead($socket); // 235
            if (!str_starts_with($authResp, '235')) {
                throw new \RuntimeException("SMTP auth failed: {$authResp}");
            }

            $this->smtpSend($socket, "MAIL FROM:<{$this->fromAddress}>");
            $this->smtpRead($socket);
            $this->smtpSend($socket, "RCPT TO:<{$to}>");
            $this->smtpRead($socket);
            $this->smtpSend($socket, "DATA");
            $this->smtpRead($socket); // 354

            $message  = "From: {$this->fromName} <{$this->fromAddress}>\r\n";
            $message .= "To: {$to}\r\n";
            $message .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $message .= "MIME-Version: 1.0\r\n";
            $message .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
            $message .= "\r\n";
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
            $message .= ($text ?: strip_tags($html)) . "\r\n\r\n";
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
            $message .= $html . "\r\n\r\n";
            $message .= "--{$boundary}--\r\n";
            $message .= ".";

            $this->smtpSend($socket, $message);
            $dataResp = $this->smtpRead($socket); // 250

            $this->smtpSend($socket, "QUIT");
            fclose($socket);

            if (!str_starts_with($dataResp, '250')) {
                throw new \RuntimeException("SMTP DATA failed: {$dataResp}");
            }

            logger("Mail sent to {$to}: {$subject}", 'info');
            return true;

        } catch (\Throwable $e) {
            logger("SMTP error sending to {$to}: " . $e->getMessage(), 'error');
            return false;
        }
    }

    private function smtpSend($socket, string $cmd): void
    {
        fwrite($socket, $cmd . "\r\n");
    }

    private function smtpRead($socket): string
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $response;
    }

    // ---------------------------------------------------------------
    // Template helpers
    // ---------------------------------------------------------------

    private static function logoHeaderHtml(string $appName): string
    {
        try {
            $company  = \App\Models\CompanySetting::get();
            $logoPath = $company['logo_path'] ?? '';
        } catch (\Throwable $e) {
            $logoPath = '';
        }

        if (!empty($logoPath)) {
            $logoUrl = rtrim(config('app.url', ''), '/') . '/' . ltrim($logoPath, '/');
            $alt     = htmlspecialchars($appName, ENT_QUOTES);
            return "<img src=\"{$logoUrl}\" alt=\"{$alt}\" style=\"max-height:50px;max-width:200px;object-fit:contain;\">";
        }

        return "<div style=\"display:inline-flex;align-items:center;gap:12px;\">"
             . "<div style=\"width:42px;height:42px;background:#3b82f6;border-radius:10px;display:inline-block;text-align:center;line-height:42px;\">"
             . "<span style=\"color:#fff;font-size:20px;\">💬</span></div>"
             . "<span style=\"color:#f1f5f9;font-size:1.3rem;font-weight:700;\">" . htmlspecialchars($appName, ENT_QUOTES) . "</span>"
             . "</div>";
    }

    public static function templateInvite(string $userName, string $inviteUrl, string $expiresAt): string
    {
        $appName  = config('app.name', 'ChatBot System');
        $expiry   = date('d/m/Y H:i', strtotime($expiresAt));
        $logoHtml = self::logoHeaderHtml($appName);
        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Convite - {$appName}</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:40px 0;">
  <tr><td align="center">
    <table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
      <!-- Header -->
      <tr><td style="background:#0f172a;padding:28px 40px;text-align:center;">
        {$logoHtml}
      </td></tr>
      <!-- Body -->
      <tr><td style="padding:40px 40px 32px;">
        <h2 style="margin:0 0 8px;color:#0f172a;font-size:1.4rem;">Olá, {$userName}! 👋</h2>
        <p style="color:#475569;margin:0 0 24px;line-height:1.6;">
          Você foi convidado para acessar o <strong>{$appName}</strong>.<br>
          Clique no botão abaixo para criar sua senha e acessar o sistema.
        </p>
        <div style="text-align:center;margin:32px 0;">
          <a href="{$inviteUrl}"
             style="background:#3b82f6;color:#fff;padding:14px 36px;border-radius:8px;text-decoration:none;font-weight:700;font-size:1rem;display:inline-block;">
            Criar minha senha
          </a>
        </div>
        <p style="color:#94a3b8;font-size:.85rem;margin:0 0 8px;">
          ⏰ Este link expira em: <strong>{$expiry}</strong>
        </p>
        <p style="color:#94a3b8;font-size:.82rem;margin:0;">
          Se o botão não funcionar, copie e cole este link no navegador:<br>
          <a href="{$inviteUrl}" style="color:#3b82f6;word-break:break-all;">{$inviteUrl}</a>
        </p>
      </td></tr>
      <!-- Footer -->
      <tr><td style="background:#f8fafc;padding:20px 40px;border-top:1px solid #e2e8f0;text-align:center;">
        <p style="color:#94a3b8;font-size:.78rem;margin:0;">
          Se você não solicitou este acesso, ignore este e-mail.<br>
          © {$appName} — Todos os direitos reservados.
        </p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
    }

    public static function templateResetPassword(string $userName, string $resetUrl, string $expiresAt): string
    {
        $appName  = config('app.name', 'ChatBot System');
        $expiry   = date('d/m/Y H:i', strtotime($expiresAt));
        $logoHtml = self::logoHeaderHtml($appName);
        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Redefinir Senha - {$appName}</title></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:40px 0;">
  <tr><td align="center">
    <table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
      <tr><td style="background:#0f172a;padding:28px 40px;text-align:center;">
        {$logoHtml}
      </td></tr>
      <tr><td style="padding:40px;">
        <h2 style="margin:0 0 8px;color:#0f172a;">Redefinição de senha</h2>
        <p style="color:#475569;margin:0 0 24px;line-height:1.6;">
          Olá, <strong>{$userName}</strong>. Recebemos uma solicitação para redefinir a senha da sua conta.
        </p>
        <div style="text-align:center;margin:32px 0;">
          <a href="{$resetUrl}"
             style="background:#ef4444;color:#fff;padding:14px 36px;border-radius:8px;text-decoration:none;font-weight:700;font-size:1rem;display:inline-block;">
            Redefinir minha senha
          </a>
        </div>
        <p style="color:#94a3b8;font-size:.85rem;margin:0 0 8px;">⏰ Link válido até: <strong>{$expiry}</strong></p>
        <p style="color:#94a3b8;font-size:.82rem;margin:0;">
          Se você não solicitou, ignore este e-mail. Sua senha não será alterada.<br>
          <a href="{$resetUrl}" style="color:#3b82f6;word-break:break-all;">{$resetUrl}</a>
        </p>
      </td></tr>
      <tr><td style="background:#f8fafc;padding:20px 40px;border-top:1px solid #e2e8f0;text-align:center;">
        <p style="color:#94a3b8;font-size:.78rem;margin:0;">© {$appName}</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
    }
}
