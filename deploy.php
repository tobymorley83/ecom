<?php
/**
 * GitHub Webhook — auto-deploy on push to main.
 *
 * Loads the shared deploy config (tracked at config/deploy.php) and
 * optionally merges a per-site override (config/config.php, gitignored).
 *
 *   site_key   — from override, else parsed from the main site config's
 *                site_url (host minus TLD), else basename of the install dir
 *   repo_path  — from override, else __DIR__
 *   secret     — from override, else the shared secret in config/deploy.php
 */

$shared_path   = __DIR__ . '/config/deploy.php';
$override_path = __DIR__ . '/config/config.php';

$cfg = is_file($shared_path) ? (array) require $shared_path : [];
if (is_file($override_path)) {
    $local = require $override_path;
    if (is_array($local)) {
        $cfg = array_replace_recursive($cfg, $local);
    }
}

$secret    = $cfg['deploy']['webhook_secret'] ?? null;
$repo_path = $cfg['deploy']['repo_path']      ?? __DIR__;

// Derive site_key: explicit override → host of site_url → install dir name
$site_key = $cfg['deploy']['site_key'] ?? null;
if (!$site_key) {
    $main = __DIR__ . '/config.php';
    if (is_file($main)) {
        $sc = @require $main;
        if (is_array($sc) && !empty($sc['site_url'])) {
            $host = parse_url((string) $sc['site_url'], PHP_URL_HOST);
            $host = preg_replace('/^www\./', '', (string) $host);
            $parts = explode('.', $host);
            $site_key = $parts[0] ?? '';
        }
    }
    if (!$site_key) $site_key = basename(__DIR__);
}

$log_file  = "/tmp/deploy_{$site_key}.log";
$timestamp = date('Y-m-d H:i:s');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

if ($secret && $secret !== 'CHANGE_ME') {
    $payload    = file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

    if (!$sig_header) {
        file_put_contents($log_file, "$timestamp REJECTED: No signature header\n", FILE_APPEND);
        http_response_code(403);
        exit('Forbidden');
    }

    $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    if (!hash_equals($expected, $sig_header)) {
        file_put_contents($log_file, "$timestamp REJECTED: Invalid signature\n", FILE_APPEND);
        http_response_code(403);
        exit('Forbidden');
    }
}

// Serialize concurrent webhook deliveries for this shop so two
// `git fetch` runs don't race on refs/remotes/origin/main.lock.
$lock_file = "/tmp/deploy_{$site_key}.lock";
$lock      = @fopen($lock_file, 'c');
if ($lock !== false) {
    flock($lock, LOCK_EX);   // blocks until acquired
}

// Defensively clear any stale ref-locks left behind by a prior crash.
foreach ((array) glob($repo_path . '/.git/refs/remotes/origin/*.lock') as $stale) {
    @unlink($stale);
}
@unlink($repo_path . '/.git/index.lock');

$cmd = sprintf(
    'cd %s && /usr/bin/git fetch --prune origin main 2>&1 && /usr/bin/git reset --hard origin/main 2>&1',
    escapeshellarg($repo_path)
);

$output    = [];
$exit_code = 0;
exec($cmd, $output, $exit_code);

if ($lock !== false) {
    flock($lock, LOCK_UN);
    fclose($lock);
}

$result = implode("\n", $output);
$status = ($exit_code === 0) ? 'SUCCESS' : 'FAILED';
file_put_contents(
    $log_file,
    "$timestamp $status (exit: $exit_code)\n  CMD: $cmd\n  OUTPUT: $result\n\n",
    FILE_APPEND
);

if ($exit_code === 0) {
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'output' => $result]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'exit_code' => $exit_code, 'output' => $result]);
}
