<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Database;
use App\Models\CompanySetting;

class WebhookLogController extends Controller
{
    public function index(Request $request): string
    {
        $db      = Database::getInstance();
        $page    = max(1, (int)$request->get('page', 1));
        $perPage = 50;
        $offset  = ($page - 1) * $perPage;
        $status  = $request->get('status', '');
        $search  = trim($request->get('search', ''));

        $where  = '1=1';
        $params = [];

        if ($status) {
            $where   .= ' AND status = ?';
            $params[] = $status;
        }
        if ($search) {
            $where   .= ' AND (from_number LIKE ? OR message_id LIKE ? OR event_type LIKE ?)';
            $like     = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $total = (int)($db->selectOne(
            "SELECT COUNT(*) as cnt FROM webhook_logs WHERE {$where}", $params
        )['cnt'] ?? 0);

        $logs = $db->select(
            "SELECT * FROM webhook_logs WHERE {$where} ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $lastPage = max(1, (int)ceil($total / $perPage));

        $company        = CompanySetting::get();
        $loggingEnabled = (bool)($company['webhook_logging'] ?? 1);

        return $this->view('webhook_logs/index', [
            'logs'           => $logs,
            'total'          => $total,
            'page'           => $page,
            'lastPage'       => $lastPage,
            'perPage'        => $perPage,
            'status'         => $status,
            'search'         => $search,
            'loggingEnabled' => $loggingEnabled,
        ]);
    }

    public function show(Request $request, string $id): void
    {
        $db  = Database::getInstance();
        $log = $db->selectOne("SELECT * FROM webhook_logs WHERE id = ? LIMIT 1", [(int)$id]);

        if (!$log) {
            $this->jsonError('Log não encontrado.', [], 404);
        }

        $this->jsonSuccess('OK', ['log' => $log]);
    }

    public function clear(Request $request): void
    {
        $days = (int)$request->post('days', -1);
        $db   = Database::getInstance();

        if ($days <= 0) {
            // Clear all
            $affected = $db->delete("DELETE FROM webhook_logs");
            $this->jsonSuccess("Todos os logs foram removidos ({$affected} registros).");
        } else {
            $affected = $db->delete(
                "DELETE FROM webhook_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
                [$days]
            );
            $this->jsonSuccess("Logs com mais de {$days} dias removidos ({$affected} registros).");
        }
    }

    public function toggleLogging(Request $request): void
    {
        try {
            $company = CompanySetting::get();
            $current = (bool)($company['webhook_logging'] ?? 1);
            $new     = $current ? 0 : 1;

            $db = Database::getInstance();
            if ($company) {
                $db->update(
                    "UPDATE company_settings SET webhook_logging = ? WHERE id = ?",
                    [$new, $company['id']]
                );
            } else {
                $db->insert(
                    "INSERT INTO company_settings (webhook_logging, updated_at) VALUES (?, NOW())",
                    [$new]
                );
            }

            $label = $new ? 'habilitado' : 'desabilitado';
            $this->jsonSuccess("Log de webhook {$label}.", ['enabled' => (bool)$new]);
        } catch (\Throwable $e) {
            $this->jsonError('Erro: ' . $e->getMessage());
        }
    }
}
