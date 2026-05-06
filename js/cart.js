var Cart = (function() {
  var STORAGE_KEY = 'shopdeals-cart';

  function getItems() {
    try {
      return JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
    } catch(e) {
      return [];
    }
  }

  // Resolve the localized display name of a product object using the
  // language-aware getInfo helper. Falls back to whatever flat .name
  // is on the object, then to the id.
  function resolveName(product) {
    if (!product) return '';
    var lang = (typeof I18n !== 'undefined' && I18n.getLang) ? I18n.getLang() : 'en';
    if (typeof Products !== 'undefined' && Products.getInfo) {
      var info = Products.getInfo(product, lang);
      if (info && info.name) return info.name;
    }
    return product.name || product.id || '';
  }

  // Build a "Product A, Product B" string from the live cart, skipping
  // free gifts. Looks up missing names via Products for items that
  // pre-date the name-on-cart-item migration.
  function brevoNames(items) {
    var names = [];
    for (var j = 0; j < items.length; j++) {
      var it = items[j];
      if (it.is_free_gift) continue;
      var n = it.name;
      if (!n && typeof Products !== 'undefined' && Products.getById) {
        var p = Products.getById(it.id);
        if (p) n = resolveName(p);
      }
      if (n) names.push(n);
    }
    return names.join(', ');
  }

  function fireBrevoCartUpdated() {
    if (!window.Brevo) return;
    var items = getItems();
    Brevo.cartUpdated(items, {
      total:         getTotal(),
      currency:      (typeof SiteConfig !== 'undefined' && SiteConfig.currency) ? SiteConfig.currency : '',
      product_names: brevoNames(items)
    });
  }

  function saveItems(items) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
    updateCartCount();
  }

  function addItem(product, qty) {
    var items = getItems();
    var existing = null;
    for (var i = 0; i < items.length; i++) {
      if (items[i].is_free_gift) continue;
      
      if (items[i].id === product.id) {
        existing = items[i];
        break;
      }
    }

    if (existing) {
      existing.qty += (qty || 1);
    } else {
      items.push({
        id: product.id,
        name: resolveName(product),
        price: product.price,
        originalPrice: product.originalPrice,
        image: product.image,
        qty: qty || 1
      });
    }

    saveItems(items);
    showToast(I18n.t('products.add_to_cart') + '!');
    animateCartCount();
    fireBrevoCartUpdated();
  }

  function removeItem(productId) {
    var items = getItems();
    var filtered = [];
    for (var i = 0; i < items.length; i++) {
      if (items[i].id !== productId) {
        filtered.push(items[i]);
      }
    }
    saveItems(filtered);
    fireBrevoCartUpdated();
  }

    function updateQty(productId, qty) {
        var items = getItems();
        for (var i = 0; i < items.length; i++) {
            if (items[i].id === productId && !items[i].is_free_gift) {
                items[i].qty = Math.max(1, qty);
                break;
            }
            // For free gifts, we deliberately do nothing — qty stays at 1.
        }
        saveItems(items);
        fireBrevoCartUpdated();
    }

  function getTotal() {
    var items = getItems();
    var total = 0;
    for (var i = 0; i < items.length; i++) {
      total += items[i].price * items[i].qty;
    }
    return total;
  }

  function getCount() {
    var items = getItems();
    var count = 0;
    for (var i = 0; i < items.length; i++) {
      count += items[i].qty;
    }
    return count;
  }

  function updateCartCount() {
    var countEls = document.querySelectorAll('#cartCount');
    var count = getCount();
    for (var i = 0; i < countEls.length; i++) {
      countEls[i].textContent = count;
    }
  }

  function animateCartCount() {
    var countEls = document.querySelectorAll('#cartCount');
    for (var i = 0; i < countEls.length; i++) {
      countEls[i].classList.remove('bump');
      void countEls[i].offsetWidth;
      countEls[i].classList.add('bump');
    }
  }

  function showToast(message) {
    var toast = document.getElementById('toast');
    var toastMsg = document.getElementById('toastMessage');
    if (!toast || !toastMsg) return;
    toastMsg.textContent = message;
    toast.classList.add('show');
    setTimeout(function() {
      toast.classList.remove('show');
    }, 2500);
  }

  function clear() {
    localStorage.removeItem(STORAGE_KEY);
    updateCartCount();
  }

  return {
    getItems: getItems,
    addItem: addItem,
    removeItem: removeItem,
    updateQty: updateQty,
    getTotal: getTotal,
    getCount: getCount,
    updateCartCount: updateCartCount,
    showToast: showToast,
    clear: clear
  };
})();
