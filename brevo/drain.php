<?php
/**
 * /brevo/drain.php  (Phase 2, sync-first edition)
 *
 * Drains the local outbox: each pending JSON file is POSTed to middleware via
 * brevo_send_sync() (same code path as live track.php). Successes are deleted,
 * retryable failures get rescheduled, non-retryable or maxed-out files get
 * moved to outbox-failed/.
 *
 * Run via cron every minute as a safety net for events that couldn't be
 * delivered live (middleware down, network blip).
 *
 * CLI:   * * * * * /usr/bin/php /www/wwwroot/<shop>/brevo/drain.php
 * HTTP:  curl -s "https://<shop>/brevo/drain.php?token=<middleware_secret>"
 */

declare(strict_types=1);

require_once __DIR__ . '/client.php';

$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    header('Content-Type: application/json; charset=utf-8');
    $token    = $_GET['token'] ?? '';
    $expected = (string) brevo_config('middleware_secret', '');
    if ($expected === '' || !hash_equals($expected, (string) $token)) {
        http_response_code(401);
        echo json_encode(['error' => 'invalid token']);
        exit;
    }
}

$dir         = (string) brevo_config('outbox.dir');
$failedDir   = (string) brevo_config('outbox.failed_dir');
$maxAttempts = (int) brevo_config('outbox.max_attempts', 8);
$backoff     = (array) brevo_config('outbox.backoff_s', [0, 30, 120, 300, 900, 1800, 3600, 7200]);

if (!is_dir($dir))       @mkdir($dir, 0775, true);
if (!is_dir($failedDir)) @mkdir($failedDir, 0775, true);

$stats = ['scanned' => 0, 'sent' => 0, 'retried' => 0, 'failed' => 0, 'skipped' => 0];

$files = glob($dir . '/EVT-*.json') ?: [];
sort($files); // oldest first

$now     = time();
$started = microtime(true);

foreach ($files as $file) {
    if ($stats['scanned'] >= 50) break;
    $stats['scanned']++;

    $envelope = brevo_read_envelope($file);
    if ($envelope === null) {
        @rename($file, $failedDir . '/' . basename($file) . '.corrupt');
        $stats['failed']++;
        continue;
    }
    if (($envelope['_meta']['next_attempt_at'] ?? 0) > $now) {
        $stats['skipped']++;
        continue;
    }

    [$ok, $httpCode, $err, $retryable] = brevo_send_sync($envelope['event'], [
        'identity'    => $envelope['identity']    ?? [],
        'contact'     => $envelope['contact']     ?? [],
        'properties'  => $envelope['properties']  ?? [],
        'occurred_at' => $envelope['occurred_at'] ?? null,
    ]);

    if ($ok) {
        @unlink($file);
        $stats['sent']++;
        continue;
    }

    $attempts = (int) ($envelope['_meta']['attempts'] ?? 0) + 1;
    $envelope['_meta']['attempts']   = $attempts;
    $envelope['_meta']['last_error'] = mb_substr((string) $err, 0, 1000);

    if (!$retryable || $attempts >= $maxAttempts) {
        $envelope['_meta']['failed_at'] = $now;
        @file_put_contents(
            $failedDir . '/' . basename($file),
            json_encode($envelope, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            LOCK_EX
        );
        @unlink($file);
        $stats['failed']++;
        brevo_log('error', 'Outbox dead-lettered', [
            'file' => basename($file), 'event' => $envelope['event'],
            'attempts' => $attempts, 'http' => $httpCode, 'err' => $err,
        ]);
        continue;
    }

    $delay = $backoff[min($attempts, count($backoff) - 1)] ?? end($backoff);
    $envelope['_meta']['next_attempt_at'] = $now + (int) $delay;
    @file_put_contents(
        $file,
        json_encode($envelope, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        LOCK_EX
    );
    $stats['retried']++;
}

$stats['elapsed_ms'] = (int) ((microtime(true) - $started) * 1000);

if ($isCli) {
    echo json_encode($stats) . "\n";
} else {
    echo json_encode(['ok' => true] + $stats);
}

function brevo_read_envelope(string $file): ?array
{
    $raw = @file_get_contents($file);
    if ($raw === false) return null;
    $data = json_decode($raw, true);
    if (!is_array($data) || empty($data['event'])) return null;
    if (!isset($data['_meta']) || !is_array($data['_meta'])) {
        $data['_meta'] = ['attempts' => 0, 'next_attempt_at' => 0, 'created_at' => time(), 'last_error' => null];
    }
    return $data;
}
