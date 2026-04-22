<?php
$pageTitle = '';
$activePage = 'home';
$pageScripts = ['/js/products.js'];
include 'includes/header.php';
?>

  <section class="hero">
    <div class="container hero-content">
      <div class="hero-badge">-70% SALE</div>
      <h1 data-i18n="hero.title">The best products at UNBEATABLE prices!</h1>
      <p data-i18n="hero.subtitle">Discover our selection of premium products at up to 70% off. Free shipping on all orders.</p>
      <a href="#products" class="hero-cta">
        <span data-i18n="hero.cta">Shop Now</span>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
      </a>
    </div>
  </section>

  <section class="trust-bar">
    <div class="container">
      <div class="trust-grid">
        <div class="trust-item">
          <div class="trust-icon delivery">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
          </div>
          <div>
            <h4 data-i18n="trust.free_delivery">FREE DELIVERY</h4>
            <p data-i18n="trust.free_delivery_desc">On all orders, 2-3 business days</p>
          </div>
        </div>
        <div class="trust-item">
          <div class="trust-icon guarantee">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
          </div>
          <div>
            <h4 data-i18n="trust.guarantee">90-DAY GUARANTEE</h4>
            <p data-i18n="trust.guarantee_desc">Not satisfied? Money back guaranteed</p>
          </div>
        </div>
        <div class="trust-item">
          <div class="trust-icon support">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
          </div>
          <div>
            <h4 data-i18n="trust.support">24/7 SUPPORT</h4>
            <p data-i18n="trust.support_desc">Our team is here to help you</p>
          </div>
        </div>
        <div class="trust-item">
          <div class="trust-icon secure">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
          </div>
          <div>
            <h4 data-i18n="trust.secure_payment">SECURE PAYMENT</h4>
            <p data-i18n="trust.secure_payment_desc">SSL encrypted transactions</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="section" id="products">
    <div class="container">
      <div class="section-header">
        <h2 data-i18n="products.all">All Products</h2>
      </div>
      <div class="filter-bar" id="filterBar"></div>
      <div class="product-grid" id="productGrid"></div>
    </div>
  </section>

  <section class="trustpilot-bar">
    <div class="container">
      <div class="trustpilot-content">
        <div class="trustpilot-stars">
          <div class="trustpilot-star">&#9733;</div>
          <div class="trustpilot-star">&#9733;</div>
          <div class="trustpilot-star">&#9733;</div>
          <div class="trustpilot-star">&#9733;</div>
          <div class="trustpilot-star">&#9733;</div>
        </div>
        <span class="trustpilot-text"><strong>4.7 / 5</strong> &mdash; <span data-i18n="footer.trustpilot_text">Rated Excellent on</span></span>
        <span class="trustpilot-logo">Trustpilot</span>
      </div>
    </div>
  </section>

  <section class="newsletter" id="contact">
    <div class="container">
      <h3 data-i18n="footer.newsletter">Newsletter</h3>
      <p data-i18n="footer.newsletter_desc">Subscribe to get special offers and updates.</p>
      <form class="newsletter-form" onsubmit="return false;">
        <input type="email" data-i18n-placeholder="footer.email_placeholder" placeholder="Your email address">
        <button type="submit" data-i18n="footer.subscribe">Subscribe</button>
      </form>
    </div>
  </section>

<?php include 'includes/footer.php'; ?>
