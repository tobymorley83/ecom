<?php
/**
 * Outputs the SiteConfig JavaScript object.
 *
 * All traffic detection (fbclid, geo, source resolution) happens
 * server-side in PHP. The browser only receives the resolved config
 * for this specific visitor — no other product files, discount codes,
 * country info, or detection logic is exposed.
 */
if (!isset($config)) {
    $config = require __DIR__ . '/../config.php';
}

// Resolve currency settings from data/currencies.json when this shop
// has switched to $config['currency_code']. No-op in legacy mode.
require_once __DIR__ . '/currency.php';
ecom_currency_apply($config);

// ── BREVO: ensure bo_uid (read-only here; header.php already set it) ─
// If header.php already ran brevo_ensure_uid(), $brevoUid is in scope.
// If config-js.php is included from somewhere else, fall back to read-only.
if (!isset($brevoUid)) {
    require_once __DIR__ . '/../brevo/identity.php';
    $brevoUid = brevo_current_uid();
}

// ── Language override from URL ───────────────────────────────────────
$langOverride = '';
if (isset($_GET['lang']) && in_array(strtolower($_GET['lang']), $config['available_langs'])) {
    $langOverride = strtolower($_GET['lang']);
}

// ── Discount code from URL ───────────────────────────────────────────
$discountParam = '';
if (isset($_GET['discount']) && !empty(trim($_GET['discount']))) {
    $discountParam = strtolower(trim($_GET['discount']));
}

// ── Geo lookup (cached in session) ───────────────────────────────────
function getVisitorCountry() {
    if (isset($_SESSION['visitor_country'])) {
        return $_SESSION['visitor_country'];
    }

    $country = '';

    // CloudFlare header (instant, no API call)
    if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
        $country = strtoupper(trim($_SERVER['HTTP_CF_IPCOUNTRY']));
    }

    // Fallback: ip-api.com
    if (empty($country) || $country === 'XX') {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }

        if ($ip && $ip !== '127.0.0.1' && $ip !== '::1') {
            $ctx = stream_context_create(['http' => ['timeout' => 3]]);
            $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=countryCode", false, $ctx);
            if ($response) {
                $data = json_decode($response, true);
                if (isset($data['countryCode'])) {
                    $country = strtoupper($data['countryCode']);
                }
            }
        }
    }

    $_SESSION['visitor_country'] = $country;
    return $country;
}

// ── Capture FB ad tokens on landing ──────────────────────────────────
// FB ad URLs land on the shop carrying these params; we stash them in
// session so they survive cart → billing → payment_redirect, where
// they get re-attached to the Binom URL (Binom t1..t7 read them by
// parameter name).
$fbAdTokenKeys = [
    'ad_id', 'adset_id', 'campaign_id',
    'ad_name', 'adset_name', 'campaign_name',
    'fbclid',
];
$_SESSION['fb_tokens'] = $_SESSION['fb_tokens'] ?? [];
foreach ($fbAdTokenKeys as $k) {
    if (isset($_GET[$k])) {
        $v = trim((string) $_GET[$k]);
        if ($v !== '') $_SESSION['fb_tokens'][$k] = $v;
    }
}

// ── Resolve traffic source (all server-side) ─────────────────────────
// Check fbclid
$hasFbclid = isset($_GET['fbclid']) && !empty(trim($_GET['fbclid']));

// If fbclid is present on this page load, check geo and save to session
if ($hasFbclid) {
    $visitorCountry = getVisitorCountry();
    $allowedCountries = $config['traffic']['fb']['allowed_countries'] ?? [];
    $geoAllowed = empty($allowedCountries) || in_array($visitorCountry, $allowedCountries);

    if ($geoAllowed) {
        $_SESSION['traffic_source'] = 'fb';
    }
}

// Resolve: session first, then default to nonfb
$trafficSource = $_SESSION['traffic_source'] ?? 'nonfb';

// ── Build ONLY the resolved config for this visitor ──────────────────
$resolvedTraffic = $config['traffic'][$trafficSource] ?? $config['traffic']['nonfb'];

$jsDiscountCodes = [];
foreach ($resolvedTraffic['discount_codes'] as $code => $data) {
    if ($data['active']) {
        // In matrix mode, fixed_price is in EUR — localize for the front-end.
        $jsDiscountCodes[$code] = [
            'label'       => $data['label'],
            'fixed_price' => ecom_local_price($config, (float) $data['fixed_price']),
        ];
    }
}

// Validate discount param against resolved codes
$validDiscountParam = '';
if ($discountParam && isset($jsDiscountCodes[$discountParam])) {
    $validDiscountParam = $discountParam;
    $_SESSION['discount_code'] = $discountParam;
}

// Resolve reviewer pool for visitor's country (only pass the matching one)
$visitorCountry = $_SESSION['visitor_country'] ?? '';
$reviewerPools = $config['reviewer_pools'] ?? [];
$resolvedReviewers = $reviewerPools[$visitorCountry] ?? $reviewerPools['default'] ?? ['names' => [], 'cities' => []];

// ── BREVO: derive store_domain and country for Brevo events ──────────
$brevoStoreDomain = parse_url($config['site_url'], PHP_URL_HOST);
// Strip leading www. so it matches the value seeded in middleware `stores` table
$brevoStoreDomain = preg_replace('/^www\./', '', (string) $brevoStoreDomain);
?>
<script>
var SiteConfig = {
  defaultLang: <?php echo json_encode($config['default_lang']); ?>,
  availableLangs: <?php echo json_encode($config['available_langs']); ?>,
  langOverride: <?php echo json_encode($langOverride); ?>,
  currency: <?php echo json_encode($config['currency']); ?>,
  country: <?php echo json_encode($config['country'] ?? 'US'); ?>,
  productsFile: <?php echo json_encode($resolvedTraffic['products_file']); ?>,
  discountCodes: <?php echo json_encode($jsDiscountCodes); ?>,
  discountParam: <?php echo json_encode($validDiscountParam); ?>,
  trafficSource: <?php echo json_encode($trafficSource); ?>,
  reviewerPool: <?php echo json_encode($resolvedReviewers); ?>,

  // ── BREVO ──────────────────────────────────────────────────────────
  brevo: {
    trackUrl:      '/brevo/track.php',
    storeDomain:   <?php echo json_encode($brevoStoreDomain); ?>,
    uid:           <?php echo json_encode($brevoUid); ?>,
    country:       <?php echo json_encode($visitorCountry); ?>,
    trafficSource: <?php echo json_encode($trafficSource); ?>
  }
};

// Persist discount code to localStorage if passed via URL
(function() {
  var DISCOUNT_KEY = 'shopdeals-discount';
  if (SiteConfig.discountParam) {
    localStorage.setItem(DISCOUNT_KEY, SiteConfig.discountParam);
  }
})();
</script>

<?php
// ── BREVO: emit the popup config as a separate <script> ──────────────
// Reads $config['popup'] and outputs window.BrevoPopupConfig.
// Outputs nothing if popup is disabled or not configured.
require __DIR__ . '/../brevo/popup-config.php';
require __DIR__ . '/../brevo/wheel-config.php';
?>