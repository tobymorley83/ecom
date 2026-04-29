<?php
/**
 * Thank-You Page
 * ==============
 * Shown after a non-FB billing submission. Reads the last order
 * summary from session, clears the cart on the client, and
 * confirms that the team will be in touch.
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$lastOrder = $_SESSION['last_order'] ?? null;
unset($_SESSION['last_order']);  // single-use

if (!$lastOrder) {
    header('Location: /');
    exit;
}

$pageTitle = 'Thank You';
$activePage = 'cart';
$pageScripts = [];
include 'includes/header.php';
?>

  <section class="checkout-form-page">
    <div class="container">
      <div class="checkout-success" style="display:block;max-width:640px;margin:0 auto;">
        <div class="checkout-success-icon">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="16 8 10.5 14 8 11.5"></polyline></svg>
        </div>

        <h1 data-i18n="thankyou.title">Thank You!</h1>
        <p data-i18n="thankyou.message">We have received your order request. Our team will contact you shortly to confirm details and arrange delivery.</p>

        <?php if (!empty($lastOrder['bundle_image'])): ?>
        <div class="checkout-bundle-image" style="margin:24px auto;max-width:280px;">
          <img src="<?php echo htmlspecialchars($lastOrder['bundle_image']); ?>" alt="Order bundle">
        </div>
        <?php endif; ?>

        <div class="checkout-product-name">
          <?php echo htmlspecialchars($lastOrder['bundle_name']); ?>
        </div>

        <div class="checkout-total-row">
          <span data-i18n="cart.total">Total</span>
          <span class="checkout-total-price">
            <?php echo htmlspecialchars($lastOrder['currency_symbol'] . $lastOrder['total'] . ' ' . $lastOrder['currency_code']); ?>
          </span>
        </div>

        <p class="checkout-success-sub" data-i18n="thankyou.email_check">
          A confirmation has been sent to your email.
        </p>

        <a href="/" class="hero-cta" id="btn-continue-shopping" data-i18n="cart.continue_shopping">Continue Shopping</a>
      </div>
    </div>
  </section>

  <script>
    (function() {
      // Fire the Yandex / GA Enhanced Ecommerce purchase event.
      // Run after Ecommerce module is available (loaded in footer.php).
      var orderData = <?php echo json_encode([
          'order_id'      => $lastOrder['order_id'] ?? '',
          'cart_items'    => $lastOrder['cart_items'] ?? [],
          'total_raw'     => (float) ($lastOrder['total_raw'] ?? 0),
          'discount_code' => $lastOrder['discount_code'] ?? '',
      ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

      function firePurchase() {
        if (window.Ecommerce && orderData.order_id) {
          Ecommerce.purchase(
            orderData.order_id,
            orderData.cart_items || [],
            orderData.total_raw,
            orderData.discount_code || null
          );
        }
        if (window.Cart && Cart.clear) Cart.clear();
      }

      if (window.Ecommerce) firePurchase();
      else document.addEventListener('DOMContentLoaded', firePurchase);
    })();
  </script>

<?php include 'includes/footer.php'; ?>
