<?php
/**
 * License Verification API
 * Called by LicenseService::check() on each chatbot installation.
 * URL: /public_owner/verify.php?action=verify&domain=<domain>&key=<key>
 */
define('OWNER_ROOT', __DIR__);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Only handle verify action
if (($_GET['action'] ?? '') !== 'verify') {
    echo json_encode(['valid' => false, 'error' => 'Invalid action']);
    exit;
}

$domain = trim($_GET['domain'] ?? '');
$key    = trim($_GET['key']    ?? '');

if (!$domain || !$key) {
    echo json_encode(['valid' => false, 'error' => 'Missing domain or key']);
    exit;
}

require __DIR__ . '/inc/db.php';

try {
    $pdo  = ownerDb();
    $stmt = $pdo->prepare(
        "SELECT * FROM licenses WHERE secret_key = ? AND domain = ? LIMIT 1"
    );
    $stmt->execute([$key, $domain]);
    $license = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {
    echo json_encode(['valid' => false, 'error' => 'DB error: ' . $e->getMessage()]);
    exit;
}

if (!$license) {
    // Try matching just by key (domain may differ slightly — www vs non-www)
    try {
        $stmt = $pdo->prepare("SELECT * FROM licenses WHERE secret_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $license = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {
        $license = null;
    }
}

if (!$license) {
    echo json_encode(['valid' => false, 'error' => 'License not found']);
    exit;
}

$status = $license['status'] ?? 'inactive';
$valid  = $status === 'active';

// Check expiry
if ($valid && !empty($license['expires_at'])) {
    $valid = strtotime($license['expires_at']) > time();
    if (!$valid) $status = 'expired';
}

// Decode features
$features = [];
if (!empty($license['features'])) {
    $decoded = json_decode($license['features'], true);
    if (is_array($decoded)) {
        $features = $decoded;
    }
}

// If license has a plan, merge plan features
if (!empty($license['plan'])) {
    try {
        $pstmt = $pdo->prepare("SELECT features FROM plans WHERE slug = ? LIMIT 1");
        $pstmt->execute([$license['plan']]);
        $plan = $pstmt->fetch(PDO::FETCH_ASSOC);
        if ($plan && !empty($plan['features'])) {
            $planFeatures = json_decode($plan['features'], true) ?: [];
            $features     = array_values(array_unique(array_merge($planFeatures, $features)));
        }
    } catch (\Throwable $e) {
        // plans table may not exist — ignore
    }
}

echo json_encode([
    'valid'      => $valid,
    'domain'     => $license['domain'],
    'plan'       => $license['plan']        ?? 'unknown',
    'max_users'  => (int)($license['max_users'] ?? 0),
    'max_flows'  => (int)($license['max_flows']  ?? 0),
    'status'     => $status,
    'expires_at' => $license['expires_at']  ?? null,
    'features'   => $features,
    'error'      => null,
]);
