<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;

class SystemUpdateController extends Controller
{
    private function projectRoot(): string
    {
        // public/index.php → two levels up = project root
        return dirname(PUBLIC_PATH);
    }

    // ---------------------------------------------------------------
    // GET /admin/system-update
    // ---------------------------------------------------------------
    public function index(Request $request): string
    {
        $root       = $this->projectRoot();
        $gitVersion = $this->gitVersion($root);
        $lastCommit = $this->lastCommit($root);

        return $this->view('system/update', [
            'gitVersion' => $gitVersion,
            'lastCommit' => $lastCommit,
            'gitAvailable' => $gitVersion !== null,
        ]);
    }

    // ---------------------------------------------------------------
    // POST /admin/system-update/pull  — run git pull
    // ---------------------------------------------------------------
    public function pull(Request $request): void
    {
        $this->requireAjax();

        $root = $this->projectRoot();

        if (!is_dir($root . '/.git')) {
            $this->jsonError('Repositório Git não encontrado nesta instalação.', [], 500);
        }

        $gitBin = $this->findGit();
        if (!$gitBin) {
            $this->jsonError('Git não está disponível no servidor.', [], 500);
        }

        // Run git pull
        $cmd    = escapeshellarg($gitBin) . ' -C ' . escapeshellarg($root) . ' pull --ff-only 2>&1';
        $output = shell_exec($cmd);
        $output = trim($output ?? '');

        // Detect success
        $success = str_contains($output, 'Already up to date')
                || str_contains($output, 'Already up-to-date')
                || str_contains($output, 'Fast-forward')
                || preg_match('/\d+ file.* changed/', $output);

        if (!$success && str_contains(strtolower($output), 'error')) {
            $this->jsonError('Erro ao atualizar: ' . $output, [], 500);
        }

        $lastCommit = $this->lastCommit($root);

        $this->jsonSuccess(
            str_contains($output, 'Already up to date') || str_contains($output, 'Already up-to-date')
                ? 'Sistema já está na versão mais recente.'
                : 'Sistema atualizado com sucesso!',
            [
                'output'     => $output,
                'last_commit'=> $lastCommit,
            ]
        );
    }

    // ---------------------------------------------------------------
    // POST /admin/system-update/status  — check if updates available
    // ---------------------------------------------------------------
    public function status(Request $request): void
    {
        $this->requireAjax();

        $root = $this->projectRoot();

        if (!is_dir($root . '/.git')) {
            $this->jsonError('Repositório Git não encontrado.', [], 500);
        }

        $gitBin = $this->findGit();
        if (!$gitBin) {
            $this->jsonError('Git não disponível.', [], 500);
        }

        // Fetch remote silently
        $fetchCmd = escapeshellarg($gitBin) . ' -C ' . escapeshellarg($root) . ' fetch --quiet 2>&1';
        shell_exec($fetchCmd);

        // Compare local HEAD vs remote
        $local  = trim(shell_exec(escapeshellarg($gitBin) . ' -C ' . escapeshellarg($root) . ' rev-parse HEAD 2>&1') ?? '');
        $remote = trim(shell_exec(escapeshellarg($gitBin) . ' -C ' . escapeshellarg($root) . ' rev-parse @{u} 2>&1') ?? '');

        $upToDate = ($local === $remote) || empty($remote);

        // Count pending commits
        $pending = 0;
        if (!$upToDate) {
            $count = trim(shell_exec(
                escapeshellarg($gitBin) . ' -C ' . escapeshellarg($root) . ' rev-list HEAD..@{u} --count 2>&1'
            ) ?? '0');
            $pending = (int)$count;
        }

        $this->jsonSuccess('OK', [
            'up_to_date'  => $upToDate,
            'pending'     => $pending,
            'local_hash'  => substr($local, 0, 7),
            'remote_hash' => substr($remote, 0, 7),
            'last_commit' => $this->lastCommit($root),
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────

    private function findGit(): ?string
    {
        foreach (['/usr/bin/git', '/usr/local/bin/git', '/opt/homebrew/bin/git'] as $path) {
            if (is_executable($path)) return $path;
        }
        $which = trim(shell_exec('which git 2>/dev/null') ?? '');
        return $which ?: null;
    }

    private function gitVersion(string $root): ?string
    {
        $git = $this->findGit();
        if (!$git || !is_dir($root . '/.git')) return null;
        $v = trim(shell_exec(escapeshellarg($git) . ' --version 2>&1') ?? '');
        return $v ?: null;
    }

    private function lastCommit(string $root): array
    {
        $git = $this->findGit();
        if (!$git || !is_dir($root . '/.git')) return [];
        $log = trim(shell_exec(
            escapeshellarg($git) . ' -C ' . escapeshellarg($root) .
            ' log -1 --format="%h|%s|%an|%ar" 2>&1'
        ) ?? '');
        if (!$log || str_contains($log, 'fatal')) return [];
        $parts = explode('|', $log, 4);
        return [
            'hash'    => $parts[0] ?? '',
            'subject' => $parts[1] ?? '',
            'author'  => $parts[2] ?? '',
            'date'    => $parts[3] ?? '',
        ];
    }
}
