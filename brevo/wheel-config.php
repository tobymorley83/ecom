<?php
/**
 * /brevo/wheel-config.php
 *
 * Outputs the spin wheel config to JS as `window.BrevoWheelConfig`.
 * Include from config-js.php AFTER the main SiteConfig is set.
 *
 * Text resolution order (most-specific wins):
 *   1. $config['spin_wheel']['text'][lang][key]   — per-shop override in config.php
 *   2. translations.json → [lang].wheel.key        — default text shared across shops
 *   3. Hard-coded fallback string                  — if neither of the above exists
 *
 * To change wheel copy for ALL shops: edit translations.json.
 * To override for ONE shop: add the key to $config['spin_wheel']['text'][lang] in that shop's config.php.
 *
 * If $config['spin_wheel']['enabled'] is false, this outputs nothing.
 */

declare(strict_types=1);

if (!isset($config) || !is_array($config)) return;
if (empty($config['spin_wheel']) || !is_array($config['spin_wheel'])) return;
if (empty($config['spin_wheel']['enabled'])) return;

$w = $config['spin_wheel'];

// Resolve language
$lang = $_SESSION['lang'] ?? ($w['default_lang'] ?? 'es');
$lang = preg_match('/^[a-z]{2}$/', $lang) ? $lang : 'es';

// ---- Load translations.json ONCE and cache statically for this request ----
$translationsPath = __DIR__ . '/../data/translations.json';
static $__translations = null;
if ($__translations === null) {
    $__translations = [];
    if (is_file($translationsPath)) {
        $raw = @file_get_contents($translationsPath);
        if ($raw !== false) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $__translations = $decoded;
            }
        }
    }
}

// ---- Text resolver: config override → translations.json → hard-coded default ----
$configText    = $w['text'][$lang]  ?? [];
$configFallbk  = $w['text']['es']   ?? ($w['text']['en'] ?? []);
$jsonText      = $__translations[$lang]['wheel']    ?? [];
$jsonFallbk    = $__translations['es']['wheel']     ?? ($__translations['en']['wheel'] ?? []);

$T = function (string $key, string $default) use ($configText, $configFallbk, $jsonText, $jsonFallbk): string {
    if (array_key_exists($key, $configText))   return (string) $configText[$key];
    if (array_key_exists($key, $jsonText))     return (string) $jsonText[$key];
    if (array_key_exists($key, $configFallbk)) return (string) $configFallbk[$key];
    if (array_key_exists($key, $jsonFallbk))   return (string) $jsonFallbk[$key];
    return $default;
};

// ---- Build segments — validate each one ----
$segments = [];
$flaggedWinnerIdx = null;   // captures segments with 'winner' => true
foreach (($w['segments'] ?? []) as $idx => $seg) {
    if (!is_array($seg)) continue;
    $type = $seg['type'] ?? 'nothing';
    if (!in_array($type, ['nothing', 'discount', 'free_gift'], true)) continue;

    $entry = [
        'type'  => $type,
        'label' => (string) ($seg['label'] ?? ''),
        'color' => (string) ($seg['color'] ?? '#e4e4e7'),
    ];

    if ($type === 'discount') {
        $entry['discount_code'] = (string) ($seg['discount_code'] ?? '');
    } elseif ($type === 'free_gift') {
        $entry['gift_mode']           = (string) ($seg['gift_mode'] ?? 'same_as_cart_item');
        $entry['fallback_product_id'] = (string) ($seg['fallback_product_id'] ?? '');
        $entry['specific_product_id'] = (string) ($seg['specific_product_id'] ?? '');
    }

    $segments[] = $entry;

    // Position-independent winner flag: 'winner' => true on the prize segment.
    // First flagged non-nothing segment wins if multiple are flagged.
    if (!empty($seg['winner']) && $type !== 'nothing' && $flaggedWinnerIdx === null) {
        $flaggedWinnerIdx = count($segments) - 1;
    }
}

// ---- Resolve the winning segment index ----
// Priority:
//   1. Explicit 'winner' => true flag on a segment (position-independent, preferred)
//   2. winner_segment_index in config IF it points at a valid non-nothing segment
//   3. Fallback: first non-nothing segment found
//
// If the config claims a 'nothing' segment is the winner, we refuse and fall through.
// Otherwise the wheel lands on "Nothing" but tells the user they won — a terrible bug.
$winnerIdx = null;

if ($flaggedWinnerIdx !== null) {
    $winnerIdx = $flaggedWinnerIdx;
} elseif (isset($w['winner_segment_index'])) {
    $candidate = (int) $w['winner_segment_index'];
    if (isset($segments[$candidate]) && $segments[$candidate]['type'] !== 'nothing') {
        $winnerIdx = $candidate;
    }
    // else: silently ignore bad config and fall through to fallback below
}

if ($winnerIdx === null) {
    foreach ($segments as $i => $s) {
        if ($s['type'] !== 'nothing') { $winnerIdx = $i; break; }
    }
}

// ---- Public-facing config ----
$public = [
    'enabled'          => true,
    'lang'             => $lang,

    // Timing
    'show_after_seconds' => (int) ($w['show_after_seconds'] ?? 5),

    // Spin mechanics
    'total_spins'        => (int) ($w['total_spins']  ?? 3),
    'winning_spin'       => (int) ($w['winning_spin'] ?? 3),
    'winner_segment_idx' => $winnerIdx,

    // Fields
    'require_phone'      => (bool) ($w['require_phone'] ?? false),
    'sms_opt_in'         => (bool) ($w['sms_opt_in']    ?? true),

    // Suppression
    'suppress_days_after_claim'   => (int) ($w['suppress_days_after_claim']   ?? 0),
    'suppress_days_after_dismiss' => (int) ($w['suppress_days_after_dismiss'] ?? 0),

    // Visual
    'segments'         => $segments,
    'bubble_position'  => (string) ($w['bubble_position'] ?? 'bottom-left'),
    'theme_color'      => (string) ($w['theme_color'] ?? ''),

    // Sounds
    'sound_spin_start' => (string) ($w['sound_spin_start'] ?? ''),
    'sound_tick'       => (string) ($w['sound_tick']       ?? ''),
    'sound_win'        => (string) ($w['sound_win']        ?? ''),
    'sound_lose'       => (string) ($w['sound_lose']       ?? ''),
    'sound_claim'      => (string) ($w['sound_claim']      ?? ''),
    'sounds_enabled'   => (bool)   ($w['sounds_enabled']   ?? true),
    'confetti_on_win'  => (bool)   ($w['confetti_on_win']  ?? true),

    // Localized text — resolved via $T closure
    'text' => [
        'bubble_label'        => $T('bubble_label',        '🎁'),
        'bubble_tooltip'      => $T('bubble_tooltip',      'Spin to win!'),
        'intro_headline'      => $T('intro_headline',      'Spin the wheel!'),
        'intro_subhead'       => $T('intro_subhead',       'You have 3 chances to win a prize.'),
        'intro_email_label'   => $T('intro_email_label',   'Email'),
        'intro_email_ph'      => $T('intro_email_ph',      'you@example.com'),
        'intro_phone_label'   => $T('intro_phone_label',   'Phone'),
        'intro_phone_ph'      => $T('intro_phone_ph',      '+52...'),
        'intro_phone_hint'    => $T('intro_phone_hint',    'Include country code'),
        'intro_sms_opt_in'    => $T('intro_sms_opt_in',    'Also notify me by SMS'),
        'intro_button'        => $T('intro_button',        'Start spinning'),
        'intro_fineprint'     => $T('intro_fineprint',     ''),
        'spin_button'         => $T('spin_button',         'SPIN'),
        'spinning'            => $T('spinning',            'Spinning...'),
        'spins_left_one'      => $T('spins_left_one',      '1 spin left'),
        'spins_left_many'     => $T('spins_left_many',     '{n} spins left'),
        'result_nothing'      => $T('result_nothing',      'Try again!'),
        'result_win'          => $T('result_win',          'You won!'),
        'claim_headline'      => $T('claim_headline',      'Congratulations!'),
        'claim_form_subhead'  => $T('claim_form_subhead',  'Almost there! Enter your details to claim your prize.'),
        'claim_form_button'   => $T('claim_form_button',   'Claim my prize'),
        'claim_body_discount' => $T('claim_body_discount', 'Your discount code is automatically applied:'),
        'claim_body_gift'     => $T('claim_body_gift',     'A free gift has been added to your cart!'),
        'claim_button'        => $T('claim_button',        'Shop now'),
        'aria_close'          => $T('aria_close',          'Close'),
        'aria_minimize'       => $T('aria_minimize',       'Minimize'),
        'error_generic'       => $T('error_generic',       'Something went wrong. Please try again.'),
    ],
];

?>
<script id="brevo-wheel-config">
window.BrevoWheelConfig = <?= json_encode($public, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
