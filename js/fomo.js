/**
 * FOMO / Urgency Module
 * =====================
 * All features are driven by FomoConfig (set by PHP).
 * If a feature key is missing from FomoConfig, it's disabled.
 */
var Fomo = (function() {

  var cfg = (typeof FomoConfig !== 'undefined') ? FomoConfig : {};

  // ── Helpers ────────────────────────────────────────────────────────
  function rand(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
  }

  function el(tag, cls, html) {
    var e = document.createElement(tag);
    if (cls) e.className = cls;
    if (html) e.innerHTML = html;
    return e;
  }

  // ══════════════════════════════════════════════════════════════════
  // 1. RECENT PURCHASE POPUPS
  // ══════════════════════════════════════════════════════════════════
  function initRecentPurchases() {
    var rc = cfg.recent_purchases;
    if (!rc) return;

    // Build popup container (fixed bottom-left)
    var popup = el('div', 'fomo-recent-popup');
    popup.id = 'fomoRecentPopup';
    document.body.appendChild(popup);

    function showPopup() {
      // Get products — filter to configured IDs if set, otherwise use all
      var allProducts = (typeof Products !== 'undefined') ? Products.getAll() : [];
      if (allProducts.length === 0) return;

      var pool = allProducts;
      if (rc.product_ids && rc.product_ids.length > 0) {
        pool = [];
        for (var p = 0; p < allProducts.length; p++) {
          if (rc.product_ids.indexOf(allProducts[p].id) !== -1) {
            pool.push(allProducts[p]);
          }
        }
        if (pool.length === 0) pool = allProducts; // fallback if no matches
      }

      var lang = (typeof I18n !== 'undefined') ? I18n.getLang() : 'en';
      var product = pool[rand(0, pool.length - 1)];
      var name = product[lang] ? product[lang].name : product.en.name;
      var image = product.image || '';
      var city = rc.cities[rand(0, rc.cities.length - 1)] || 'México';
      var minsAgo = rand(rc.time_ago_min, rc.time_ago_max);
      var timeText = minsAgo + ' ' + ((typeof I18n !== 'undefined') ? I18n.t('fomo.minutes_ago') : 'min ago');

      popup.innerHTML =
        '<button class="fomo-recent-close" onclick="Fomo.closeRecent()">&times;</button>' +
        (image ? '<img class="fomo-recent-img" src="' + image + '" alt="">' : '') +
        '<div class="fomo-recent-body">' +
          '<div class="fomo-recent-text">' +
            '<span class="fomo-recent-who">' + ((typeof I18n !== 'undefined') ? I18n.t('fomo.someone_in') : 'Someone in') + ' <strong>' + city + '</strong></span>' +
            ' ' + ((typeof I18n !== 'undefined') ? I18n.t('fomo.just_bought') : 'just bought') +
          '</div>' +
          '<div class="fomo-recent-product">' + name + '</div>' +
          '<div class="fomo-recent-time">' + timeText + '</div>' +
        '</div>';

      popup.classList.add('fomo-show');

      setTimeout(function() {
        popup.classList.remove('fomo-show');
      }, rc.display_time * 1000);
    }

    function scheduleNext() {
      var min = (rc.interval_min || 15) * 1000;
      var max = (rc.interval_max || 30) * 1000;
      var delay = min + Math.random() * (max - min);
      setTimeout(function() {
        showPopup();
        scheduleNext();
      }, delay);
    }

    setTimeout(function() {
      showPopup();
      scheduleNext();
    }, rc.delay_first * 1000);
  }

  function closeRecent() {
    var p = document.getElementById('fomoRecentPopup');
    if (p) p.classList.remove('fomo-show');
  }

  // ══════════════════════════════════════════════════════════════════
  // 2. WELCOME / DISCOUNT POPUP
  // ══════════════════════════════════════════════════════════════════
  function initWelcomePopup() {
    var wc = cfg.welcome_popup;
    if (!wc) return;

    if (wc.show_once && sessionStorage.getItem('fomo-welcome-shown')) return;

    setTimeout(function() {
      var overlay = el('div', 'fomo-overlay fomo-welcome-overlay');
      overlay.id = 'fomoWelcomeOverlay';
      overlay.onclick = function(e) { if (e.target === overlay) closeWelcome(); };

      var t = (typeof I18n !== 'undefined') ? I18n : null;
      var title = t ? t.t('fomo.welcome_title') : 'Welcome!';
      var subtitle = t ? t.t('fomo.welcome_subtitle') : "Don't miss our special offers";
      var btnText = t ? t.t('fomo.welcome_btn') : 'Start Shopping';

      var codeHtml = '';
      if (wc.discount_code) {
        var codeLabel = t ? t.t('fomo.your_code') : 'Your discount code:';
        codeHtml =
          '<div class="fomo-popup-code">' +
            '<span class="fomo-popup-code-label">' + codeLabel + '</span>' +
            '<span class="fomo-popup-code-value">' + wc.discount_code + '</span>' +
          '</div>';
      }

      var modal = el('div', 'fomo-popup-modal');
      modal.innerHTML =
        '<button class="fomo-popup-close" onclick="Fomo.closeWelcome()">&times;</button>' +
        '<div class="fomo-popup-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg></div>' +
        '<h2>' + title + '</h2>' +
        '<p>' + subtitle + '</p>' +
        codeHtml +
        '<a href="/#products" class="fomo-popup-btn" onclick="Fomo.closeWelcome()">' + btnText + '</a>';

      overlay.appendChild(modal);
      document.body.appendChild(overlay);

      requestAnimationFrame(function() {
        overlay.classList.add('fomo-show');
      });

      if (wc.show_once) sessionStorage.setItem('fomo-welcome-shown', '1');
    }, wc.delay * 1000);
  }

  function closeWelcome() {
    var ov = document.getElementById('fomoWelcomeOverlay');
    if (ov) {
      ov.classList.remove('fomo-show');
      setTimeout(function() { ov.remove(); }, 300);
    }
  }

  // ══════════════════════════════════════════════════════════════════
  // 3. EXIT-INTENT POPUP
  // ══════════════════════════════════════════════════════════════════
  function initExitIntent() {
    var ec = cfg.exit_intent;
    if (!ec) return;
    if (ec.show_once && sessionStorage.getItem('fomo-exit-shown')) return;

    var triggered = false;

    function showExitPopup() {
      if (triggered) return;
      triggered = true;

      var overlay = el('div', 'fomo-overlay fomo-exit-overlay');
      overlay.id = 'fomoExitOverlay';
      overlay.onclick = function(e) { if (e.target === overlay) closeExit(); };

      var t = (typeof I18n !== 'undefined') ? I18n : null;
      var title = t ? t.t('fomo.exit_title') : 'Wait!';
      var subtitle = t ? t.t('fomo.exit_subtitle') : "Don't leave empty-handed";
      var btnText = t ? t.t('fomo.exit_btn') : 'View Offers';

      var codeHtml = '';
      if (ec.discount_code) {
        var codeLabel = t ? t.t('fomo.your_code') : 'Your discount code:';
        codeHtml =
          '<div class="fomo-popup-code">' +
            '<span class="fomo-popup-code-label">' + codeLabel + '</span>' +
            '<span class="fomo-popup-code-value">' + ec.discount_code + '</span>' +
          '</div>';
      }

      var modal = el('div', 'fomo-popup-modal');
      modal.innerHTML =
        '<button class="fomo-popup-close" onclick="Fomo.closeExit()">&times;</button>' +
        '<div class="fomo-popup-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg></div>' +
        '<h2>' + title + '</h2>' +
        '<p>' + subtitle + '</p>' +
        codeHtml +
        '<a href="/#products" class="fomo-popup-btn" onclick="Fomo.closeExit()">' + btnText + '</a>';

      overlay.appendChild(modal);
      document.body.appendChild(overlay);

      requestAnimationFrame(function() {
        overlay.classList.add('fomo-show');
      });

      if (ec.show_once) sessionStorage.setItem('fomo-exit-shown', '1');
    }

    // Desktop: mouse leaves viewport from top
    document.addEventListener('mouseout', function(e) {
      if (e.clientY < 5 && e.relatedTarget === null) {
        showExitPopup();
      }
    });

    // Mobile: idle timeout
    if ('ontouchstart' in window && ec.mobile_idle_sec > 0) {
      var idleTimer;
      function resetIdle() {
        clearTimeout(idleTimer);
        idleTimer = setTimeout(showExitPopup, ec.mobile_idle_sec * 1000);
      }
      document.addEventListener('touchstart', resetIdle);
      document.addEventListener('scroll', resetIdle);
      resetIdle();
    }
  }

  function closeExit() {
    var ov = document.getElementById('fomoExitOverlay');
    if (ov) {
      ov.classList.remove('fomo-show');
      setTimeout(function() { ov.remove(); }, 300);
    }
  }

  // ══════════════════════════════════════════════════════════════════
  // 4. COUNTDOWN TIMER
  // ══════════════════════════════════════════════════════════════════
  function initCountdown() {
    var cc = cfg.countdown;
    if (!cc) return;

    // Set end time on first visit, stored in localStorage
    var COUNTDOWN_KEY = 'fomo-countdown-end';
    var endTime = localStorage.getItem(COUNTDOWN_KEY);

    if (!endTime) {
      endTime = Date.now() + (cc.hours * 60 * 60 * 1000);
      localStorage.setItem(COUNTDOWN_KEY, endTime);
    } else {
      endTime = parseInt(endTime);
    }

    // If expired, reset with new time
    if (Date.now() > endTime) {
      endTime = Date.now() + (cc.hours * 60 * 60 * 1000);
      localStorage.setItem(COUNTDOWN_KEY, endTime);
    }

    function renderCountdown() {
      var targets = document.querySelectorAll('.fomo-countdown');
      if (targets.length === 0) return;

      var diff = endTime - Date.now();
      if (diff < 0) diff = 0;

      var h = Math.floor(diff / 3600000);
      var m = Math.floor((diff % 3600000) / 60000);
      var s = Math.floor((diff % 60000) / 1000);

      var timeStr = String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
      var label = (typeof I18n !== 'undefined') ? I18n.t('fomo.offer_ends_in') : 'Offer ends in';

      for (var i = 0; i < targets.length; i++) {
        targets[i].innerHTML =
          '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> ' +
          '<span>' + label + ' <strong>' + timeStr + '</strong></span>';
      }
    }

    // Inject countdown elements into the page
    if (cc.show_on_home) {
      var heroContent = document.querySelector('.hero-content');
      if (heroContent) {
        var cdEl = el('div', 'fomo-countdown fomo-countdown-hero');
        heroContent.appendChild(cdEl);
      }
    }

    if (cc.show_on_product) {
      function injectProductCountdown() {
        var priceEl = document.querySelector('.product-info .product-price');
        if (priceEl && !priceEl.parentNode.querySelector('.fomo-countdown')) {
          var cdEl = el('div', 'fomo-countdown fomo-countdown-product');
          priceEl.parentNode.insertBefore(cdEl, priceEl.nextSibling);
        }
      }

      // Run immediately in case product detail is already rendered
      injectProductCountdown();

      // Also observe for future renders
      var detailEl = document.getElementById('productDetail');
      if (detailEl) {
        var observer = new MutationObserver(injectProductCountdown);
        observer.observe(detailEl, { childList: true, subtree: true });
      }
    }

    renderCountdown();
    setInterval(renderCountdown, 1000);
  }

  // ══════════════════════════════════════════════════════════════════
  // 5. LOW STOCK SCARCITY
  // ══════════════════════════════════════════════════════════════════
  function initLowStock() {
    var lc = cfg.low_stock;
    if (!lc) return;

    // Generate consistent stock per product (seeded by product ID hash)
    var stockCache = {};
    function getStock(productId) {
      if (stockCache[productId] !== undefined) return stockCache[productId];
      // Simple hash to get deterministic "random" per product per session
      var hash = 0;
      for (var i = 0; i < productId.length; i++) {
        hash = ((hash << 5) - hash) + productId.charCodeAt(i);
        hash |= 0;
      }
      // Use session-based seed so it changes between sessions
      var sessionSeed = sessionStorage.getItem('fomo-stock-seed');
      if (!sessionSeed) {
        sessionSeed = String(Math.random());
        sessionStorage.setItem('fomo-stock-seed', sessionSeed);
      }
      hash = hash + Math.floor(parseFloat(sessionSeed) * 1000);
      var stock = lc.min + (Math.abs(hash) % (lc.max - lc.min + 1));
      stockCache[productId] = stock;
      return stock;
    }

    function getStockHtml(stock) {
      if (stock > lc.threshold) return '';
      var label = (typeof I18n !== 'undefined') ? I18n.t('fomo.only_left') : 'Only {n} left in stock!';
      label = label.replace('{n}', '<strong>' + stock + '</strong>');
      return '<div class="fomo-low-stock"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg> <span>' + label + '</span></div>';
    }

    // Inject into product cards
    if (lc.show_on_cards) {
      var cardObserver = new MutationObserver(function() {
        var cards = document.querySelectorAll('.product-card:not([data-fomo-stock])');
        for (var i = 0; i < cards.length; i++) {
          var card = cards[i];
          var id = card.getAttribute('data-id');
          if (!id) continue;
          card.setAttribute('data-fomo-stock', '1');
          var stock = getStock(id);
          var html = getStockHtml(stock);
          if (html) {
            var body = card.querySelector('.product-card-body');
            if (body) body.insertAdjacentHTML('beforeend', html);
          }
        }
      });
      var gridEl = document.getElementById('productGrid');
      if (gridEl) {
        cardObserver.observe(gridEl, { childList: true, subtree: true });
      }
    }

    // Inject into product detail
    if (lc.show_on_detail) {
      function injectDetailStock() {
        var detailEl = document.getElementById('productDetail');
        if (!detailEl || detailEl.querySelector('.fomo-low-stock')) return;

        // Get product ID from URL
        var params = new URLSearchParams(window.location.search);
        var pid = params.get('id');
        if (!pid) return;

        var stock = getStock(pid);
        var html = getStockHtml(stock);
        if (html) {
          var metaEl = detailEl.querySelector('.product-meta');
          if (metaEl) metaEl.insertAdjacentHTML('afterend', html);
        }
      }

      // Run immediately in case product detail is already rendered
      injectDetailStock();

      // Also observe for future renders (language switch, etc.)
      var pdEl = document.getElementById('productDetail');
      if (pdEl) {
        var detailObserver = new MutationObserver(injectDetailStock);
        detailObserver.observe(pdEl, { childList: true, subtree: true });
      }
    }

    // Expose for external use
    Fomo.getStock = getStock;
    Fomo.getStockHtml = getStockHtml;
  }

  // ══════════════════════════════════════════════════════════════════
  // 6. CART PROGRESS BAR
  // ══════════════════════════════════════════════════════════════════
  function initCartProgress() {
    var cp = cfg.cart_progress;
    if (!cp) return;

    function renderBar() {
      var container = document.getElementById('cartPage');
      if (!container) return;

      // Remove old bar if exists
      var oldBar = document.querySelector('.fomo-cart-progress');
      if (oldBar) oldBar.remove();

      var total = (typeof Cart !== 'undefined') ? Cart.getTotal() : 0;
      if (total <= 0) return;

      var remaining = cp.threshold - total;
      var percent = Math.min(100, (total / cp.threshold) * 100);

      var t = (typeof I18n !== 'undefined') ? I18n : null;
      var message;
      if (remaining > 0) {
        var formatted = (typeof Currency !== 'undefined') ? Currency.format(remaining) : remaining.toFixed(2);
        message = t ? t.t('fomo.cart_add_more').replace('{amount}', formatted) : 'Add ' + formatted + ' more for free shipping!';
      } else {
        message = t ? t.t('fomo.cart_unlocked') : 'You unlocked free shipping!';
      }

      var bar = el('div', 'fomo-cart-progress');
      bar.innerHTML =
        '<div class="fomo-cart-progress-text">' + message + '</div>' +
        '<div class="fomo-cart-progress-track"><div class="fomo-cart-progress-fill" style="width:' + percent + '%"></div></div>';

      // Insert after cart heading
      var heading = container.querySelector('h1');
      if (heading) {
        heading.parentNode.insertBefore(bar, heading.nextSibling);
      }
    }

    // Observe cart page changes
    var cartEl = document.getElementById('cartPage');
    if (cartEl) {
      var cartObserver = new MutationObserver(renderBar);
      cartObserver.observe(cartEl, { childList: true, subtree: true });
    }
  }

  // ══════════════════════════════════════════════════════════════════
  // INIT — called after app.js loads
  // ══════════════════════════════════════════════════════════════════
  function init() {
    if (typeof FomoConfig === 'undefined') return;

    initRecentPurchases();
    initWelcomePopup();
    initExitIntent();
    initCountdown();
    initLowStock();
    initCartProgress();
  }

  return {
    init: init,
    closeRecent: closeRecent,
    closeWelcome: closeWelcome,
    closeExit: closeExit
  };
})();

// Auto-init after DOM + translations are ready
document.addEventListener('DOMContentLoaded', function() {
  // Small delay to ensure Products/I18n are loaded
  setTimeout(function() { Fomo.init(); }, 500);
});
