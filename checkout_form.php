<?php
$pageTitle = 'Request Order';
$activePage = 'cart';
$pageScripts = [];
include 'includes/header.php';

// Read order details from query params (passed from checkout.php redirect)
$productName  = $_GET['productname'] ?? '';
$totalPrice   = $_GET['totalprice'] ?? '0.00';
$productImage = $_GET['productimage'] ?? '';
$currencyCode = $_GET['currency'] ?? $config['currency']['code'];
$currencySymbol = $config['currency']['symbol'];
?>

  <section class="checkout-form-page">
    <div class="container">
      <div class="checkout-form-layout">

        <!-- Order Summary Column -->
        <div class="checkout-order-summary">
          <h2 data-i18n="checkout.order_summary">Order Summary</h2>

          <?php if ($productImage): ?>
          <div class="checkout-bundle-image">
            <img src="<?php echo htmlspecialchars($productImage); ?>" alt="Order bundle">
          </div>
          <?php endif; ?>

          <div class="checkout-product-name">
            <?php echo htmlspecialchars($productName); ?>
          </div>

          <div class="checkout-total-row">
            <span data-i18n="cart.total">Total</span>
            <span class="checkout-total-price"><?php echo htmlspecialchars($currencySymbol . $totalPrice . ' ' . $currencyCode); ?></span>
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

        <!-- Order Form Column -->
        <div class="checkout-form-container">
          <h1 data-i18n="checkout.request_order">Request Order</h1>
          <p class="checkout-form-intro" data-i18n="checkout.form_intro">Please fill in your details below. Our customer support team will verify your order, confirm product availability, and contact you to complete the purchase.</p>

          <div id="checkoutFormWrapper">
            <div class="checkout-form-fields">

              <h3 data-i18n="checkout.personal_info">Personal Information</h3>

              <div class="form-row form-row-2col">
                <div class="form-group">
                  <label for="cf_firstname" data-i18n="checkout.first_name">First Name</label>
                  <input type="text" id="cf_firstname" name="firstname" required>
                </div>
                <div class="form-group">
                  <label for="cf_lastname" data-i18n="checkout.last_name">Last Name</label>
                  <input type="text" id="cf_lastname" name="lastname" required>
                </div>
              </div>

              <div class="form-row form-row-2col">
                <div class="form-group">
                  <label for="cf_email" data-i18n="checkout.email">Email</label>
                  <input type="email" id="cf_email" name="email" required>
                </div>
                <div class="form-group">
                  <label for="cf_phone" data-i18n="checkout.phone">Phone Number</label>
                  <input type="tel" id="cf_phone" name="phone" required>
                </div>
              </div>

              <h3 data-i18n="checkout.shipping_address">Shipping Address</h3>

              <div class="form-group">
                <label for="cf_address" data-i18n="checkout.address">Street Address</label>
                <input type="text" id="cf_address" name="address" required>
              </div>

              <div class="form-group">
                <label for="cf_address2" data-i18n="checkout.address2">Apartment / Suite / Floor (optional)</label>
                <input type="text" id="cf_address2" name="address2">
              </div>

              <div class="form-row form-row-3col">
                <div class="form-group">
                  <label for="cf_city" data-i18n="checkout.city">City</label>
                  <input type="text" id="cf_city" name="city" required>
                </div>
                <div class="form-group">
                  <label for="cf_state" data-i18n="checkout.state">State / Province</label>
                  <input type="text" id="cf_state" name="state" required>
                </div>
                <div class="form-group">
                  <label for="cf_zip" data-i18n="checkout.zip">Postal Code</label>
                  <input type="text" id="cf_zip" name="zip" required>
                </div>
              </div>

              <div class="form-group">
                <label for="cf_country" data-i18n="checkout.country">Country</label>
                <input type="text" id="cf_country" name="country" required>
              </div>

              <h3 data-i18n="checkout.additional_notes">Additional Notes (optional)</h3>

              <div class="form-group">
                <textarea id="cf_notes" name="notes" rows="3" data-i18n-placeholder="checkout.notes_placeholder" placeholder="Any special requests or delivery instructions..."></textarea>
              </div>

              <!-- Hidden order data -->
              <input type="hidden" id="cf_productname" value="<?php echo htmlspecialchars($productName); ?>">
              <input type="hidden" id="cf_totalprice" value="<?php echo htmlspecialchars($totalPrice); ?>">
              <input type="hidden" id="cf_currency" value="<?php echo htmlspecialchars($currencyCode); ?>">
              <input type="hidden" id="cf_productimage" value="<?php echo htmlspecialchars($productImage); ?>">

              <button type="button" class="checkout-submit-btn" onclick="submitOrderRequest()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <span data-i18n="checkout.request_order_btn">Request Order</span>
              </button>

              <p class="checkout-form-note" data-i18n="checkout.form_note">No payment is required at this time. Our team will review your order and contact you within 24 hours to confirm availability, verify your details, and arrange payment.</p>

            </div>
          </div>

          <!-- Success message (hidden by default) -->
          <div id="checkoutSuccess" class="checkout-success" style="display:none;">
            <div class="checkout-success-icon">
              <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="16 8 10.5 14 8 11.5"></polyline></svg>
            </div>
            <h2 data-i18n="checkout.success_title">Order Request Received!</h2>
            <p data-i18n="checkout.success_message">Thank you for your order request. Our customer support team will review your order, verify product availability, and contact you within 24 hours to finalize your purchase.</p>
            <p class="checkout-success-sub" data-i18n="checkout.success_check_email">Please check your email for a confirmation with your order details.</p>
            <a href="/" class="hero-cta" data-i18n="cart.continue_shopping">Continue Shopping</a>
          </div>

        </div>
      </div>
    </div>
  </section>

  <script>
  function submitOrderRequest() {
    // Basic validation
    var required = ['cf_firstname','cf_lastname','cf_email','cf_phone','cf_address','cf_city','cf_state','cf_zip','cf_country'];
    var allValid = true;

    for (var i = 0; i < required.length; i++) {
      var el = document.getElementById(required[i]);
      if (!el || !el.value.trim()) {
        el.style.borderColor = '#e94560';
        allValid = false;
      } else {
        el.style.borderColor = '';
      }
    }

    // Email format check
    var emailEl = document.getElementById('cf_email');
    if (emailEl && emailEl.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailEl.value)) {
      emailEl.style.borderColor = '#e94560';
      allValid = false;
    }

    if (!allValid) {
      Cart.showToast(I18n.t('checkout.fill_required'));
      return;
    }

    // Gather all data
    var orderData = {
      firstname:    document.getElementById('cf_firstname').value.trim(),
      lastname:     document.getElementById('cf_lastname').value.trim(),
      email:        document.getElementById('cf_email').value.trim(),
      phone:        document.getElementById('cf_phone').value.trim(),
      address:      document.getElementById('cf_address').value.trim(),
      address2:     document.getElementById('cf_address2').value.trim(),
      city:         document.getElementById('cf_city').value.trim(),
      state:        document.getElementById('cf_state').value.trim(),
      zip:          document.getElementById('cf_zip').value.trim(),
      country:      document.getElementById('cf_country').value.trim(),
      notes:        document.getElementById('cf_notes').value.trim(),
      productname:  document.getElementById('cf_productname').value,
      totalprice:   document.getElementById('cf_totalprice').value,
      currency:     document.getElementById('cf_currency').value,
      productimage: document.getElementById('cf_productimage').value
    };

    // Send to server
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/order_request.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4) {
        if (xhr.status === 200) {
          // Show success, hide form
          document.getElementById('checkoutFormWrapper').style.display = 'none';
          document.getElementById('checkoutSuccess').style.display = 'block';
          // Clear cart
          Cart.clear();
          // Scroll to top
          window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
          Cart.showToast(I18n.t('checkout.error_try_again'));
        }
      }
    };
    xhr.send(JSON.stringify(orderData));
  }
  </script>

<?php include 'includes/footer.php'; ?>
