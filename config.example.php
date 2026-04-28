<?php
/**
 * ============================================================
 * Site Configuration — EXAMPLE / TEMPLATE
 * ============================================================
 * On each server:
 *   cp config.example.php config.php
 * Then edit config.php with this site's:
 *   - language, currency, country allowlist
 *   - traffic checkout URLs (contains embedded key — per-site)
 *   - discount codes
 *
 * config.php is gitignored so each site keeps its own version.
 * ============================================================
 */
/**
 * Site Configuration
 * ==================
 * Central config file for language, currency, and discount codes.
 * Edit this file to change defaults across the entire site.
 */

// ── Language Settings ────────────────────────────────────────────────
// Available languages: 'en', 'es'
// This is the default when no ?lang= param and no localStorage preference
$config = [];

$config['default_lang'] = 'es';
$config['available_langs'] = ['en', 'es', 'fr', 'de', 'it'];


// ── Currency Settings ────────────────────────────────────────────────
// symbol       : displayed before/after price (e.g. "$", "€", "£")
// code         : ISO 4217 code (e.g. "USD", "EUR", "MXN", "GBP")
// position     : 'before' or 'after' the amount
// decimals     : number of decimal places
// thousands_sep: thousands separator character
// decimal_sep  : decimal separator character

$config['currency'] = [
    'symbol'        => 'MX$',
    'code'          => 'MXN',
    'position'      => 'before',   // 'before' = $19.95  |  'after' = 19.95€
    'decimals'      => 2,
    'thousands_sep' => ',',
    'decimal_sep'   => '.',
];


//&ad_id={{ad.id}}&adset_id={{adset.id}}&campaign_id={{campaign.id}}&ad_name={{ad.name}}&adset_name={{adset.name}}&campaign_name={{campaign.name}}&fbclid={{fbclid}}

$config['traffic'] = [

    // ── Facebook traffic (has fbclid) ────────────────────────────────
    'fb' => [
        'products_file' => '/data/products.json',
        'checkout_url'  => 'CHANGE_ME_PER_SITE',
        'allowed_countries' => ['MX', 'ES'],  // ← add this line

        'discount_codes' => [

            'marchsale1' => [
                'label'       => 'March Sale',
                'fixed_price' => 219.00,
                'active'      => true,
            ],
        
            'springsale2' => [
                'label'       => 'Spring Special',
                'fixed_price' => 327.00,
                'active'      => true,
            ],
        
            'discount3' => [
                'label'       => 'VIP Discount 2026',
                'fixed_price' => 438.00,
                'active'      => true,
            ],
        
            'BIENVENIDA10' => [
                'label'       => 'Special Discount 2026',
                'fixed_price' => 219.00,
                'active'      => false,  // disabled — flip to true to activate
            ],
            
           'ruleta10' => [
               'active' => true,
               'label'  => 'Ruleta 10%',
               'fixed_price' => 99,   // or whatever the 10% equivalent is
           ],
           
           'ruleta30' => [
               'active' => true,
               'label'  => 'Ruleta 30%',
               'fixed_price' => 77,
           ],
           
           'ruleta50' => [
               'active' => true,
               'label'  => 'Ruleta 50%',
               'fixed_price' => 55,
           ],

        ],
    ],

    // ── Non-Facebook traffic (no fbclid) ─────────────────────────────
    'nonfb' => [
        'products_file' => '/data/products.json',
        'checkout_url'  => '/checkout_form.php',

        'discount_codes' => [

            'marchsale1' => [
                'label'       => 'March Sale',
                'fixed_price' => 219.00,
                'active'      => true,
            ],
        
            'springsale2' => [
                'label'       => 'Spring Special',
                'fixed_price' => 327.00,
                'active'      => true,
            ],
        
            'discount3' => [
                'label'       => 'VIP Discount 2026',
                'fixed_price' => 438.00,
                'active'      => true,
            ],
    
            'BIENVENIDA10' => [
                'label'       => 'Special Discount 2026',
                'fixed_price' => 219.00,
                'active'      => true,  // disabled — flip to true to activate
            ],
            
           'ruleta10' => [
               'active' => true,
               'label'  => 'Ruleta 10%',
               'fixed_price' => 99,   // or whatever the 10% equivalent is
           ],
           
           'ruleta30' => [
               'active' => true,
               'label'  => 'Ruleta 30%',
               'fixed_price' => 77,
           ],
           
           'ruleta50' => [
               'active' => true,
               'label'  => 'Ruleta 50%',
               'fixed_price' => 55,
           ],

        ],
    ],

];





// NEW:
// ── Site / Payment Settings ──────────────────────────────────────────
// site_url      : Your domain (used to build absolute image URLs for the
//                 payment gateway). No trailing slash.
// checkout_url  : Now defined per traffic source in the traffic block above.
//                 FB → external payment gateway, Non-FB → internal order form.

$config['site_url']     = 'https://ofertasydescuento.com';
$config['site_name']    = 'Ofertas y Descuento';
$config['support_email'] = 'support@ofertasydescuento.com';


// ── Default Country ──────────────────────────────────────────────────
// ISO 3166-1 alpha-2 code (e.g. 'MX', 'ES', 'US'). Used to preselect
// the country dropdown and the phone prefix on the billing page.

$config['country'] = 'MX';


// ── Tracking / Analytics ─────────────────────────────────────────────
// sitebehaviour_secret : Per-site SiteBehaviour tracking ID.
//                        Leave '' to skip the SiteBehaviour snippet.
// facebook_pixels      : Zero, one, or more Meta Pixel IDs. Each ID gets
//                        its own fbq('init') + noscript img. Leave [] to
//                        skip the Meta Pixel snippet entirely.

$config['tracking'] = [
    'sitebehaviour_secret' => '',
    'facebook_pixels'      => [],   // e.g. ['937583255649675'] or ['ID1', 'ID2']
];



// ── Bundle Image Settings ────────────────────────────────────────────
// width/height : Output image dimensions in pixels
// background   : Background colour (hex)
// quality      : JPEG quality 0-100 (only used if format is jpg)

$config['bundle_image'] = [
    'width'      => 400,
    'height'     => 400,
    'background' => '#ffffff',
];

// ── FOMO / Urgency Features ──────────────────────────────────────────
// Each feature can be enabled/disabled independently.
// Set 'enabled' => false to turn off without deleting config.


// ── Reviewer Pools (by country) ──────────────────────────────────────
// Names and cities used for generating product reviews.
// PHP resolves which pool to use based on visitor's country, then
// only passes that single pool to JS — the visitor never sees other pools.
// Add more country codes as needed. 'default' is the fallback.

$config['reviewer_pools'] = [

    'MX' => [
        'names' => ['María G.', 'Carlos R.', 'Ana L.', 'José M.', 'Sofía P.', 'Diego H.', 'Valentina S.', 'Luis F.', 'Camila T.', 'Andrés V.', 'Fernanda B.', 'Roberto N.'],
        'cities' => ['Ciudad de México', 'Guadalajara', 'Monterrey', 'Puebla', 'Cancún', 'Mérida', 'Querétaro', 'Tijuana', 'León', 'Oaxaca'],
    ],

    'ES' => [
        'names' => ['María G.', 'Carlos R.', 'Ana L.', 'Pablo M.', 'Lucía S.', 'Javier P.', 'Carmen T.', 'David F.', 'Elena V.', 'Miguel B.', 'Laura N.', 'Sergio H.'],
        'cities' => ['Madrid', 'Barcelona', 'Valencia', 'Sevilla', 'Málaga', 'Bilbao', 'Zaragoza', 'Granada', 'Alicante', 'Palma de Mallorca'],
    ],

    'US' => [
        'names' => ['Sarah M.', 'James R.', 'Emily L.', 'Michael T.', 'Jessica P.', 'David S.', 'Ashley W.', 'Chris B.', 'Amanda K.', 'Ryan H.', 'Nicole F.', 'Brandon G.'],
        'cities' => ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Miami', 'Denver', 'Seattle', 'Austin', 'San Diego'],
    ],

    'CO' => [
        'names' => ['María P.', 'Juan C.', 'Daniela R.', 'Andrés M.', 'Valentina L.', 'Sebastián G.', 'Camila H.', 'Diego F.', 'Laura S.', 'Carlos T.'],
        'cities' => ['Bogotá', 'Medellín', 'Cali', 'Barranquilla', 'Cartagena', 'Bucaramanga', 'Santa Marta', 'Pereira'],
    ],

    'AR' => [
        'names' => ['María F.', 'Martín R.', 'Luciana S.', 'Nicolás G.', 'Florencia P.', 'Agustín L.', 'Camila M.', 'Tomás B.', 'Valentina H.', 'Facundo T.'],
        'cities' => ['Buenos Aires', 'Córdoba', 'Rosario', 'Mendoza', 'La Plata', 'Mar del Plata', 'San Miguel de Tucumán', 'Salta'],
    ],

    'default' => [
        'names' => ['María G.', 'Carlos R.', 'Ana L.', 'José M.', 'Sofía P.', 'Diego H.', 'Laura S.', 'Luis F.', 'Carmen T.', 'Andrés V.'],
        'cities' => ['Ciudad de México', 'Guadalajara', 'Monterrey', 'Puebla', 'Cancún', 'Madrid', 'Barcelona', 'Bogotá'],
    ],

];

$config['fomo'] = [

    // ── Recent Purchase Popups ───────────────────────────────────────
    // Fake "Someone in X just bought Y" notifications
    'recent_purchases' => [
        'enabled'       => true,
        'delay_first'   => 8,      // seconds before first popup
        'interval_min'  => 25,     // minimum seconds between popups
        'interval_max'  => 45,     // maximum seconds between popups
        'display_time'  => 5,      // seconds each popup stays visible
        'product_ids'   => [       // which products to show — leave empty [] for all products
            'mac-destino-mexico-beauty-case',
            'ado-travel-comfort-gift-set',
            'la-roche-posay-advanced-skincare-gift-kit',
        ],
        'cities' => [              // random cities shown in the popup
            'Ciudad de México', 'Guadalajara', 'Monterrey', 'Puebla',
            'Tijuana', 'León', 'Cancún', 'Mérida', 'Querétaro',
            'Oaxaca', 'Chihuahua', 'Aguascalientes', 'Morelia',
        ],
        'time_ago_min'  => 1,      // "X minutes ago" random range min
        'time_ago_max'  => 45,     // "X minutes ago" random range max
    ],

    // ── Welcome / Discount Popup ─────────────────────────────────────
    // Shows once per visitor session with a discount offer
    'welcome_popup' => [
        'enabled'       => false,
        'delay'         => 5,      // seconds before showing
        'discount_code' => '',     // leave empty to not show a code, or set e.g. 'WELCOME10'
        'show_once'     => true,   // only show once per session (uses sessionStorage)
    ],

    // ── Exit-Intent Popup ────────────────────────────────────────────
    // Shows when visitor moves mouse toward browser close/back (desktop)
    // or after idle timeout on mobile
    'exit_intent' => [
        'enabled'         => false,
        'discount_code'   => '',     // leave empty to not show a code
        'mobile_idle_sec' => 30,     // on mobile: show after X seconds idle
        'show_once'       => true,   // only trigger once per session
    ],

    // ── Countdown Timer ──────────────────────────────────────────────
    // Urgency countdown displayed on product pages and homepage
    'countdown' => [
        'enabled'       => true,
        'hours'         => 2,      // countdown duration in hours from first visit
        'show_on_home'  => true,   // show on homepage hero section
        'show_on_product' => true, // show on product detail pages
    ],

    // ── Low Stock Scarcity ───────────────────────────────────────────
    // "Only X left in stock" on product cards and detail pages
    'low_stock' => [
        'enabled'       => true,
        'min'           => 2,      // random stock count minimum
        'max'           => 8,      // random stock count maximum
        'threshold'     => 10,     // only show warning when "stock" <= this
        'show_on_cards' => true,   // show on product grid cards
        'show_on_detail' => true,  // show on product detail page
    ],

    // ── Cart Progress / Free Shipping Bar ────────────────────────────
    // "Add $X more for free shipping!"
    'cart_progress' => [
        'enabled'       => false,  // disabled by default since you already have free shipping
        'threshold'     => 500.00, // spend this much to unlock free shipping
    ],

];

$config['popup'] = [
    'enabled'      => true,

    // Default language if visitor language can't be resolved from session.
    'default_lang' => 'es',

    // The discount code shown to everyone who submits.
    'discount_code' => 'BIENVENIDA10',

    // Optional hero image (full URL or absolute path on the shop).
    'image'         => '', // /img/popup-hero.jpg

    // Override accent color (CSS color string). Leave empty to use the default red.
    'theme_color'   => '',

    // ---- Triggers ----
    'triggers' => [
        'time_seconds_desktop' => 0,     // 0 = disabled. Set e.g. 30 for 30s timer.
        'time_seconds_mobile'  => 20,    // mobile has no exit intent, so use a timer.
        'scroll_percent'       => 0,     // 0 = disabled. Set e.g. 50 for 50% scroll.
        'exit_intent_desktop'  => true,  // mouse leaves viewport heading up
        'on_add_to_cart'       => false, // show when user adds without identifying
    ],

    // ---- Suppression (cookie-based, in days) ----
    'suppress_days_after_dismiss' => 7,
    'suppress_days_after_submit'  => 90,

    // ---- Field configuration ----
    'phone_field'    => true,   // show a phone field
    'phone_required' => false,  // require it (lower conversion)
    'sms_opt_in'     => true,   // show "Also notify me by SMS" checkbox
    'name_field'     => false,  // optional first-name field

    // ---- Localized text ----
    'text' => [

        'es' => [
            'headline'          => '¡10% de descuento en tu primera compra!',
            'subhead'           => 'Suscríbete y recibe ofertas exclusivas en tu correo.',
            'email_label'       => 'Correo electrónico',
            'email_placeholder' => 'tucorreo@ejemplo.com',
            'phone_label'       => 'Teléfono',
            'phone_placeholder' => '+52...',
            'phone_hint'        => 'Incluye el código de país (+52 para México)',
            'name_label'        => 'Nombre',
            'sms_opt_in_label'  => 'También quiero recibir ofertas por SMS',
            'submit_button'     => 'Obtener mi descuento',
            'submitting'        => 'Enviando...',
            'success_headline'  => '¡Tu código de descuento!',
            'success_body'      => 'Usa este código en el checkout:',
            'success_close'     => 'Seguir comprando',
            'error_generic'     => 'Algo salió mal. Por favor intenta de nuevo.',
            'fineprint'         => 'Al suscribirte aceptas recibir correos promocionales. Puedes darte de baja en cualquier momento.',
            'aria_close'        => 'Cerrar',
        ],

        'en' => [
            'headline'          => '10% off your first order!',
            'subhead'           => 'Subscribe and get exclusive deals in your inbox.',
            'email_label'       => 'Email',
            'email_placeholder' => 'you@example.com',
            'phone_label'       => 'Phone',
            'phone_placeholder' => '+1...',
            'phone_hint'        => 'Include country code',
            'name_label'        => 'First name',
            'sms_opt_in_label'  => 'Also notify me by SMS for the best deals',
            'submit_button'     => 'Get my discount',
            'submitting'        => 'Sending...',
            'success_headline'  => 'Your discount code!',
            'success_body'      => 'Use this code at checkout:',
            'success_close'     => 'Continue shopping',
            'error_generic'     => 'Something went wrong. Please try again.',
            'fineprint'         => 'By subscribing you agree to receive promotional emails. You can unsubscribe anytime.',
            'aria_close'        => 'Close',
        ],

    ],
];


$config['spin_wheel'] = [
    'enabled'      => true,
    'default_lang' => 'es',

    // ── Free Gift ────────────────────────────────────────────────────
    // The product ID awarded when the user wins on the wheel.
    // Must match an `id` in /data/products.json. Every "free_gift" segment
    // below uses this product unless that segment overrides it via
    // `specific_product_id`.

    'free_gift_product_id' => 'PRODUCT_ID_HERE',

    // Timing
    'show_after_seconds' => 5,    // auto-open modal after N seconds on first page load

    // Spin mechanics
    'total_spins'  => 3,
    'winning_spin' => 3,            // user always wins on this spin number (1-indexed)

    // Which segment they win on the winning spin. Set to the array index below.
    // Must point to a non-"nothing" segment.
    'winner_segment_index' => 3,

    // Segments — the wheel is drawn in the order listed.
    // Multiple "nothing" segments increase perceived fairness during the first 2 spins.
    // type options: "nothing", "discount", "free_gift"
    // For free_gift segments without `specific_product_id`, the top-level
    // `free_gift_product_id` (above) is used.
    'segments' => [
        [ 'type' => 'nothing',   'label' => 'Nothing',   'color' => '#e5e7eb' ],
        [ 'type' => 'free_gift', 'label' => 'FREE GIFT', 'color' => '#10b981' ],
        [ 'type' => 'nothing',   'label' => 'Nothing',   'color' => '#d1d5db' ],
        [ 'type' => 'free_gift', 'label' => 'FREE GIFT', 'color' => '#22c55e',
          'winner' => true ], // ← winner_segment_index = 3 points here
        [ 'type' => 'nothing',   'label' => 'Nothing',   'color' => '#e5e7eb' ],
        [ 'type' => 'free_gift', 'label' => 'FREE GIFT', 'color' => '#10b981' ],
        [ 'type' => 'nothing',   'label' => 'Nothing',   'color' => '#d1d5db' ],
        [ 'type' => 'nothing',   'label' => 'Nothing',   'color' => '#e5e7eb' ],
    ],

    // Fields
    'require_phone' => false,
    'sms_opt_in'    => true,

    // Suppression (0 = no suppression; bubble stays until claim)
    'suppress_days_after_claim'   => 0,
    'suppress_days_after_dismiss' => 0,

    // Visual
    'bubble_position'  => 'bottom-left',    // 'bottom-left' or 'bottom-right'
    'theme_color'      => '',

    // Sounds — URLs to MP3/OGG, or empty for synthesized Web Audio beeps
    'sounds_enabled'   => true,
    'sound_spin_start' => '',    // e.g. '/sounds/wheel/spin-start.mp3'
    'sound_tick'       => '',    // e.g. '/sounds/wheel/tick.mp3'
    'sound_win'        => '',    // e.g. '/sounds/wheel/win.mp3'
    'sound_lose'       => '',    // e.g. '/sounds/wheel/lose.mp3'
    'sound_claim'      => '',    // e.g. '/sounds/wheel/claim.mp3'

    'confetti_on_win'  => true,
];

return $config;
