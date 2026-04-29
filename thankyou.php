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
    if (window.Cart && Cart.clear) Cart.clear();
  </script>

<?php include 'includes/footer.php'; ?>
