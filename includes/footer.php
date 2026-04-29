  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-col">
          <h4 data-i18n="footer.about">About Us</h4>
          <p data-i18n="footer.about_text">We are dedicated to bringing you the best products at unbeatable prices. Quality and customer satisfaction are our top priorities.</p>
        </div>
        <div class="footer-col">
          <h4 data-i18n="footer.quick_links">Quick Links</h4>
          <ul>
            <li><a href="/" data-i18n="nav.home">Home</a></li>
            <li><a href="/#products" data-i18n="nav.catalog">Catalog</a></li>
            <li><a href="/cart.php" data-i18n="nav.cart">Cart</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4 data-i18n="footer.customer_service">Customer Service</h4>
          <ul>
            <li><a href="/shipping.php" data-i18n="footer.shipping_policy">Shipping Policy</a></li>
            <li><a href="/refund.php" data-i18n="footer.refund_policy">Refund Policy</a></li>
            <li><a href="/terms.php" data-i18n="footer.terms">Terms & Conditions</a></li>
            <li><a href="/privacy.php" data-i18n="footer.privacy">Privacy Policy</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4 data-i18n="footer.contact_us">Contact Us</h4>
          <p><?php echo htmlspecialchars($config['support_email']); ?></p>
          <p style="margin-top:8px">Mon - Fri: 9:00 - 18:00</p>
        </div>
      </div>
      <div class="footer-bottom">
        <span>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($config['site_name']); ?>. <span data-i18n="footer.rights">All rights reserved.</span></span>
        <div class="payment-icons">
          <span class="payment-icon">VISA</span>
          <span class="payment-icon">MC</span>
          <span class="payment-icon">AMEX</span>
          <span class="payment-icon">PayPal</span>
        </div>
      </div>
    </div>
  </footer>

  <div class="toast" id="toast">
    <span class="toast-icon">&#10003;</span>
    <span id="toastMessage"></span>
  </div>

  <script src="/js/i18n.js"></script>
  <script src="/js/currency.js"></script>
  <script src="/js/ecommerce.js"></script>
  <script src="/js/brevo.js"></script>
  <script src="/js/brevo-popup.js"></script>
  <script src="/js/brevo-wheel.js"></script>
  <script src="/js/cart.js"></script>
<?php if(isset($pageScripts) && is_array($pageScripts)): ?>
<?php foreach($pageScripts as $script): ?>
  <script src="<?php echo htmlspecialchars($script); ?>"></script>
<?php endforeach; ?>
<?php endif; ?>
  <script src="/js/app.js"></script>
<?php include __DIR__ . '/fomo-config.php'; ?>
  <script src="/js/fomo.js"></script>

</body>
</html>
