var CartPage = (function() {
  var products = [];
  var appliedDiscount = null; // { code, label, fixed_price }
  var DISCOUNT_KEY = 'shopdeals-discount';

  function init() {
    Products.load(function(allProducts) {
      products = allProducts;

      // Auto-apply discount: URL param first, then localStorage
      var codeToApply = '';
      if (typeof SiteConfig !== 'undefined' && SiteConfig.discountParam) {
        codeToApply = SiteConfig.discountParam;
      } else {
        var saved = localStorage.getItem(DISCOUNT_KEY);
        if (saved) codeToApply = saved;
      }

      if (codeToApply) {
        applyCode(codeToApply, true);
      }

      render();
    });
  }

  function applyCode(code, silent) {
    var key = code.toLowerCase().trim();
    if (!key) return false;

    if (typeof SiteConfig !== 'undefined' && SiteConfig.discountCodes && SiteConfig.discountCodes[key]) {
      var dc = SiteConfig.discountCodes[key];
      appliedDiscount = {
        code: key,
        label: dc.label,
        fixed_price: dc.fixed_price
      };
      // Persist to localStorage so it survives page navigation
      localStorage.setItem(DISCOUNT_KEY, key);
      if (!silent) {
        Cart.showToast(I18n.t('cart.discount_applied') + ': ' + dc.label);
        render();
      }
      return true;
    } else {
      if (!silent) {
        Cart.showToast(I18n.t('cart.discount_invalid'));
        render();
      }
      return false;
    }
  }

  function removeDiscount() {
    appliedDiscount = null;
    localStorage.removeItem(DISCOUNT_KEY);
    render();
  }

  function render() {
    var container = document.getElementById('cartPage');
    if (!container) return;

    var items = Cart.getItems();

    if (items.length === 0) {
      appliedDiscount = null;
      container.innerHTML =
        '<div class="cart-empty">' +
          '<div class="cart-empty-icon"><svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg></div>' +
          '<h2>' + I18n.t('cart.empty') + '</h2>' +
          '<p>' + I18n.t('cart.empty_desc') + '</p>' +
          '<a href="/" class="hero-cta">' + I18n.t('cart.continue_shopping') + '</a>' +
        '</div>';
      return;
    }

    var count = Cart.getCount();
    var countText = count + ' ' + (count === 1 ? I18n.t('cart.item') : I18n.t('cart.items'));

    var itemsHtml = '';
    for (var i = 0; i < items.length; i++) {
        var item = items[i];
        var product = Products.getById(item.id);
        var lang = I18n.getLang();
        var name = product ? product[lang].name : item.id;
        var image = product ? product.image : item.image;
    
        if (item.is_free_gift) {
            // ===== Free gift rendering =====
            itemsHtml +=
                '<div class="cart-item cart-item-gift">' +
                    '<div class="cart-item-image">' +
                        '<a href="/product.php?id=' + item.id + '"><img src="' + image + '" alt="' + name + '"></a>' +
                        '<span class="gift-badge">🎁 GIFT</span>' +
                    '</div>' +
                    '<div class="cart-item-details">' +
                        '<div>' +
                            '<div class="cart-item-title">' +
                                '<a href="/product.php?id=' + item.id + '">' + name + '</a>' +
                            '</div>' +
                            '<div class="cart-item-price gift-price">' +
                                (I18n.t('cart.free_gift') || 'Free gift from your spin!') +
                            '</div>' +
                        '</div>' +
                        '<div class="cart-item-actions">' +
                            '<div class="gift-qty-locked">x1</div>' +
                            '<button class="cart-item-remove" onclick="CartPage.remove(\'' + item.id + '\')">' + I18n.t('cart.remove') + '</button>' +
                            '<span class="cart-item-total gift-total">' + (I18n.t('cart.free') || 'FREE') + '</span>' +
                        '</div>' +
                    '</div>' +
                '</div>';
            continue;
        }
    
        // ===== Normal paid line rendering (unchanged) =====
        var lineTotal = item.price * item.qty;
    
        var originalPriceHtml = '';
        var originalPrice = product ? product.originalPrice : (item.originalPrice || 0);
        if (originalPrice && originalPrice > item.price) {
            var savedPct = Math.round((1 - item.price / originalPrice) * 100);
            originalPriceHtml = ' <span class="cart-item-original-price"><s>' + Currency.format(originalPrice) + '</s></span>' +
                ' <span class="cart-item-save-badge">-' + savedPct + '%</span>';
        }
    
        itemsHtml +=
            '<div class="cart-item">' +
                '<div class="cart-item-image">' +
                    '<a href="/product.php?id=' + item.id + '"><img src="' + image + '" alt="' + name + '"></a>' +
                '</div>' +
                '<div class="cart-item-details">' +
                    '<div>' +
                        '<div class="cart-item-title"><a href="/product.php?id=' + item.id + '">' + name + '</a></div>' +
                        '<div class="cart-item-price">' + Currency.format(item.price) + ' ' + I18n.t('cart.each') + originalPriceHtml + '</div>' +
                    '</div>' +
                    '<div class="cart-item-actions">' +
                        '<div class="quantity-controls">' +
                            '<button onclick="CartPage.updateQty(\'' + item.id + '\', ' + (item.qty - 1) + ')">-</button>' +
                            '<input type="number" value="' + item.qty + '" min="1" max="99" onchange="CartPage.updateQty(\'' + item.id + '\', parseInt(this.value))">' +
                            '<button onclick="CartPage.updateQty(\'' + item.id + '\', ' + (item.qty + 1) + ')">+</button>' +
                        '</div>' +
                        '<button class="cart-item-remove" onclick="CartPage.remove(\'' + item.id + '\')">' + I18n.t('cart.remove') + '</button>' +
                        '<span class="cart-item-total">' + Currency.format(lineTotal) + '</span>' +
                    '</div>' +
                '</div>' +
            '</div>';
    }

    var subtotal = Cart.getTotal();

    // Discount code input section
    var discountHtml = '';
    if (appliedDiscount) {
      discountHtml =
        '<div class="discount-applied">' +
          '<div class="discount-applied-info">' +
            '<span class="discount-tag"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg> ' +
              appliedDiscount.label + ' <code>' + appliedDiscount.code.toUpperCase() + '</code></span>' +
          '</div>' +
          '<button class="discount-remove-btn" onclick="CartPage.removeDiscount()">&times;</button>' +
        '</div>';
    } else {
      discountHtml =
        '<div class="discount-input-row">' +
          '<input type="text" id="discountCodeInput" placeholder="' + I18n.t('cart.discount_placeholder') + '" onkeydown="if(event.key===\'Enter\')CartPage.applyFromInput()">' +
          '<button class="discount-apply-btn" onclick="CartPage.applyFromInput()">' + I18n.t('cart.apply') + '</button>' +
        '</div>';
    }

    // Calculate final total
    var finalTotal = subtotal;
    var discountSummaryHtml = '';
    var showDiscountSection = true;
    if (appliedDiscount && items.length > 0) {
      var savings = subtotal - appliedDiscount.fixed_price;
      if (savings > 0) {
        // Discount actually saves money — show it
        finalTotal = appliedDiscount.fixed_price;
        discountSummaryHtml =
          '<div class="cart-summary-row discount-row">' +
            '<span>' + I18n.t('cart.discount') + ' (' + appliedDiscount.label + ')</span>' +
            '<span class="discount-amount">-' + Currency.format(savings) + '</span>' +
          '</div>';
      } else {
        // Cart is already at or below the discount price — don't show discount at all
        showDiscountSection = false;
      }
    }

    container.innerHTML =
      '<h1>' + I18n.t('cart.your_cart') + '</h1>' +
      '<p class="cart-count-text">' + countText + '</p>' +
      '<div class="cart-layout">' +
        '<div class="cart-items">' + itemsHtml + '</div>' +
        '<div class="cart-summary">' +
          '<h3>' + I18n.t('cart.order_summary') + '</h3>' +
          '<div class="cart-summary-row">' +
            '<span>' + I18n.t('cart.subtotal') + '</span>' +
            '<span>' + Currency.format(subtotal) + '</span>' +
          '</div>' +
          '<div class="cart-summary-row">' +
            '<span>' + I18n.t('cart.shipping') + '</span>' +
            '<span class="free-badge">' + I18n.t('cart.free') + '</span>' +
          '</div>' +
          discountSummaryHtml +
          (showDiscountSection ? '<div class="discount-section">' + discountHtml + '</div>' : '') +
          '<div class="cart-summary-row total">' +
            '<span>' + I18n.t('cart.total') + '</span>' +
            '<span>' + Currency.format(finalTotal) + '</span>' +
          '</div>' +
          '<button class="checkout-btn" id="btn-checkout" onclick="CartPage.checkout()">' + I18n.t('cart.proceed_checkout') + '</button>' +
          '<div class="cart-trust-icons">' +
            '<div class="cart-trust-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> SSL Secure</div>' +
            '<div class="cart-trust-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg> 90-Day Guarantee</div>' +
          '</div>' +
        '</div>' +
      '</div>';
  }

  function applyFromInput() {
    var input = document.getElementById('discountCodeInput');
    if (!input) return;
    applyCode(input.value, false);
  }

  function updateQty(productId, qty) {
    if (qty < 1) {
      remove(productId);
      return;
    }
    Cart.updateQty(productId, qty);
    render();
  }

  function remove(productId) {
    Cart.removeItem(productId);
    render();
  }

  function checkout() {
    // Build cart data and POST to billing.php
    var items = Cart.getItems();
    if (items.length === 0) return;

    var lang = I18n.getLang();

    // Collect product IDs and names
    var productIds = [];
    var productNames = [];
    for (var i = 0; i < items.length; i++) {
      var product = Products.getById(items[i].id);
      productIds.push(items[i].id);
      productNames.push(product ? product[lang].name : items[i].id);
    }

    var subtotal = Cart.getTotal();
    var finalTotal = subtotal;
    var discountCode = '';
    if (appliedDiscount) {
      finalTotal = appliedDiscount.fixed_price;
      discountCode = appliedDiscount.code;
    }

    // Fire Brevo checkout_started BEFORE the form submit so beacon goes out cleanly
    if (window.Brevo) {
      Brevo.checkoutStarted(items, {
        total:    finalTotal,
        currency: (typeof SiteConfig !== 'undefined' && SiteConfig.currency) ? SiteConfig.currency : '',
        subtotal: subtotal,
        discount_code: discountCode
      });
    }

    // Create and submit a hidden form to billing.php
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '/billing.php';
    form.target = '_blank';

    var fields = {
      'product_ids': JSON.stringify(productIds),
      'product_names': JSON.stringify(productNames),
      'cart_items': JSON.stringify(items),
      'subtotal': subtotal.toFixed(2),
      'total': finalTotal.toFixed(2),
      'discount_code': discountCode,
      'lang': lang,
      'traffic_source': (typeof SiteConfig !== 'undefined' && SiteConfig.trafficSource) ? SiteConfig.trafficSource : 'nonfb'
    };

    for (var name in fields) {
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      input.value = fields[name];
      form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
  }

  return {
    init: init,
    render: render,
    updateQty: updateQty,
    remove: remove,
    checkout: checkout,
    applyFromInput: applyFromInput,
    removeDiscount: removeDiscount
  };
})();
