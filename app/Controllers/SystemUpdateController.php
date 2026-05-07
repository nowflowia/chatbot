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

        // Run pending DB migrations in-process (no subprocess required)
        $migration = $this->runPendingMigrations();

        $msg = $upToDate
            ? 'Sistema já está na versão mais recente.'
            : 'Sistema atualizado com sucesso!';

        if ($migration['ran'] > 0) {
            $msg .= " {$migration['ran']} migration(s) aplicada(s).";
        } elseif (!empty($migration['error'])) {
            $msg .= ' Atenção: erro ao aplicar migrations.';
        }

        $this->jsonSuccess($msg, [
            'output'      => $out,
            'last_commit' => $lastCommit,
            'migrations'  => $migration,
        ]);
    }

    // ---------------------------------------------------------------
    // GET /admin/system-update/backup-files  — download project as .zip
    // ---------------------------------------------------------------
    public function backupFiles(Request $request): void
    {
        if (!Auth::isAdmin()) {
            http_response_code(403);
            echo 'Acesso negado.';
            return;
        }

        if (!class_exists('ZipArchive')) {
            http_response_code(500);
            echo 'Extensão ZipArchive não disponível neste servidor.';
            return;
        }

        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $root    = $this->projectRoot();
        $tmpFile = tempnam(sys_get_temp_dir(), 'bk_files_') . '.zip';
        $zip     = new \ZipArchive();

        if ($zip->open($tmpFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            http_response_code(500);
            echo 'Falha ao criar arquivo zip.';
            return;
        }

        $excludeDirs = [
            '.git', 'vendor', 'node_modules',
            'storage/cache', 'storage/logs',
            'public/assets/uploads/cache',
        ];

        $rootLen = strlen($root) + 1;
        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iter as $file) {
            $absolute = $file->getPathname();
            $relative = str_replace('\\', '/', substr($absolute, $rootLen));

            // Skip excluded directories
            $skip = false;
            foreach ($excludeDirs as $ex) {
                if ($relative === $ex || str_starts_with($relative, $ex . '/')) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) continue;

            if ($file->isDir()) {
                $zip->addEmptyDir($relative);
            } elseif ($file->isFile()) {
                $zip->addFile($absolute, $relative);
            }
        }

        $zip->close();

        $filename = 'backup-files-' . date('Y-m-d_His') . '.zip';
        $this->streamFile($tmpFile, $filename, 'application/zip');
        @unlink($tmpFile);
    }

    // ---------------------------------------------------------------
    // GET /admin/system-update/backup-db  — download MySQL dump
    // ---------------------------------------------------------------
    public function backupDb(Request $request): void
    {
        if (!Auth::isAdmin()) {
            http_response_code(403);
            echo 'Acesso negado.';
            return;
        }

        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $tmpFile = tempnam(sys_get_temp_dir(), 'bk_db_') . '.sql';
        $fp      = fopen($tmpFile, 'w');
        if (!$fp) {
            http_response_code(500);
            echo 'Falha ao abrir arquivo temporário.';
            return;
        }

        try {
            $pdo = \Core\Database::getInstance()->getPdo();

            $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();

            fwrite($fp, "-- ChatBot DB Backup\n");
            fwrite($fp, "-- Database: {$dbName}\n");
            fwrite($fp, '-- Generated: ' . date('Y-m-d H:i:s') . "\n\n");
            fwrite($fp, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($fp, "SET NAMES utf8mb4;\n\n");

            $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                fwrite($fp, "\n-- Table: {$table}\n");
                fwrite($fp, "DROP TABLE IF EXISTS `{$table}`;\n");

                $row    = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
                $create = $row['Create Table'] ?? '';
                fwrite($fp, $create . ";\n\n");

                $stmt = $pdo->query("SELECT * FROM `{$table}`");
                $cols = null;
                while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    if ($cols === null) {
                        $cols = '`' . implode('`,`', array_keys($r)) . '`';
                    }
                    $vals = [];
                    foreach ($r as $v) {
                        if ($v === null) {
                            $vals[] = 'NULL';
                        } elseif (is_int($v) || is_float($v)) {
                            $vals[] = $v;
                        } else {
                            $vals[] = $pdo->quote((string)$v);
                        }
                    }
                    fwrite($fp, "INSERT INTO `{$table}` ({$cols}) VALUES (" . implode(',', $vals) . ");\n");
                }
            }

            fwrite($fp, "\nSET FOREIGN_KEY_CHECKS=1;\n");
        } catch (\Throwable $e) {
            fclose($fp);
            @unlink($tmpFile);
            http_response_code(500);
            echo 'Erro ao gerar dump: ' . $e->getMessage();
            return;
        }

        fclose($fp);

        $filename = 'backup-db-' . date('Y-m-d_His') . '.sql';
        $this->streamFile($tmpFile, $filename, 'application/sql');
        @unlink($tmpFile);
    }

    // ---------------------------------------------------------------
    private function streamFile(string $path, string $downloadName, string $mime): void
    {
        if (ob_get_level()) {
            @ob_end_clean();
        }
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        readfile($path);
    }

    // ---------------------------------------------------------------
    // Run pending DB migrations directly (no shell exec).
    // Returns ['ran' => int, 'names' => string[], 'error' => ?string]
    // ---------------------------------------------------------------
    private function runPendingMigrations(): array
    {
        $result = ['ran' => 0, 'names' => [], 'error' => null];
        $root   = $this->projectRoot();
        $dir    = $root . '/database/migrations';

        if (!is_dir($dir)) {
            return $result;
        }

        try {
            $db = \Core\Database::getInstance();

            $db->statement("
                CREATE TABLE IF NOT EXISTS `migrations` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    `migration` VARCHAR(255) NOT NULL,
                    `batch` INT NOT NULL DEFAULT 1,
                    `ran_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            $ran   = array_column(
                $db->select("SELECT migration FROM migrations ORDER BY id ASC"),
                'migration'
            );

            $files = glob($dir . '/*.php') ?: [];
            sort($files);

            $batch = (int)($db->selectOne("SELECT MAX(batch) AS b FROM migrations")['b'] ?? 0) + 1;

            foreach ($files as $file) {
                $name = pathinfo($file, PATHINFO_FILENAME);
                if (in_array($name, $ran, true)) continue;

                require_once $file;

                // Pattern: 2024_01_01_000001_create_something_table → CreateSomethingTable
                $parts = explode('_', $name);
                $classParts = count($parts) > 4 ? array_slice($parts, 4) : $parts;
                $class = implode('', array_map('ucfirst', $classParts));

                if (!class_exists($class)) {
                    logger("[migrate] Class not found: {$class} in {$name}", 'warning');
                    continue;
                }

                (new $class())->up();
                $db->insert(
                    "INSERT INTO migrations (migration, batch) VALUES (?, ?)",
                    [$name, $batch]
                );
                $result['ran']++;
                $result['names'][] = $name;
            }
        } catch (\Throwable $e) {
            logger('[migrate] ' . $e->getMessage(), 'error');
            $result['error'] = $e->getMessage();
        }

        return $result;
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
