<?php
$pageTitle = 'Refund Policy';
$pageScripts = [];
include 'includes/header.php';
?>

  <section class="legal-page">
    <div class="container">
      <h1 data-i18n="legal.refund_title">Refund Policy</h1>
      <p class="last-updated">Last updated: January 2025</p>
      <div class="legal-content">
        <h2>1. 90-Day Money-Back Guarantee</h2>
        <p>We offer a 90-day money-back guarantee on all purchases. If you are not completely satisfied with your purchase, you may return the product within 90 days of the date of receipt for a full refund of the purchase price.</p>

        <h2>2. Eligibility for Refund</h2>
        <p>To be eligible for a refund, the following conditions must be met:</p>
        <ul>
          <li>The return request must be made within 90 days of receipt</li>
          <li>The product must be unused and in its original packaging</li>
          <li>A valid proof of purchase (order confirmation or receipt) must be provided</li>
          <li>The product must not show signs of misuse or damage caused by the customer</li>
        </ul>

        <h2>3. How to Request a Refund</h2>
        <p>To initiate a return, please contact our customer service team at <?php echo htmlspecialchars($config["support_email"]); ?> with your order number and reason for the return. We will provide you with return instructions and a return shipping label.</p>

        <h2>4. Return Shipping</h2>
        <p>Return shipping costs are covered by <?php echo htmlspecialchars($config["site_name"]); ?>. We will provide you with a prepaid return shipping label. Please use the provided label to return the product.</p>

        <h2>5. Refund Processing</h2>
        <p>Once we receive and inspect your returned product, we will send you an email notification confirming the receipt of your return. Your refund will be processed within 5-10 business days and will be credited to the original payment method used for the purchase.</p>

        <h2>6. Damaged or Defective Products</h2>
        <p>If you receive a damaged or defective product, please contact us immediately at <?php echo htmlspecialchars($config["support_email"]); ?> with photos of the damage. We will arrange for a replacement or full refund at no additional cost to you.</p>

        <h2>7. Exchanges</h2>
        <p>If you would like to exchange a product for a different item, please return the original product following our return process and place a new order for the desired item.</p>

        <h2>8. Non-Refundable Items</h2>
        <p>The following items are not eligible for refund:</p>
        <ul>
          <li>Products that have been used or altered</li>
          <li>Products without original packaging</li>
          <li>Gift cards</li>
        </ul>

        <h2>9. Contact Us</h2>
        <p>If you have any questions about our refund policy, please contact us at <?php echo htmlspecialchars($config["support_email"]); ?>. Our customer service team is available Monday through Friday, 9:00 AM to 6:00 PM.</p>
      </div>
    </div>
  </section>

<?php include 'includes/footer.php'; ?>
