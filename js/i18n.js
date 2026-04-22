var I18n = (function() {
  var translations = {};

  // Priority: 1) ?lang= URL param  2) localStorage  3) config default
  var defaultLang = (typeof SiteConfig !== 'undefined' && SiteConfig.defaultLang) ? SiteConfig.defaultLang : 'en';
  var langOverride = (typeof SiteConfig !== 'undefined' && SiteConfig.langOverride) ? SiteConfig.langOverride : '';

  var currentLang;
  if (langOverride) {
    currentLang = langOverride;
    localStorage.setItem('shopdeals-lang', langOverride);
  } else {
    currentLang = localStorage.getItem('shopdeals-lang') || defaultLang;
  }

  function load(callback) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', '/data/translations.json', true);
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        translations = JSON.parse(xhr.responseText);
        if (callback) callback();
      }
    };
    xhr.send();
  }

  function t(key) {
    var keys = key.split('.');
    var value = translations[currentLang];
    for (var i = 0; i < keys.length; i++) {
      if (!value) return key;
      value = value[keys[i]];
    }
    return value || key;
  }

  function applyTranslations() {
    var elements = document.querySelectorAll('[data-i18n]');
    for (var i = 0; i < elements.length; i++) {
      var key = elements[i].getAttribute('data-i18n');
      elements[i].textContent = t(key);
    }

    var placeholders = document.querySelectorAll('[data-i18n-placeholder]');
    for (var j = 0; j < placeholders.length; j++) {
      var pKey = placeholders[j].getAttribute('data-i18n-placeholder');
      placeholders[j].setAttribute('placeholder', t(pKey));
    }

    document.documentElement.lang = currentLang;

    var langLabel = document.getElementById('langLabel');
    if (langLabel) {
      langLabel.textContent = currentLang.toUpperCase();
    }
  }

  function setLang(lang) {
    currentLang = lang;
    localStorage.setItem('shopdeals-lang', lang);
    applyTranslations();
  }

  function toggleLang() {
    var langs = (typeof SiteConfig !== 'undefined' && SiteConfig.availableLangs) ? SiteConfig.availableLangs : ['en', 'es'];
    var idx = langs.indexOf(currentLang);
    var nextIdx = (idx + 1) % langs.length;
    setLang(langs[nextIdx]);
    return currentLang;
  }

  function getLang() {
    return currentLang;
  }

  return {
    load: load,
    t: t,
    applyTranslations: applyTranslations,
    setLang: setLang,
    toggleLang: toggleLang,
    getLang: getLang
  };
})();
