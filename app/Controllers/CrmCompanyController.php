<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Auth;
use App\Models\Crm\CrmCompany;
use App\Models\Crm\CrmContact;
use App\Models\Crm\CrmDeal;
use Core\Database;

class CrmCompanyController extends Controller
{
    public function index(Request $request): string
    {
        $page   = max(1, (int)$request->get('page', 1));
        $search = trim($request->get('search', ''));
        $result = CrmCompany::allPaginated($page, 20, $search);

        return $this->view('crm/companies/index', [
            'companies'   => $result['data'],
            'pagination'  => $result,
            'search'      => $search,
        ]);
    }

    public function show(Request $request, string $id): string
    {
        $company = CrmCompany::find((int)$id);
        if (!$company) {
            return $this->view('errors/404');
        }

        $contacts = CrmContact::byCompany((int)$id);
        $deals    = Database::getInstance()->select(
            "SELECT d.*, s.name as stage_name, s.color as stage_color
             FROM crm_deals d
             LEFT JOIN crm_stages s ON s.id = d.stage_id
             WHERE d.company_id = ? ORDER BY d.created_at DESC",
            [(int)$id]
        );

        return $this->view('crm/companies/show', [
            'company'  => $company,
            'contacts' => $contacts,
            'deals'    => $deals,
        ]);
    }

    public function store(Request $request): void
    {
        $data = $request->all();
        if (empty($data['name'])) {
            $this->jsonError('O nome da empresa é obrigatório.');
        }

        $data['created_by'] = Auth::id();
        $companyId = CrmCompany::create($data);

        $this->jsonSuccess('Empresa criada com sucesso.', ['company' => CrmCompany::find($companyId)]);
    }

    public function update(Request $request, string $id): void
    {
        $company = CrmCompany::find((int)$id);
        if (!$company) {
            $this->jsonError('Empresa não encontrada.', [], 404);
        }

        $data = $request->all();
        if (empty($data['name'])) {
            $this->jsonError('O nome da empresa é obrigatório.');
        }

        CrmCompany::update((int)$id, $data);
        $this->jsonSuccess('Empresa atualizada.', ['company' => CrmCompany::find((int)$id)]);
    }

    public function destroy(Request $request, string $id): void
    {
        if (!Auth::isSupervisorOrAdmin()) {
            $this->jsonError('Sem permissão.', [], 403);
        }

        $company = CrmCompany::find((int)$id);
        if (!$company) {
            $this->jsonError('Empresa não encontrada.', [], 404);
        }

        CrmCompany::delete((int)$id);
        $this->jsonSuccess('Empresa excluída.');
    }
}
