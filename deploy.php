<?php
/**
 * GitHub Webhook — auto-deploy on push to main.
 *
 * Defaults (used by every shop, with NO per-site config required):
 *   webhook_secret  — config/deploy.php (tracked, shared across shops)
 *   repo_path       — __DIR__   (the directory deploy.php is in)
 *   site_key        — first label of $config['site_url']'s host,
 *                     or basename(__DIR__) as a fallback
 *
 * config/config.php is OPTIONAL and ONLY for the rare case where one
 * shop needs to override one of those defaults (e.g. rotate the
 * webhook secret on a single shop). It is gitignored — there is no
 * tracked template — so it can never be created accidentally by a
 * fresh deploy. If you don't have one, you don't need one.
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

$result    = '';
$exit_code = 1;
$runner    = run_cmd($cmd, $result, $exit_code);

if ($lock !== false) {
    flock($lock, LOCK_UN);
    fclose($lock);
}

if ($runner === null) {
    // No exec function available on this host.
    $msg = 'No PHP exec function available (exec/shell_exec/proc_open all disabled). '
         . 'Whitelist exec in disable_functions for this site.';
    file_put_contents($log_file, "$timestamp NO_EXEC: $msg\n", FILE_APPEND);
    http_response_code(503);
    echo json_encode(['status' => 'no_exec', 'message' => $msg]);
    exit;
}

$status = ($exit_code === 0) ? 'SUCCESS' : 'FAILED';
file_put_contents(
    $log_file,
    "$timestamp $status (exit: $exit_code, via: $runner)\n  CMD: $cmd\n  OUTPUT: $result\n\n",
    FILE_APPEND
);

if ($exit_code === 0) {
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'output' => $result]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'exit_code' => $exit_code, 'output' => $result]);
}

/**
 * Run a shell command using whichever PHP exec function the host allows.
 * Captures stdout+stderr into $output and the exit code into $exit_code.
 * Returns the name of the function used, or null if none are available.
 */
function run_cmd(string $cmd, string &$output, int &$exit_code): ?string
{
    $output    = '';
    $exit_code = 1;

    if (function_exists('exec')) {
        $arr = [];
        exec($cmd . ' 2>&1', $arr, $exit_code);
        $output = implode("\n", $arr);
        return 'exec';
    }
    if (function_exists('shell_exec')) {
        $marker = '__EX_' . bin2hex(random_bytes(4));
        $raw    = shell_exec($cmd . ' 2>&1; echo "' . $marker . '$?"');
        if (is_string($raw)) {
            if (preg_match('/' . preg_quote($marker, '/') . '(\d+)\s*$/', $raw, $m)) {
                $exit_code = (int) $m[1];
                $raw       = preg_replace('/' . preg_quote($marker, '/') . '\d+\s*$/', '', $raw);
            } else {
                $exit_code = 0;
            }
            $output = rtrim($raw);
            return 'shell_exec';
        }
    }
    if (function_exists('proc_open')) {
        $proc = @proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (is_resource($proc)) {
            $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
            $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
            $exit_code = proc_close($proc);
            $output    = trim($stdout . ($stderr ? "\n" . $stderr : ''));
            return 'proc_open';
        }
    }
    if (function_exists('passthru')) {
        ob_start();
        passthru($cmd . ' 2>&1', $exit_code);
        $output = trim(ob_get_clean());
        return 'passthru';
    }
    if (function_exists('system')) {
        ob_start();
        system($cmd . ' 2>&1', $exit_code);
        $output = trim(ob_get_clean());
        return 'system';
    }
    return null;
}
