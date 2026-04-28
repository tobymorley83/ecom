/* /js/brevo-wheel.js
 *
 * Spin-to-win wheel with prize claim after winning.
 *
 * Flow:
 *   1. Wheel auto-opens after cfg.show_after_seconds — no email capture up-front
 *   2. User gets 3 spins. First 2 always lose, 3rd always wins (deterministic).
 *   3. On win → email+phone form to claim. If identity partially captured
 *      (from a prior popup/submit), only ask for what's missing.
 *   4. After submit → prize is revealed, discount code applied to localStorage,
 *      or free gift added to cart. `spin_wheel_won` event fires once.
 *   5. User can close/minimize anytime. Bubble persists until they claim.
 *
 * Depends on window.BrevoWheelConfig (from /brevo/wheel-config.php)
 * and window.Brevo (from /js/brevo.js). Both must load first.
 *
 * Also suppresses the regular popup (window.BrevoPopup) when enabled.
 *
 * Public API (window.BrevoWheel):
 *   BrevoWheel.open()       Force-open the wheel modal
 *   BrevoWheel.minimize()   Close modal, show bubble
 *   BrevoWheel.close()      Fully close (bubble stays visible unless suppressed)
 *   BrevoWheel.reset()      Clear all state (suppression, spins used, identity link)
 *   BrevoWheel.state()      Inspect current persisted state (debug)
 */

(function () {
    'use strict';

    var cfg = window.BrevoWheelConfig;
    if (!cfg || !cfg.enabled) return;

    if (!window.Brevo) {
        console.warn('[BrevoWheel] window.Brevo not found — load /js/brevo.js first.');
        return;
    }

    // When wheel is enabled, suppress the regular popup (decision #2: option c)
    // This must handle THREE cases:
    //   1. Popup JS hasn't loaded yet  → cookie blocks its auto-arm
    //   2. Popup JS loaded & armed triggers → need to prevent it from firing
    //   3. Popup already on screen → rip it out
    function killPopup() {
        // Case 1+2: set the long-lived suppression cookie
        try {
            document.cookie = 'bo_popup_suppress=1; path=/; max-age=' + (60 * 60 * 24 * 365) + '; SameSite=Lax' +
                (location.protocol === 'https:' ? '; Secure' : '');
        } catch (e) {}
        // Case 2: if popup exposes a public hide(), use it (cleaner animation + cleanup)
        if (window.BrevoPopup && typeof BrevoPopup.hide === 'function') {
            try { BrevoPopup.hide(); } catch (e) {}
        }
        // Case 3: belt-and-braces — rip any lingering popup DOM
        var lingering = document.querySelectorAll('.bp-backdrop');
        for (var i = 0; i < lingering.length; i++) {
            if (lingering[i].parentNode) lingering[i].parentNode.removeChild(lingering[i]);
        }
        // Restore body scroll in case popup set overflow:hidden
        if (document.body.style.overflow === 'hidden' && !document.querySelector('.bw-backdrop')) {
            document.body.style.overflow = '';
        }
    }
    // Call once at boot to kill anything the popup already armed/showed
    killPopup();

    // ----- Storage keys -----
    var KEY_STATE       = 'bw_state';      // { spinsUsed, won, claimedAt, minimized }
    var COOKIE_SUPPRESS = 'bw_suppress';

    // ----- State -----
    var bubbleEl = null;
    var modalEl  = null;
    var state    = loadState();

    // ----- Utility: cookie + storage -----
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
    function lsGet(k) { try { return localStorage.getItem(k); } catch (e) { return null; } }
    function lsSet(k, v) {
        try {
            if (v === null || v === undefined) localStorage.removeItem(k);
            else localStorage.setItem(k, typeof v === 'string' ? v : JSON.stringify(v));
        } catch (e) {}
    }

    function loadState() {
        var raw = lsGet(KEY_STATE);
        if (!raw) return { spinsUsed: 0, won: null, claimedAt: null, minimized: false };
        try { return JSON.parse(raw); } catch (e) { return { spinsUsed: 0, won: null, claimedAt: null, minimized: false }; }
    }
    function saveState() { lsSet(KEY_STATE, state); }

    function isSuppressed() { return !!readCookie(COOKIE_SUPPRESS); }
    function suppress(days) { if (days > 0) writeCookie(COOKIE_SUPPRESS, '1', days); }

    function escapeHtml(s) {
        if (s == null) return '';
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
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

    // =====================================================================
    // Sound engine — plays configured URLs, falls back to Web Audio synthesis
    // =====================================================================
    var Sound = (function () {
        var ctx = null;
        function getCtx() {
            if (!cfg.sounds_enabled) return null;
            if (ctx) return ctx;
            try {
                var C = window.AudioContext || window.webkitAudioContext;
                if (!C) return null;
                ctx = new C();
                return ctx;
            } catch (e) { return null; }
        }

        function playUrl(url) {
            if (!cfg.sounds_enabled) return;
            try {
                var a = new Audio(url);
                a.volume = 0.5;
                a.play().catch(function () {});
            } catch (e) {}
        }

        function beep(freq, duration, type) {
            var c = getCtx();
            if (!c) return;
            try {
                var osc = c.createOscillator();
                var gain = c.createGain();
                osc.type = type || 'sine';
                osc.frequency.value = freq;
                gain.gain.value = 0.15;
                gain.gain.exponentialRampToValueAtTime(0.001, c.currentTime + duration);
                osc.connect(gain); gain.connect(c.destination);
                osc.start();
                osc.stop(c.currentTime + duration);
            } catch (e) {}
        }

        return {
            spinStart: function () {
                if (cfg.sound_spin_start) return playUrl(cfg.sound_spin_start);
                beep(440, 0.15, 'square');
                setTimeout(function () { beep(660, 0.15, 'square'); }, 80);
            },
            tick: function () {
                if (cfg.sound_tick) return playUrl(cfg.sound_tick);
                beep(800, 0.03, 'square');
            },
            lose: function () {
                if (cfg.sound_lose) return playUrl(cfg.sound_lose);
                beep(200, 0.3, 'sawtooth');
            },
            win: function () {
                if (cfg.sound_win) return playUrl(cfg.sound_win);
                // Little fanfare
                var notes = [523, 659, 784, 1047];
                notes.forEach(function (f, i) {
                    setTimeout(function () { beep(f, 0.18, 'triangle'); }, i * 120);
                });
            },
            claim: function () {
                if (cfg.sound_claim) return playUrl(cfg.sound_claim);
                beep(880, 0.1, 'sine');
                setTimeout(function () { beep(1200, 0.12, 'sine'); }, 100);
            },
        };
    })();

    // =====================================================================
    // Bubble (minimized state)
    // =====================================================================
    function showBubble() {
        if (bubbleEl) return;

        bubbleEl = document.createElement('button');
        bubbleEl.className = 'bw-bubble';
        bubbleEl.type = 'button';
        bubbleEl.setAttribute('data-position', cfg.bubble_position || 'bottom-left');
        bubbleEl.setAttribute('aria-label', cfg.text.bubble_tooltip);
        bubbleEl.innerHTML =
            '<span class="bw-bubble-tooltip">' + escapeHtml(cfg.text.bubble_tooltip) + '</span>' +
            '<span>' + escapeHtml(cfg.text.bubble_label) + '</span>';

        bubbleEl.addEventListener('click', function () { openModal(); });

        document.body.appendChild(bubbleEl);
    }

    function hideBubble() {
        if (!bubbleEl) return;
        bubbleEl.parentNode && bubbleEl.parentNode.removeChild(bubbleEl);
        bubbleEl = null;
    }

    // =====================================================================
    // Modal open / close
    // =====================================================================
    function openModal() {
        if (modalEl) return;

        // If the popup somehow got rendered in the meantime (manual .show() call,
        // race condition on first page load), kill it now.
        killPopup();

        hideBubble();
        state.minimized = false;
        saveState();

        // Flow (post-refactor): wheel FIRST, email capture AFTER winning.
        //
        //   Already claimed     → show claim screen (read-only view of what they won)
        //   Won but not claimed → show claim form (capture missing email/phone, then reveal prize)
        //   Fresh state         → show wheel, spin away
        if (state.claimedAt) {
            renderClaimResult(state.won);
        } else if (state.won) {
            // They won on a previous visit but never submitted email — let them finish.
            renderClaimForm(state.won);
        } else {
            renderWheel();
        }
    }

    function closeModal(options) {
        if (!modalEl) return;
        options = options || {};

        modalEl.classList.add('bw-closing');
        setTimeout(function () {
            if (modalEl && modalEl.parentNode) modalEl.parentNode.removeChild(modalEl);
            modalEl = null;
            document.body.style.overflow = '';

            // Decide post-close behavior
            if (options.fullClose) {
                // X button pressed — user fully dismisses
                suppress(cfg.suppress_days_after_dismiss);
                if (!isSuppressed()) showBubble();  // if no suppression set, bubble stays
            } else if (options.afterClaim) {
                // Claim completed
                suppress(cfg.suppress_days_after_claim);
                // Bubble stays visible only if not suppressed
                if (!isSuppressed()) showBubble();
            } else {
                // Default: minimize, bubble visible
                state.minimized = true;
                saveState();
                showBubble();
            }
        }, 180);
    }

    function createModalShell(bodyHtml, options) {
        options = options || {};
        var el = document.createElement('div');
        el.className = 'bw-backdrop';
        el.setAttribute('role', 'dialog');
        el.setAttribute('aria-modal', 'true');
        el.innerHTML =
            '<div class="bw-modal">' +
                (options.showMinimize !== false ? '<button class="bw-minimize" type="button" aria-label="' + escapeHtml(cfg.text.aria_minimize) + '">&minus;</button>' : '') +
                '<button class="bw-close" type="button" aria-label="' + escapeHtml(cfg.text.aria_close) + '">&times;</button>' +
                '<div class="bw-body">' + bodyHtml + '</div>' +
            '</div>';

        document.body.appendChild(el);
        document.body.style.overflow = 'hidden';
        modalEl = el;

        // Wire close buttons
        el.querySelector('.bw-close').addEventListener('click', function () { closeModal({ fullClose: true }); });
        var minBtn = el.querySelector('.bw-minimize');
        if (minBtn) minBtn.addEventListener('click', function () { closeModal(); });

        // Backdrop click = minimize (not full close)
        el.addEventListener('click', function (e) { if (e.target === el) closeModal(); });

        // Esc minimizes
        var esc = function (e) { if (e.key === 'Escape') { closeModal(); document.removeEventListener('keydown', esc); } };
        document.addEventListener('keydown', esc);

        return el;
    }

    // =====================================================================
    // Wheel view
    // =====================================================================
    function renderWheel() {
        var t = cfg.text;
        var spinsLeft = cfg.total_spins - state.spinsUsed;

        var body =
            '<h2 class="bw-headline">' + escapeHtml(t.intro_headline) + '</h2>' +
            '<p class="bw-spin-info"><strong id="bw-spins-left">' + formatSpinsLeft(spinsLeft) + '</strong></p>' +
            '<div class="bw-wheel-wrap">' +
                buildWheelSvg() +
                '<div class="bw-wheel-pointer"></div>' +
                '<div class="bw-wheel-center">🎁</div>' +
            '</div>' +
            '<div class="bw-result" hidden></div>' +
            '<button type="button" class="bw-cta" id="bw-spin-btn">' + escapeHtml(t.spin_button) + '</button>';

        var el = createModalShell(body);

        var spinBtn = el.querySelector('#bw-spin-btn');
        if (spinsLeft <= 0 || state.won) {
            // Shouldn't happen since we'd show claim screen, but safe
            spinBtn.disabled = true;
        }
        spinBtn.addEventListener('click', function () { doSpin(el); });
    }

    function formatSpinsLeft(n) {
        var t = cfg.text;
        if (n === 1) return t.spins_left_one;
        return t.spins_left_many.replace('{n}', String(n));
    }

    function buildWheelSvg() {
        var segments = cfg.segments || [];
        var n = segments.length;
        if (n === 0) return '';

        // Wrapper div: we rotate THIS, not anything inside the SVG.
        // DOM element rotation with transform-origin: 50% 50% is unambiguous
        // in every browser — unlike CSS transforms on SVG <g> elements.
        var svg = '<div id="bw-wheel-rotor" style="width: 100%; height: 100%; transform-origin: 50% 50%;">';
        svg += '<svg class="bw-wheel" viewBox="-110 -110 220 220" xmlns="http://www.w3.org/2000/svg">';
        svg += '<g>';  // no transform here — wrapper div handles it

        var anglePerSeg = 360 / n;
        for (var i = 0; i < n; i++) {
            var seg = segments[i];
            var startAngle = i * anglePerSeg - 90;  // start at top
            var endAngle   = (i + 1) * anglePerSeg - 90;

            var startRad = startAngle * Math.PI / 180;
            var endRad   = endAngle * Math.PI / 180;

            var x1 = 100 * Math.cos(startRad);
            var y1 = 100 * Math.sin(startRad);
            var x2 = 100 * Math.cos(endRad);
            var y2 = 100 * Math.sin(endRad);

            var largeArc = anglePerSeg > 180 ? 1 : 0;
            var d = 'M 0 0 L ' + x1.toFixed(2) + ' ' + y1.toFixed(2) +
                    ' A 100 100 0 ' + largeArc + ' 1 ' + x2.toFixed(2) + ' ' + y2.toFixed(2) + ' Z';

            svg += '<path d="' + d + '" fill="' + escapeHtml(seg.color) + '" stroke="white" stroke-width="2"/>';

            // Label: put it radially from center, rotated so it reads from center outward
            var midAngle = startAngle + anglePerSeg / 2;
            var midRad = midAngle * Math.PI / 180;
            var lx = 62 * Math.cos(midRad);
            var ly = 62 * Math.sin(midRad);
            // Text rotation: make text sit tangent to the radius, readable from center outward
            // (reading direction runs along the radius pointing away from center)
            var textRot = midAngle + 90;

            var textColor = getContrastColor(seg.color);
            svg += '<text x="' + lx.toFixed(2) + '" y="' + ly.toFixed(2) + '" ' +
                   'text-anchor="middle" dominant-baseline="middle" ' +
                   'transform="rotate(' + textRot.toFixed(2) + ' ' + lx.toFixed(2) + ' ' + ly.toFixed(2) + ')" ' +
                   'font-family="system-ui, sans-serif" font-size="10" font-weight="700" fill="' + textColor + '">' +
                   escapeHtml(seg.label) + '</text>';
        }
        svg += '</g>';
        svg += '<circle cx="0" cy="0" r="100" fill="none" stroke="rgba(0,0,0,0.12)" stroke-width="1.5"/>';
        svg += '</svg>';
        svg += '</div>';
        return svg;
    }

    function getContrastColor(hex) {
        // Rough YIQ contrast
        hex = hex.replace('#', '');
        if (hex.length === 3) hex = hex.split('').map(function (c) { return c + c; }).join('');
        if (hex.length !== 6) return '#000';
        var r = parseInt(hex.substr(0, 2), 16);
        var g = parseInt(hex.substr(2, 2), 16);
        var b = parseInt(hex.substr(4, 2), 16);
        var yiq = (r * 299 + g * 587 + b * 114) / 1000;
        return yiq >= 160 ? '#18181b' : '#ffffff';
    }

    // =====================================================================
    // Read the actual current rotation angle of a transformed element from
    // its computed transform matrix. More reliable than trusting data-rot,
    // which is just the last commanded value.
    // =====================================================================
    function readCurrentRotation(el) {
        var t = getComputedStyle(el).transform;
        if (!t || t === 'none') return 0;
        var m = t.match(/^matrix\(([^)]+)\)$/);
        if (!m) return 0;
        var parts = m[1].split(',').map(parseFloat);
        if (parts.length < 4) return 0;
        var a = parts[0], b = parts[1];
        var angle = Math.atan2(b, a) * 180 / Math.PI;
        // atan2 returns -180..180; we want 0..360
        if (angle < 0) angle += 360;
        return angle;
    }

    // =====================================================================
    // Spin action
    // =====================================================================
    function doSpin(el) {
        if (state.won) return;  // already won; claim screen should be up
        if (state.spinsUsed >= cfg.total_spins) return;

        var spinBtn     = el.querySelector('#bw-spin-btn');
        var rotor       = el.querySelector('#bw-wheel-rotor');
        var resultEl    = el.querySelector('.bw-result');
        var spinsLeftEl = el.querySelector('#bw-spins-left');

        spinBtn.disabled = true;
        spinBtn.textContent = cfg.text.spinning;
        resultEl.hidden = true;

        var segments = cfg.segments;
        var n = segments.length;

        // Determine target segment
        var thisSpinNumber = state.spinsUsed + 1;
        var targetIdx;
        if (thisSpinNumber === cfg.winning_spin && cfg.winner_segment_idx !== null) {
            targetIdx = cfg.winner_segment_idx;
        } else {
            // Pick any 'nothing' segment
            var nothingIndexes = [];
            for (var i = 0; i < n; i++) if (segments[i].type === 'nothing') nothingIndexes.push(i);
            if (nothingIndexes.length === 0) {
                targetIdx = 0;  // Shouldn't happen if config is sane
            } else {
                targetIdx = nothingIndexes[Math.floor(Math.random() * nothingIndexes.length)];
            }
        }

        // Compute target rotation
        //
        // Segments are drawn starting at SVG angle -90° (top), going clockwise.
        // Segment i's midpoint, in the UN-rotated wheel, is at SVG angle:
        //     -90° + i * anglePerSeg + anglePerSeg / 2
        // Or expressed as "clockwise degrees from top":
        //     targetMidAngle = i * anglePerSeg + anglePerSeg / 2
        //
        // To bring that midpoint TO the pointer (top), rotate the wheel
        // COUNTER-clockwise by targetMidAngle. In CSS rotate() where positive
        // values are clockwise, that's equivalent to rotating clockwise by
        // (360 - targetMidAngle).
        var anglePerSeg    = 360 / n;
        var targetMidAngle = targetIdx * anglePerSeg + anglePerSeg / 2;

        // Read the ACTUAL current rotation from the DOM (not data-rot, which is
        // just what we last commanded — may differ from real position if the
        // previous transition was interrupted).
        var currentRotation = readCurrentRotation(rotor);

        // Now compute the final target as an absolute angle + extra spins.
        // We want `targetRotation mod 360 === (360 - targetMidAngle) mod 360`.
        var spins               = 5 + Math.floor(Math.random() * 3);  // 5–7 full rotations
        var desiredResidue      = ((360 - targetMidAngle) % 360 + 360) % 360;
        var currentResidue      = ((currentRotation % 360) + 360) % 360;
        // Base: how far clockwise from here to the target residue (0..360)
        var baseDelta           = (desiredResidue - currentResidue + 360) % 360;
        var targetRotation      = currentRotation + (spins * 360) + baseDelta;

        rotor.setAttribute('data-rot', targetRotation);

        // ---- Animate the WRAPPER DIV, not the SVG group ----
        // DOM element rotation with transform-origin: 50% 50% is unambiguous
        // across all browsers. No SVG transform-box pitfalls.
        //
        // Force a reflow so the browser registers the starting state before
        // applying the transition, otherwise it sometimes jumps straight to
        // the end with no animation.
        rotor.style.transition = 'none';
        rotor.style.transform  = 'rotate(' + currentRotation + 'deg)';
        // Force reflow
        void rotor.offsetWidth;
        rotor.style.transition = 'transform 4s cubic-bezier(0.17, 0.67, 0.12, 0.99)';
        rotor.style.transform  = 'rotate(' + targetRotation + 'deg)';

        Sound.spinStart();

        // Tick sounds during spin (decays in frequency)
        var tickTimes = [200, 400, 650, 950, 1300, 1700, 2200, 2800, 3400];
        tickTimes.forEach(function (ms) { setTimeout(Sound.tick, ms); });

        // Wait for transition to complete, then handle result.
        // NOTE: we wait 4100ms (100ms buffer) AND guarantee the final angle is
        // set explicitly — if anything throttled the transition, we still land
        // on exactly the target segment.
        setTimeout(function () {
            // Guarantee final state — defensive pin
            rotor.style.transform = 'rotate(' + targetRotation + 'deg)';

            state.spinsUsed++;
            var landedSeg = segments[targetIdx];

            if (landedSeg.type === 'nothing') {
                resultEl.textContent = cfg.text.result_nothing;
                resultEl.className = 'bw-result bw-result-nothing';
                resultEl.hidden = false;
                Sound.lose();

                saveState();

                var left = cfg.total_spins - state.spinsUsed;
                spinsLeftEl.textContent = formatSpinsLeft(left);

                if (left > 0) {
                    spinBtn.disabled = false;
                    spinBtn.textContent = cfg.text.spin_button;
                } else {
                    // Shouldn't happen with our "always win on spin 3" logic, but safeguard
                    spinBtn.disabled = true;
                    spinBtn.textContent = cfg.text.spin_button;
                }
            } else {
                // Win!
                resultEl.textContent = cfg.text.result_win + ' ' + landedSeg.label;
                resultEl.className = 'bw-result bw-result-win';
                resultEl.hidden = false;
                Sound.win();

                state.won = landedSeg;
                saveState();

                if (cfg.confetti_on_win) fireConfetti();

                // Short pause, then the claim flow.
                // Q4(c): if they already have email AND phone captured, skip the form.
                //        If either is missing, show the form to collect what's missing.
                setTimeout(function () {
                    modalEl.parentNode.removeChild(modalEl);
                    modalEl = null;
                    document.body.style.overflow = '';

                    var id = Brevo.identity();
                    var hasEmail = !!id.email;
                    var hasPhone = !!id.sms;
                    if (hasEmail && hasPhone) {
                        // Fully identified — fire event and show prize directly.
                        finalizeClaim(landedSeg, {});
                    } else {
                        renderClaimForm(landedSeg);
                    }
                }, 1600);
            }
        }, 4100);
    }

    // =====================================================================
    // Claim FORM — shown after winning, captures missing email/phone before
    // revealing the prize. Partial re-identification supported via Q4(c):
    // if user already had some identity (e.g. email but no phone), only the
    // missing fields are asked.
    // =====================================================================
    function renderClaimForm(segment) {
        var t = cfg.text;
        var id = Brevo.identity();
        var needEmail = !id.email;
        var needPhone = !id.sms;

        // Safety: if nothing's missing, just finalize directly (shouldn't happen
        // because openModal routes to finalize in that case, but defensive).
        if (!needEmail && !needPhone) {
            finalizeClaim(segment, {});
            return;
        }

        var body =
            '<h2 class="bw-headline">' + escapeHtml(t.result_win) + ' 🎉</h2>' +
            '<p class="bw-subhead">' + escapeHtml(t.claim_form_subhead || 'Almost there! Enter your details to claim your prize.') + '</p>' +
            (segment.type === 'free_gift' ? renderGiftPreview(segment) : '') +
            '<form class="bw-form" novalidate>' +
                '<div class="bw-error" hidden></div>';

        if (needEmail) {
            body +=
                '<div class="bw-field">' +
                    '<label class="bw-label" for="bw-email">' + escapeHtml(t.intro_email_label) + '</label>' +
                    '<input class="bw-input" type="email" id="bw-email" name="email" required ' +
                        'placeholder="' + escapeHtml(t.intro_email_ph) + '" autocomplete="email" inputmode="email">' +
                '</div>';
        }

        if (needPhone) {
            body +=
                '<div class="bw-field">' +
                    '<label class="bw-label" for="bw-phone">' + escapeHtml(t.intro_phone_label) +
                        (cfg.require_phone ? '' : ' <span style="text-transform:none;font-weight:400;opacity:0.7">(optional)</span>') +
                    '</label>' +
                    '<input class="bw-input" type="tel" id="bw-phone" name="phone" ' +
                        (cfg.require_phone ? 'required ' : '') +
                        'placeholder="' + escapeHtml(t.intro_phone_ph) + '" autocomplete="tel" inputmode="tel">' +
                    (t.intro_phone_hint ? '<p class="bw-hint">' + escapeHtml(t.intro_phone_hint) + '</p>' : '') +
                '</div>' +
                (cfg.sms_opt_in
                    ? '<label class="bw-checkbox"><input type="checkbox" name="sms_opt_in" checked><span>' + escapeHtml(t.intro_sms_opt_in) + '</span></label>'
                    : '');
        }

        body +=
                '<button type="submit" class="bw-cta">' + escapeHtml(t.claim_form_button || 'Claim my prize') + '</button>' +
                (t.intro_fineprint ? '<p class="bw-fineprint">' + t.intro_fineprint + '</p>' : '') +
            '</form>';

        // Show minimize button so they can come back later (Q1a: bubble persists)
        var el = createModalShell(body, { showMinimize: true });
        var form = el.querySelector('form');
        var errorBox = el.querySelector('.bw-error');

        form.addEventListener('submit', function (ev) {
            ev.preventDefault();
            errorBox.hidden = true;

            var emailInput = form.querySelector('input[name=email]');
            var phoneInput = form.querySelector('input[name=phone]');
            var smsCheck   = form.querySelector('input[name=sms_opt_in]');

            [emailInput, phoneInput].forEach(function (i) { if (i) i.classList.remove('bw-invalid'); });

            var email = null, phone = null;

            if (emailInput) {
                email = normalizeEmail(emailInput.value);
                if (!email) {
                    emailInput.classList.add('bw-invalid');
                    errorBox.textContent = 'Please enter a valid email.';
                    errorBox.hidden = false;
                    return;
                }
            }

            if (phoneInput && phoneInput.value.trim() !== '') {
                phone = normalizeSms(phoneInput.value);
                if (!phone) {
                    phoneInput.classList.add('bw-invalid');
                    errorBox.textContent = 'Please include country code, e.g. +52...';
                    errorBox.hidden = false;
                    return;
                }
            }
            if (phoneInput && cfg.require_phone && !phone) {
                phoneInput.classList.add('bw-invalid');
                errorBox.textContent = 'Phone number is required.';
                errorBox.hidden = false;
                return;
            }

            var fields = { source: 'spin_wheel' };
            if (email) fields.email = email;
            if (phone) fields.sms = phone;
            if (smsCheck) fields.sms_opt_in = !!smsCheck.checked;

            var btn = form.querySelector('.bw-cta');
            btn.disabled = true;
            btn.textContent = t.spinning;  // reuse "Sending..." style

            // Identify the user locally so future events carry the info.
            Brevo.identify(fields);

            finalizeClaim(segment, fields);
        });
    }

    // =====================================================================
    // Finalize claim — applies prize (discount code / free gift), fires
    // `spin_wheel_won` event (Q3a), then transitions to reveal screen.
    // =====================================================================
    function finalizeClaim(segment, capturedFields) {
        var claimProperties = {
            segment_label: segment.label,
            segment_type:  segment.type,
            source:        'spin_wheel',
        };
        if (capturedFields && capturedFields.sms_opt_in !== undefined) {
            claimProperties.sms_opt_in = capturedFields.sms_opt_in;
        }

        // Apply the prize
        if (segment.type === 'discount') {
            var code = segment.discount_code || '';
            claimProperties.discount_code = code;
            if (code) {
                try { localStorage.setItem('shopdeals-discount', code.toLowerCase()); } catch (e) {}
            }
        } else if (segment.type === 'free_gift') {
            var giftProductId = applyFreeGift(segment);
            claimProperties.gift_product_id = giftProductId || '';
        }

        // Mark claimed and fire the event (only once — guarded by claimedAt)
        if (!state.claimedAt) {
            state.claimedAt = Date.now();
            saveState();
            Sound.claim();
            if (window.Brevo) {
                Brevo.track('spin_wheel_won', claimProperties);
            }
        }

        // Swap modal to result view (unless still open — close current first)
        if (modalEl) {
            modalEl.parentNode.removeChild(modalEl);
            modalEl = null;
            document.body.style.overflow = '';
        }
        renderClaimResult(segment);
    }

    // =====================================================================
    // Claim RESULT — the prize reveal screen shown after identity + finalize.
    // Also shown when a previously-claimed user re-opens the modal.
    // =====================================================================
    function renderClaimResult(segment) {
        var t = cfg.text;
        var body = '<div class="bw-success">';
        body += '<h2 class="bw-headline">' + escapeHtml(t.claim_headline) + ' 🎉</h2>';

        if (segment.type === 'discount') {
            var code = segment.discount_code || '';
            body += '<p class="bw-subhead">' + escapeHtml(t.claim_body_discount) + '</p>';
            body += '<div class="bw-claim-code">' + escapeHtml(code.toUpperCase()) + '</div>';
        } else if (segment.type === 'free_gift') {
            body += renderGiftPreview(segment);
            body += '<p class="bw-subhead">' + escapeHtml(t.claim_body_gift) + '</p>';
        }

        body += '<button type="button" class="bw-cta" id="bw-claim-btn">' + escapeHtml(t.claim_button) + '</button>';
        body += '</div>';

        var el = createModalShell(body, { showMinimize: false });

        el.querySelector('#bw-claim-btn').addEventListener('click', function () {
            closeModal({ afterClaim: true });
        });
    }

    // =====================================================================
    // Free gift: resolve which product, get product info, add to cart
    // =====================================================================
    function resolveGiftProductId(segment) {
        // Priority:
        //   1. segment.specific_product_id (if 'specific_product' mode)
        //   2. cfg.free_gift_product_id    (top-level config)
        //   3. first cart item             (if 'same_as_cart_item' mode)
        //   4. segment.fallback_product_id (for 'same_as_cart_item' with empty cart)
        if (segment.gift_mode === 'specific_product') {
            return segment.specific_product_id || cfg.free_gift_product_id || null;
        }
        if (typeof Cart !== 'undefined') {
            var items = Cart.getItems();
            if (items.length > 0) return items[0].id;
        }
        return segment.fallback_product_id || cfg.free_gift_product_id || null;
    }

    function getGiftProductInfo(segment) {
        var id = resolveGiftProductId(segment);
        if (!id || typeof Products === 'undefined' || !Products.getById) return null;
        var p = Products.getById(id);
        if (!p) return null;
        var lang = (window.I18n && I18n.getLang) ? I18n.getLang() : 'en';
        return {
            id:          id,
            image:       p.image || '',
            price:       (typeof p.price === 'number') ? p.price : 0,
            name:        (p[lang] && p[lang].name)        || (p.en && p.en.name)        || (p.es && p.es.name)        || '',
            description: (p[lang] && p[lang].description) || (p.en && p.en.description) || (p.es && p.es.description) || ''
        };
    }

    function formatGiftValue(price) {
        if (!price || typeof Currency === 'undefined' || !Currency.format) return '';
        var label = (window.I18n && I18n.t) ? I18n.t('cart.value') : 'value';
        return ' <span class="bw-claim-gift-value">(' + escapeHtml(label) + ' ' + escapeHtml(Currency.format(price)) + ')</span>';
    }

    function renderGiftPreview(segment) {
        var info = getGiftProductInfo(segment);
        var iconHtml = (info && info.image)
            ? '<img src="' + escapeHtml(info.image) + '" alt="' + escapeHtml(info.name || '') + '">'
            : '🎁';
        return '<div class="bw-claim-gift">' +
            '<div class="bw-claim-gift-icon">' + iconHtml + '</div>' +
            '<div class="bw-claim-gift-text">' +
                (info && info.name ? '<strong>' + escapeHtml(info.name) + formatGiftValue(info && info.price) + '</strong>' : '') +
                (info && info.description ? '<p>' + escapeHtml(info.description) + '</p>' : '') +
            '</div>' +
        '</div>';
    }

    function applyFreeGift(segment) {
        if (typeof Cart === 'undefined' || typeof Products === 'undefined') {
            console.warn('[BrevoWheel] Cart or Products module not available; cannot add free gift.');
            return null;
        }

        var targetProductId = resolveGiftProductId(segment);

        if (!targetProductId) {
            console.warn('[BrevoWheel] No target product for free gift.');
            return null;
        }

        // Get the product definition so we can push an item with correct image/originalPrice
        var product = Products.getById ? Products.getById(targetProductId) : null;
        if (!product) {
            console.warn('[BrevoWheel] Product not found:', targetProductId);
            return null;
        }

        // Add directly to localStorage since Cart.addItem uses `product.price` which we want to override
        try {
            var STORAGE_KEY = 'shopdeals-cart';
            var raw = localStorage.getItem(STORAGE_KEY);
            var items = raw ? JSON.parse(raw) : [];

            // Check if this gift is already in cart (by is_free_gift marker)
            var existing = null;
            for (var i = 0; i < items.length; i++) {
                if (items[i].id === targetProductId && items[i].is_free_gift) {
                    existing = items[i];
                    break;
                }
            }

            if (existing) {
                existing.qty += 1;
            } else {
                items.push({
                    id: targetProductId,
                    price: 0,
                    originalPrice: product.originalPrice || product.price,
                    image: product.image,
                    qty: 1,
                    is_free_gift: true
                });
            }
            localStorage.setItem(STORAGE_KEY, JSON.stringify(items));

            // Update the cart count UI if the function exists
            if (Cart.updateCartCount) Cart.updateCartCount();

            return targetProductId;
        } catch (e) {
            console.warn('[BrevoWheel] Failed to add gift:', e);
            return null;
        }
    }

    // =====================================================================
    // Confetti
    // =====================================================================
    function fireConfetti() {
        var wrap = document.createElement('div');
        wrap.className = 'bw-confetti';
        document.body.appendChild(wrap);

        var colors = ['#dc2626', '#f59e0b', '#10b981', '#3b82f6', '#ec4899', '#8b5cf6'];
        var count = 60;

        for (var i = 0; i < count; i++) {
            var piece = document.createElement('div');
            piece.className = 'bw-confetti-piece';
            piece.style.left = Math.random() * 100 + '%';
            piece.style.background = colors[Math.floor(Math.random() * colors.length)];
            piece.style.animationDuration = (2.5 + Math.random() * 2) + 's';
            piece.style.animationDelay = (Math.random() * 0.6) + 's';
            piece.style.transform = 'rotate(' + (Math.random() * 360) + 'deg)';
            wrap.appendChild(piece);
        }

        setTimeout(function () {
            if (wrap.parentNode) wrap.parentNode.removeChild(wrap);
        }, 5000);
    }

    // =====================================================================
    // Boot / trigger
    // =====================================================================
    function boot() {
        // If suppressed, do nothing at all
        if (isSuppressed()) return;

        // If already claimed, just show the bubble (clicking opens the claim screen again)
        if (state.claimedAt) {
            showBubble();
            return;
        }

        // Q1(a): If they won but never submitted email to claim, show bubble —
        // clicking it reopens the claim FORM so they can still get the prize.
        if (state.won) {
            showBubble();
            return;
        }

        // If previously minimized, show bubble immediately (no auto-open)
        if (state.minimized) {
            showBubble();
            return;
        }

        // Otherwise: auto-open after N seconds
        var delay = Math.max(0, cfg.show_after_seconds) * 1000;
        setTimeout(function () {
            if (!modalEl && !isSuppressed()) openModal();
        }, delay);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    // =====================================================================
    // Public API
    // =====================================================================
    window.BrevoWheel = {
        open: function () { openModal(); },
        minimize: function () { closeModal(); },
        close: function () { closeModal({ fullClose: true }); },
        reset: function () {
            lsSet(KEY_STATE, null);
            document.cookie = COOKIE_SUPPRESS + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
            state = loadState();
            hideBubble();
            if (modalEl) { modalEl.parentNode.removeChild(modalEl); modalEl = null; document.body.style.overflow = ''; }
            console.log('[BrevoWheel] state + suppression cleared');
        },
        state: function () { return Object.assign({}, state, { suppressed: isSuppressed() }); },
        _cfg: cfg,
    };
})();
