<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Auth;
use App\Models\Crm\CrmPipeline;
use App\Models\Crm\CrmDeal;
use App\Models\User;

class CrmController extends Controller
{
    public function index(Request $request): string
    {
        $pipelines = CrmPipeline::allActive();
        if (empty($pipelines)) {
            return $this->view('crm/index', [
                'pipelines'    => [],
                'pipeline'     => null,
                'stages'       => [],
                'deals'        => [],
                'summary'      => [],
                'agents'       => [],
                'filters'      => [],
                'selectedPipelineId' => null,
            ]);
        }

        $selectedId = (int)$request->get('pipeline', $pipelines[0]['id']);
        $pipeline   = CrmPipeline::getWithStages($selectedId) ?? CrmPipeline::getWithStages($pipelines[0]['id']);

        $isAgent  = Auth::hasRole('agent');
        $userId   = Auth::id();
        $filters  = [
            'assigned_to' => (int)$request->get('assigned_to', 0),
            'search'      => trim($request->get('search', '')),
            'date_from'   => $request->get('date_from', ''),
            'date_to'     => $request->get('date_to', ''),
        ];

        $deals   = CrmDeal::kanban($pipeline['id'], $filters, $userId, $isAgent);
        $summary = CrmDeal::summary($pipeline['id'], $filters, $userId, $isAgent);
        $agents  = Auth::isSupervisorOrAdmin() ? User::allAgents() : [];

        return $this->view('crm/index', [
            'pipelines'          => $pipelines,
            'pipeline'           => $pipeline,
            'stages'             => $pipeline['stages'] ?? [],
            'deals'              => $deals,
            'summary'            => $summary,
            'agents'             => $agents,
            'filters'            => $filters,
            'selectedPipelineId' => $pipeline['id'],
        ]);
    }

    public function board(Request $request, string $pipelineId): void
    {
        $pipeline = CrmPipeline::getWithStages((int)$pipelineId);
        if (!$pipeline) {
            $this->jsonError('Pipeline não encontrado.', [], 404);
        }

        $isAgent = Auth::hasRole('agent');
        $userId  = Auth::id();
        $filters = [
            'assigned_to' => (int)$request->get('assigned_to', 0),
            'search'      => trim($request->get('search', '')),
            'date_from'   => $request->get('date_from', ''),
            'date_to'     => $request->get('date_to', ''),
        ];

        $deals   = CrmDeal::kanban($pipeline['id'], $filters, $userId, $isAgent);
        $summary = CrmDeal::summary($pipeline['id'], $filters, $userId, $isAgent);

        $this->jsonSuccess('OK', [
            'pipeline' => $pipeline,
            'stages'   => $pipeline['stages'],
            'deals'    => $deals,
            'summary'  => $summary,
        ]);
    }
}
