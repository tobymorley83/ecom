<?php
/**
 * Shared deploy webhook config — applied to every shop in the repo.
 *
 * `site_key` and `repo_path` are NOT set here:
 *   - site_key  → derived from the host of $config['site_url'] (or basename of the install dir)
 *   - repo_path → defaults to the dir deploy.php is in (__DIR__ of deploy.php)
 *
 * Optional override: `config/config.php` (gitignored) can set
 *   ['deploy']['webhook_secret' | 'site_key' | 'repo_path']
 * which take precedence over these defaults. New shops don't need it.
 */
return [
    'deploy' => [
        'webhook_secret' => 'c8436f6a15dc051710abc96e37ec14586ac4cbb45d07eba1e826d64d19a9f012',
    ],
];
