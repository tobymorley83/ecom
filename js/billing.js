/**
 * Billing form: populates country + phone-prefix dropdowns from
 * Countries (countries.js), preselects from SiteConfig.country,
 * keeps prefix in sync with country, and validates on submit.
 */
(function() {

  function init() {
    if (!window.Countries || !window.Countries.length) return;

    var form          = document.getElementById('billingForm');
    var countrySelect = document.getElementById('bf_country');
    var prefixSelect  = document.getElementById('bf_phone_prefix');
    if (!form || !countrySelect || !prefixSelect) return;

    populateCountry(countrySelect);
    populatePrefix(prefixSelect);

    var defaultCode = (typeof SiteConfig !== 'undefined' && SiteConfig.country) ? SiteConfig.country : 'US';
    if (!CountryByCode[defaultCode]) defaultCode = 'US';

    countrySelect.value = defaultCode;
    prefixSelect.value  = CountryByCode[defaultCode].prefix;

    // Prefill from anything Brevo already captured (wheel, popup, prior visit).
    prefillFromBrevo(countrySelect, prefixSelect);

    // Keep phone prefix synced to country selection.
    countrySelect.addEventListener('change', function() {
      var entry = CountryByCode[countrySelect.value];
      if (entry) prefixSelect.value = entry.prefix;
    });

    form.addEventListener('submit', function(e) {
      if (!validate()) {
        e.preventDefault();
        showError();
        return;
      }
      // Persist whatever the user typed back into Brevo so the next
      // visit (or the FB gateway return) finds the same identity.
      saveIdentity(countrySelect, prefixSelect);
    });
  }

  function prefillFromBrevo(countrySelect, prefixSelect) {
    if (typeof Brevo === 'undefined' || !Brevo.identity) return;
    var id = Brevo.identity() || {};

    if (id.firstname) document.getElementById('bf_firstname').value = id.firstname;
    if (id.lastname)  document.getElementById('bf_lastname').value  = id.lastname;
    if (id.email)     document.getElementById('bf_email').value     = id.email;

    if (id.country && CountryByCode[id.country]) {
      countrySelect.value = id.country;
      prefixSelect.value  = CountryByCode[id.country].prefix;
    }

    if (id.sms) {
      var parsed = parseE164(id.sms);
      if (parsed) {
        prefixSelect.value = parsed.prefix;
        document.getElementById('bf_phone').value = parsed.national;
      }
    }
  }

  function parseE164(e164) {
    if (!e164 || !window.Countries) return null;
    var seen = {};
    for (var i = 0; i < Countries.length; i++) seen[Countries[i].prefix] = true;
    var prefixes = Object.keys(seen).sort(function(a, b) { return b.length - a.length; });
    for (var j = 0; j < prefixes.length; j++) {
      if (e164.indexOf(prefixes[j]) === 0) {
        return {
          prefix:   prefixes[j],
          national: e164.slice(prefixes[j].length).replace(/[^\d]/g, '')
        };
      }
    }
    return null;
  }

  function saveIdentity(countrySelect, prefixSelect) {
    if (typeof Brevo === 'undefined' || !Brevo.identify) return;
    var prefixDigits = (prefixSelect.value || '').replace(/[^\d]/g, '');
    var phoneDigits  = (document.getElementById('bf_phone').value || '').replace(/[^\d]/g, '');
    Brevo.identify({
      email:     document.getElementById('bf_email').value,
      sms:       prefixDigits ? '+' + prefixDigits + phoneDigits : '',
      firstname: document.getElementById('bf_firstname').value,
      lastname:  document.getElementById('bf_lastname').value,
      country:   countrySelect.value
    });
  }

  function populateCountry(select) {
    for (var i = 0; i < Countries.length; i++) {
      var c = Countries[i];
      var opt = document.createElement('option');
      opt.value = c.code;
      opt.textContent = c.name;
      select.appendChild(opt);
    }
  }

  function populatePrefix(select) {
    var seen = {};
    var unique = [];
    for (var i = 0; i < Countries.length; i++) {
      var p = Countries[i].prefix;
      if (!seen[p]) { seen[p] = true; unique.push(p); }
    }
    unique.sort(function(a, b) {
      return parseInt(a.slice(1), 10) - parseInt(b.slice(1), 10);
    });
    for (var j = 0; j < unique.length; j++) {
      var opt = document.createElement('option');
      opt.value = unique[j];
      opt.textContent = unique[j];
      select.appendChild(opt);
    }
  }

  function validate() {
    var ok = true;
    var ids = ['bf_firstname','bf_lastname','bf_email','bf_phone',
               'bf_phone_prefix','bf_address','bf_city','bf_zip','bf_country'];

    for (var i = 0; i < ids.length; i++) {
      var el = document.getElementById(ids[i]);
      var v = el ? (el.value || '').trim() : '';
      if (!v) { mark(el, false); ok = false; } else { mark(el, true); }
    }

    var emailEl = document.getElementById('bf_email');
    if (emailEl && emailEl.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailEl.value.trim())) {
      mark(emailEl, false); ok = false;
    }

    var phoneEl = document.getElementById('bf_phone');
    if (phoneEl) {
      var digits = (phoneEl.value || '').replace(/[^\d]/g, '');
      if (digits.length < 6 || digits.length > 15) { mark(phoneEl, false); ok = false; }
    }
    return ok;
  }

  function mark(el, valid) {
    if (!el) return;
    el.style.borderColor = valid ? '' : '#e94560';
  }

  function showError() {
    var msg = (window.I18n && I18n.t) ? I18n.t('billing.fill_required') : 'Please complete all fields correctly.';
    if (window.Cart && Cart.showToast) Cart.showToast(msg);
    else alert(msg);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
