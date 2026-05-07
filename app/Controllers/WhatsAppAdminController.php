<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Auth;
use Core\View;
use App\Models\WhatsappTemplate;
use App\Services\MetaWhatsAppService;

class WhatsAppAdminController extends Controller
{
    private function requireAdmin(): void
    {
        if (!Auth::isAdmin()) {
            $this->redirect(url('admin/dashboard'));
            exit;
        }
    }

    // ── GET /admin/whatsapp ──────────────────────────────────────────

    public function index(Request $request): string
    {
        $this->requireAdmin();

        $all = WhatsappTemplate::all();

        $grouped = [
            'marketing'      => [],
            'utility'        => [],
            'service'        => [],
            'authentication' => [],
        ];

        foreach ($all as $tpl) {
            $cat = $tpl['category'] ?? 'marketing';
            if (isset($grouped[$cat])) {
                $grouped[$cat][] = $tpl;
            }
        }

        return $this->view('whatsapp/index', [
            'grouped' => $grouped,
        ]);
    }

    // ── POST /admin/whatsapp/templates ───────────────────────────────

    public function store(Request $request): void
    {
        $this->requireAdmin();

        $name       = trim((string) $request->post('name', ''));
        $category   = (string) $request->post('category', 'marketing');
        $language   = (string) $request->post('language', 'pt_BR');
        $headerType = (string) $request->post('header_type', 'none');
        $headerText = trim((string) $request->post('header_text', ''));
        $bodyText   = trim((string) $request->post('body_text', ''));
        $footerText = trim((string) $request->post('footer_text', ''));

        // Validate
        if ($name === '') {
            $this->jsonError('O nome do template é obrigatório.', ['name' => ['Campo obrigatório.']], 422);
        }
        if (!preg_match('/^[a-z0-9_]+$/', $name)) {
            $this->jsonError('O nome deve usar apenas letras minúsculas, números e underscore.', ['name' => ['Use snake_case (ex: meu_template_1).']], 422);
        }
        if ($bodyText === '') {
            $this->jsonError('O corpo da mensagem é obrigatório.', ['body_text' => ['Campo obrigatório.']], 422);
        }

        $validCategories = ['marketing', 'utility', 'service', 'authentication'];
        if (!in_array($category, $validCategories, true)) {
            $category = 'marketing';
        }

        $validLanguages = ['pt_BR', 'en_US', 'es_ES'];
        if (!in_array($language, $validLanguages, true)) {
            $language = 'pt_BR';
        }

        $validHeaderTypes = ['none', 'text', 'image', 'video', 'document'];
        if (!in_array($headerType, $validHeaderTypes, true)) {
            $headerType = 'none';
        }

        $newId = WhatsappTemplate::create([
            'name'        => $name,
            'category'    => $category,
            'language'    => $language,
            'header_type' => $headerType,
            'header_text' => ($headerType === 'text' && $headerText !== '') ? $headerText : null,
            'body_text'   => $bodyText,
            'footer_text' => $footerText !== '' ? mb_substr($footerText, 0, 60) : null,
            'status'      => 'draft',
        ]);

        $template = WhatsappTemplate::find((int) $newId);

        $this->jsonSuccess('Template criado com sucesso!', ['template' => $template]);
    }

    // ── POST /admin/whatsapp/templates/{id}/delete ───────────────────

    public function destroy(Request $request, string $id): void
    {
        $this->requireAdmin();

        $tpl = WhatsappTemplate::find((int) $id);
        if (!$tpl) {
            $this->jsonError('Template não encontrado.', [], 404);
        }

        WhatsappTemplate::delete((int) $id);

        $this->jsonSuccess('Template excluído.');
    }

    // ── POST /admin/whatsapp/templates/{id}/submit ───────────────────

    public function submit(Request $request, string $id): void
    {
        $this->requireAdmin();

        $tpl = WhatsappTemplate::find((int) $id);
        if (!$tpl) {
            $this->jsonError('Template não encontrado.', [], 404);
        }

        if (!in_array($tpl['status'], ['draft', 'rejected'], true)) {
            $this->jsonError('Apenas templates em rascunho ou rejeitados podem ser enviados para aprovação.');
        }

        try {
            $service  = new MetaWhatsAppService();
            $buttons  = !empty($tpl['buttons']) ? json_decode($tpl['buttons'], true) : [];
            $response = $service->createTemplate([
                'name'        => $tpl['name'],
                'category'    => $tpl['category'],
                'language'    => $tpl['language'],
                'header_text' => $tpl['header_type'] === 'text' ? ($tpl['header_text'] ?? '') : '',
                'body_text'   => $tpl['body_text'],
                'footer_text' => $tpl['footer_text'] ?? '',
                'buttons'     => $buttons,
            ]);

            if (isset($response['error'])) {
                $errMsg = $response['error']['message'] ?? 'Erro na API Meta.';
                $this->jsonError("Falha ao enviar para Meta: {$errMsg}");
            }

            // Meta returns id and status
            $metaId     = $response['id']     ?? null;
            $metaStatus = strtolower($response['status'] ?? 'pending');

            // Map Meta statuses to our local statuses
            $localStatus = match ($metaStatus) {
                'approved' => 'approved',
                'rejected' => 'rejected',
                default    => 'pending',
            };

            WhatsappTemplate::update((int) $id, [
                'status'           => $localStatus,
                'meta_template_id' => $metaId,
                'rejection_reason' => null,
            ]);

            $this->jsonSuccess('Template enviado para aprovação!', [
                'status'     => $localStatus,
                'meta_id'    => $metaId,
            ]);
        } catch (\Throwable $e) {
            $this->jsonError('Erro ao conectar com a API Meta: ' . $e->getMessage());
        }
    }

    // ── POST /admin/whatsapp/sync ────────────────────────────────────

    public function syncFromMeta(Request $request): void
    {
        $this->requireAdmin();

        try {
            $service   = new MetaWhatsAppService();
            $response  = $service->getTemplates();

            if (isset($response['error'])) {
                $errMsg = $response['error']['message'] ?? 'Erro na API Meta.';
                $this->jsonError("Falha ao buscar templates da Meta: {$errMsg}");
            }

            $metaTemplates = $response['data'] ?? [];
            $synced        = 0;

            foreach ($metaTemplates as $mt) {
                $metaName   = $mt['name']     ?? '';
                $metaStatus = strtolower($mt['status'] ?? '');
                $metaId     = $mt['id']       ?? null;

                if ($metaName === '') {
                    continue;
                }

                $localStatus = match ($metaStatus) {
                    'approved' => 'approved',
                    'rejected' => 'rejected',
                    'pending'  => 'pending',
                    default    => 'pending',
                };

                // Find matching local template by name
                $local = \Core\Database::getInstance()->selectOne(
                    "SELECT id FROM whatsapp_templates WHERE name = ? LIMIT 1",
                    [$metaName]
                );

                if ($local) {
                    $updateData = ['status' => $localStatus];
                    if ($metaId) {
                        $updateData['meta_template_id'] = (string) $metaId;
                    }
                    if ($metaStatus === 'rejected' && isset($mt['rejected_reason'])) {
                        $updateData['rejection_reason'] = $mt['rejected_reason'];
                    }
                    WhatsappTemplate::update((int) $local['id'], $updateData);
                    $synced++;
                }
            }

            $this->jsonSuccess("Sincronização concluída. {$synced} template(s) atualizado(s).", [
                'synced' => $synced,
                'total'  => count($metaTemplates),
            ]);
        } catch (\Throwable $e) {
            $this->jsonError('Erro ao sincronizar: ' . $e->getMessage());
        }
    }
}
