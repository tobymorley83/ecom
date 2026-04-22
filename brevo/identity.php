<?php
/**
 * /brevo/identity.php
 *
 * Manages the bo_uid first-party cookie. Call brevo_ensure_uid() once per
 * page request from header.php BEFORE any output is sent.
 */

declare(strict_types=1);

require_once __DIR__ . '/client.php';

/**
 * Ensure the bo_uid cookie is set. Returns the value (existing or newly
 * generated). MUST be called before any output (it sets a cookie).
 */
function brevo_ensure_uid(): string
{
    $cfg = brevo_config('cookie');
    $name     = $cfg['name'];
    $lifetime = (int) $cfg['lifetime_s'];
    $secure   = (bool) $cfg['secure'];
    $samesite = (string) $cfg['samesite'];

    if (!empty($_COOKIE[$name]) && brevo_uid_is_valid($_COOKIE[$name])) {
        return $_COOKIE[$name];
    }

    $uid = brevo_generate_uid();

    if (!headers_sent()) {
        setcookie($name, $uid, [
            'expires'  => time() + $lifetime,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => false,        // JS needs to read it
            'samesite' => $samesite,
        ]);
    }
    // So the rest of THIS request can also see it
    $_COOKIE[$name] = $uid;

    return $uid;
}

/**
 * Return current bo_uid without setting it (read-only).
 */
function brevo_current_uid(): ?string
{
    $name = brevo_config('cookie.name');
    if (empty($_COOKIE[$name])) return null;
    if (!brevo_uid_is_valid($_COOKIE[$name])) return null;
    return $_COOKIE[$name];
}

function brevo_generate_uid(): string
{
    // 16 random bytes -> 32 hex chars -> 35-char total with prefix.
    return 'bo_' . bin2hex(random_bytes(16));
}

function brevo_uid_is_valid(string $uid): bool
{
    return (bool) preg_match('/^bo_[a-f0-9]{32}$/', $uid);
}
