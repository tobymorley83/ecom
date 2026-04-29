<?php
/**
 * Deploy configuration (per-site).
 * Copy to config/config.php on each server and fill in.
 * Used by deploy.php for webhook authentication and logging.
 */
return [
    'deploy' => [
        'site_key'       => 'ofertasydescuento',
        'webhook_secret' => 'CHANGE_ME',
        'repo_path'      => '/www/wwwroot/ofertasydescuento.com',
    ],
];
