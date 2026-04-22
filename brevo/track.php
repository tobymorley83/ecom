<?php
/**
 * /brevo/track.php  (Phase 2, sync-first edition)
 *
 * Receives events from JS (via window.Brevo.track / sendBeacon).
 * Validates, normalizes, and tries to deliver to middleware immediately.
 * Falls back to local outbox if the middleware is unreachable.
 */

declare(strict_types=1);

require_once __DIR__ . '/client.php';
require_once __DIR__ . '/identity.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$max = (int) brevo_config('track.max_body_bytes', 16384);
$raw = file_get_contents('php://input') ?: '';
if (strlen($raw) > $max) {
    http_response_code(413);
    echo json_encode(['error' => 'Body too large']);
    exit;
}

$body = json_decode($raw, true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['error' => 'Body must be JSON object']);
    exit;
}

$event = trim((string) ($body['event'] ?? ''));
if ($event === '' || !preg_match('/^[a-z][a-z0-9_]{1,63}$/', $event)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid event name']);
    exit;
}

// Identity: bo_uid is always available as ext_id.
$uid = brevo_current_uid() ?: brevo_ensure_uid();

$identityIn = is_array($body['identity'] ?? null) ? $body['identity'] : [];
$identity = array_filter([
    'email'  => brevo_normalize_email($identityIn['email']  ?? null),
    'sms'    => brevo_normalize_sms($identityIn['sms']      ?? null),
    'ext_id' => brevo_normalize_ext_id($identityIn['ext_id'] ?? $uid),
], static fn($v) => $v !== null && $v !== '');

$dropAnonymous  = (bool) brevo_config('track.drop_anonymous', true);
$hasContactable = !empty($identity['email']) || !empty($identity['sms']);

if ($dropAnonymous && !$hasContactable) {
    brevo_log('debug', 'Dropping anonymous event', ['event' => $event, 'uid' => $uid]);
    http_response_code(202);
    echo json_encode(['accepted' => true, 'forwarded' => false, 'reason' => 'anonymous']);
    exit;
}

$payload = [
    'identity'    => $identity,
    'contact'     => is_array($body['contact']    ?? null) ? $body['contact']    : [],
    'properties'  => is_array($body['properties'] ?? null) ? $body['properties'] : [],
    'occurred_at' => isset($body['occurred_at']) ? (string) $body['occurred_at'] : gmdate('c'),
];

// --- Sync send with outbox fallback ---
$result = brevo_send($event, $payload);

http_response_code(202);
echo json_encode([
    'accepted'  => true,
    'forwarded' => $result['delivered'] || $result['outboxed'],
    'delivered' => $result['delivered'],
    'outboxed'  => $result['outboxed'],
]);
