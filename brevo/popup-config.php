<?php
/**
 * /brevo/popup-config.php
 *
 * Outputs the popup config to JS as `window.BrevoPopupConfig`.
 *
 * Include from header.php (or wherever your config-js.php is) AFTER the main
 * SiteConfig is set. Example:
 *
 *   <script>window.SiteConfig = <?= json_encode([...]) ?>;</script>
 *   <?php require __DIR__ . '/../brevo/popup-config.php'; ?>
 *
 * The popup config is read from $config['popup'] in the shop's config.php.
 * If $config['popup']['enabled'] is false (or the block is missing entirely),
 * this outputs nothing and the popup never shows.
 *
 * See docs/popup-config-reference.md for every supported key.
 */

declare(strict_types=1);

// $config is expected to be in scope (the shop's config.php was already loaded
// by header.php). If it isn't, bail silently — popup just won't appear.
if (!isset($config) || !is_array($config)) return;
if (empty($config['popup']) || !is_array($config['popup'])) return;
if (empty($config['popup']['enabled'])) return;

$p = $config['popup'];

// Pick the language. Prefer the visitor's resolved language from session,
// fall back to the popup's default_lang, then 'es'.
$lang = $_SESSION['lang']
    ?? ($p['default_lang'] ?? 'es');
$lang = preg_match('/^[a-z]{2}$/', $lang) ? $lang : 'es';

// Pick the localized text bundle. If the lang is missing, fall back.
$texts = $p['text'][$lang] ?? $p['text']['es'] ?? $p['text']['en'] ?? [];

// Build the public-facing config (NEVER include secrets here — this goes to JS).
$public = [
    'enabled'       => true,
    'lang'          => $lang,

    // Triggers
    'triggers' => [
        'time_seconds_desktop' => (int) ($p['triggers']['time_seconds_desktop'] ?? 0),
        'time_seconds_mobile'  => (int) ($p['triggers']['time_seconds_mobile']  ?? 20),
        'scroll_percent'       => (int) ($p['triggers']['scroll_percent']       ?? 0),
        'exit_intent_desktop'  => (bool) ($p['triggers']['exit_intent_desktop'] ?? true),
        'on_add_to_cart'       => (bool) ($p['triggers']['on_add_to_cart']      ?? false),
    ],

    // Suppression: how many days to wait before re-showing after dismiss/submit
    'suppress_days_after_dismiss' => (int) ($p['suppress_days_after_dismiss'] ?? 7),
    'suppress_days_after_submit'  => (int) ($p['suppress_days_after_submit']  ?? 90),

    // Fields
    'phone_field'      => (bool) ($p['phone_field']      ?? true),
    'phone_required'   => (bool) ($p['phone_required']   ?? false),
    'sms_opt_in'       => (bool) ($p['sms_opt_in']       ?? true),
    'name_field'       => (bool) ($p['name_field']       ?? false),

    // What gets returned on submit
    'discount_code'    => (string) ($p['discount_code'] ?? ''),

    // Visual
    'image'            => (string) ($p['image']         ?? ''),
    'theme_color'      => (string) ($p['theme_color']   ?? ''),

    // Localized text
    'text' => [
        'headline'         => (string) ($texts['headline']          ?? ''),
        'subhead'          => (string) ($texts['subhead']           ?? ''),
        'email_label'      => (string) ($texts['email_label']       ?? 'Email'),
        'email_placeholder'=> (string) ($texts['email_placeholder'] ?? 'you@example.com'),
        'phone_label'      => (string) ($texts['phone_label']       ?? 'Phone'),
        'phone_placeholder'=> (string) ($texts['phone_placeholder'] ?? '+52...'),
        'phone_hint'       => (string) ($texts['phone_hint']        ?? 'Include country code'),
        'name_label'       => (string) ($texts['name_label']        ?? 'Name'),
        'sms_opt_in_label' => (string) ($texts['sms_opt_in_label']  ?? 'Also notify me by SMS'),
        'submit_button'    => (string) ($texts['submit_button']     ?? 'Get my discount'),
        'submitting'       => (string) ($texts['submitting']        ?? 'Sending...'),
        'success_headline' => (string) ($texts['success_headline']  ?? 'Your discount code'),
        'success_body'     => (string) ($texts['success_body']      ?? 'Use this code at checkout:'),
        'success_close'    => (string) ($texts['success_close']     ?? 'Continue shopping'),
        'error_generic'    => (string) ($texts['error_generic']     ?? 'Something went wrong. Please try again.'),
        'fineprint'        => (string) ($texts['fineprint']         ?? ''),
        'aria_close'       => (string) ($texts['aria_close']        ?? 'Close'),
    ],
];

?>
<script id="brevo-popup-config">
window.BrevoPopupConfig = <?= json_encode($public, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
