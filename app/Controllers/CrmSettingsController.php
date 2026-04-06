<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Auth;
use App\Models\Crm\CrmPipeline;
use App\Models\Crm\CrmStage;
use Core\Database;

class CrmSettingsController extends Controller
{
    public function index(Request $request): string
    {
        if (!Auth::isSupervisorOrAdmin()) {
            return $this->view('errors/403');
        }

        $pipelines = Database::getInstance()->select(
            "SELECT p.*, COUNT(s.id) as stage_count, COUNT(d.id) as deal_count
             FROM crm_pipelines p
             LEFT JOIN crm_stages s ON s.pipeline_id = p.id
             LEFT JOIN crm_deals  d ON d.pipeline_id = p.id
             GROUP BY p.id ORDER BY p.sort_order ASC, p.id ASC"
        );

        foreach ($pipelines as &$p) {
            $p['stages'] = CrmStage::byPipeline($p['id']);
        }
        unset($p);

        return $this->view('crm/settings/index', [
            'pipelines' => $pipelines,
        ]);
    }

    /* ── Pipelines ─────────────────────────────────────────────── */

    public function storePipeline(Request $request): void
    {
        if (!Auth::isSupervisorOrAdmin()) {
            $this->jsonError('Sem permissão.', [], 403);
        }

        $data = $request->all();
        if (empty($data['name'])) {
            $this->jsonError('O nome do pipeline é obrigatório.');
        }

        $data['slug']       = CrmPipeline::generateSlug($data['name']);
        $data['created_by'] = Auth::id();

        $id = CrmPipeline::create($data);
        $this->jsonSuccess('Pipeline criado.', ['pipeline' => CrmPipeline::getWithStages($id)]);
    }

    public function updatePipeline(Request $request, string $id): void
    {
        if (!Auth::isSupervisorOrAdmin()) {
            $this->jsonError('Sem permissão.', [], 403);
        }

        $pipeline = CrmPipeline::find((int)$id);
        if (!$pipeline) {
            $this->jsonError('Pipeline não encontrado.', [], 404);
        }

        $data = $request->all();
        if (empty($data['name'])) {
            $this->jsonError('O nome é obrigatório.');
        }

        if ($data['slug'] !== $pipeline['slug']) {
            $data['slug'] = CrmPipeline::generateSlug($data['name']);
        }

        CrmPipeline::update((int)$id, $data);
        $this->jsonSuccess('Pipeline atualizado.', ['pipeline' => CrmPipeline::getWithStages((int)$id)]);
    }

    public function destroyPipeline(Request $request, string $id): void
    {
        if (!Auth::isSupervisorOrAdmin()) {
            $this->jsonError('Sem permissão.', [], 403);
        }

        $pipeline = CrmPipeline::find((int)$id);
        if (!$pipeline) {
            $this->jsonError('Pipeline não encontrado.', [], 404);
        }

        $dealCount = (int)(Database::getInstance()->selectOne(
            "SELECT COUNT(*) as cnt FROM crm_deals WHERE pipeline_id = ?", [(int)$id]
        )['cnt'] ?? 0);

        if ($dealCount > 0) {
            $this->jsonError("Este pipeline possui {$dealCount} negociação(ões) e não pode ser excluído.");
        }

        CrmPipeline::delete((int)$id);
        $this->jsonSuccess('Pipeline excluído.');
    }

    /* ── Stages ─────────────────────────────────────────────────── */

    public function storeStage(Request $request): void
    {
        if (!Auth::isSupervisorOrAdmin()) {
            $this->jsonError('Sem permissão.', [], 403);
        }

        $data = $request->all();
        if (empty($data['name']) || empty($data['pipeline_id'])) {
            $this->jsonError('Nome e pipeline são obrigatórios.');
        }

        // Set sort_order to end of current stages
        $max = (int)(Database::getInstance()->selectOne(
            "SELECT COALESCE(MAX(sort_order),0) as m FROM crm_stages WHERE pipeline_id = ?",
            [(int)$data['pipeline_id']]
        )['m'] ?? 0);
        $data['sort_order'] = $max + 1;

        $stageId = CrmStage::create($data);
        $this->jsonSuccess('Etapa criada.', ['stage' => CrmStage::find($stageId)]);
    }

    public function updateStage(Request $request, string $id): void
    {
        if (!Auth::isSupervisorOrAdmin()) {
            $this->jsonError('Sem permissão.', [], 403);
        }

        $stage = CrmStage::find((int)$id);
        if (!$stage) {
            $this->jsonError('Etapa não encontrada.', [], 404);
        }

        $data = $request->all();
        if (empty($data['name'])) {
            $this->jsonError('O nome é obrigatório.');
        }

        CrmStage::update((int)$id, $data);
        $this->jsonSuccess('Etapa atualizada.', ['stage' => CrmStage::find((int)$id)]);
    }

    public function reorderStages(Request $request): void
    {
        if (!Auth::isSupervisorOrAdmin()) {
            $this->jsonError('Sem permissão.', [], 403);
        }

        $ids = $request->get('ids', []);
        if (!is_array($ids) || empty($ids)) {
            $this->jsonError('Lista de IDs inválida.');
        }

        CrmStage::reorder(array_map('intval', $ids));
        $this->jsonSuccess('Ordem das etapas atualizada.');
    }

    public function destroyStage(Request $request, string $id): void
    {
        if (!Auth::isSupervisorOrAdmin()) {
            $this->jsonError('Sem permissão.', [], 403);
        }

        $stage = CrmStage::find((int)$id);
        if (!$stage) {
            $this->jsonError('Etapa não encontrada.', [], 404);
        }

        $dealCount = (int)(Database::getInstance()->selectOne(
            "SELECT COUNT(*) as cnt FROM crm_deals WHERE stage_id = ?", [(int)$id]
        )['cnt'] ?? 0);

        if ($dealCount > 0) {
            $this->jsonError("Esta etapa possui {$dealCount} negociação(ões). Mova-as antes de excluir.");
        }

        CrmStage::delete((int)$id);
        $this->jsonSuccess('Etapa excluída.');
    }
}
