<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Auth;
use Core\Database;

class AiConfigController extends Controller
{
    private const ALLOWED_DOC_MIMES = [
        'application/pdf'                                                          => 'pdf',
        'application/msword'                                                       => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'  => 'docx',
        'text/markdown'                                                            => 'md',
        'text/plain'                                                               => 'md',
        'text/x-markdown'                                                          => 'md',
    ];

    private const MAX_DOC_BYTES = 25 * 1024 * 1024; // 25 MB

    private function requireAdmin(): void
    {
        if (!Auth::isAdmin()) {
            $this->redirect(url('admin/dashboard'));
            exit;
        }
    }

    private function docsDir(): string
    {
        $dir = STORAGE_PATH . '/ai/docs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    public function index(Request $request): string
    {
        $this->requireAdmin();
        $db  = Database::getInstance();
        $tab = $request->get('tab', 'persona');

        $persona = $db->selectOne("SELECT * FROM ai_persona ORDER BY id ASC LIMIT 1") ?: ['prompt' => ''];
        $qa      = $db->select("SELECT * FROM ai_knowledge_qa ORDER BY id DESC");
        $docs    = $db->select("SELECT * FROM ai_knowledge_docs ORDER BY id DESC");
        $sites   = $db->select("SELECT * FROM ai_knowledge_sites ORDER BY id DESC");

        return $this->view('ai-config/index', [
            'activeTab' => $tab,
            'persona'   => $persona,
            'qa'        => $qa,
            'docs'      => $docs,
            'sites'     => $sites,
        ]);
    }

    // ── Persona ───────────────────────────────────────────────────

    public function savePersona(Request $request): void
    {
        $this->requireAdmin();
        $prompt = trim((string)$request->post('prompt', ''));
        $ts     = now();
        $db     = Database::getInstance();

        $row = $db->selectOne("SELECT id FROM ai_persona ORDER BY id ASC LIMIT 1");
        if ($row) {
            $db->update("UPDATE ai_persona SET prompt=?, updated_at=? WHERE id=?", [$prompt, $ts, (int)$row['id']]);
        } else {
            $db->insert("INSERT INTO ai_persona (prompt, created_at, updated_at) VALUES (?, ?, ?)", [$prompt, $ts, $ts]);
        }

        $this->jsonSuccess('Persona salva com sucesso!');
    }

    // ── Q&A ───────────────────────────────────────────────────────

    public function storeQa(Request $request): void
    {
        $this->requireAdmin();
        $q = trim((string)$request->post('question', ''));
        $a = trim((string)$request->post('answer', ''));

        if ($q === '' || $a === '') {
            $this->jsonError('Pergunta e resposta são obrigatórias.', [], 422);
        }

        $ts = now();
        Database::getInstance()->insert(
            "INSERT INTO ai_knowledge_qa (question, answer, is_active, created_at, updated_at) VALUES (?, ?, 1, ?, ?)",
            [$q, $a, $ts, $ts]
        );

        $this->jsonSuccess('Pergunta cadastrada!');
    }

    public function destroyQa(Request $request, $id): void
    {
        $this->requireAdmin();
        Database::getInstance()->delete("DELETE FROM ai_knowledge_qa WHERE id=?", [(int)$id]);
        $this->jsonSuccess('Removida.');
    }

    public function toggleQa(Request $request, $id): void
    {
        $this->requireAdmin();
        Database::getInstance()->update(
            "UPDATE ai_knowledge_qa SET is_active = 1 - is_active, updated_at=? WHERE id=?",
            [now(), (int)$id]
        );
        $this->jsonSuccess('Atualizada.');
    }

    // ── Documents ─────────────────────────────────────────────────

    public function uploadDoc(Request $request): void
    {
        $this->requireAdmin();

        if (empty($_FILES['document']['tmp_name']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonError('Falha no upload do arquivo.');
        }

        $file = $_FILES['document'];

        if ($file['size'] > self::MAX_DOC_BYTES) {
            $this->jsonError('Arquivo excede o limite de 25 MB.');
        }

        $mime = mime_content_type($file['tmp_name']) ?: ($file['type'] ?? '');
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Allow by mime OR by extension (md/doc detection on shared hosts is unreliable)
        $allowedExts = ['pdf', 'doc', 'docx', 'md', 'markdown', 'txt'];
        if (!isset(self::ALLOWED_DOC_MIMES[$mime]) && !in_array($ext, $allowedExts, true)) {
            $this->jsonError('Formato inválido. Aceitos: PDF, MD, DOC, DOCX.');
        }

        $stored = uniqid('doc_', true) . ($ext ? ".{$ext}" : '');
        $dest   = $this->docsDir() . '/' . $stored;

        if (!@move_uploaded_file($file['tmp_name'], $dest)) {
            $this->jsonError('Falha ao salvar o arquivo. Verifique permissões em storage/ai/docs.');
        }

        $ts = now();
        $id = Database::getInstance()->insert(
            "INSERT INTO ai_knowledge_docs (original_name, stored_name, mime, size_bytes, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, 1, ?, ?)",
            [$file['name'], $stored, $mime ?: 'application/octet-stream', (int)$file['size'], $ts, $ts]
        );

        $this->jsonSuccess('Documento enviado!', [
            'id'            => (int)$id,
            'original_name' => $file['name'],
            'size_bytes'    => (int)$file['size'],
        ]);
    }

    public function destroyDoc(Request $request, $id): void
    {
        $this->requireAdmin();
        $db  = Database::getInstance();
        $row = $db->selectOne("SELECT * FROM ai_knowledge_docs WHERE id=?", [(int)$id]);
        if ($row) {
            $path = $this->docsDir() . '/' . $row['stored_name'];
            if (file_exists($path)) @unlink($path);
            $db->delete("DELETE FROM ai_knowledge_docs WHERE id=?", [(int)$id]);
        }
        $this->jsonSuccess('Documento removido.');
    }

    // ── Sites ─────────────────────────────────────────────────────

    public function storeSite(Request $request): void
    {
        $this->requireAdmin();
        $url   = trim((string)$request->post('url', ''));
        $title = trim((string)$request->post('title', ''));

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->jsonError('URL inválida.', ['url' => ['Informe uma URL completa (https://…).']], 422);
        }

        $ts = now();
        Database::getInstance()->insert(
            "INSERT INTO ai_knowledge_sites (url, title, is_active, created_at, updated_at) VALUES (?, ?, 1, ?, ?)",
            [$url, $title ?: null, $ts, $ts]
        );

        $this->jsonSuccess('URL cadastrada!');
    }

    public function destroySite(Request $request, $id): void
    {
        $this->requireAdmin();
        Database::getInstance()->delete("DELETE FROM ai_knowledge_sites WHERE id=?", [(int)$id]);
        $this->jsonSuccess('URL removida.');
    }
}
