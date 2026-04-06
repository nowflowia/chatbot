<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Auth;
use App\Models\Crm\CrmTask;
use App\Models\Crm\CrmDeal;
use App\Models\Crm\CrmActivity;

class CrmTaskController extends Controller
{
    public function store(Request $request): void
    {
        $data = $request->all();
        if (empty($data['title'])) {
            $this->jsonError('O título da tarefa é obrigatório.');
        }

        if (!empty($data['deal_id'])) {
            $deal = CrmDeal::find((int)$data['deal_id']);
            if (!$deal) {
                $this->jsonError('Negociação não encontrada.', [], 404);
            }
        }

        $data['created_by'] = Auth::id();
        $taskId = CrmTask::create($data);

        if (!empty($data['deal_id'])) {
            CrmActivity::log(
                (int)$data['deal_id'], Auth::id(), 'note',
                'Tarefa criada: ' . $data['title'], null, null
            );
        }

        $this->jsonSuccess('Tarefa criada.', ['task' => CrmTask::find($taskId)]);
    }

    public function update(Request $request, string $id): void
    {
        $task = CrmTask::find((int)$id);
        if (!$task) {
            $this->jsonError('Tarefa não encontrada.', [], 404);
        }

        $data = $request->all();
        if (empty($data['title'])) {
            $this->jsonError('O título da tarefa é obrigatório.');
        }

        CrmTask::update((int)$id, $data);
        $this->jsonSuccess('Tarefa atualizada.', ['task' => CrmTask::find((int)$id)]);
    }

    public function done(Request $request, string $id): void
    {
        $task = CrmTask::find((int)$id);
        if (!$task) {
            $this->jsonError('Tarefa não encontrada.', [], 404);
        }

        CrmTask::done((int)$id);

        if ($task['deal_id']) {
            CrmActivity::log(
                (int)$task['deal_id'], Auth::id(), 'task_done',
                'Tarefa concluída: ' . $task['title'], null, null
            );
        }

        $this->jsonSuccess('Tarefa marcada como concluída.');
    }

    public function destroy(Request $request, string $id): void
    {
        $task = CrmTask::find((int)$id);
        if (!$task) {
            $this->jsonError('Tarefa não encontrada.', [], 404);
        }

        CrmTask::delete((int)$id);
        $this->jsonSuccess('Tarefa excluída.');
    }
}
