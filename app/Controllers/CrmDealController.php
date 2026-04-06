<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Auth;
use App\Models\Crm\CrmDeal;
use App\Models\Crm\CrmStage;
use App\Models\Crm\CrmActivity;
use App\Models\Crm\CrmPipeline;
use App\Models\Crm\CrmCompany;
use App\Models\Crm\CrmContact;
use App\Models\User;
use Core\Database;

class CrmDealController extends Controller
{
    public function show(Request $request, string $id): string
    {
        $deal = CrmDeal::getWithRelations((int)$id);
        if (!$deal) {
            return $this->view('errors/404');
        }

        $isAgent = Auth::hasRole('agent');
        if ($isAgent && $deal['assigned_to'] != Auth::id()) {
            return $this->view('errors/403');
        }

        $pipeline = CrmPipeline::getWithStages($deal['pipeline_id']);
        $agents   = Auth::isSupervisorOrAdmin() ? User::allAgents() : [];
        $companies = CrmCompany::search('', 50);
        $contacts  = $deal['company_id'] ? CrmContact::byCompany($deal['company_id']) : [];

        return $this->view('crm/deal', [
            'deal'      => $deal,
            'pipeline'  => $pipeline,
            'stages'    => $pipeline['stages'] ?? [],
            'agents'    => $agents,
            'companies' => $companies,
            'contacts'  => $contacts,
        ]);
    }

    public function store(Request $request): void
    {
        $data = $request->all();

        if (empty($data['title']) || empty($data['pipeline_id']) || empty($data['stage_id'])) {
            $this->jsonError('Título, pipeline e etapa são obrigatórios.');
        }

        $data['created_by'] = Auth::id();
        if (Auth::hasRole('agent')) {
            $data['assigned_to'] = Auth::id();
        }

        $dealId = CrmDeal::create($data);
        $deal   = CrmDeal::find($dealId);

        CrmActivity::log($dealId, Auth::id(), 'note', 'Negociação criada', null, null);

        $this->jsonSuccess('Negociação criada com sucesso.', ['deal' => $deal]);
    }

    public function update(Request $request, string $id): void
    {
        $deal = CrmDeal::find((int)$id);
        if (!$deal) {
            $this->jsonError('Negociação não encontrada.', [], 404);
        }

        $isAgent = Auth::hasRole('agent');
        if ($isAgent && $deal['assigned_to'] != Auth::id()) {
            $this->jsonError('Sem permissão.', [], 403);
        }

        CrmDeal::update((int)$id, $request->all());
        $this->jsonSuccess('Negociação atualizada.', ['deal' => CrmDeal::find((int)$id)]);
    }

    public function destroy(Request $request, string $id): void
    {
        if (!Auth::isSupervisorOrAdmin()) {
            $this->jsonError('Sem permissão.', [], 403);
        }

        $deal = CrmDeal::find((int)$id);
        if (!$deal) {
            $this->jsonError('Negociação não encontrada.', [], 404);
        }

        CrmDeal::delete((int)$id);
        $this->jsonSuccess('Negociação excluída.');
    }

    public function moveStage(Request $request, string $id): void
    {
        $stageId = (int)$request->get('stage_id');
        if (!$stageId) {
            $this->jsonError('stage_id é obrigatório.');
        }

        $deal = CrmDeal::find((int)$id);
        if (!$deal) {
            $this->jsonError('Negociação não encontrada.', [], 404);
        }

        $isAgent = Auth::hasRole('agent');
        if ($isAgent && $deal['assigned_to'] != Auth::id()) {
            $this->jsonError('Sem permissão.', [], 403);
        }

        $change = CrmDeal::moveStage((int)$id, $stageId);
        if (!$change) {
            $this->jsonError('Etapa inválida.');
        }

        CrmActivity::log(
            (int)$id, Auth::id(), 'stage_change',
            "Etapa alterada: {$change['from']} → {$change['to']}",
            null,
            ['from_stage' => $change['from'], 'to_stage' => $change['to']]
        );

        $this->jsonSuccess('Etapa atualizada.', ['deal' => CrmDeal::find((int)$id)]);
    }

    public function win(Request $request, string $id): void
    {
        $deal = CrmDeal::find((int)$id);
        if (!$deal) {
            $this->jsonError('Negociação não encontrada.', [], 404);
        }

        CrmDeal::win((int)$id);
        CrmActivity::log((int)$id, Auth::id(), 'status_change', 'Negociação marcada como Ganha', null, ['status' => 'won']);

        $this->jsonSuccess('Negociação marcada como ganha.', ['deal' => CrmDeal::find((int)$id)]);
    }

    public function lose(Request $request, string $id): void
    {
        $deal = CrmDeal::find((int)$id);
        if (!$deal) {
            $this->jsonError('Negociação não encontrada.', [], 404);
        }

        $reason = trim($request->get('lost_reason', ''));
        CrmDeal::lose((int)$id, $reason);
        CrmActivity::log((int)$id, Auth::id(), 'status_change', 'Negociação marcada como Perdida', $reason ?: null, ['status' => 'lost']);

        $this->jsonSuccess('Negociação marcada como perdida.', ['deal' => CrmDeal::find((int)$id)]);
    }

    public function addNote(Request $request, string $id): void
    {
        $deal = CrmDeal::find((int)$id);
        if (!$deal) {
            $this->jsonError('Negociação não encontrada.', [], 404);
        }

        $body = trim($request->get('body', ''));
        if ($body === '') {
            $this->jsonError('A anotação não pode estar vazia.');
        }

        CrmActivity::log((int)$id, Auth::id(), 'note', 'Anotação', $body, null);

        $this->jsonSuccess('Anotação adicionada.', [
            'activities' => CrmActivity::byDeal((int)$id),
        ]);
    }

    public function uploadFile(Request $request, string $id): void
    {
        $deal = CrmDeal::find((int)$id);
        if (!$deal) {
            $this->jsonError('Negociação não encontrada.', [], 404);
        }

        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonError('Nenhum arquivo enviado ou erro no upload.');
        }

        $file        = $_FILES['file'];
        $uploadDir   = BASE_PATH . '/storage/crm/' . (int)$id . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext         = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename    = uniqid('f_', true) . ($ext ? '.' . $ext : '');
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $this->jsonError('Falha ao salvar o arquivo.');
        }

        $db = Database::getInstance();
        $db->insert(
            "INSERT INTO crm_deal_files (deal_id, filename, original_name, mime_type, size, uploaded_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())",
            [(int)$id, $filename, $file['name'], $file['type'], $file['size'], Auth::id()]
        );

        CrmActivity::log((int)$id, Auth::id(), 'file', 'Arquivo enviado: ' . $file['name'], null, null);

        $files = $db->select(
            "SELECT f.*, u.name as uploaded_by_name
             FROM crm_deal_files f LEFT JOIN users u ON u.id = f.uploaded_by
             WHERE f.deal_id = ? ORDER BY f.created_at DESC",
            [(int)$id]
        );

        $this->jsonSuccess('Arquivo enviado.', ['files' => $files]);
    }

    public function deleteFile(Request $request, string $id, string $fileId): void
    {
        if (!Auth::isSupervisorOrAdmin()) {
            $this->jsonError('Sem permissão.', [], 403);
        }

        $db   = Database::getInstance();
        $file = $db->selectOne("SELECT * FROM crm_deal_files WHERE id = ? AND deal_id = ?", [(int)$fileId, (int)$id]);
        if (!$file) {
            $this->jsonError('Arquivo não encontrado.', [], 404);
        }

        $path = BASE_PATH . '/storage/crm/' . (int)$id . '/' . $file['filename'];
        if (file_exists($path)) {
            unlink($path);
        }

        $db->delete("DELETE FROM crm_deal_files WHERE id = ?", [(int)$fileId]);
        $this->jsonSuccess('Arquivo excluído.');
    }
}
