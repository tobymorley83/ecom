<?php
/**
 * Payment Redirect Handler
 * ========================
 * Receives the billing form POST from /billing.php.
 * 1. Re-validates the order server-side.
 * 2. Builds an E.164 phone from prefix + national number.
 * 3. Fires `billing_submitted` to the Brevo middleware (which stores
 *    the lead in its own MySQL and pushes onward to Brevo).
 * 4. FB traffic  → redirect to 3rd-party gateway with order params
 *                  plus aff_unique* / aff_sub* / adv_sub params.
 *    Non-FB      → redirect to /thankyou.php (no payment).
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/brevo/client.php';
require_once __DIR__ . '/brevo/identity.php';
require_once __DIR__ . '/includes/currency.php';

$config = require __DIR__ . '/config.php';
ecom_currency_apply($config);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: /cart.php');
    exit;
}

// ── Order data ───────────────────────────────────────────────────────
$productIds    = json_decode($_POST['product_ids']   ?? '[]', true) ?: [];
$productNames  = json_decode($_POST['product_names'] ?? '[]', true) ?: [];
$cartItems     = json_decode($_POST['cart_items']    ?? '[]', true) ?: [];
$subtotal      = (float) ($_POST['subtotal'] ?? 0);
$total         = (float) ($_POST['total']    ?? 0);
$discountCode  = trim($_POST['discount_code'] ?? '');
$lang          = $_POST['lang'] ?? $config['default_lang'];
$trafficSource = (($_POST['traffic_source'] ?? 'nonfb') === 'fb') ? 'fb' : 'nonfb';

if (empty($productIds) || empty($productNames)) {
    header('Location: /cart.php');
    exit;
}

// Re-validate discount server-side (don't trust the form)
$discountCodes = $config['traffic'][$trafficSource]['discount_codes'] ?? [];
if ($discountCode !== '') {
    $codeKey = strtolower($discountCode);
    if (isset($discountCodes[$codeKey]) && $discountCodes[$codeKey]['active']) {
        $total = ecom_local_price($config, (float) $discountCodes[$codeKey]['fixed_price']);
    } else {
        $discountCode = '';
        $total = $subtotal;
    }
}

$bundleName     = 'Product Bundle: ' . implode(' + ', $productNames);
$imageIds       = implode(',', $productIds);
$bundleImageUrl = $config['site_url'] . '/bundle-image.php?q=' . urlencode($trafficSource . ',' . $imageIds);

$totalFormatted = number_format(
    $total,
    $config['currency']['decimals'],
    $config['currency']['decimal_sep'],
    ''
);

// ── Billing fields ───────────────────────────────────────────────────
$firstname   = trim($_POST['firstname']    ?? '');
$lastname    = trim($_POST['lastname']     ?? '');
$email       = trim($_POST['email']        ?? '');
$phonePrefix = trim($_POST['phone_prefix'] ?? '');
$phoneRaw    = trim($_POST['phone']        ?? '');
$address     = trim($_POST['address']      ?? '');
$city        = trim($_POST['city']         ?? '');
$zip         = trim($_POST['zip']          ?? '');
$country     = trim($_POST['country']      ?? '');

// Required check (defence in depth — JS already validated)
if ($firstname === '' || $lastname === '' || $email === '' ||
    $phonePrefix === '' || $phoneRaw === '' ||
    $address === '' || $city === '' || $zip === '' || $country === '') {
    header('Location: /cart.php');
    exit;
}

// E.164: prefix as-is + digits-only of national number
$phoneDigits = preg_replace('/[^\d]/', '', $phoneRaw) ?? '';
$prefixDigits = preg_replace('/[^\d]/', '', $phonePrefix) ?? '';
$phoneE164 = '+' . $prefixDigits . $phoneDigits;

// ── Push lead to middleware (saves to its MySQL → Brevo) ─────────────
$uid = brevo_current_uid() ?: brevo_ensure_uid();

$brevoPayload = [
    'identity' => array_filter([
        'email'  => brevo_normalize_email($email),
        'sms'    => brevo_normalize_sms($phoneE164),
        'ext_id' => brevo_normalize_ext_id($uid),
    ], static fn($v) => $v !== null && $v !== ''),

    'contact' => [
        'firstname' => $firstname,
        'lastname'  => $lastname,
        'address'   => $address,
        'city'      => $city,
        'zip'       => $zip,
        'country'   => $country,
    ],

    'properties' => [
        'traffic_source' => $trafficSource,
        'lang'           => $lang,
        'currency'       => $config['currency']['code'],
        'subtotal'       => $subtotal,
        'total'          => $total,
        'discount_code'  => $discountCode,
        'product_ids'    => $productIds,
        'product_names'  => $productNames,
        'cart_items'     => $cartItems,
        'bundle_name'    => $bundleName,
        'bundle_image'   => $bundleImageUrl,
        'phone_e164'     => $phoneE164,
    ],

    'occurred_at' => gmdate('c'),
];

brevo_send('billing_submitted', $brevoPayload);

$orderId = 'ORD-' . strtoupper(bin2hex(random_bytes(6)));

// ── Branch: non-FB → thank-you page; FB → 3rd-party gateway ──────────
if ($trafficSource !== 'fb') {
    $_SESSION['last_order'] = [
        'order_id'       => $orderId,
        'bundle_name'    => $bundleName,
        'bundle_image'   => $bundleImageUrl,
        'total'          => $totalFormatted,
        'total_raw'      => $total,
        'currency_code'  => $config['currency']['code'],
        'currency_symbol'=> $config['currency']['symbol'],
        'firstname'      => $firstname,
        'email'          => $email,
        'cart_items'     => $cartItems,
        'discount_code'  => $discountCode,
    ];
    header('Location: /thankyou.php');
    exit;
}

// FB branch — append billing fields to gateway URL alongside order params.
// If `checkout_urls` (EUR-tier list) is set, pick the URL whose `max` (in EUR)
// converts to the smallest local threshold greater than the order total.
// Falls back to the single `checkout_url` for any unmatched / unconfigured case.
$fbConfig    = $config['traffic']['fb'] ?? [];
$tiers       = is_array($fbConfig['checkout_urls'] ?? null) ? $fbConfig['checkout_urls'] : [];
$fallbackUrl = (string) ($fbConfig['checkout_url'] ?? '');
$checkoutUrl = ecom_pick_checkout_url($config, $tiers, $total, $fallbackUrl);
if ($checkoutUrl === '' || $checkoutUrl === 'CHANGE_ME_PER_SITE') {
    // Misconfigured — fall back to thank-you so we don't drop the lead
    $_SESSION['last_order'] = [
        'order_id'       => $orderId,
        'bundle_name'    => $bundleName,
        'bundle_image'   => $bundleImageUrl,
        'total'          => $totalFormatted,
        'total_raw'      => $total,
        'currency_code'  => $config['currency']['code'],
        'currency_symbol'=> $config['currency']['symbol'],
        'firstname'      => $firstname,
        'email'          => $email,
        'cart_items'     => $cartItems,
        'discount_code'  => $discountCode,
    ];
    header('Location: /thankyou.php');
    exit;
}

$params = [
    'productname'  => $bundleName,
    'totalprice'   => $totalFormatted,
    'productimage' => $bundleImageUrl,
    'currency'     => $config['currency']['code'],
    'source'       => $bundleName . '___' . $bundleImageUrl,

    // Billing fields appended per affiliate spec
    'aff_unique1'  => $firstname,
    'aff_unique2'  => $lastname,
    'aff_unique3'  => $country,
    'aff_unique4'  => $zip,
    'aff_unique5'  => $city,
    'aff_sub3'     => $address,
    'aff_sub4'     => $email,
    'adv_sub'      => $phoneE164,
];

$separator = (strpos($checkoutUrl, '?') !== false) ? '&' : '?';
$redirectUrl = $checkoutUrl . $separator . http_build_query($params);

header('Location: ' . $redirectUrl);
exit;
