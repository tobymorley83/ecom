<?php
/**
 * OPTIONAL per-site override for the deploy webhook config.
 *
 * In most cases you don't need this file — config/deploy.php (tracked,
 * shared) provides the webhook secret, and deploy.php derives site_key
 * and repo_path automatically.
 *
 * Copy to config/config.php only when you need to override one of these
 * (e.g. to rotate the secret on a single shop). The override merges
 * recursively over the shared config.
 */
return [
    'deploy' => [
        // 'webhook_secret' => 'override-this-shop-with-a-different-secret',
        // 'site_key'       => 'custom-key-for-the-deploy-log',
        // 'repo_path'      => '/some/other/path',
    ],
];
