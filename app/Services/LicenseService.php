<?php

namespace App\Services;

use Core\Database;

/**
 * Verifies the installation's license against the owner's API.
 * Result is cached in the database for 1 hour to avoid excessive API calls.
 */
class LicenseService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_KEY = 'license_data';

    public static function check(): array
    {
        $apiUrl = config('app.license_api_url', env('LICENSE_API_URL', ''));
        $key    = config('app.license_key',     env('LICENSE_KEY', ''));

        // If no license API configured → allow everything (development mode)
        if (!$apiUrl || !$key) {
            return self::unlimitedLicense();
        }

        // Check cache
        $cached = self::getCached();
        if ($cached !== null) {
            return $cached;
        }

        // Call API
        $domain = parse_url(config('app.url', ''), PHP_URL_HOST) ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $url    = rtrim($apiUrl, '/') . '?action=verify&domain=' . urlencode($domain) . '&key=' . urlencode($key);

        $http = self::httpGet($url);
        $data = is_string($http['body']) ? json_decode($http['body'], true) : null;

        if (!is_array($data)) {
            // API unreachable / non-JSON — use last known good or deny
            logger('LicenseService failed: HTTP ' . $http['code'] . ' — ' . substr((string)$http['body'], 0, 300) . ' — ' . $http['error'], 'error');
            $cached = self::getCached(ignoreExpiry: true);
            $reason = $http['error']
                ? 'License API unreachable: ' . $http['error']
                : 'License API returned HTTP ' . $http['code'];
            return $cached ?? self::deniedLicense($reason);
        }

        $result = [
            'valid'      => (bool)($data['valid'] ?? false),
            'domain'     => $data['domain']     ?? $domain,
            'plan'       => $data['plan']        ?? 'unknown',
            'max_users'  => (int)($data['max_users'] ?? 0),
            'max_flows'  => (int)($data['max_flows']  ?? 0),
            'status'     => $data['status']      ?? 'unknown',
            'expires_at' => $data['expires_at']  ?? null,
            'features'   => $data['features']    ?? [],
            'error'      => $data['error']       ?? null,
            'checked_at' => time(),
        ];

        self::saveCache($result);
        return $result;
    }

    public static function isValid(): bool
    {
        return self::check()['valid'] ?? false;
    }

    public static function maxUsers(): int
    {
        $license = self::check();
        if (!$license['valid']) return 0;
        return $license['max_users'] > 0 ? $license['max_users'] : PHP_INT_MAX;
    }

    public static function maxFlows(): int
    {
        $license = self::check();
        if (!$license['valid']) return 0;
        return $license['max_flows'] > 0 ? $license['max_flows'] : PHP_INT_MAX;
    }

    public static function clearCache(): void
    {
        try {
            Database::getInstance()->delete(
                "DELETE FROM license_cache WHERE cache_key = ?",
                [self::CACHE_KEY]
            );
        } catch (\Throwable $e) {}
    }

    /**
     * Public HTTP probe — used by the License diagnostics page.
     * Returns ['url'=>string,'code'=>int,'body'=>?string,'error'=>?string,'duration_ms'=>int]
     */
    public static function probeApi(): array
    {
        $apiUrl = (string)(config('app.license_api_url', env('LICENSE_API_URL', '')) ?: '');
        $key    = (string)(config('app.license_key',     env('LICENSE_KEY', ''))     ?: '');
        $domain = parse_url(config('app.url', ''), PHP_URL_HOST) ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');

        if ($apiUrl === '' || $key === '') {
            return [
                'url'         => $apiUrl,
                'code'        => 0,
                'body'        => null,
                'error'       => 'LICENSE_API_URL ou LICENSE_KEY não definidos no .env',
                'duration_ms' => 0,
            ];
        }

        $url = rtrim($apiUrl, '/') . '?action=verify&domain=' . urlencode($domain) . '&key=' . urlencode($key);
        $r   = self::httpGet($url);
        $r['url'] = $url;
        return $r;
    }

    /**
     * cURL with file_get_contents fallback.
     * Returns ['code'=>int,'body'=>?string,'error'=>?string,'duration_ms'=>int]
     */
    private static function httpGet(string $url): array
    {
        $start = microtime(true);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 5,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_USERAGENT      => 'NowFlow-License/1.0',
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $body = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);

            return [
                'code'        => $code,
                'body'        => $body !== false ? (string)$body : null,
                'error'       => $err !== '' ? $err : null,
                'duration_ms' => (int)round((microtime(true) - $start) * 1000),
            ];
        }

        // Fallback: file_get_contents
        if (!ini_get('allow_url_fopen')) {
            return [
                'code'        => 0,
                'body'        => null,
                'error'       => 'cURL e allow_url_fopen indisponíveis no PHP.',
                'duration_ms' => 0,
            ];
        }

        $ctx  = stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true]]);
        $body = @file_get_contents($url, false, $ctx);
        $code = 0;
        if (isset($http_response_header[0]) && preg_match('#HTTP/\S+\s+(\d+)#', $http_response_header[0], $m)) {
            $code = (int)$m[1];
        }

        return [
            'code'        => $code,
            'body'        => $body !== false ? (string)$body : null,
            'error'       => $body === false ? 'file_get_contents falhou' : null,
            'duration_ms' => (int)round((microtime(true) - $start) * 1000),
        ];
    }

    // ── Private helpers ───────────────────────────────────────────

    private static function getCached(bool $ignoreExpiry = false): ?array
    {
        try {
            $row = Database::getInstance()->selectOne(
                "SELECT * FROM license_cache WHERE cache_key = ? LIMIT 1",
                [self::CACHE_KEY]
            );
            if (!$row) return null;
            if (!$ignoreExpiry && (time() - (int)$row['checked_at']) > self::CACHE_TTL) return null;
            return json_decode($row['payload'], true);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function saveCache(array $data): void
    {
        try {
            $db  = Database::getInstance();
            $row = $db->selectOne("SELECT id FROM license_cache WHERE cache_key = ?", [self::CACHE_KEY]);
            $ts  = now();
            if ($row) {
                $db->update(
                    "UPDATE license_cache SET payload=?, checked_at=?, updated_at=? WHERE cache_key=?",
                    [json_encode($data), $data['checked_at'], $ts, self::CACHE_KEY]
                );
            } else {
                $db->insert(
                    "INSERT INTO license_cache (cache_key, payload, checked_at, updated_at) VALUES (?,?,?,?)",
                    [self::CACHE_KEY, json_encode($data), $data['checked_at'], $ts]
                );
            }
        } catch (\Throwable $e) {
            logger('LicenseService cache save error: ' . $e->getMessage(), 'error');
        }
    }

    private static function unlimitedLicense(): array
    {
        return [
            'valid'      => true,
            'domain'     => 'localhost',
            'plan'       => 'dev',
            'max_users'  => PHP_INT_MAX,
            'max_flows'  => PHP_INT_MAX,
            'status'     => 'active',
            'expires_at' => null,
            'features'   => [],
            'error'      => null,
            'checked_at' => time(),
        ];
    }

    private static function deniedLicense(string $reason): array
    {
        return [
            'valid'      => false,
            'domain'     => '',
            'plan'       => '',
            'max_users'  => 0,
            'max_flows'  => 0,
            'status'     => 'error',
            'expires_at' => null,
            'features'   => [],
            'error'      => $reason,
            'checked_at' => time(),
        ];
    }
}
