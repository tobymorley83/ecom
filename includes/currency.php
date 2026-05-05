<?php
/**
 * Currency resolver — loads data/currencies.json once and exposes helpers
 * for converting EUR amounts in config.php to local currency (psychological
 * pricing matrix) and for picking a checkout URL by total-amount tier.
 *
 * Activation: a shop opts in by setting $config['currency_code'] = 'XXX'
 * in its config.php. ecom_currency_apply() then populates $config['currency']
 * (symbol/decimals/separators/etc.) from the matrix entry. Legacy shops with
 * the verbose $config['currency'] block keep working unchanged.
 */
declare(strict_types=1);

function ecom_currency_matrix(): array
{
    static $matrix = null;
    if ($matrix === null) {
        $path = __DIR__ . '/../data/currencies.json';
        $matrix = [];
        if (is_file($path)) {
            $raw = @file_get_contents($path);
            $decoded = $raw !== false ? json_decode($raw, true) : null;
            if (is_array($decoded)) $matrix = $decoded;
        }
    }
    return $matrix;
}

/**
 * If $config['currency_code'] is set, populate $config['currency'] from the
 * matrix entry (without overwriting any keys the shop has already supplied).
 * No-op in legacy mode.
 */
function ecom_currency_apply(array &$config): void
{
    if (empty($config['currency_code'])) return;

    $code   = (string) $config['currency_code'];
    $matrix = ecom_currency_matrix();
    if (!isset($matrix[$code])) return;

    $entry = $matrix[$code];
    $existing = is_array($config['currency'] ?? null) ? $config['currency'] : [];

    $config['currency'] = array_merge([
        'symbol'        => (string) ($entry['symbol']        ?? ''),
        'code'          => $code,
        'position'      => (string) ($entry['position']      ?? 'before'),
        'decimals'      => (int)    ($entry['decimals']      ?? 2),
        'thousands_sep' => (string) ($entry['thousands_sep'] ?? ','),
        'decimal_sep'   => (string) ($entry['decimal_sep']   ?? '.'),
    ], $existing);
}

/**
 * Look up the local-currency equivalent of a EUR price-point.
 * Falls back to nearest matrix column if not an exact match.
 * Returns null if currency unknown.
 */
function ecom_eur_to_local(float $eur, string $code): ?float
{
    if ($code === 'EUR') return $eur;

    $matrix = ecom_currency_matrix();
    if (!isset($matrix[$code]['prices']) || !is_array($matrix[$code]['prices'])) {
        return null;
    }
    $prices = $matrix[$code]['prices'];

    $bestKey  = null;
    $bestDiff = PHP_FLOAT_MAX;
    foreach ($prices as $eurKey => $localVal) {
        $diff = abs((float) $eurKey - $eur);
        if ($diff < $bestDiff) {
            $bestDiff = $diff;
            $bestKey  = $eurKey;
        }
    }
    return $bestKey !== null ? (float) $prices[$bestKey] : null;
}

/**
 * Return a price in the shop's local currency.
 *   - matrix mode: treat $price as EUR and look up the local equivalent.
 *   - legacy mode: pass through unchanged.
 */
function ecom_local_price(array $config, float $price): float
{
    if (empty($config['currency_code'])) return $price;
    $code = (string) ($config['currency']['code'] ?? 'EUR');
    if ($code === 'EUR') return $price;
    $local = ecom_eur_to_local($price, $code);
    return $local !== null ? $local : $price;
}

/**
 * Pick a checkout URL from a tier list keyed by EUR `max` thresholds.
 * Each tier:  [ 'max' => float|null, 'url' => string ]
 * `max => null` is the catch-all (used last). Returns the fallback if
 * no tier matches or the list is empty/invalid.
 */
function ecom_pick_checkout_url(array $config, array $tiers, float $localTotal, string $fallback): string
{
    if (empty($tiers)) return $fallback;

    // Sort: ascending max, with nulls last
    usort($tiers, function ($a, $b) {
        $am = $a['max'] ?? null;
        $bm = $b['max'] ?? null;
        if ($am === null && $bm === null) return 0;
        if ($am === null) return 1;
        if ($bm === null) return -1;
        return (float) $am <=> (float) $bm;
    });

    foreach ($tiers as $tier) {
        $url = (string) ($tier['url'] ?? '');
        if ($url === '') continue;

        // Catch-all
        if (!array_key_exists('max', $tier) || $tier['max'] === null) {
            return $url;
        }

        $eurMax   = (float) $tier['max'];
        $localMax = ecom_local_price($config, $eurMax);
        if ($localTotal < $localMax) {
            return $url;
        }
    }

    return $fallback;
}
