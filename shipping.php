<?php
$pageTitle = 'Shipping Policy';
$pageScripts = [];
include 'includes/header.php';
?>

  <section class="legal-page">
    <div class="container">
      <h1 data-i18n="legal.shipping_title">Shipping Policy</h1>
      <p class="last-updated">Last updated: January 2025</p>
      <div class="legal-content">
        <h2>1. Free Shipping</h2>
        <p>We are proud to offer free shipping on all orders, regardless of the order amount. No minimum purchase is required to qualify for free shipping.</p>

        <h2>2. Processing Time</h2>
        <p>Orders are processed within 1-2 business days after payment confirmation. You will receive an email notification with your tracking number once your order has been shipped.</p>

        <h2>3. Delivery Times</h2>
        <p>Estimated delivery times after your order has been shipped:</p>
        <ul>
          <li>Domestic orders: 2-3 business days</li>
          <li>European Union: 3-5 business days</li>
          <li>International: 5-10 business days</li>
        </ul>
        <p>Please note that these are estimated delivery times and actual delivery may vary depending on your location and local postal services.</p>

        <h2>4. Order Tracking</h2>
        <p>Once your order has been shipped, you will receive a confirmation email with a tracking number. You can use this tracking number to monitor the status of your delivery on the carrier's website.</p>

        <h2>5. Shipping Carriers</h2>
        <p>We partner with reputable shipping carriers to ensure your orders arrive safely and on time. Our shipping partners include major carriers such as DHL, FedEx, UPS, and local postal services.</p>

        <h2>6. Delivery Issues</h2>
        <p>If you experience any issues with your delivery, such as delayed or missing packages, please contact our customer service team at <?php echo htmlspecialchars($config["support_email"]); ?>. We will work with the shipping carrier to resolve the issue as quickly as possible.</p>

        <h2>7. Incorrect Address</h2>
        <p>Please ensure that your shipping address is correct and complete at the time of purchase. We are not responsible for orders delivered to incorrect addresses provided by the customer.</p>

        <h2>8. Customs and Import Duties</h2>
        <p>For international orders, customs and import duties may be applied by your country's customs authority. These charges are the responsibility of the recipient and are not included in the product price or shipping cost.</p>
      </div>
    </div>
  </section>

<?php include 'includes/footer.php'; ?>
