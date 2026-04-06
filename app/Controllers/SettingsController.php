<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Validator;
use Core\Auth;
use App\Models\WhatsappSetting;
use App\Models\MailSetting;
use App\Models\CompanySetting;
use App\Services\MetaWhatsAppService;
use App\Services\MailService;

class SettingsController extends Controller
{
    public function index(Request $request): string
    {
        if (!Auth::isAdmin()) {
            $this->redirect(url('admin/dashboard'));
        }

        $settings        = WhatsappSetting::getActive();
        $mailSettings    = MailSetting::get();
        $companySettings = CompanySetting::get();
        $webhookUrl      = url('webhook');
        $tab = $request->get('tab', 'empresa');

        // Redirect non-admins away from the update tab
        if ($tab === 'atualizacao' && !Auth::isAdmin()) {
            $tab = 'empresa';
        }

        $updateData = [];
        if ($tab === 'atualizacao' && Auth::isAdmin()) {
            $updateData = (new SystemUpdateController())->buildData();
        }

        return $this->view('settings/index', [
            'settings'        => $settings,
            'mailSettings'    => $mailSettings,
            'companySettings' => $companySettings,
            'webhookUrl'      => $webhookUrl,
            'activeTab'       => $tab,
            'updateData'      => $updateData,
        ]);
    }

    // ── WhatsApp ──────────────────────────────────────────────────

    public function store(Request $request): void
    {
        if (!Auth::isAdmin()) { $this->jsonError('Acesso negado.', [], 403); }
        if (!$request->isAjax()) {
            $this->redirect(url('admin/settings'));
        }

        $data = $request->only([
            'name', 'access_token', 'verify_token',
            'phone_number_id', 'business_account_id',
            'app_id', 'app_secret', 'webhook_url', 'api_version',
        ]);

        $validator = new Validator($data, [
            'name'            => 'required|min:2|max:100',
            'access_token'    => 'required',
            'verify_token'    => 'required|min:8',
            'phone_number_id' => 'required',
            'api_version'     => 'required',
        ]);

        if ($validator->fails()) {
            $this->jsonError('Verifique os campos obrigatórios.', $validator->errors(), 422);
        }

        $saved = WhatsappSetting::saveSettings($data);

        $this->jsonSuccess('Configurações salvas com sucesso!', [
            'id'     => $saved['id']     ?? null,
            'status' => $saved['status'] ?? 'inactive',
        ]);
    }

    public function test(Request $request): void
    {
        if (!Auth::isAdmin()) { $this->jsonError('Acesso negado.', [], 403); }
        if (!$request->isAjax()) {
            $this->redirect(url('admin/settings'));
        }

        $settings = WhatsappSetting::getActive();
        if (!$settings) {
            $this->jsonError('Nenhuma configuração encontrada. Salve as configurações primeiro.');
        }

        $service = new MetaWhatsAppService($settings);
        $result  = $service->testConnection();

        if ($result['ok']) {
            WhatsappSetting::updateStatus((int)$settings['id'], 'active');
            $this->jsonSuccess($result['message'], $result['data']);
        } else {
            WhatsappSetting::updateStatus((int)$settings['id'], 'error', $result['message']);
            $this->jsonError($result['message'], [], 400);
        }
    }

    // ── Empresa ───────────────────────────────────────────────────

    public function storeCompany(Request $request): void
    {
        if (!Auth::isAdmin()) { $this->jsonError('Acesso negado.', [], 403); }
        if (!$request->isAjax()) {
            $this->redirect(url('admin/settings?tab=empresa'));
        }

        try {
            $data = $request->only([
                'company_name', 'cnpj', 'email', 'phone',
                'address', 'zip', 'city', 'state',
            ]);

            // Handle logo upload
            $uploadsDir = PUBLIC_PATH . '/assets/uploads';
            if (!is_dir($uploadsDir)) {
                @mkdir($uploadsDir, 0755, true);
            }

            $existing = CompanySetting::get();

            foreach (['logo' => 'logo_path', 'icon' => 'icon_path'] as $field => $col) {
                if (!empty($_FILES[$field]['tmp_name']) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                    $mime    = mime_content_type($_FILES[$field]['tmp_name']);
                    $allowed = ['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml', 'image/x-icon', 'image/webp'];
                    if (!in_array($mime, $allowed)) {
                        $this->jsonError("Formato de arquivo inválido para {$field}. Use PNG, JPG, SVG ou ICO.", [], 422);
                    }
                    $ext      = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION) ?: 'png';
                    $filename = $field . '_' . time() . '.' . $ext;
                    if (!@move_uploaded_file($_FILES[$field]['tmp_name'], $uploadsDir . '/' . $filename)) {
                    $this->jsonError("Falha ao salvar o arquivo {$field}. Verifique permissões da pasta uploads.");
                }
                    $data[$col] = 'assets/uploads/' . $filename;

                    // Delete old file
                    if (!empty($existing[$col])) {
                        $old = PUBLIC_PATH . '/' . $existing[$col];
                        if (file_exists($old)) @unlink($old);
                    }
                }
            }

            CompanySetting::save($data);

            $this->jsonSuccess('Dados da empresa salvos com sucesso!');

        } catch (\Throwable $e) {
            logger('storeCompany error: ' . $e->getMessage(), 'error');
            $this->jsonError('Erro ao salvar: ' . $e->getMessage());
        }
    }

    // ── Template ──────────────────────────────────────────────────

    public function storeTemplate(Request $request): void
    {
        if (!Auth::isAdmin()) { $this->jsonError('Acesso negado.', [], 403); }
        if (!$request->isAjax()) {
            $this->redirect(url('admin/settings?tab=template'));
        }

        try {
            $data = $request->only(['custom_css', 'primary_color']);

            CompanySetting::saveTemplate($data);

            $this->jsonSuccess('Template salvo com sucesso!');
        } catch (\Throwable $e) {
            logger('storeTemplate error: ' . $e->getMessage(), 'error');
            $this->jsonError('Erro ao salvar: ' . $e->getMessage());
        }
    }

    // ── SMTP ──────────────────────────────────────────────────────

    public function storeMail(Request $request): void
    {
        if (!Auth::isAdmin()) { $this->jsonError('Acesso negado.', [], 403); }
        if (!$request->isAjax()) {
            $this->redirect(url('admin/settings?tab=smtp'));
        }

        $data = $request->only([
            'mail_host', 'mail_port', 'mail_encryption',
            'mail_username', 'mail_password',
            'mail_from_address', 'mail_from_name',
        ]);

        $validator = new Validator($data, [
            'mail_host'         => 'required',
            'mail_port'         => 'required',
            'mail_from_address' => 'required|email',
            'mail_from_name'    => 'required',
        ]);

        if ($validator->fails()) {
            $this->jsonError('Verifique os campos obrigatórios.', $validator->errors(), 422);
        }

        MailSetting::save($data);

        $this->jsonSuccess('Configurações de e-mail salvas com sucesso!');
    }

    public function testMail(Request $request): void
    {
        if (!Auth::isAdmin()) { $this->jsonError('Acesso negado.', [], 403); }
        $to = trim($request->post('test_email', ''));
        if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->jsonError('Informe um e-mail válido para o teste.');
        }

        $mailSettings = MailSetting::get();
        if (!$mailSettings) {
            $this->jsonError('Salve as configurações de SMTP antes de testar.');
        }

        try {
            $mailer  = new MailService($mailSettings);
            $subject = 'Teste de e-mail — ' . config('app.name', 'ChatBot');
            $appName = htmlspecialchars(config('app.name', 'ChatBot'));
            $date    = date('d/m/Y H:i:s');
            $html    = <<<HTML
<!DOCTYPE html><html><body style="font-family:sans-serif;padding:32px;background:#f1f5f9;">
<div style="max-width:480px;margin:0 auto;background:#fff;border-radius:12px;padding:32px;box-shadow:0 2px 12px rgba(0,0,0,.08);">
  <h2 style="color:#0f172a;margin:0 0 12px;">✅ Teste de SMTP</h2>
  <p style="color:#475569;line-height:1.6;">
    Se você está lendo este e-mail, as configurações de SMTP estão corretas e funcionando!
  </p>
  <p style="color:#94a3b8;font-size:.82rem;margin-top:24px;">Enviado por {$appName} em {$date}</p>
</div>
</body></html>
HTML;

            $ok = $mailer->send($to, $subject, $html);

            if ($ok) {
                $this->jsonSuccess("✅ E-mail enviado para {$to} com sucesso! Verifique a caixa de entrada.");
            } else {
                $this->jsonError('Falha ao enviar. Verifique host, porta, usuário e senha.');
            }
        } catch (\Throwable $e) {
            logger('SMTP test error: ' . $e->getMessage(), 'error');
            $this->jsonError('Erro SMTP: ' . $e->getMessage());
        }
    }
}
