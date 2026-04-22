var Products = (function() {
  var allProducts = [];
  var currentFilter = 'all';

  function load(callback) {
    var xhr = new XMLHttpRequest();
    var productsUrl = (typeof SiteConfig !== 'undefined' && SiteConfig.productsFile) ? SiteConfig.productsFile : '/data/products.json';
    xhr.open('GET', productsUrl, true);
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        allProducts = JSON.parse(xhr.responseText);
        if (callback) callback(allProducts);
      }
    };
    xhr.send();
  }

  function getAll() {
    return allProducts;
  }

  function getById(id) {
    for (var i = 0; i < allProducts.length; i++) {
      if (allProducts[i].id === id) return allProducts[i];
    }
    return null;
  }

  function getByCategory(category) {
    if (category === 'all') return allProducts;
    var result = [];
    for (var i = 0; i < allProducts.length; i++) {
      if (allProducts[i].category === category) result.push(allProducts[i]);
    }
    return result;
  }

  function search(query) {
    var q = query.toLowerCase();
    var lang = I18n.getLang();
    var result = [];
    for (var i = 0; i < allProducts.length; i++) {
      var p = allProducts[i];
      var name = p[lang].name.toLowerCase();
      var desc = p[lang].description.toLowerCase();
      if (name.indexOf(q) !== -1 || desc.indexOf(q) !== -1) {
        result.push(p);
      }
    }
    return result;
  }

  function renderStars(rating) {
    var html = '';
    for (var i = 1; i <= 5; i++) {
      if (i <= Math.floor(rating)) {
        html += '<span>&#9733;</span>';
      } else if (i - 0.5 <= rating) {
        html += '<span>&#9733;</span>';
      } else {
        html += '<span style="opacity:0.3">&#9733;</span>';
      }
    }
    return html;
  }

  function renderCard(product) {
    var lang = I18n.getLang();
    var info = product[lang];
    var discount = Math.round((1 - product.price / product.originalPrice) * 100);

    var badgeHtml = '';
    if (product.badge) {
      badgeHtml = '<span class="product-badge ' + product.badge + '">' + I18n.t('products.' + product.badge) + '</span>';
    }

    return '<div class="product-card" data-id="' + product.id + '">' +
      '<div class="product-card-image">' +
        badgeHtml +
        '<a href="/product.php?id=' + product.id + '">' +
          '<img src="' + product.image + '" alt="' + info.name + '" loading="lazy">' +
        '</a>' +
        '<div class="product-card-quick">' +
          '<button onclick="Products.quickView(\'' + product.id + '\')">' + I18n.t('products.view_details') + '</button>' +
        '</div>' +
      '</div>' +
      '<div class="product-card-body">' +
        '<h3 class="product-card-title"><a href="/product.php?id=' + product.id + '">' + info.name + '</a></h3>' +
        '<div class="product-rating">' +
          '<div class="stars">' + renderStars(product.rating) + '</div>' +
          '<span class="rating-count">(' + product.reviews + ' ' + I18n.t('products.reviews') + ')</span>' +
        '</div>' +
        '<div class="product-price">' +
          '<span class="price-current">' + Currency.format(product.price) + '</span>' +
          '<span class="price-original">' + Currency.format(product.originalPrice) + '</span>' +
          '<span class="price-discount">-' + discount + '%</span>' +
        '</div>' +
      '</div>' +
      '<div class="product-card-footer">' +
        '<button class="add-cart-btn" onclick="Products.addToCart(\'' + product.id + '\', event)">' +
          '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>' +
          I18n.t('products.add_to_cart') +
        '</button>' +
        '<a href="/cart.php" class="view-cart-btn view-cart-btn-sm">' +
          '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>' +
          I18n.t('products.view_cart') +
        '</a>' +
      '</div>' +
    '</div>';
  }

  function renderGrid(products, containerId) {
    var container = document.getElementById(containerId);
    if (!container) return;
    var html = '';
    for (var i = 0; i < products.length; i++) {
      html += renderCard(products[i]);
    }
    container.innerHTML = html;
  }

  function renderFilters() {
    var filterBar = document.getElementById('filterBar');
    if (!filterBar) return;

    var categories = ['all', 'home', 'electronics', 'outdoor', 'kitchen', 'fitness'];
    var html = '';
    for (var i = 0; i < categories.length; i++) {
      var cat = categories[i];
      var activeClass = cat === currentFilter ? ' active' : '';
      html += '<button class="filter-btn' + activeClass + '" onclick="Products.filter(\'' + cat + '\')">' +
        I18n.t('products.filter_' + cat) + '</button>';
    }
    filterBar.innerHTML = html;
  }

  function filter(category) {
    currentFilter = category;
    var products = getByCategory(category);
    renderGrid(products, 'productGrid');
    renderFilters();
  }

  function addToCart(productId, event) {
    var product = getById(productId);
    if (!product) return;
    Cart.addItem(product);

    if (event && event.target) {
      var btn = event.target.closest('.add-cart-btn');
      if (btn) {
        btn.classList.add('added');
        var originalText = btn.innerHTML;
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg> Added!';
        setTimeout(function() {
          btn.classList.remove('added');
          btn.innerHTML = originalText;
        }, 1500);
      }
    }
  }

  function quickView(productId) {
    window.location.href = '/product.php?id=' + productId;
  }

  function init() {
    load(function() {
      if (document.getElementById('productGrid')) {
        renderGrid(allProducts, 'productGrid');
        renderFilters();
        setupSearch();
      }
    });
  }

  function setupSearch() {
    var searchInput = document.getElementById('searchInput');
    if (!searchInput) return;

    var timeout;
    searchInput.addEventListener('input', function() {
      clearTimeout(timeout);
      var query = searchInput.value.trim();
      timeout = setTimeout(function() {
        if (query.length > 0) {
          var results = search(query);
          renderGrid(results, 'productGrid');
        } else {
          filter(currentFilter);
        }
      }, 300);
    });
  }

  return {
    load: load,
    getAll: getAll,
    getById: getById,
    renderCard: renderCard,
    renderGrid: renderGrid,
    renderFilters: renderFilters,
    renderStars: renderStars,
    filter: filter,
    addToCart: addToCart,
    quickView: quickView,
    init: init,
    search: search
  };
})();
