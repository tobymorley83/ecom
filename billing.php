<?php
/**
 * Billing Page
 * ============
 * Receives cart data via POST from cart-page.js, validates the order,
 * and renders the billing form. On submit the form posts to
 * /payment_redirect.php which saves the lead and redirects to
 * either the 3rd-party payment gateway (FB traffic) or the
 * thank-you page (non-FB traffic).
 */

$pageTitle = 'Billing';
$activePage = 'cart';
$pageScripts = ['/js/countries.js', '/js/billing.js'];

$config = require __DIR__ . '/config.php';

// ── Validate request ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /cart.php');
    exit;
}

$productIds    = json_decode($_POST['product_ids']   ?? '[]', true);
$productNames  = json_decode($_POST['product_names'] ?? '[]', true);
$subtotal      = floatval($_POST['subtotal'] ?? 0);
$total         = floatval($_POST['total']    ?? 0);
$discountCode  = trim($_POST['discount_code'] ?? '');
$lang          = $_POST['lang'] ?? $config['default_lang'];
$trafficSource = ($_POST['traffic_source'] ?? 'nonfb') === 'fb' ? 'fb' : 'nonfb';

if (empty($productIds) || empty($productNames)) {
    header('Location: /cart.php');
    exit;
}

// ── Re-validate discount server-side ─────────────────────────────────
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

// ── Build bundle name + image URL (same logic as checkout.php) ───────
$bundleName     = 'Product Bundle: ' . implode(' + ', $productNames);
$imageIds       = implode(',', $productIds);
$bundleImageUrl = $config['site_url'] . '/bundle-image.php?q=' . urlencode($trafficSource . ',' . $imageIds);

$totalFormatted = number_format(
    $total,
    $config['currency']['decimals'],
    $config['currency']['decimal_sep'],
    ''
);

include 'includes/header.php';
?>

  <section class="checkout-form-page">
    <div class="container">
      <div class="checkout-form-layout">

        <!-- Order Summary -->
        <div class="checkout-order-summary">
          <h2 data-i18n="checkout.order_summary">Order Summary</h2>

          <div class="checkout-bundle-image">
            <img src="<?php echo htmlspecialchars($bundleImageUrl); ?>" alt="Order bundle">
          </div>

          <div class="checkout-product-name">
            <?php echo htmlspecialchars($bundleName); ?>
          </div>

          <div class="checkout-total-row">
            <span data-i18n="cart.total">Total</span>
            <span class="checkout-total-price"><?php echo htmlspecialchars($config['currency']['symbol'] . $totalFormatted . ' ' . $config['currency']['code']); ?></span>
          </div>

          <div class="checkout-trust-badges">
            <div class="checkout-trust-badge">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
              <span>SSL Secure</span>
            </div>
            <div class="checkout-trust-badge">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
              <span data-i18n="trust.guarantee">90-Day Guarantee</span>
            </div>
            <div class="checkout-trust-badge">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
              <span data-i18n="trust.free_delivery">Free Delivery</span>
            </div>
          </div>
        </div>

        <!-- Billing Form -->
        <div class="checkout-form-container">
          <h1 data-i18n="billing.title">Billing Information</h1>
          <p class="checkout-form-intro" data-i18n="billing.intro">Please fill in your details to continue.</p>

          <form id="billingForm" class="checkout-form-fields" method="POST" action="/payment_redirect.php" autocomplete="on" novalidate>

            <h3 data-i18n="checkout.personal_info">Personal Information</h3>

            <div class="form-row form-row-2col">
              <div class="form-group">
                <label for="bf_firstname" data-i18n="checkout.first_name">First Name</label>
                <input type="text" id="bf_firstname" name="firstname"
                       autocomplete="given-name" required>
              </div>
              <div class="form-group">
                <label for="bf_lastname" data-i18n="checkout.last_name">Last Name</label>
                <input type="text" id="bf_lastname" name="lastname"
                       autocomplete="family-name" required>
              </div>
            </div>

            <div class="form-group">
              <label for="bf_email" data-i18n="checkout.email">Email</label>
              <input type="email" id="bf_email" name="email"
                     autocomplete="email" required>
            </div>

            <div class="form-group">
              <label for="bf_phone" data-i18n="checkout.phone">Phone Number</label>
              <div class="phone-input-row">
                <select id="bf_phone_prefix" name="phone_prefix"
                        autocomplete="tel-country-code" class="phone-prefix-select" required>
                  <!-- populated by billing.js -->
                </select>
                <input type="tel" id="bf_phone" name="phone"
                       autocomplete="tel-national" inputmode="tel"
                       class="phone-number-input" required>
              </div>
            </div>

            <h3 data-i18n="checkout.shipping_address">Shipping Address</h3>

            <div class="form-group">
              <label for="bf_address" data-i18n="checkout.address">Street Address</label>
              <input type="text" id="bf_address" name="address"
                     autocomplete="street-address" required>
            </div>

            <div class="form-row form-row-2col">
              <div class="form-group">
                <label for="bf_city" data-i18n="checkout.city">City</label>
                <input type="text" id="bf_city" name="city"
                       autocomplete="address-level2" required>
              </div>
              <div class="form-group">
                <label for="bf_zip" data-i18n="checkout.zip">Postal Code</label>
                <input type="text" id="bf_zip" name="zip"
                       autocomplete="postal-code" inputmode="numeric" required>
              </div>
            </div>

            <div class="form-group">
              <label for="bf_country" data-i18n="checkout.country">Country</label>
              <select id="bf_country" name="country"
                      autocomplete="country" required>
                <!-- populated by billing.js -->
              </select>
            </div>

            <!-- Order data forwarded to /payment_redirect.php -->
            <input type="hidden" name="product_ids"     value="<?php echo htmlspecialchars(json_encode($productIds)); ?>">
            <input type="hidden" name="product_names"   value="<?php echo htmlspecialchars(json_encode($productNames)); ?>">
            <input type="hidden" name="subtotal"        value="<?php echo htmlspecialchars((string) $subtotal); ?>">
            <input type="hidden" name="total"           value="<?php echo htmlspecialchars((string) $total); ?>">
            <input type="hidden" name="discount_code"   value="<?php echo htmlspecialchars($discountCode); ?>">
            <input type="hidden" name="lang"            value="<?php echo htmlspecialchars($lang); ?>">
            <input type="hidden" name="traffic_source"  value="<?php echo htmlspecialchars($trafficSource); ?>">
            <input type="hidden" name="bundle_name"     value="<?php echo htmlspecialchars($bundleName); ?>">
            <input type="hidden" name="bundle_image"    value="<?php echo htmlspecialchars($bundleImageUrl); ?>">

            <button type="submit" class="checkout-submit-btn">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
              <span data-i18n="billing.go_to_payment">Go to Payment</span>
            </button>
          </form>
        </div>

      </div>
    </div>
  </section>

<?php include 'includes/footer.php'; ?>
