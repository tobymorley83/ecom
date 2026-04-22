<?php
/**
 * GitHub Webhook - Auto-deploy on push to main.
 * Per-site log file based on config 'site_key'.
 */

$config_path = __DIR__ . '/config/config.php';
$cfg = [];
$secret = null;
if (file_exists($config_path)) {
    $cfg = require $config_path;
    $secret = $cfg['deploy']['webhook_secret'] ?? null;
}

$site_key = $cfg['deploy']['site_key'] ?? 'unknown';
$log_file = "/tmp/deploy_{$site_key}.log";
$timestamp = date('Y-m-d H:i:s');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

if ($secret && $secret !== 'CHANGE_ME') {
    $payload = file_get_contents('php://input');
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

$repo_path = $cfg['deploy']['repo_path'] ?? __DIR__;

$cmd = sprintf(
    'cd %s && /usr/bin/git fetch origin main 2>&1 && /usr/bin/git reset --hard origin/main 2>&1',
    escapeshellarg($repo_path)
);

$output = [];
$exit_code = 0;
exec($cmd, $output, $exit_code);

$result = implode("\n", $output);
$status = ($exit_code === 0) ? 'SUCCESS' : 'FAILED';
file_put_contents($log_file, "$timestamp $status (exit: $exit_code)\n  CMD: $cmd\n  OUTPUT: $result\n\n", FILE_APPEND);

if ($exit_code === 0) {
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'output' => $result]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'exit_code' => $exit_code, 'output' => $result]);
}
