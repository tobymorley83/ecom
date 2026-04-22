<?php
$pageTitle = 'Terms & Conditions';
$pageScripts = [];
include 'includes/header.php';
?>

  <section class="legal-page">
    <div class="container">
      <h1 data-i18n="legal.terms_title">Terms & Conditions</h1>
      <p class="last-updated">Last updated: January 2025</p>
      <div class="legal-content" id="termsContent">
        <h2>1. Introduction</h2>
        <p>Welcome to <?php echo htmlspecialchars($config["site_name"]); ?>. These Terms and Conditions govern your use of our website and the purchase of products from our online store. By accessing or using our website, you agree to be bound by these Terms and Conditions.</p>

        <h2>2. Definitions</h2>
        <p>"Company", "we", "us", or "our" refers to <?php echo htmlspecialchars($config["site_name"]); ?>. "Website" refers to <?php echo parse_url($config["site_url"], PHP_URL_HOST); ?>. "Customer", "you", or "your" refers to the person accessing or using the website. "Products" refers to the items listed for sale on our website.</p>

        <h2>3. Eligibility</h2>
        <p>You must be at least 18 years of age to make a purchase from our website. By placing an order, you represent and warrant that you are at least 18 years old and have the legal capacity to enter into a binding contract.</p>

        <h2>4. Products and Pricing</h2>
        <p>All products are subject to availability. We reserve the right to discontinue any product at any time. Prices for our products are subject to change without notice. We shall not be liable to you or to any third party for any modification, price change, or discontinuance of any product.</p>

        <h2>5. Orders and Payment</h2>
        <p>When you place an order, you are making an offer to purchase the products in your order. We reserve the right to accept or decline your order for any reason. Payment must be made at the time of purchase using one of our accepted payment methods (Visa, MasterCard, American Express, PayPal).</p>

        <h2>6. Shipping and Delivery</h2>
        <p>We offer free shipping on all orders. Estimated delivery time is 2-3 business days. Delivery times are estimates and are not guaranteed. We are not responsible for delays caused by shipping carriers or customs.</p>

        <h2>7. Returns and Refunds</h2>
        <p>We offer a 90-day money-back guarantee. If you are not satisfied with your purchase, you may return the product within 90 days of receipt for a full refund. Please see our Refund Policy for complete details.</p>

        <h2>8. Intellectual Property</h2>
        <p>All content on this website, including text, graphics, logos, images, and software, is the property of <?php echo htmlspecialchars($config["site_name"]); ?> and is protected by international copyright, trademark, and other intellectual property laws.</p>

        <h2>9. Limitation of Liability</h2>
        <p>To the fullest extent permitted by applicable law, <?php echo htmlspecialchars($config["site_name"]); ?> shall not be liable for any indirect, incidental, special, consequential, or punitive damages, or any loss of profits or revenues, whether incurred directly or indirectly.</p>

        <h2>10. Governing Law</h2>
        <p>These Terms shall be governed by and construed in accordance with the laws of the jurisdiction in which the Company operates, without regard to its conflict of law provisions.</p>

        <h2>11. Changes to Terms</h2>
        <p>We reserve the right to update or modify these Terms at any time without prior notice. Your continued use of the website following any changes constitutes your acceptance of the new Terms.</p>

        <h2>12. Contact Information</h2>
        <p>If you have any questions about these Terms and Conditions, please contact us at <?php echo htmlspecialchars($config["support_email"]); ?>.</p>
      </div>
    </div>
  </section>

<?php include 'includes/footer.php'; ?>
