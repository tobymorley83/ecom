/* /js/brevo-popup.js
 *
 * Lead capture popup. Reads window.BrevoPopupConfig (set by /brevo/popup-config.php)
 * and window.Brevo (set by /js/brevo.js). Both must be loaded before this file.
 *
 * Triggers: time delay, scroll depth, exit intent (desktop), add-to-cart.
 * Suppression: cookie-based, configurable days for dismiss vs submit.
 *
 * Public API:
 *   BrevoPopup.show(reason)    Force-show now (e.g. from a "Get discount" button)
 *   BrevoPopup.hide()          Force-close
 *   BrevoPopup.reset()         Clear suppression cookie (debug)
 */

(function () {
    'use strict';

    var cfg = window.BrevoPopupConfig;
    if (!cfg || !cfg.enabled) return;

    if (!window.Brevo) {
        console.warn('[BrevoPopup] window.Brevo not found — load /js/brevo.js first.');
        return;
    }

    // ----- State -----
    var SUPPRESS_COOKIE = 'bo_popup_suppress';
    var shown = false;
    var rootEl = null;

    // ----- Helpers -----
    function isMobile() {
        return window.matchMedia && window.matchMedia('(max-width: 768px), (pointer: coarse)').matches;
    }

    function readCookie(name) {
        var m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[.$?*|{}()[\]\\\/+^]/g, '\\$&') + '=([^;]*)'));
        return m ? decodeURIComponent(m[1]) : null;
    }
    function writeCookie(name, value, days) {
        var d = new Date();
        d.setTime(d.getTime() + days * 86400000);
        var secure = location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = name + '=' + encodeURIComponent(value) +
            '; expires=' + d.toUTCString() +
            '; path=/; SameSite=Lax' + secure;
    }
    function isSuppressed() {
        return !!readCookie(SUPPRESS_COOKIE);
    }
    function suppress(days) {
        if (days > 0) writeCookie(SUPPRESS_COOKIE, '1', days);
    }

    function escapeHtml(s) {
        if (s == null) return '';
        return String(s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function normalizeEmail(v) {
        if (!v) return null;
        v = String(v).trim().toLowerCase();
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) ? v : null;
    }
    function normalizeSms(v) {
        if (!v) return null;
        v = String(v).replace(/[\s\-\(\)\.]/g, '');
        if (v.charAt(0) !== '+') return null;
        var digits = v.slice(1).replace(/\D/g, '');
        return (digits.length >= 8 && digits.length <= 15) ? '+' + digits : null;
    }

    // ----- Render -----
    function render() {
        var t = cfg.text || {};
        var hasImage = !!cfg.image;

        var html = '' +
            '<div class="bp-backdrop" role="dialog" aria-modal="true" aria-labelledby="bp-headline">' +
                '<div class="bp-modal">' +
                    '<button type="button" class="bp-close" aria-label="' + escapeHtml(t.aria_close) + '">&times;</button>' +
                    (hasImage
                        ? '<img class="bp-image" src="' + escapeHtml(cfg.image) + '" alt="">'
                        : '') +
                    '<div class="bp-body' + (hasImage ? '' : ' bp-no-image') + '">' +
                        '<div class="bp-form-view">' +
                            '<h2 id="bp-headline" class="bp-headline">' + escapeHtml(t.headline) + '</h2>' +
                            (t.subhead ? '<p class="bp-subhead">' + escapeHtml(t.subhead) + '</p>' : '') +
                            '<form class="bp-form" novalidate>' +
                                '<div class="bp-error" hidden></div>' +
                                (cfg.name_field
                                    ? '<div class="bp-field">' +
                                        '<label class="bp-label" for="bp-name">' + escapeHtml(t.name_label) + '</label>' +
                                        '<input class="bp-input" type="text" id="bp-name" name="name" autocomplete="given-name">' +
                                      '</div>'
                                    : '') +
                                '<div class="bp-field">' +
                                    '<label class="bp-label" for="bp-email">' + escapeHtml(t.email_label) + '</label>' +
                                    '<input class="bp-input" type="email" id="bp-email" name="email" required ' +
                                        'placeholder="' + escapeHtml(t.email_placeholder) + '" ' +
                                        'autocomplete="email" inputmode="email">' +
                                '</div>' +
                                (cfg.phone_field
                                    ? '<div class="bp-field">' +
                                        '<label class="bp-label" for="bp-phone">' + escapeHtml(t.phone_label) +
                                            (cfg.phone_required ? '' : ' <span style="text-transform:none;font-weight:400;opacity:.7">(optional)</span>') +
                                        '</label>' +
                                        '<input class="bp-input" type="tel" id="bp-phone" name="phone" ' +
                                            (cfg.phone_required ? 'required ' : '') +
                                            'placeholder="' + escapeHtml(t.phone_placeholder) + '" ' +
                                            'autocomplete="tel" inputmode="tel">' +
                                        (t.phone_hint ? '<p class="bp-hint">' + escapeHtml(t.phone_hint) + '</p>' : '') +
                                      '</div>'
                                    : '') +
                                (cfg.phone_field && cfg.sms_opt_in
                                    ? '<label class="bp-checkbox">' +
                                        '<input type="checkbox" name="sms_opt_in" checked>' +
                                        '<span>' + escapeHtml(t.sms_opt_in_label) + '</span>' +
                                      '</label>'
                                    : '') +
                                '<button type="submit" class="bp-submit">' + escapeHtml(t.submit_button) + '</button>' +
                                (t.fineprint ? '<p class="bp-fineprint">' + t.fineprint + '</p>' : '') +
                            '</form>' +
                        '</div>' +
                        '<div class="bp-success-view" hidden>' +
                            '<div class="bp-success">' +
                                '<h2 class="bp-headline">' + escapeHtml(t.success_headline) + '</h2>' +
                                '<p class="bp-subhead">' + escapeHtml(t.success_body) + '</p>' +
                                '<div class="bp-code" tabindex="0">' + escapeHtml(cfg.discount_code) + '</div>' +
                                '<div class="bp-code-copied" aria-live="polite"></div>' +
                                '<button type="button" class="bp-submit bp-success-close">' + escapeHtml(t.success_close) + '</button>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>';

        var wrap = document.createElement('div');
        wrap.innerHTML = html;
        rootEl = wrap.firstChild;

        // Apply theme color if configured
        if (cfg.theme_color) {
            rootEl.querySelector('.bp-modal').style.setProperty('--bp-accent', cfg.theme_color);
        }

        return rootEl;
    }

    // ----- Show / Hide -----
    function show(reason) {
        if (shown) return;
        if (isSuppressed() && reason !== 'manual') return;
        shown = true;

        var el = render();
        document.body.appendChild(el);
        document.body.style.overflow = 'hidden';

        // Focus first input shortly after open (avoid scroll jumping on iOS)
        setTimeout(function () {
            var first = el.querySelector('input[type=email], input[type=text]');
            if (first) first.focus({ preventScroll: true });
        }, 100);

        wireEvents(el, reason);

        // Track impression
        if (window.Brevo && Brevo.track) {
            // Anonymous events get dropped at track.php (drop_anonymous=true),
            // so this impression is silent for non-identified users. That's fine —
            // we mainly care about submissions.
            Brevo.track('discount_popup_shown', { trigger: reason || 'unknown' });
        }
    }

    function hide(silent) {
        if (!rootEl) return;
        rootEl.classList.add('bp-closing');
        setTimeout(function () {
            if (rootEl && rootEl.parentNode) rootEl.parentNode.removeChild(rootEl);
            rootEl = null;
            document.body.style.overflow = '';
        }, 180);
        // Don't reset `shown` — once shown per page load, stay shown to avoid re-trigger flicker.
        if (!silent) {
            suppress(cfg.suppress_days_after_dismiss || 7);
        }
    }

    // ----- Wire events -----
    function wireEvents(el, reason) {
        var backdrop = el;
        var modal    = el.querySelector('.bp-modal');
        var closeBtn = el.querySelector('.bp-close');
        var form     = el.querySelector('.bp-form');
        var errorBox = el.querySelector('.bp-error');
        var formView = el.querySelector('.bp-form-view');
        var successView = el.querySelector('.bp-success-view');
        var successClose = el.querySelector('.bp-success-close');
        var codeEl   = el.querySelector('.bp-code');
        var copiedEl = el.querySelector('.bp-code-copied');

        // Close handlers
        closeBtn.addEventListener('click', function () { hide(); });
        backdrop.addEventListener('click', function (e) {
            if (e.target === backdrop) hide();
        });
        document.addEventListener('keydown', function escHandler(e) {
            if (e.key === 'Escape') {
                hide();
                document.removeEventListener('keydown', escHandler);
            }
        });

        // Click-to-copy code
        if (codeEl) {
            codeEl.addEventListener('click', function () {
                copyToClipboard(codeEl.textContent || '');
                copiedEl.textContent = 'Copied!';
                setTimeout(function () { copiedEl.textContent = ''; }, 1800);
            });
        }
        if (successClose) {
            successClose.addEventListener('click', function () { hide(true); });
        }

        // Submit
        form.addEventListener('submit', function (ev) {
            ev.preventDefault();
            errorBox.hidden = true;

            var emailInput = form.querySelector('input[name=email]');
            var phoneInput = form.querySelector('input[name=phone]');
            var nameInput  = form.querySelector('input[name=name]');
            var smsCheck   = form.querySelector('input[name=sms_opt_in]');

            // Reset visual state
            [emailInput, phoneInput].forEach(function (i) { if (i) i.classList.remove('bp-invalid'); });

            var email = normalizeEmail(emailInput ? emailInput.value : '');
            if (!email) {
                if (emailInput) emailInput.classList.add('bp-invalid');
                showError(cfg.text.error_generic || 'Please enter a valid email.');
                return;
            }

            var phone = null;
            if (phoneInput && phoneInput.value.trim() !== '') {
                phone = normalizeSms(phoneInput.value);
                if (!phone) {
                    phoneInput.classList.add('bp-invalid');
                    showError('Please include the country code, e.g. +52...');
                    return;
                }
                if (cfg.phone_field && cfg.phone_required && !phone) {
                    phoneInput.classList.add('bp-invalid');
                    showError('Phone number is required.');
                    return;
                }
            } else if (cfg.phone_field && cfg.phone_required) {
                if (phoneInput) phoneInput.classList.add('bp-invalid');
                showError('Phone number is required.');
                return;
            }

            var fields = { email: email, source: 'discount_popup', trigger: reason || 'unknown' };
            if (phone) fields.sms = phone;
            if (nameInput && nameInput.value.trim() !== '') fields.firstname = nameInput.value.trim();
            if (smsCheck) fields.sms_opt_in = !!smsCheck.checked;

            var btn = form.querySelector('.bp-submit');
            btn.disabled = true;
            var origLabel = btn.textContent;
            btn.textContent = cfg.text.submitting || 'Sending...';

            // Fire via the standard Brevo client. captureLead identifies + tracks
            // 'lead_form_submitted' by default; we override to 'discount_popup_submitted'.
            Promise.resolve(Brevo.captureLead(fields, 'discount_popup_submitted'))
                .then(function () {
                    formView.hidden = true;
                    successView.hidden = false;
                    suppress(cfg.suppress_days_after_submit || 90);
                })
                .catch(function (err) {
                    console.warn('[BrevoPopup] submit failed', err);
                    btn.disabled = false;
                    btn.textContent = origLabel;
                    showError(cfg.text.error_generic || 'Something went wrong.');
                });
        });

        function showError(msg) {
            errorBox.textContent = msg;
            errorBox.hidden = false;
        }
    }

    function copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).catch(function () {
                fallbackCopy(text);
            });
        } else {
            fallbackCopy(text);
        }
    }
    function fallbackCopy(text) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed'; ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(ta);
    }

    // ----- Triggers -----
    function arm() {
        if (isSuppressed()) return;

        var trig = cfg.triggers || {};
        var mobile = isMobile();

        // Time delay
        var timeSec = mobile ? trig.time_seconds_mobile : trig.time_seconds_desktop;
        if (timeSec > 0) {
            setTimeout(function () { show('time'); }, timeSec * 1000);
        }

        // Scroll depth
        if (trig.scroll_percent > 0) {
            var scrollHandler = function () {
                var doc = document.documentElement;
                var scrolled = (window.scrollY || doc.scrollTop) + window.innerHeight;
                var height   = Math.max(doc.scrollHeight, doc.offsetHeight);
                if (height <= 0) return;
                if ((scrolled / height) * 100 >= trig.scroll_percent) {
                    show('scroll');
                    window.removeEventListener('scroll', scrollHandler);
                }
            };
            window.addEventListener('scroll', scrollHandler, { passive: true });
        }

        // Exit intent (desktop only)
        if (trig.exit_intent_desktop && !mobile) {
            var exitHandler = function (e) {
                // Mouse moving up out of viewport, fast-ish, near top
                if (e.clientY <= 0 || (e.relatedTarget == null && e.clientY < 50)) {
                    show('exit_intent');
                    document.removeEventListener('mouseout', exitHandler);
                }
            };
            // Slight delay so exit-intent doesn't trigger on initial page focus
            setTimeout(function () {
                document.addEventListener('mouseout', exitHandler);
            }, 3000);
        }

        // Add to cart trigger (listens for a custom event the shop's cart.js can dispatch)
        if (trig.on_add_to_cart) {
            document.addEventListener('brevo:cart-added', function () { show('add_to_cart'); }, { once: true });
        }
    }

    // ----- Boot -----
    function boot() {
        arm();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    // ----- Public API -----
    window.BrevoPopup = {
        show: function (reason) { show(reason || 'manual'); },
        hide: function () { hide(true); },
        reset: function () {
            // Expire the suppression cookie immediately
            document.cookie = SUPPRESS_COOKIE + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
            shown = false;
            console.log('[BrevoPopup] suppression cleared');
        },
        _cfg: cfg,
    };
})();
