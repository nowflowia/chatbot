<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;

/**
 * Public license verification endpoint.
 *
 * Mounted on the *license server* installation (e.g. apichat.nowflow.com.br).
 * Clients call:
 *   GET /license/verify?action=verify&domain=<domain>&key=<secret_key>
 *
 * Requires the license_manager DB to be configured at config/license_db.php.
 * On client installations (no license_db.php), responds 503.
 */
class LicenseVerifyController extends Controller
{
    public function verify(Request $request): void
    {
        // Force JSON output and clean any prior buffering
        if (ob_get_level()) {
            @ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Access-Control-Allow-Origin: *');

        $cfgPath = ROOT_PATH . '/config/license_db.php';
        if (!file_exists($cfgPath)) {
            http_response_code(503);
            echo json_encode(['valid' => false, 'error' => 'License server not configured on this installation.']);
            return;
        }

        $domain = strtolower(trim((string)$request->get('domain', '')));
        $key    = trim((string)$request->get('key', ''));

        // Normalize domain: strip scheme, www., trailing path
        $domain = preg_replace('#^https?://#i', '', $domain);
        $domain = preg_replace('#^www\.#i', '', $domain);
        $domain = strtok($domain, '/') ?: $domain;

        if (!$domain || !$key) {
            http_response_code(400);
            echo json_encode(['valid' => false, 'error' => 'Missing domain or key']);
            return;
        }

        try {
            $cfg = require $cfgPath;
            $dsn = "mysql:host={$cfg['host']};dbname={$cfg['database']};charset={$cfg['charset']}";
            $pdo = new \PDO(
                $dsn,
                $cfg['username'],
                $cfg['password'],
                [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_TIMEOUT            => 5,
                ]
            );

            // Look up by domain + secret_key
            $stmt = $pdo->prepare("SELECT * FROM licenses WHERE secret_key=? AND domain=? LIMIT 1");
            $stmt->execute([$key, $domain]);
            $license = $stmt->fetch();

            // Fallback: match by key only (handles www vs non-www mismatches)
            if (!$license) {
                $stmt = $pdo->prepare("SELECT * FROM licenses WHERE secret_key=? LIMIT 1");
                $stmt->execute([$key]);
                $license = $stmt->fetch();
            }

            // Log
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            try {
                $pdo->prepare(
                    "INSERT INTO license_logs (domain, ip_address, status, checked_at) VALUES (?, ?, ?, NOW())"
                )->execute([$domain, $ip, $license['status'] ?? 'not_found']);
            } catch (\Throwable $e) { /* logs table optional */ }

            if (!$license) {
                echo json_encode(['valid' => false, 'error' => 'License not found']);
                return;
            }

            $status = (string)($license['status'] ?? 'inactive');
            $valid  = $status === 'active';

            // Expiry check
            if ($valid && !empty($license['expires_at'])) {
                $valid = strtotime((string)$license['expires_at']) > time();
                if (!$valid) {
                    $status = 'expired';
                    try {
                        $pdo->prepare("UPDATE licenses SET status='expired' WHERE id=?")
                            ->execute([(int)$license['id']]);
                    } catch (\Throwable $e) {}
                }
            }

            // Decode license features
            $features = [];
            if (!empty($license['features'])) {
                $decoded = json_decode((string)$license['features'], true);
                if (is_array($decoded)) {
                    $features = $decoded;
                }
            }

            // Merge with plan features (if plan slug exists in plans table)
            if (!empty($license['plan'])) {
                try {
                    $pstmt = $pdo->prepare("SELECT features FROM plans WHERE slug=? LIMIT 1");
                    $pstmt->execute([$license['plan']]);
                    $plan = $pstmt->fetch();
                    if ($plan && !empty($plan['features'])) {
                        $planFeatures = json_decode((string)$plan['features'], true) ?: [];
                        if (is_array($planFeatures)) {
                            $features = array_values(array_unique(array_merge($planFeatures, $features)));
                        }
                    }
                } catch (\Throwable $e) { /* plans table optional */ }
            }

            echo json_encode([
                'valid'      => $valid,
                'domain'     => $license['domain'],
                'plan'       => $license['plan']        ?? 'unknown',
                'max_users'  => (int)($license['max_users'] ?? 0),
                'max_flows'  => (int)($license['max_flows'] ?? 0),
                'status'     => $status,
                'expires_at' => $license['expires_at']  ?? null,
                'features'   => $features,
                'error'      => null,
            ]);

        } catch (\Throwable $e) {
            error_log('[LicenseVerify] ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['valid' => false, 'error' => 'Server error: ' . $e->getMessage()]);
        }
    }
}
