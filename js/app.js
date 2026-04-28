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
    var btn      = document.getElementById('langSwitcherBtn');
    var menu     = document.getElementById('langSwitcherMenu');
    if (!switcher || !btn || !menu) return;

    var langs = (typeof SiteConfig !== 'undefined' && SiteConfig.availableLangs) ? SiteConfig.availableLangs : ['en'];

    function openMenu()  { menu.hidden = false; btn.setAttribute('aria-expanded', 'true');  switcher.classList.add('open'); }
    function closeMenu() { menu.hidden = true;  btn.setAttribute('aria-expanded', 'false'); switcher.classList.remove('open'); }

    function buildMenu() {
      menu.innerHTML = '';
      var current = I18n.getLang();
      for (var i = 0; i < langs.length; i++) {
        var code = langs[i];
        var li = document.createElement('li');
        li.className = 'lang-switcher-item' + (code === current ? ' active' : '');
        li.setAttribute('role', 'menuitem');
        li.dataset.lang = code;
        li.innerHTML =
          '<span class="lang-switcher-code">' + code.toUpperCase() + '</span>' +
          '<span class="lang-switcher-name">' + I18n.t('language.' + code) + '</span>';
        li.addEventListener('click', function (ev) {
          changeLang(ev.currentTarget.dataset.lang);
          closeMenu();
        });
        menu.appendChild(li);
      }
    }

    function changeLang(lang) {
      I18n.setLang(lang);   // updates currentLang, persists, applies translations

      if (typeof Products !== 'undefined' && document.getElementById('productGrid')) {
        Products.filter('all');
      }
      if (typeof ProductDetail !== 'undefined' && document.getElementById('productDetail')) {
        ProductDetail.init();
      }
      if (typeof CartPage !== 'undefined' && document.getElementById('cartPage')) {
        CartPage.render();
      }
    }

    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      if (menu.hidden) { buildMenu(); openMenu(); }
      else closeMenu();
    });

    document.addEventListener('click', function () { closeMenu(); });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeMenu();
    });
  }
})();
