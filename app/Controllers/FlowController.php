<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Auth;
use App\Models\Flow;

class FlowController extends Controller
{
    // ----------------------------------------------------------------
    // List
    // ----------------------------------------------------------------

    public function index(Request $request): string
    {
        $page   = max(1, (int)$request->get('page', 1));
        $search = trim($request->get('search', ''));
        $result = Flow::allPaginated($page, 20, $search);

        return $this->view('flows/index', [
            'flows'  => $result['data'],
            'meta'   => $result,
            'search' => $search,
        ]);
    }

    // ----------------------------------------------------------------
    // Create
    // ----------------------------------------------------------------

    public function store(Request $request): void
    {
        $name = trim($request->post('name', ''));
        $desc = trim($request->post('description', ''));
        $trigger = $request->post('trigger', 'keyword');

        if (!$name) {
            $this->jsonError('Nome é obrigatório.', ['name' => ['Campo obrigatório.']]);
        }

        $user = Auth::user();
        $slug = Flow::generateSlug($name);

        $id = \Core\Database::getInstance()->insert(
            "INSERT INTO flows (name, slug, description, `trigger`, trigger_keywords, is_active, is_default, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, 0, 0, ?, ?, ?)",
            [$name, $slug, $desc ?: null, $trigger, null, $user['id'] ?? null, now(), now()]
        );

        $flow = Flow::find((int)$id);

        $this->jsonSuccess('Fluxo criado.', ['flow' => $flow, 'redirect' => url("admin/flows/{$id}/edit")]);
    }

    // ----------------------------------------------------------------
    // Editor page
    // ----------------------------------------------------------------

    public function edit(Request $request, string $id): string
    {
        $flow = Flow::find((int)$id);
        if (!$flow) {
            $this->redirect(url('admin/flows'));
        }

        return $this->view('flows/editor', ['flow' => $flow]);
    }

    // ----------------------------------------------------------------
    // Update meta (name, trigger, active)
    // ----------------------------------------------------------------

    public function update(Request $request, string $id): void
    {
        $flow = Flow::find((int)$id);
        if (!$flow) {
            $this->jsonError('Fluxo não encontrado.', [], 404);
        }

        $name    = trim($request->post('name', ''));
        $trigger = $request->post('trigger', $flow['trigger']);
        $active  = (int)$request->post('is_active', 0);
        $keywords= trim($request->post('trigger_keywords', ''));
        $desc    = trim($request->post('description', ''));

        if (!$name) {
            $this->jsonError('Nome é obrigatório.', ['name' => ['Campo obrigatório.']]);
        }

        \Core\Database::getInstance()->update(
            "UPDATE flows SET name = ?, description = ?, `trigger` = ?, trigger_keywords = ?, is_active = ?, updated_at = ? WHERE id = ?",
            [$name, $desc ?: null, $trigger, $keywords ?: null, $active, now(), (int)$id]
        );

        $this->jsonSuccess('Fluxo atualizado.', ['flow' => Flow::find((int)$id)]);
    }

    // ----------------------------------------------------------------
    // Delete
    // ----------------------------------------------------------------

    public function destroy(Request $request, string $id): void
    {
        $flow = Flow::find((int)$id);
        if (!$flow) {
            $this->jsonError('Fluxo não encontrado.', [], 404);
        }

        $db = \Core\Database::getInstance();
        $db->delete("DELETE FROM flow_connections WHERE flow_id = ?", [(int)$id]);
        $db->delete("DELETE FROM flow_nodes      WHERE flow_id = ?", [(int)$id]);
        $db->delete("DELETE FROM flows            WHERE id      = ?", [(int)$id]);

        $this->jsonSuccess('Fluxo excluído.');
    }

    // ----------------------------------------------------------------
    // Builder — load data
    // ----------------------------------------------------------------

    public function getBuilderData(Request $request, string $id): void
    {
        $flow = Flow::getWithNodes((int)$id);
        if (!$flow) {
            $this->jsonError('Fluxo não encontrado.', [], 404);
        }
        $this->jsonSuccess('OK', ['flow' => $flow]);
    }

    // ----------------------------------------------------------------
    // Builder — save canvas
    // ----------------------------------------------------------------

    public function saveBuilder(Request $request, string $id): void
    {
        $flow = Flow::find((int)$id);
        if (!$flow) {
            $this->jsonError('Fluxo não encontrado.', [], 404);
        }

        $nodes       = $request->post('nodes', []);
        $connections = $request->post('connections', []);

        if (!is_array($nodes)) {
            $nodes = json_decode($nodes, true) ?? [];
        }
        if (!is_array($connections)) {
            $connections = json_decode($connections, true) ?? [];
        }

        Flow::saveBuilder((int)$id, $nodes, $connections);

        $this->jsonSuccess('Fluxo salvo com sucesso.');
    }
}
