<?php
/**
 * Per-shop Brevo client config (Phase 2, sync-first edition).
 *
 * Copy to config.local.php on each shop and fill in.
 * Same code on every shop — only `store_domain` changes per deployment.
 */

return [

    // ----------------------------------------------------------------------
    // Middleware (same for ALL shops)
    // ----------------------------------------------------------------------
    'middleware_url'    => 'https://shopconnect.magnoleads.com',
    'middleware_secret' => 'PASTE_SHOP_SHARED_SECRET_FROM_MIDDLEWARE_CONFIG_HERE',

    // Timeouts for the live S2S call from track.php to middleware.
    // Keep these tight — the user is waiting on this request.
    'middleware_connect_timeout_s' => 3,
    'middleware_total_timeout_s'   => 5,

    // ----------------------------------------------------------------------
    // This shop (CHANGE per deployment)
    // ----------------------------------------------------------------------
    'store_domain' => 'ofertasydescuento.com',

    // ----------------------------------------------------------------------
    // Identity cookie
    // ----------------------------------------------------------------------
    'cookie' => [
        'name'        => 'bo_uid',
        'lifetime_s'  => 63072000,    // 2 years
        'samesite'    => 'Lax',
        'secure'      => true,         // HTTPS required; set false for local dev only
    ],

    // ----------------------------------------------------------------------
    // Outbox (fallback only — populated when live S2S fails)
    // ----------------------------------------------------------------------
    'outbox' => [
        'dir'           => __DIR__ . '/outbox',
        'failed_dir'    => __DIR__ . '/outbox-failed',
        'max_attempts'  => 8,
        'backoff_s'     => [0, 30, 120, 300, 900, 1800, 3600, 7200],
    ],

    // ----------------------------------------------------------------------
    // Track endpoint behavior
    // ----------------------------------------------------------------------
    'track' => [
        'drop_anonymous' => true,    // drop events with no email/sms (ext_id alone isn't enough)
        'max_body_bytes' => 16384,
    ],

    // ----------------------------------------------------------------------
    // Local logging
    // ----------------------------------------------------------------------
    'logging' => [
        'file'  => __DIR__ . '/brevo-client.log',
        'level' => 'info',
    ],
];
