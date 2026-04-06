<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Auth;
use App\Models\Crm\CrmContact;
use App\Models\Crm\CrmCompany;

class CrmContactController extends Controller
{
    public function index(Request $request): string
    {
        $page   = max(1, (int)$request->get('page', 1));
        $search = trim($request->get('search', ''));
        $result = CrmContact::allPaginated($page, 20, $search);

        return $this->view('crm/contacts/index', [
            'contacts'  => $result['data'],
            'pagination' => $result,
            'search'    => $search,
        ]);
    }

    public function store(Request $request): void
    {
        $data = $request->all();
        if (empty($data['name'])) {
            $this->jsonError('O nome do contato é obrigatório.');
        }

        $data['created_by'] = Auth::id();
        $contactId = CrmContact::create($data);

        $this->jsonSuccess('Contato criado com sucesso.', ['contact' => CrmContact::find($contactId)]);
    }

    public function update(Request $request, string $id): void
    {
        $contact = CrmContact::find((int)$id);
        if (!$contact) {
            $this->jsonError('Contato não encontrado.', [], 404);
        }

        $data = $request->all();
        if (empty($data['name'])) {
            $this->jsonError('O nome do contato é obrigatório.');
        }

        CrmContact::update((int)$id, $data);
        $this->jsonSuccess('Contato atualizado.', ['contact' => CrmContact::find((int)$id)]);
    }

    public function destroy(Request $request, string $id): void
    {
        if (!Auth::isSupervisorOrAdmin()) {
            $this->jsonError('Sem permissão.', [], 403);
        }

        $contact = CrmContact::find((int)$id);
        if (!$contact) {
            $this->jsonError('Contato não encontrado.', [], 404);
        }

        CrmContact::delete((int)$id);
        $this->jsonSuccess('Contato excluído.');
    }
}
