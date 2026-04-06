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
        $gitBin     = $this->findGit();
        $gitVersion = $this->gitVersion($root);
        $lastCommit = $this->lastCommit($root);
        $hasRepo    = is_dir($root . '/.git');
        $execEnabled= $this->run('echo ok') === 'ok';

        return $this->view('system/update', [
            'gitVersion'  => $gitVersion,
            'lastCommit'  => $lastCommit,
            'gitAvailable'=> $gitVersion !== null,
            'gitBin'      => $gitBin,
            'hasRepo'     => $hasRepo,
            'execEnabled' => $execEnabled,
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
        $output = $this->run(escapeshellarg($gitBin) . ' -C ' . escapeshellarg($root) . ' pull --ff-only');
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

        $g = escapeshellarg($gitBin) . ' -C ' . escapeshellarg($root);

        // Fetch remote silently
        $this->run("$g fetch --quiet");

        // Compare local HEAD vs remote
        $local  = trim($this->run("$g rev-parse HEAD") ?? '');
        $remote = trim($this->run("$g rev-parse @{u}") ?? '');

        $upToDate = ($local === $remote) || empty($remote) || str_contains($remote, 'fatal');

        // Count pending commits
        $pending = 0;
        if (!$upToDate) {
            $count   = trim($this->run("$g rev-list HEAD..@{u} --count") ?? '0');
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
        $candidates = [
            '/usr/bin/git',
            '/usr/local/bin/git',
            '/usr/local/cpanel/3rdparty/bin/git',
            '/opt/cpanel/ea-git/root/usr/bin/git',
            '/opt/cpanel/ea-git/root/usr/libexec/git-core/git',
            '/opt/homebrew/bin/git',
            '/usr/libexec/git-core/git',
        ];
        foreach ($candidates as $path) {
            if (@is_executable($path)) return $path;
        }
        // try which/where via exec (less likely to be disabled)
        $which = $this->run('which git');
        if ($which && str_starts_with($which, '/') && @is_executable(trim($which))) {
            return trim($which);
        }
        return null;
    }

    private function gitVersion(string $root): ?string
    {
        $git = $this->findGit();
        if (!$git || !is_dir($root . '/.git')) return null;
        $v = $this->run(escapeshellarg($git) . ' --version');
        return ($v && !str_contains($v, 'not found')) ? trim($v) : null;
    }

    private function lastCommit(string $root): array
    {
        $git = $this->findGit();
        if (!$git || !is_dir($root . '/.git')) return [];
        $log = $this->run(
            escapeshellarg($git) . ' -C ' . escapeshellarg($root) .
            ' log -1 --format="%h|%s|%an|%ar"'
        );
        if (!$log || str_contains($log, 'fatal')) return [];
        $parts = explode('|', trim($log), 4);
        return [
            'hash'    => $parts[0] ?? '',
            'subject' => $parts[1] ?? '',
            'author'  => $parts[2] ?? '',
            'date'    => $parts[3] ?? '',
        ];
    }

    /**
     * Run a shell command using whichever function is available.
     * Returns trimmed stdout+stderr, or null if all disabled.
     */
    private function run(string $cmd): ?string
    {
        $cmd .= ' 2>&1';
        if (function_exists('shell_exec') && !in_array('shell_exec', array_map('trim', explode(',', ini_get('disable_functions'))))) {
            $out = @shell_exec($cmd);
            if ($out !== null) return trim($out);
        }
        if (function_exists('exec')) {
            $lines = [];
            @exec($cmd, $lines);
            if ($lines) return trim(implode("\n", $lines));
        }
        if (function_exists('system')) {
            ob_start();
            @system($cmd);
            $out = ob_get_clean();
            if ($out !== false && $out !== '') return trim($out);
        }
        return null;
    }
}
