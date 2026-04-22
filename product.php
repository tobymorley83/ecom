<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$config = require __DIR__ . '/config.php';

$pageTitle = 'Product';
$activePage = 'catalog';
$pageScripts = ['/js/products.js', '/js/product-detail.js'];

// Load product data server-side for OG meta tags (Facebook can't run JS)
$productId = $_GET['id'] ?? '';
if ($productId) {
    $trafficSource = $_SESSION['traffic_source'] ?? 'nonfb';
    $productsPath = $config['traffic'][$trafficSource]['products_file'] ?? '/data/products.json';
    $productsData = json_decode(file_get_contents(__DIR__ . $productsPath), true);
    $lang = $_SESSION['lang'] ?? $config['default_lang'];

    if ($productsData) {
        foreach ($productsData as $p) {
            if ($p['id'] === $productId) {
                $info = $p[$lang] ?? $p['en'] ?? [];
                $ogMeta = [
                    'title'       => $info['name'] ?? '',
                    'description' => $info['description'] ?? '',
                    'image'       => $p['image'] ?? '',
                    'url'         => $config['site_url'] . '/product.php?id=' . urlencode($productId),
                ];
                $pageTitle = $info['name'] ?? 'Product';
                break;
            }
        }
    }
}

include 'includes/header.php';
?>

  <section class="product-detail">
    <div class="container">
      <div class="breadcrumb" id="breadcrumb">
        <a href="/" data-i18n="nav.home">Home</a>
        <span class="separator">/</span>
        <a href="/#products" data-i18n="nav.catalog">Catalog</a>
        <span class="separator">/</span>
        <span id="breadcrumbProduct"></span>
      </div>
      <div class="product-detail-grid" id="productDetail"></div>

      <div class="section-header" style="margin-top:40px;">
        <h2 data-i18n="products.you_may_also_like">You May Also Like</h2>
      </div>
      <div class="product-grid" id="relatedProducts"></div>
    </div>
  </section>

<?php include 'includes/footer.php'; ?>