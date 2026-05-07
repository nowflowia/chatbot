<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Auth;
use Core\Database;

class CrmAdminController extends Controller
{
    private const CSV_LIMIT = 1000;

    private function requireAdmin(): void
    {
        if (!Auth::isAdmin()) {
            $this->redirect(url('admin/dashboard'));
            exit;
        }
    }

    // ── GET /admin/crm-admin ─────────────────────────────────────────

    public function index(Request $request): string
    {
        $this->requireAdmin();

        return $this->view('crm_admin/index', []);
    }

    // ── GET /admin/crm-admin/contacts/template ───────────────────────

    public function contactsTemplate(Request $request): void
    {
        $this->requireAdmin();

        $filename = 'modelo_contatos.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $out = fopen('php://output', 'w');
        // BOM for Excel UTF-8 compatibility
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['nome', 'telefone', 'email', 'observacoes'], ';');
        fputcsv($out, ['João Silva', '11999990001', 'joao@email.com', 'Cliente VIP'], ';');
        fputcsv($out, ['Maria Souza', '21988880002', 'maria@email.com', ''], ';');
        fclose($out);
        exit;
    }

    // ── POST /admin/crm-admin/contacts/import ────────────────────────

    public function importContacts(Request $request): void
    {
        $this->requireAdmin();

        $file = $_FILES['csv_file'] ?? null;

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->jsonError('Nenhum arquivo enviado ou erro no upload.', [], 422);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'txt'], true)) {
            $this->jsonError('O arquivo deve ser um CSV (.csv).', [], 422);
        }

        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            $this->jsonError('Não foi possível ler o arquivo.', [], 500);
        }

        // Strip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Detect delimiter
        $firstLine = fgets($handle);
        rewind($handle);
        if ($bom === "\xEF\xBB\xBF") {
            fread($handle, 3);
        }
        $delimiter = str_contains($firstLine, ';') ? ';' : ',';

        // Read header row
        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header) {
            fclose($handle);
            $this->jsonError('O arquivo está vazio ou mal formatado.', [], 422);
        }

        // Normalize header keys
        $header = array_map(fn($h) => strtolower(trim(preg_replace('/\s+/', '_', $h))), $header);

        $colMap = [];
        $aliases = [
            'nome'        => ['nome', 'name', 'contato', 'cliente'],
            'telefone'    => ['telefone', 'phone', 'celular', 'fone', 'tel'],
            'email'       => ['email', 'e-mail', 'e_mail'],
            'observacoes' => ['observacoes', 'observações', 'notes', 'obs', 'nota', 'notas'],
        ];

        foreach ($aliases as $field => $options) {
            foreach ($options as $alias) {
                $idx = array_search($alias, $header, true);
                if ($idx !== false) {
                    $colMap[$field] = $idx;
                    break;
                }
            }
        }

        if (!isset($colMap['nome']) && !isset($colMap['telefone'])) {
            fclose($handle);
            $this->jsonError(
                'O CSV não possui as colunas esperadas. Baixe o modelo e use-o como referência.',
                [],
                422
            );
        }

        $db      = Database::getInstance();
        $row     = 1;
        $inserted = 0;
        $updated  = 0;
        $skipped  = 0;
        $errors   = [];

        while (($cols = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($row > self::CSV_LIMIT) {
                $errors[] = "Limite de " . self::CSV_LIMIT . " registros atingido. O restante foi ignorado.";
                break;
            }

            $name  = isset($colMap['nome'])     ? trim($cols[$colMap['nome']]     ?? '') : '';
            $phone = isset($colMap['telefone'])  ? trim($cols[$colMap['telefone']] ?? '') : '';
            $email = isset($colMap['email'])     ? trim($cols[$colMap['email']]    ?? '') : '';
            $notes = isset($colMap['observacoes']) ? trim($cols[$colMap['observacoes']] ?? '') : '';

            // Normalize phone digits
            $phoneDigits = preg_replace('/\D/', '', $phone);

            if ($name === '' && $phoneDigits === '') {
                $row++;
                continue; // blank row
            }

            if ($phoneDigits === '') {
                $errors[] = "Linha {$row}: telefone obrigatório quando nome está preenchido.";
                $skipped++;
                $row++;
                continue;
            }

            if (strlen($phoneDigits) < 10 || strlen($phoneDigits) > 15) {
                $errors[] = "Linha {$row}: telefone inválido '{$phone}'.";
                $skipped++;
                $row++;
                continue;
            }

            $name = $name ?: $phoneDigits;

            // Upsert by normalized phone
            $existing = $db->selectOne(
                "SELECT id FROM contacts WHERE REGEXP_REPLACE(phone, '[^0-9]', '') = ? LIMIT 1",
                [$phoneDigits]
            );

            $ts = now();

            if ($existing) {
                $sets = ['name = ?', 'updated_at = ?'];
                $bindings = [$name, $ts];
                if ($email !== '') { $sets[] = 'email = ?'; $bindings[] = $email; }
                if ($notes !== '') { $sets[] = 'notes = ?'; $bindings[] = $notes; }
                $bindings[] = $existing['id'];
                $db->update(
                    "UPDATE contacts SET " . implode(', ', $sets) . " WHERE id = ?",
                    $bindings
                );
                $updated++;
            } else {
                $db->insert(
                    "INSERT INTO contacts (name, phone, email, notes, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [$name, $phoneDigits, $email ?: null, $notes ?: null, $ts, $ts]
                );
                $inserted++;
            }

            $row++;
        }

        fclose($handle);

        $total   = $inserted + $updated + $skipped;
        $message = "Importação concluída: {$inserted} inserido(s), {$updated} atualizado(s), {$skipped} ignorado(s) de {$total} linha(s).";

        $this->jsonSuccess($message, [
            'inserted' => $inserted,
            'updated'  => $updated,
            'skipped'  => $skipped,
            'errors'   => array_slice($errors, 0, 20),
        ]);
    }
}
