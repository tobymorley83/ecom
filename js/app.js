(function() {
  I18n.load(function() {
    I18n.applyTranslations();

    Cart.updateCartCount();

    if (typeof Products !== 'undefined' && typeof Products.init === 'function' && document.getElementById('productGrid')) {
      Products.init();
    }

    if (typeof ProductDetail !== 'undefined' && document.getElementById('productDetail')) {
      ProductDetail.init();
    }

    if (typeof CartPage !== 'undefined' && document.getElementById('cartPage')) {
      CartPage.init();
    }

    setupHeader();
    setupMobileNav();
    setupLangSwitcher();
  });

  function setupHeader() {
    var header = document.getElementById('siteHeader');
    if (!header) return;

    window.addEventListener('scroll', function() {
      if (window.scrollY > 10) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }
    });
  }

  function setupMobileNav() {
    var btn = document.getElementById('mobileMenuBtn');
    var overlay = document.getElementById('mobileOverlay');
    var nav = document.getElementById('mobileNav');
    var closeBtn = document.getElementById('mobileNavClose');

    if (!btn || !overlay || !nav) return;

    function openNav() {
      overlay.classList.add('active');
      nav.classList.add('active');
      document.body.style.overflow = 'hidden';
    }

    function closeNav() {
      overlay.classList.remove('active');
      nav.classList.remove('active');
      document.body.style.overflow = '';
    }

    btn.addEventListener('click', openNav);
    overlay.addEventListener('click', closeNav);
    if (closeBtn) closeBtn.addEventListener('click', closeNav);

    var navLinks = nav.querySelectorAll('a');
    for (var i = 0; i < navLinks.length; i++) {
      navLinks[i].addEventListener('click', closeNav);
    }
  }

  function setupLangSwitcher() {
    var switcher = document.getElementById('langSwitcher');
    if (!switcher) return;

    switcher.addEventListener('click', function() {
      var newLang = I18n.toggleLang();

      if (typeof Products !== 'undefined' && document.getElementById('productGrid')) {
        Products.filter('all');
      }

      if (typeof ProductDetail !== 'undefined' && document.getElementById('productDetail')) {
        ProductDetail.init();
      }

      if (typeof CartPage !== 'undefined' && document.getElementById('cartPage')) {
        CartPage.render();
      }

      I18n.applyTranslations();
    });
  }
})();
