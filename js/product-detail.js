var ProductDetail = (function() {
  var currentProduct = null;
  var currentQty = 1;
  var currentImageIndex = 0;

  function init() {
    var params = new URLSearchParams(window.location.search);
    var productId = params.get('id');
    if (!productId) {
      window.location.href = '/';
      return;
    }

    Products.load(function(allProducts) {
      currentProduct = Products.getById(productId);
      if (!currentProduct) {
        window.location.href = '/';
        return;
      }
      render();
      renderRelated(allProducts);
    });
  }

  function render() {
    var lang = I18n.getLang();
    var info = (typeof Products !== 'undefined' && Products.getInfo) ? Products.getInfo(currentProduct, lang) : (currentProduct[lang] || currentProduct.en || currentProduct.es || {});
    var discount = Math.round((1 - currentProduct.price / currentProduct.originalPrice) * 100);

    document.title = info.name || '';

    var breadcrumbProduct = document.getElementById('breadcrumbProduct');
    if (breadcrumbProduct) breadcrumbProduct.textContent = info.name;

    var thumbsHtml = '';
    for (var i = 0; i < currentProduct.images.length; i++) {
      var activeClass = i === 0 ? ' active' : '';
      thumbsHtml += '<img src="' + currentProduct.images[i] + '" alt="' + info.name + ' ' + (i+1) + '" class="' + activeClass + '" onclick="ProductDetail.switchImage(' + i + ')">';
    }

    var featuresHtml = '';
    for (var j = 0; j < info.features.length; j++) {
      featuresHtml += '<li>' + info.features[j] + '</li>';
    }

    var detail = document.getElementById('productDetail');
    if (!detail) return;

    // Build social proof section (only if product has it)
    var socialProofHtml = '';
    if (info.social_proof) {
      socialProofHtml =
        '<div class="pd-social-proof">' +
          '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>' +
          '<p>' + info.social_proof + '</p>' +
        '</div>';
    }

    // Build who-for section (only if product has it)
    var whoForHtml = '';
    if (info.who_for) {
      whoForHtml =
        '<div class="pd-who-for">' +
          '<h3>' + I18n.t('products.who_for') + '</h3>' +
          '<p>' + info.who_for + '</p>' +
        '</div>';
    }

    // Build reviews section
    var reviewsHtml = '';
    var reviewTexts = info.reviews_text || [];
    if (reviewTexts.length > 0) {
      var pool = (typeof SiteConfig !== 'undefined' && SiteConfig.reviewerPool) ? SiteConfig.reviewerPool : {names:[], cities:[]};
      var reviewItemsHtml = '';

      for (var r = 0; r < reviewTexts.length; r++) {
        var rv = reviewTexts[r];
        var rName = pool.names.length > 0 ? pool.names[r % pool.names.length] : 'Customer';
        var rCity = pool.cities.length > 0 ? pool.cities[r % pool.cities.length] : '';
        var rDaysAgo = 3 + (r * 7) + Math.floor(currentProduct.id.length % 5);
        var rDateLabel = rDaysAgo + ' ' + I18n.t('products.days_ago');

        reviewItemsHtml +=
          '<div class="pd-review-item">' +
            '<div class="pd-review-header">' +
              '<div class="pd-review-avatar">' + rName.charAt(0) + '</div>' +
              '<div class="pd-review-meta">' +
                '<div class="pd-review-name">' + rName + (rCity ? ' <span class="pd-review-city">— ' + rCity + '</span>' : '') + '</div>' +
                '<div class="pd-review-stars">' + Products.renderStars(rv.rating) + '</div>' +
              '</div>' +
              '<div class="pd-review-date">' + rDateLabel + '</div>' +
            '</div>' +
            '<p class="pd-review-text">' + rv.text + '</p>' +
            '<div class="pd-review-verified"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg> ' + I18n.t('products.verified_purchase') + '</div>' +
          '</div>';
      }

      reviewsHtml =
        '<div class="pd-reviews">' +
          '<h3>' + I18n.t('products.customer_reviews') + ' (' + currentProduct.reviews + ')</h3>' +
          reviewItemsHtml +
        '</div>';
    }

    detail.innerHTML =
      '<div class="product-gallery">' +
        '<div class="product-gallery-main" id="mainImage">' +
          '<img src="' + currentProduct.images[0] + '" alt="' + info.name + '">' +
        '</div>' +
        '<div class="product-gallery-thumbs" id="thumbs">' + thumbsHtml + '</div>' +
      '</div>' +
      '<div class="product-info">' +
        '<h1>' + info.name + '</h1>' +
        '<div class="product-rating">' +
          '<div class="stars">' + Products.renderStars(currentProduct.rating) + '</div>' +
          '<span class="rating-count">(' + currentProduct.reviews + ' ' + I18n.t('products.reviews') + ')</span>' +
        '</div>' +
        '<div class="product-price">' +
          '<span class="price-current">' + Currency.format(currentProduct.price) + '</span>' +
          '<span class="price-original">' + Currency.format(currentProduct.originalPrice) + '</span>' +
          '<span class="price-discount">-' + discount + '%</span>' +
        '</div>' +
        '<p class="product-description">' + info.description + '</p>' +
        socialProofHtml +
        '<div class="product-features">' +
          '<h3>' + I18n.t('products.features') + '</h3>' +
          '<ul>' + featuresHtml + '</ul>' +
        '</div>' +
        whoForHtml +
        '<div class="product-meta">' +
          '<div class="product-meta-item">' +
            '<span class="meta-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg></span>' +
            '<span>' + I18n.t('products.in_stock') + '</span>' +
          '</div>' +
          '<div class="product-meta-item">' +
            '<span class="meta-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></span>' +
            '<span>' + I18n.t('products.free_shipping') + '</span>' +
          '</div>' +
        '</div>' +
        '<div class="pd-guarantee">' +
          '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>' +
          '<span>' + I18n.t('products.guarantee_full') + '</span>' +
        '</div>' +
        '<div class="quantity-selector">' +
          '<label>' + I18n.t('products.quantity') + '</label>' +
          '<div class="quantity-controls">' +
            '<button onclick="ProductDetail.changeQty(-1)">-</button>' +
            '<input type="number" id="qtyInput" value="1" min="1" max="99" onchange="ProductDetail.setQty(this.value)">' +
            '<button onclick="ProductDetail.changeQty(1)">+</button>' +
          '</div>' +
        '</div>' +
        '<div class="detail-add-cart">' +
          '<button class="add-cart-btn" onclick="ProductDetail.addToCart()">' +
            '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>' +
            I18n.t('products.add_to_cart') +
          '</button>' +
          '<a href="/cart.php" class="view-cart-btn">' +
            '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>' +
            I18n.t('products.view_cart') +
          '</a>' +
        '</div>' +
      '</div>' +
      reviewsHtml;
  }

  function renderRelated(allProducts) {
    var related = [];
    for (var i = 0; i < allProducts.length; i++) {
      if (allProducts[i].id !== currentProduct.id) {
        related.push(allProducts[i]);
      }
      if (related.length >= 4) break;
    }
    Products.renderGrid(related, 'relatedProducts');
  }

  function switchImage(index) {
    currentImageIndex = index;
    var mainImage = document.querySelector('#mainImage img');
    if (mainImage) {
      mainImage.src = currentProduct.images[index];
    }

    var thumbs = document.querySelectorAll('#thumbs img');
    for (var i = 0; i < thumbs.length; i++) {
      thumbs[i].classList.toggle('active', i === index);
    }
  }

  function changeQty(delta) {
    currentQty = Math.max(1, Math.min(99, currentQty + delta));
    var input = document.getElementById('qtyInput');
    if (input) input.value = currentQty;
  }

  function setQty(val) {
    currentQty = Math.max(1, Math.min(99, parseInt(val) || 1));
    var input = document.getElementById('qtyInput');
    if (input) input.value = currentQty;
  }

  function addToCart() {
    if (!currentProduct) return;
    Cart.addItem(currentProduct, currentQty);
  }

  return {
    init: init,
    switchImage: switchImage,
    changeQty: changeQty,
    setQty: setQty,
    addToCart: addToCart
  };
})();
