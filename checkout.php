<?php
/**
 * Checkout Handler
 * ================
 * Receives cart data via POST from the cart page, builds the bundle
 * product name and composite image URL, then redirects to the
 * configured payment gateway with all parameters.
 *
 * POST fields expected:
 *   product_ids    — JSON array of product IDs
 *   product_names  — JSON array of product display names
 *   subtotal       — Original subtotal
 *   total          — Final total (after discount if any)
 *   discount_code  — Applied discount code (empty if none)
 *   lang           — Current language
 *   traffic_source — 'fb' or 'nonfb'
 */

$config = require __DIR__ . '/config.php';

// ── Validate request ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /cart.php');
    exit;
}

$productIds   = json_decode($_POST['product_ids'] ?? '[]', true);
$productNames = json_decode($_POST['product_names'] ?? '[]', true);
$subtotal     = floatval($_POST['subtotal'] ?? 0);
$total        = floatval($_POST['total'] ?? 0);
$discountCode = trim($_POST['discount_code'] ?? '');
$lang         = $_POST['lang'] ?? $config['default_lang'];
$trafficSource = ($_POST['traffic_source'] ?? 'nonfb') === 'fb' ? 'fb' : 'nonfb';

if (empty($productIds) || empty($productNames)) {
    header('Location: /cart.php');
    exit;
}

// ── Validate discount code server-side ───────────────────────────────
$discountCodes = $config['traffic'][$trafficSource]['discount_codes'] ?? [];

if ($discountCode !== '') {
    $codeKey = strtolower($discountCode);
    if (isset($discountCodes[$codeKey]) && $discountCodes[$codeKey]['active']) {
        $total = $discountCodes[$codeKey]['fixed_price'];
    } else {
        $discountCode = '';
        $total = $subtotal;
    }
}

// ── Build bundle product name ────────────────────────────────────────
$bundleName = 'Product Bundle: ' . implode(' + ', $productNames);

// ── Build bundle image URL ───────────────────────────────────────────
// Single parameter q= with format: source,id1,id2,id3
// No & in the URL so tracking redirects can't break it.
$imageIds = implode(',', $productIds);
$bundleImageUrl = $config['site_url'] . '/bundle-image.php?q=' . urlencode($trafficSource . ',' . $imageIds);

// ── Format total price ───────────────────────────────────────────────
$totalFormatted = number_format(
    $total,
    $config['currency']['decimals'],
    $config['currency']['decimal_sep'],
    ''
);

// ── Build payment gateway redirect URL ───────────────────────────────
$checkoutParams = http_build_query([
    'productname'  => $bundleName,
    'totalprice'   => $totalFormatted,
    'productimage' => $bundleImageUrl,
    'currency'     => $config['currency']['code'],
    'source'       => $bundleName . '___' . $bundleImageUrl,
]);

$checkoutUrl = $config['traffic'][$trafficSource]['checkout_url'] ?? '/checkout_form.php';
$separator = (strpos($checkoutUrl, '?') !== false) ? '&' : '?';
$redirectUrl = $checkoutUrl . $separator . $checkoutParams;

// ── Redirect ─────────────────────────────────────────────────────────
header('Location: ' . $redirectUrl);
exit;
