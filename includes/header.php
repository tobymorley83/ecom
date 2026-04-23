<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
<?php
// Load site config if not already loaded
if (!isset($config)) {
    $config = require __DIR__ . '/../config.php';
}
require_once __DIR__ . '/../brevo/identity.php';
$brevoUid = brevo_ensure_uid();   // Sets bo_uid cookie if missing
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo (isset($pageTitle) && $pageTitle !== '' ? htmlspecialchars($pageTitle) . ' - ' : '') . htmlspecialchars($config['site_name']); ?></title>
  
    <?php if (isset($ogMeta)): ?>
      <meta property="og:title" content="<?php echo htmlspecialchars($ogMeta['title']); ?>" />
      <meta property="og:description" content="<?php echo htmlspecialchars($ogMeta['description']); ?>" />
      <meta property="og:image" content="<?php echo htmlspecialchars($ogMeta['image']); ?>" />
      <meta property="og:url" content="<?php echo htmlspecialchars($ogMeta['url']); ?>" />
      <meta property="og:type" content="product" />
      <meta property="og:site_name" content="<?php echo htmlspecialchars($config['site_name']); ?>" />
      <meta property="og:image:width" content="600" />
      <meta property="og:image:height" content="600" />
    <?php endif; ?>  
  
  
  
  <link rel="stylesheet" href="/styles.css?v=5">
  <link rel="stylesheet" href="/css/fomo.css?v=3">
  <link rel="stylesheet" href="/css/brevo-popup.css">
  <link rel="stylesheet" href="/css/brevo-wheel.css?v=3">

  <?php include __DIR__ . '/config-js.php'; ?>
<?php
$tracking = isset($config['tracking']) && is_array($config['tracking']) ? $config['tracking'] : [];
$sbSecret = isset($tracking['sitebehaviour_secret']) ? trim((string)$tracking['sitebehaviour_secret']) : '';
$fbPixels = [];
if (!empty($tracking['facebook_pixels']) && is_array($tracking['facebook_pixels'])) {
    foreach ($tracking['facebook_pixels'] as $pid) {
        $pid = trim((string)$pid);
        if ($pid !== '') { $fbPixels[] = $pid; }
    }
}
?>
<?php if ($sbSecret !== ''): ?>
<script type="text/javascript">
  (function() {
    try {
      if (window.location && window.location.search && window.location.search.indexOf('capture-sitebehaviour-heatmap') !== -1) {
        sessionStorage.setItem('capture-sitebehaviour-heatmap', '_');
      }
      var sbSiteSecret = <?php echo json_encode($sbSecret); ?>;
      window.sitebehaviourTrackingSecret = sbSiteSecret;
      var scriptElement = document.createElement('script');
      scriptElement.defer = true;
      scriptElement.id = 'site-behaviour-script-v2';
      scriptElement.src = 'https://sitebehaviour-cdn.fra1.cdn.digitaloceanspaces.com/index.min.js?sitebehaviour-secret=' + sbSiteSecret;
      document.head.appendChild(scriptElement);
    } catch (e) { console.error(e); }
  })();
</script>
<?php endif; ?>
<?php if (!empty($fbPixels)): ?>
<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
<?php foreach ($fbPixels as $pid): ?>
fbq('init', <?php echo json_encode($pid); ?>);
<?php endforeach; ?>
fbq('track', 'PageView');
</script>
<noscript>
<?php foreach ($fbPixels as $pid): ?>
<img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=<?php echo urlencode($pid); ?>&ev=PageView&noscript=1" />
<?php endforeach; ?>
</noscript>
<!-- End Meta Pixel Code -->
<?php endif; ?>
</head>
<body>
  <div class="announcement-bar">
    <span>75%</span> LIMITED TIME OFFER &mdash; Free shipping on all orders!
  </div>

  <header class="site-header" id="siteHeader">
    <div class="container header-inner">
      <a href="/" class="logo"><img src="/img/logo.png" alt="<?php echo htmlspecialchars($config['site_name']); ?>" /><?php echo htmlspecialchars($config['site_name']); ?></a>
      <nav class="nav-links" id="navLinks">
        <a href="/"<?php if(isset($activePage) && $activePage === 'home') echo ' class="active"'; ?> data-i18n="nav.home">Home</a>
        <a href="/#products"<?php if(isset($activePage) && $activePage === 'catalog') echo ' class="active"'; ?> data-i18n="nav.catalog">Catalog</a>
        <a href="/#contact" data-i18n="nav.contact">Contact</a>
      </nav>
      <div class="header-actions">
        <div class="search-bar">
          <input type="text" id="searchInput" data-i18n-placeholder="nav.search_placeholder" placeholder="Search products...">
          <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        </div>
        <button class="lang-switcher" id="langSwitcher" title="Change language">
          <svg class="globe-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
          <span id="langLabel">EN</span>
        </button>
        <a href="/cart.php" class="cart-btn">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
          <span data-i18n="nav.cart">Cart</span>
          <span class="cart-count" id="cartCount">0</span>
        </a>
        <button class="mobile-menu-btn" id="mobileMenuBtn">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>
      </div>
    </div>
  </header>

  <div class="mobile-nav-overlay" id="mobileOverlay"></div>
  <div class="mobile-nav" id="mobileNav">
    <button class="mobile-nav-close" id="mobileNavClose">&times;</button>
    <a href="/" data-i18n="nav.home">Home</a>
    <a href="/#products" data-i18n="nav.catalog">Catalog</a>
    <a href="/#contact" data-i18n="nav.contact">Contact</a>
    <a href="/cart.php" data-i18n="nav.cart">Cart</a>
  </div>
