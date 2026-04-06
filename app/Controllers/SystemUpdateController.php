<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Auth;

class SystemUpdateController extends Controller
{
    private const GITHUB_REPO = 'nowflowia/chatbot';
    private const GITHUB_BRANCH = 'main';

    private function projectRoot(): string
    {
        return dirname(PUBLIC_PATH);
    }

    // ---------------------------------------------------------------
    // GET /admin/system-update  (kept for direct URL — redirects to settings tab)
    // ---------------------------------------------------------------
    public function index(Request $request): string
    {
        if (!Auth::isAdmin()) {
            return $this->redirect(url('admin/dashboard'));
        }
        return $this->redirect(url('admin/settings?tab=atualizacao'));
    }

    /**
     * Build update data array — shared with SettingsController.
     */
    public function buildData(): array
    {
        $root        = $this->projectRoot();
        $gitBin      = $this->findGit();
        $gitVersion  = $this->gitVersion($root);
        $hasRepo     = is_dir($root . '/.git');
        $execEnabled = $this->run('echo ok') === 'ok';
        $localVersion= $this->readVersionJson($root);
        $lastCommit  = $hasRepo && $gitBin ? $this->lastCommit($root) : [];

        return [
            'gitVersion'   => $gitVersion,
            'lastCommit'   => $lastCommit,
            'gitAvailable' => ($gitVersion !== null && $hasRepo),
            'gitBin'       => $gitBin,
            'hasRepo'      => $hasRepo,
            'execEnabled'  => $execEnabled,
            'localVersion' => $localVersion,
            'gitPathHint'  => env('GIT_PATH', ''),
        ];
    }

    // ---------------------------------------------------------------
    // POST /admin/system-update/pull  — run git pull
    // ---------------------------------------------------------------
    public function pull(Request $request): void
    {
        $this->requireAjax();
        if (!Auth::isAdmin()) { $this->jsonError('Acesso negado.', [], 403); }

        $root = $this->projectRoot();

        if (!is_dir($root . '/.git')) {
            $this->jsonError('Repositório Git não encontrado nesta instalação.', [], 500);
        }

        $gitBin = $this->findGit();
        if (!$gitBin) {
            $this->jsonError('Git não está disponível. Defina GIT_PATH no arquivo .env com o caminho completo do git.', [], 500);
        }

        $g   = $this->gitCmd($gitBin, $root);
        $out = $this->run("$g pull --ff-only origin " . self::GITHUB_BRANCH);
        $out = trim($out ?? '');

        if (str_contains(strtolower($out), 'error') || str_contains(strtolower($out), 'fatal')) {
            $this->jsonError('Erro ao atualizar: ' . $out, [], 500);
        }

        $upToDate   = str_contains($out, 'Already up to date') || str_contains($out, 'Already up-to-date');
        $lastCommit = $this->lastCommit($root);

        $this->jsonSuccess(
            $upToDate ? 'Sistema já está na versão mais recente.' : 'Sistema atualizado com sucesso!',
            ['output' => $out, 'last_commit' => $lastCommit]
        );
    }

    // ---------------------------------------------------------------
    // POST /admin/system-update/status  — check versions
    // ---------------------------------------------------------------
    public function status(Request $request): void
    {
        $this->requireAjax();
        if (!Auth::isAdmin()) { $this->jsonError('Acesso negado.', [], 403); }

        $root   = $this->projectRoot();
        $gitBin = $this->findGit();
        $hasRepo= is_dir($root . '/.git');

        // Local commit hash
        $localHash = '';
        if ($hasRepo && $gitBin) {
            $g = $this->gitCmd($gitBin, $root);
            $this->run("$g fetch --quiet origin " . self::GITHUB_BRANCH);
            $localHash = trim($this->run("$g rev-parse HEAD") ?? '');
        } else {
            // Fallback: read from version.json
            $v = $this->readVersionJson($root);
            $localHash = $v['commit'] ?? '';
        }

        // Remote commit via GitHub API
        $remote = $this->fetchGitHubLatest();

        if (!$remote) {
            $this->jsonError('Não foi possível consultar o GitHub. Verifique a conexão do servidor.', [], 503);
        }

        $remoteHash = $remote['sha'] ?? '';
        $upToDate   = $localHash && $remoteHash && str_starts_with($remoteHash, $localHash)
                   || str_starts_with($localHash, substr($remoteHash, 0, 7));

        // Count pending commits if local git available
        $pending = 0;
        if ($hasRepo && $gitBin && !$upToDate && $localHash && $remoteHash) {
            $g       = $this->gitCmd($gitBin, $root);
            $count   = trim($this->run("$g rev-list HEAD..@{u} --count") ?? '0');
            $pending = (int)$count;
        }

        $lastCommit = ($hasRepo && $gitBin) ? $this->lastCommit($root) : $this->readVersionJson($root);

        $this->jsonSuccess('OK', [
            'up_to_date'     => $upToDate,
            'pending'        => $pending,
            'local_hash'     => substr($localHash, 0, 7),
            'remote_hash'    => substr($remoteHash, 0, 7),
            'remote_commit'  => $remote,
            'last_commit'    => $lastCommit,
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────

    private function fetchGitHubLatest(): ?array
    {
        $url = 'https://api.github.com/repos/' . self::GITHUB_REPO . '/commits/' . self::GITHUB_BRANCH;
        $ctx = stream_context_create(['http' => [
            'timeout'       => 8,
            'ignore_errors' => true,
            'header'        => "User-Agent: ChatBot-System/1.0\r\n",
        ]]);
        try {
            $resp = @file_get_contents($url, false, $ctx);
            $data = $resp ? json_decode($resp, true) : null;
            if (!is_array($data) || empty($data['sha'])) return null;
            return [
                'sha'     => $data['sha'],
                'message' => strtok($data['commit']['message'] ?? '', "\n"),
                'author'  => $data['commit']['author']['name'] ?? '',
                'date'    => $data['commit']['author']['date'] ?? '',
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function readVersionJson(string $root): array
    {
        $file = $root . '/version.json';
        if (!file_exists($file)) return [];
        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : [];
    }

    private function findGit(): ?string
    {
        // Allow explicit path via .env
        $envPath = env('GIT_PATH', '');
        if ($envPath && @is_executable($envPath)) return $envPath;

        $candidates = [
            '/usr/bin/git',
            '/usr/local/bin/git',
            '/usr/local/cpanel/3rdparty/bin/git',
            '/opt/cpanel/ea-git/root/usr/bin/git',
            '/opt/cpanel/ea-git/root/usr/libexec/git-core/git',
            '/usr/libexec/git-core/git',
            '/opt/homebrew/bin/git',
        ];
        foreach ($candidates as $path) {
            if (@is_executable($path)) return $path;
        }
        $which = $this->run('which git');
        if ($which && str_starts_with(trim($which), '/') && @is_executable(trim($which))) {
            return trim($which);
        }
        return null;
    }

    /**
     * Build git command prefix with safe.directory to avoid
     * "dubious ownership" errors on cPanel/shared hosting.
     */
    private function gitCmd(string $gitBin, string $root): string
    {
        return escapeshellarg($gitBin)
             . ' -c safe.directory=' . escapeshellarg($root)
             . ' -C ' . escapeshellarg($root);
    }

    private function gitVersion(string $root): ?string
    {
        $git = $this->findGit();
        if (!$git || !is_dir($root . '/.git')) return null;
        $v = $this->run(escapeshellarg($git) . ' --version');
        return ($v && !str_contains($v, 'not found') && !str_contains($v, 'fatal')) ? trim($v) : null;
    }

    private function lastCommit(string $root): array
    {
        $git = $this->findGit();
        if (!$git || !is_dir($root . '/.git')) return [];
        $log = $this->run(
            $this->gitCmd($git, $root) . ' log -1 --format="%h|%s|%an|%ar"'
        );
        if (!$log || str_contains($log, 'fatal') || str_contains($log, 'error')) return [];
        $parts = explode('|', trim($log), 4);
        if (empty($parts[0]) || strlen($parts[0]) < 4) return [];
        return [
            'hash'    => $parts[0] ?? '',
            'subject' => $parts[1] ?? '',
            'author'  => $parts[2] ?? '',
            'date'    => $parts[3] ?? '',
        ];
    }

    private function run(string $cmd): ?string
    {
        $cmd .= ' 2>&1';
        $disabled = array_map('trim', explode(',', ini_get('disable_functions')));

        if (function_exists('shell_exec') && !in_array('shell_exec', $disabled)) {
            $out = @shell_exec($cmd);
            if ($out !== null) return trim($out);
        }
        if (function_exists('exec') && !in_array('exec', $disabled)) {
            $lines = [];
            @exec($cmd, $lines);
            if ($lines) return trim(implode("\n", $lines));
        }
        if (function_exists('proc_open') && !in_array('proc_open', $disabled)) {
            $desc = [1 => ['pipe','w'], 2 => ['pipe','w']];
            $proc = @proc_open($cmd, $desc, $pipes);
            if ($proc) {
                $out = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
                fclose($pipes[1]); fclose($pipes[2]);
                proc_close($proc);
                return trim($out);
            }
        }
        return null;
    }
}
