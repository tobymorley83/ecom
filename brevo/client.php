<?php
/**
 * /brevo/client.php  (Phase 2, sync-first edition)
 *
 * Core PHP-side helpers for the shop:
 *   - brevo_config()        config accessor (dot-path)
 *   - brevo_send()          fire an event: try sync S2S first, fall back to outbox
 *   - brevo_send_sync()     low-level sync POST to middleware (returns success bool)
 *   - brevo_outbox_write()  enqueue an event for later delivery (used by fallback)
 *   - brevo_log()           local logging
 *
 * This file is pure helpers; nothing executes on include.
 */

declare(strict_types=1);

// ----------------------------------------------------------------------------
// Config singleton
// ----------------------------------------------------------------------------

function brevo_config(?string $path = null, mixed $default = null): mixed
{
    static $cfg = null;
    if ($cfg === null) {
        $file = __DIR__ . '/config.local.php';
        if (!is_file($file)) {
            throw new RuntimeException(
                'Brevo client config missing. Copy config.local.example.php to config.local.php and fill it in.'
            );
        }
        $cfg = require $file;
        if (!is_array($cfg)) {
            throw new RuntimeException('Brevo client config must return an array.');
        }
    }
    if ($path === null) return $cfg;

    $node = $cfg;
    foreach (explode('.', $path) as $key) {
        if (!is_array($node) || !array_key_exists($key, $node)) {
            return $default;
        }
        $node = $node[$key];
    }
    return $node;
}

// ----------------------------------------------------------------------------
// Send: try sync, fall back to outbox
// ----------------------------------------------------------------------------

/**
 * Fire an event. Tries an immediate S2S POST to the middleware; on failure,
 * writes to the local outbox so the cron drainer can retry.
 *
 * Returns ['delivered' => true|false, 'outboxed' => true|false, 'error' => ?string].
 *
 * Always succeeds from the caller's POV unless even the disk write fails.
 *
 * @param string $event    Event name (snake_case, [a-z][a-z0-9_]{1,63}).
 * @param array  $payload  identity, contact, properties, occurred_at (all optional).
 */
function brevo_send(string $event, array $payload): array
{
    [$ok, $httpCode, $err, $retryable] = brevo_send_sync($event, $payload);

    if ($ok) {
        brevo_log('info', 'Event delivered sync', ['event' => $event, 'http' => $httpCode]);
        return ['delivered' => true, 'outboxed' => false, 'error' => null];
    }

    // Sync failed. Outbox it for retry — but only if the failure is retryable.
    // Non-retryable 4xx errors (bad event name, bad identity) won't get better;
    // we still outbox-failed them for inspection.
    $file = brevo_outbox_write($event, $payload);

    if (!$retryable && $file !== false) {
        // Move straight to failed dir — no point retrying a 400.
        $failedDir = (string) brevo_config('outbox.failed_dir');
        if (!is_dir($failedDir)) @mkdir($failedDir, 0775, true);
        @rename($file, $failedDir . '/' . basename($file));
        brevo_log('error', 'Event rejected by middleware (non-retryable)', [
            'event' => $event, 'http' => $httpCode, 'err' => $err,
        ]);
        return ['delivered' => false, 'outboxed' => false, 'error' => $err];
    }

    if ($file === false) {
        brevo_log('error', 'Event lost: sync failed and outbox write failed', [
            'event' => $event, 'http' => $httpCode, 'err' => $err,
        ]);
        return ['delivered' => false, 'outboxed' => false, 'error' => $err];
    }

    brevo_log('warning', 'Event outboxed for retry', [
        'event' => $event, 'http' => $httpCode, 'err' => $err, 'file' => basename($file),
    ]);
    return ['delivered' => false, 'outboxed' => true, 'error' => $err];
}

/**
 * Direct sync POST to middleware /ingest.php. Used by brevo_send() and by drain.php.
 *
 * @return array{0:bool,1:int,2:?string,3:bool}  [ok, httpCode, err, retryable]
 */
function brevo_send_sync(string $event, array $payload): array
{
    $url    = rtrim((string) brevo_config('middleware_url'), '/') . '/ingest.php';
    $secret = (string) brevo_config('middleware_secret');
    $store  = (string) brevo_config('store_domain');

    $body = [
        'event'        => $event,
        'occurred_at'  => $payload['occurred_at'] ?? gmdate('c'),
        'identity'     => $payload['identity']   ?? [],
        'contact'      => $payload['contact']    ?? [],
        'properties'   => $payload['properties'] ?? [],
    ];

    $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $connectTimeout = (int) brevo_config('middleware_connect_timeout_s', 3);
    $totalTimeout   = (int) brevo_config('middleware_total_timeout_s', 5);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $json,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-Shop-Secret: ' . $secret,
            'X-Store-Domain: ' . $store,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => $connectTimeout,
        CURLOPT_TIMEOUT        => $totalTimeout,
    ]);
    $resp     = curl_exec($ch);
    $err      = curl_error($ch);
    $errno    = curl_errno($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno !== 0 || $resp === false) {
        return [false, 0, "curl($errno): $err", true];   // network -> retryable
    }
    if ($httpCode >= 200 && $httpCode < 300) {
        return [true, $httpCode, null, false];
    }
    $retryable = ($httpCode === 429 || $httpCode >= 500);
    return [false, $httpCode, "HTTP $httpCode: " . mb_substr((string) $resp, 0, 500), $retryable];
}

// ----------------------------------------------------------------------------
// Outbox: write an event to disk for later delivery
// ----------------------------------------------------------------------------

function brevo_outbox_write(string $event, array $payload): string|false
{
    $dir = (string) brevo_config('outbox.dir');
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    if (!is_writable($dir)) {
        brevo_log('error', 'Outbox dir not writable', ['dir' => $dir]);
        return false;
    }

    $envelope = [
        'event'        => $event,
        'store_domain' => brevo_config('store_domain'),
        'occurred_at'  => $payload['occurred_at'] ?? gmdate('c'),
        'identity'     => $payload['identity']   ?? [],
        'contact'      => $payload['contact']    ?? [],
        'properties'   => $payload['properties'] ?? [],
        '_meta' => [
            'attempts'        => 0,
            'next_attempt_at' => time(),
            'created_at'      => time(),
            'last_error'      => null,
        ],
    ];

    $filename = sprintf(
        '%s/EVT-%s-%s.json',
        $dir,
        gmdate('Ymd-His'),
        bin2hex(random_bytes(6))
    );

    $bytes = file_put_contents(
        $filename,
        json_encode($envelope, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        LOCK_EX
    );

    if ($bytes === false) {
        brevo_log('error', 'Failed to write outbox file', ['file' => $filename]);
        return false;
    }
    return $filename;
}

// ----------------------------------------------------------------------------
// Logging
// ----------------------------------------------------------------------------

function brevo_log(string $level, string $message, array $context = []): void
{
    static $levels = ['debug' => 10, 'info' => 20, 'warning' => 30, 'error' => 40];
    $threshold = $levels[brevo_config('logging.level', 'info')] ?? 20;
    if (($levels[$level] ?? 0) < $threshold) return;

    $file = (string) brevo_config('logging.file');
    if (!$file) return;

    $line = sprintf(
        "[%s] %s: %s %s\n",
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $message,
        $context ? json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : ''
    );
    @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}

// ----------------------------------------------------------------------------
// Normalizers (kept in sync with middleware/src/Normalize.php)
// ----------------------------------------------------------------------------

function brevo_normalize_email(?string $raw): ?string
{
    if ($raw === null) return null;
    $v = strtolower(trim($raw));
    if ($v === '') return null;
    return filter_var($v, FILTER_VALIDATE_EMAIL) ? $v : null;
}

function brevo_normalize_sms(?string $raw): ?string
{
    if ($raw === null) return null;
    $v = preg_replace('/[\s\-\(\)\.]/', '', trim($raw)) ?? '';
    if ($v === '' || !str_starts_with($v, '+')) return null;
    $digits = preg_replace('/[^\d]/', '', substr($v, 1)) ?? '';
    $len = strlen($digits);
    return ($len >= 8 && $len <= 15) ? '+' . $digits : null;
}

function brevo_normalize_ext_id(?string $raw): ?string
{
    if ($raw === null) return null;
    $v = trim($raw);
    if ($v === '' || !preg_match('/^[A-Za-z0-9_\-:.]{1,128}$/', $v)) return null;
    return $v;
}
