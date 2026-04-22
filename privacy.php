<?php
$pageTitle = 'Privacy Policy';
$pageScripts = [];
include 'includes/header.php';
?>

  <section class="legal-page">
    <div class="container">
      <h1 data-i18n="legal.privacy_title">Privacy Policy</h1>
      <p class="last-updated">Last updated: January 2025</p>
      <div class="legal-content">
        <h2>1. Information We Collect</h2>
        <p>We collect information you provide directly to us, such as when you create an account, make a purchase, subscribe to our newsletter, or contact us. This information may include your name, email address, postal address, phone number, and payment information.</p>

        <h2>2. How We Use Your Information</h2>
        <p>We use the information we collect to:</p>
        <ul>
          <li>Process and fulfill your orders</li>
          <li>Send you order confirmations and shipping updates</li>
          <li>Respond to your comments, questions, and customer service requests</li>
          <li>Send you marketing communications (with your consent)</li>
          <li>Improve our website and customer experience</li>
          <li>Detect and prevent fraud</li>
        </ul>

        <h2>3. Information Sharing</h2>
        <p>We do not sell, trade, or otherwise transfer your personal information to outside parties except to trusted third parties who assist us in operating our website, conducting our business, or servicing you, as long as those parties agree to keep this information confidential.</p>

        <h2>4. Cookies and Tracking</h2>
        <p>We use cookies and similar tracking technologies to track activity on our website and hold certain information. Cookies are files with a small amount of data which may include an anonymous unique identifier. You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent.</p>

        <h2>5. Data Security</h2>
        <p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. All payment transactions are processed through encrypted SSL connections.</p>

        <h2>6. Third-Party Services</h2>
        <p>Our website may contain links to third-party websites. We have no control over, and assume no responsibility for, the content, privacy policies, or practices of any third-party sites or services.</p>

        <h2>7. Your Rights (GDPR)</h2>
        <p>If you are a resident of the European Economic Area (EEA), you have certain data protection rights:</p>
        <ul>
          <li>The right to access your personal data</li>
          <li>The right to rectification of inaccurate personal data</li>
          <li>The right to erasure of your personal data</li>
          <li>The right to restrict processing of your personal data</li>
          <li>The right to data portability</li>
          <li>The right to object to processing of your personal data</li>
        </ul>

        <h2>8. Data Retention</h2>
        <p>We will retain your personal information only for as long as is necessary to fulfill the purposes for which it was collected, including to satisfy any legal, accounting, or reporting requirements.</p>

        <h2>9. Children's Privacy</h2>
        <p>Our website does not address anyone under the age of 18. We do not knowingly collect personal information from children under 18.</p>

        <h2>10. Changes to This Policy</h2>
        <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last updated" date.</p>

        <h2>11. Contact Us</h2>
        <p>If you have any questions about this Privacy Policy, please contact us at <?php echo htmlspecialchars($config["support_email"]); ?>.</p>
      </div>
    </div>
  </section>

<?php include 'includes/footer.php'; ?>
