var Currency = (function() {
  var cfg = (typeof SiteConfig !== 'undefined' && SiteConfig.currency) ? SiteConfig.currency : {
    symbol: '€',
    code: 'EUR',
    position: 'before',
    decimals: 2,
    thousands_sep: ',',
    decimal_sep: '.'
  };

  function formatPrice(amount) {
    var num = parseFloat(amount);
    if (isNaN(num)) num = 0;

    // Format number with correct decimals
    var fixed = num.toFixed(cfg.decimals);
    var parts = fixed.split('.');
    var intPart = parts[0];
    var decPart = parts.length > 1 ? parts[1] : '';

    // Add thousands separator
    if (cfg.thousands_sep) {
      var rgx = /(\d+)(\d{3})/;
      while (rgx.test(intPart)) {
        intPart = intPart.replace(rgx, '$1' + cfg.thousands_sep + '$2');
      }
    }

    var formatted = decPart ? (intPart + cfg.decimal_sep + decPart) : intPart;

    if (cfg.position === 'after') {
      return formatted + cfg.symbol;
    }
    return cfg.symbol + formatted;
  }

  function getSymbol() {
    return cfg.symbol;
  }

  function getCode() {
    return cfg.code;
  }

  return {
    format: formatPrice,
    symbol: getSymbol,
    code: getCode
  };
})();
