/* eslint-disable */
/**
 * /js/brevo.js
 *
 * Browser-side Brevo event tracker. Same code on every shop.
 *
 * Reads from window.SiteConfig.brevo (provided by includes/config-js.php):
 *   - trackUrl:    "/brevo/track.php"
 *   - storeDomain: "ofertasydescuento.com"
 *   - uid:         "bo_<32 hex chars>"  (also in cookie bo_uid)
 *
 * Public API (window.Brevo):
 *   Brevo.track(eventName, properties = {})           Fire a generic event.
 *   Brevo.identify({ email, sms, firstname, ... })    Store identity in localStorage.
 *   Brevo.identity()                                  Read current identity.
 *   Brevo.captureLead(fields, eventName='lead_form_submitted')
 *                                                     Identify + fire a lead event.
 *   Brevo.cartUpdated(cart, totals)                   Convenience helper for cart events.
 *   Brevo.checkoutStarted(cart, totals)               Convenience helper.
 *
 * Identity storage:
 *   localStorage.bo_email — last captured email
 *   localStorage.bo_sms   — last captured phone (E.164)
 *   localStorage.bo_first / bo_last — names if captured
 *   localStorage.bo_country — ISO-2
 *   cookie bo_uid — server-set, ext_id
 */

(function () {
    'use strict';

    var LS_KEYS = {
        email:   'bo_email',
        sms:     'bo_sms',
        first:   'bo_first',
        last:    'bo_last',
        country: 'bo_country',
        cartTok: 'bo_cart_token',
    };

    var cfg = (window.SiteConfig && window.SiteConfig.brevo) || {};
    if (!cfg.trackUrl)   cfg.trackUrl   = '/brevo/track.php';
    if (!cfg.storeDomain) cfg.storeDomain = location.hostname.replace(/^www\./, '');

    // --- Helpers ---------------------------------------------------------

    function readCookie(name) {
        var m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[.$?*|{}()[\]\\\/+^]/g, '\\$&') + '=([^;]*)'));
        return m ? decodeURIComponent(m[1]) : null;
    }

    function lsGet(k) {
        try { return localStorage.getItem(k); } catch (e) { return null; }
    }
    function lsSet(k, v) {
        try {
            if (v === null || v === undefined || v === '') localStorage.removeItem(k);
            else localStorage.setItem(k, v);
        } catch (e) {}
    }

    function normEmail(v) {
        if (!v) return null;
        v = String(v).trim().toLowerCase();
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) ? v : null;
    }
    function normSms(v) {
        if (!v) return null;
        v = String(v).replace(/[\s\-\(\)\.]/g, '');
        if (v.charAt(0) !== '+') return null;
        var digits = v.slice(1).replace(/\D/g, '');
        return (digits.length >= 8 && digits.length <= 15) ? '+' + digits : null;
    }

    function currentUid() {
        return cfg.uid || readCookie('bo_uid') || null;
    }

    function currentIdentity() {
        var id = {
            ext_id: currentUid(),
            email:  normEmail(lsGet(LS_KEYS.email)),
            sms:    normSms(lsGet(LS_KEYS.sms)),
        };
        // Strip empties
        Object.keys(id).forEach(function (k) { if (!id[k]) delete id[k]; });
        return id;
    }

    function currentContact() {
        var c = {
            firstname: lsGet(LS_KEYS.first) || undefined,
            lastname:  lsGet(LS_KEYS.last)  || undefined,
            country:   lsGet(LS_KEYS.country) || undefined,
        };
        Object.keys(c).forEach(function (k) { if (!c[k]) delete c[k]; });
        return c;
    }

    function getOrCreateCartToken() {
        var t = lsGet(LS_KEYS.cartTok);
        if (t) return t;
        t = 'cart_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 10);
        lsSet(LS_KEYS.cartTok, t);
        return t;
    }
    function clearCartToken() { lsSet(LS_KEYS.cartTok, null); }

    // --- Core: send event -----------------------------------------------

    /**
     * Fire an event. Returns a Promise (resolves even on network failure —
     * the server-side outbox + middleware queue handle delivery).
     */
    function track(eventName, properties) {
        if (!eventName || typeof eventName !== 'string') return Promise.resolve();

        var body = {
            event:       eventName,
            occurred_at: new Date().toISOString(),
            identity:    currentIdentity(),
            contact:     currentContact(),
            properties:  properties || {},
        };

        // Use sendBeacon when available for unload-safe sends (e.g. checkout transition).
        try {
            if (navigator.sendBeacon && eventName === 'checkout_started') {
                var blob = new Blob([JSON.stringify(body)], { type: 'application/json' });
                navigator.sendBeacon(cfg.trackUrl, blob);
                return Promise.resolve({ beacon: true });
            }
        } catch (e) {}

        return fetch(cfg.trackUrl, {
            method:      'POST',
            credentials: 'same-origin',
            keepalive:   true,
            headers:     { 'Content-Type': 'application/json' },
            body:        JSON.stringify(body),
        }).catch(function (e) {
            // Swallow — event is lost only if the local server is down,
            // and there's nothing the browser can do about that.
            if (window.console && console.warn) {
                console.warn('Brevo.track failed', eventName, e);
            }
        });
    }

    // --- Identification -------------------------------------------------

    /**
     * Store identity locally. Subsequent track() calls will include it.
     * Pass any subset of: email, sms, firstname, lastname, country.
     * Does NOT fire any event by itself — call captureLead() if you want that.
     */
    function identify(fields) {
        if (!fields || typeof fields !== 'object') return;
        var e = normEmail(fields.email);
        var s = normSms(fields.sms);
        if (e !== null) lsSet(LS_KEYS.email, e);
        if (s !== null) lsSet(LS_KEYS.sms, s);
        if (fields.firstname) lsSet(LS_KEYS.first, String(fields.firstname).trim());
        if (fields.lastname)  lsSet(LS_KEYS.last,  String(fields.lastname).trim());
        if (fields.country)   lsSet(LS_KEYS.country, String(fields.country).trim().toUpperCase());
    }

    function identity() {
        return Object.assign({}, currentIdentity(), currentContact());
    }

    /**
     * Identify + fire an event in one call. Use this from popups, lead forms, chat.
     * Returns the track() promise.
     */
    function captureLead(fields, eventName) {
        identify(fields);
        return track(eventName || 'lead_form_submitted', {
            source: fields && fields.source ? fields.source : undefined,
        });
    }

    // --- Cart helpers ---------------------------------------------------

    function cartSummary(cart, totals) {
        cart = cart || [];
        var qty = 0;
        for (var i = 0; i < cart.length; i++) {
            qty += (parseInt(cart[i].qty, 10) || 0);
        }
        var props = {
            cart_token: getOrCreateCartToken(),
            item_count: cart.length,
            unit_count: qty,
        };
        if (totals && typeof totals === 'object') {
            if (totals.total != null)    props.total    = totals.total;
            if (totals.subtotal != null) props.subtotal = totals.subtotal;
            if (totals.currency)         props.currency = totals.currency;
            if (totals.discount != null) props.discount = totals.discount;
            if (totals.discount_code)    props.discount_code = totals.discount_code;
            if (totals.product_names)    props.product_names = totals.product_names;
            if (totals.product_ids)      props.product_ids   = totals.product_ids;
        }
        return props;
    }

    function cartUpdated(cart, totals) {
        if (!cart || cart.length === 0) {
            clearCartToken();
            return Promise.resolve();
        }
        return track('cart_updated', cartSummary(cart, totals));
    }

    function checkoutStarted(cart, totals) {
        return track('checkout_started', cartSummary(cart, totals));
    }

    // --- Public API -----------------------------------------------------

    window.Brevo = {
        track:           track,
        identify:        identify,
        identity:        identity,
        captureLead:     captureLead,
        cartUpdated:     cartUpdated,
        checkoutStarted: checkoutStarted,
        // Internal-ish, exposed for debugging from devtools
        _config:    cfg,
        _cartToken: getOrCreateCartToken,
    };
})();
