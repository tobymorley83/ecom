<?php
/**
 * Brevo middleware client config — shared across all shops.
 *
 * `store_domain` is NOT set here — it is derived per-request from the
 * main site config's `site_url` (see brevo_store_domain() in client.php).
 *
 * Optional override: drop a `brevo/config.local.php` next to this file
 * (gitignored) returning a partial array. Its keys merge over these.
 */
return [
    'middleware_url'    => 'https://shopconnect.magnoleads.com',
    'middleware_secret' => 'sc_5f8b2a7d9e4c1f6b3a8d2e7c9f4b1a6d8e3c7f2b9a4d1e6c3f8b5a7d2e9c4f1b',

    // Timeouts for the live S2S call from track.php to middleware.
    'middleware_connect_timeout_s' => 3,
    'middleware_total_timeout_s'   => 5,

    // Identity cookie
    'cookie' => [
        'name'       => 'bo_uid',
        'lifetime_s' => 63072000,    // 2 years
        'samesite'   => 'Lax',
        'secure'     => true,         // HTTPS required
    ],

    // Outbox (fallback only — populated when live S2S fails)
    'outbox' => [
        'dir'          => __DIR__ . '/outbox',
        'failed_dir'   => __DIR__ . '/outbox-failed',
        'max_attempts' => 8,
        'backoff_s'    => [0, 30, 120, 300, 900, 1800, 3600, 7200],
    ],

    // Track endpoint behavior
    'track' => [
        'drop_anonymous' => true,
        'max_body_bytes' => 16384,
    ],

    // Local logging
    'logging' => [
        'file'  => __DIR__ . '/brevo-client.log',
        'level' => 'info',
    ],
];
