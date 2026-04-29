/**
 * Ecommerce events — pushes to window.dataLayer using the GA Enhanced
 * Ecommerce format. Yandex Metrika is initialized with
 * `ecommerce: "dataLayer"`, so it picks these up automatically. The
 * same payloads also feed Google Analytics if/when it is added.
 */
var Ecommerce = (function() {
  window.dataLayer = window.dataLayer || [];

  function currency() {
    return (typeof SiteConfig !== 'undefined' && SiteConfig.currency && SiteConfig.currency.code)
      ? SiteConfig.currency.code : 'USD';
  }

  function info(product) {
    var lang = (window.I18n && I18n.getLang) ? I18n.getLang() : 'en';
    if (typeof Products !== 'undefined' && Products.getInfo) {
      return Products.getInfo(product, lang);
    }
    return product[lang] || product.en || product.es || {};
  }

  function toItem(product, qty) {
    var i = info(product);
    var item = {
      id:    product.id,
      name:  i.name || product.id
    };
    if (typeof product.price === 'number') item.price = product.price;
    if (product.category) item.category = product.category;
    if (qty != null) item.quantity = qty;
    return item;
  }

  function push(action, payload) {
    var pkt = { ecommerce: {} };
    pkt.ecommerce.currencyCode = currency();
    pkt.ecommerce[action] = payload;
    try { window.dataLayer.push(pkt); } catch (e) {}
  }

  function buildItems(cartItems) {
    var items = [];
    if (!cartItems || !cartItems.length) return items;
    for (var i = 0; i < cartItems.length; i++) {
      var ci = cartItems[i];
      var p  = (typeof Products !== 'undefined' && Products.getById) ? Products.getById(ci.id) : null;
      if (p) {
        var it = toItem(p, ci.qty || 1);
        if (typeof ci.price === 'number') it.price = ci.price;  // honour overridden price (e.g. free gift)
        items.push(it);
      } else {
        items.push({ id: ci.id, quantity: ci.qty || 1, price: typeof ci.price === 'number' ? ci.price : 0 });
      }
    }
    return items;
  }

  function viewProduct(product) {
    if (!product) return;
    push('detail', { products: [toItem(product)] });
  }

  function addToCart(product, qty, overridePrice) {
    if (!product) return;
    var item = toItem(product, qty || 1);
    if (typeof overridePrice === 'number') item.price = overridePrice;
    push('add', { products: [item] });
  }

  function removeFromCart(product, qty) {
    if (!product) return;
    push('remove', { products: [toItem(product, qty || 1)] });
  }

  function checkout(cartItems, step) {
    push('checkout', {
      actionField: { step: step || 1 },
      products: buildItems(cartItems)
    });
  }

  function purchase(orderId, cartItems, total, discountCode) {
    var actionField = {
      id:      orderId,
      revenue: Number(total).toFixed(2)
    };
    if (discountCode) actionField.coupon = discountCode;
    push('purchase', {
      actionField: actionField,
      products: buildItems(cartItems)
    });
  }

  return {
    viewProduct:    viewProduct,
    addToCart:      addToCart,
    removeFromCart: removeFromCart,
    checkout:       checkout,
    purchase:       purchase
  };
})();
