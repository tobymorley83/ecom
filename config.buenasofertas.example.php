<?php
/**
 * TEMP — buenasofertas.org bootstrap config.
 * On the server:
 *   cp config.buenasofertas.example.php config.php
 *   chmod 600 config.php
 *   chown www:www config.php
 * Once tested, this file can be removed from the repo.
 */

$config = [];

$config['default_lang']    = 'es';
$config['available_langs'] = ['en', 'es', 'fr', 'de', 'it'];

$config['currency'] = [
    'symbol'        => 'MX$',
    'code'          => 'MXN',
    'position'      => 'before',
    'decimals'      => 2,
    'thousands_sep' => ',',
    'decimal_sep'   => '.',
];

$config['traffic'] = [

    'fb' => [
        'products_file'     => '/data/products.json',
        'checkout_url'      => 'https://letsgo.fruityapples.top/click?key=3cfe693ef86f4ed6a7bf8b337934e353',
        'allowed_countries' => ['MX', 'ES'],

        'discount_codes' => [
            'marchsale1' => [ 'label' => 'March Sale',         'fixed_price' => 204.00, 'active' => true  ],
            'springsale2'=> [ 'label' => 'Spring Special',     'fixed_price' => 204.00, 'active' => true  ],
            'discount3'  => [ 'label' => 'VIP Discount 2026',  'fixed_price' => 140.00, 'active' => true  ],
            'offer4'     => [ 'label' => 'Big offer 4',        'fixed_price' => 999.00, 'active' => false ],
        ],
    ],

    'nonfb' => [
        'products_file' => '/data/products.json',
        'checkout_url'  => '/checkout_form.php', // unused by new billing flow; legacy fallback

        'discount_codes' => [
            'marchsale1' => [ 'label' => 'March Sale',         'fixed_price' => 219.00, 'active' => false ],
            'springsale2'=> [ 'label' => 'Spring Special',     'fixed_price' => 327.00, 'active' => false ],
            'discount3'  => [ 'label' => 'VIP Discount 2026',  'fixed_price' => 438.00, 'active' => false ],
            'offer4'     => [ 'label' => 'Big offer 4',        'fixed_price' => 999.00, 'active' => false ],
        ],
    ],

];

$config['site_url']      = 'https://buenasofertas.org';
$config['site_name']     = 'Buenas Ofertas';
$config['support_email'] = 'support@buenasofertas.org';

// Default country for billing form preselect (ISO 3166-1 alpha-2).
$config['country'] = 'MX';

// Tracking / Analytics — fill in when you have IDs for this site.
$config['tracking'] = [
    'sitebehaviour_secret' => '',
    'facebook_pixels'      => [],
];

$config['bundle_image'] = [
    'width'      => 400,
    'height'     => 400,
    'background' => '#ffffff',
];

$config['reviewer_pools'] = [

    'MX' => [
        'names'  => ['María G.', 'Carlos R.', 'Ana L.', 'José M.', 'Sofía P.', 'Diego H.', 'Valentina S.', 'Luis F.', 'Camila T.', 'Andrés V.', 'Fernanda B.', 'Roberto N.'],
        'cities' => ['Ciudad de México', 'Guadalajara', 'Monterrey', 'Puebla', 'Cancún', 'Mérida', 'Querétaro', 'Tijuana', 'León', 'Oaxaca'],
    ],

    'ES' => [
        'names'  => ['María G.', 'Carlos R.', 'Ana L.', 'Pablo M.', 'Lucía S.', 'Javier P.', 'Carmen T.', 'David F.', 'Elena V.', 'Miguel B.', 'Laura N.', 'Sergio H.'],
        'cities' => ['Madrid', 'Barcelona', 'Valencia', 'Sevilla', 'Málaga', 'Bilbao', 'Zaragoza', 'Granada', 'Alicante', 'Palma de Mallorca'],
    ],

    'US' => [
        'names'  => ['Sarah M.', 'James R.', 'Emily L.', 'Michael T.', 'Jessica P.', 'David S.', 'Ashley W.', 'Chris B.', 'Amanda K.', 'Ryan H.', 'Nicole F.', 'Brandon G.'],
        'cities' => ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Miami', 'Denver', 'Seattle', 'Austin', 'San Diego'],
    ],

    'CO' => [
        'names'  => ['María P.', 'Juan C.', 'Daniela R.', 'Andrés M.', 'Valentina L.', 'Sebastián G.', 'Camila H.', 'Diego F.', 'Laura S.', 'Carlos T.'],
        'cities' => ['Bogotá', 'Medellín', 'Cali', 'Barranquilla', 'Cartagena', 'Bucaramanga', 'Santa Marta', 'Pereira'],
    ],

    'AR' => [
        'names'  => ['María F.', 'Martín R.', 'Luciana S.', 'Nicolás G.', 'Florencia P.', 'Agustín L.', 'Camila M.', 'Tomás B.', 'Valentina H.', 'Facundo T.'],
        'cities' => ['Buenos Aires', 'Córdoba', 'Rosario', 'Mendoza', 'La Plata', 'Mar del Plata', 'San Miguel de Tucumán', 'Salta'],
    ],

    'default' => [
        'names'  => ['María G.', 'Carlos R.', 'Ana L.', 'José M.', 'Sofía P.', 'Diego H.', 'Laura S.', 'Luis F.', 'Carmen T.', 'Andrés V.'],
        'cities' => ['Ciudad de México', 'Guadalajara', 'Monterrey', 'Puebla', 'Cancún', 'Madrid', 'Barcelona', 'Bogotá'],
    ],

];

$config['fomo'] = [

    'recent_purchases' => [
        'enabled'      => true,
        'delay_first'  => 8,
        'interval_min' => 25,
        'interval_max' => 45,
        'display_time' => 5,
        'product_ids'  => [
            'mac-destino-mexico-beauty-case',
            'ado-travel-comfort-gift-set',
            'la-roche-posay-advanced-skincare-gift-kit',
        ],
        'cities' => [
            'Ciudad de México', 'Guadalajara', 'Monterrey', 'Puebla',
            'Tijuana', 'León', 'Cancún', 'Mérida', 'Querétaro',
            'Oaxaca', 'Chihuahua', 'Aguascalientes', 'Morelia',
        ],
        'time_ago_min' => 1,
        'time_ago_max' => 45,
    ],

    'welcome_popup' => [ 'enabled' => false, 'delay' => 5, 'discount_code' => '', 'show_once' => true ],
    'exit_intent'   => [ 'enabled' => false, 'discount_code' => '', 'mobile_idle_sec' => 30, 'show_once' => true ],
    'countdown'     => [ 'enabled' => true, 'hours' => 2, 'show_on_home' => true, 'show_on_product' => true ],
    'low_stock'     => [ 'enabled' => true, 'min' => 2, 'max' => 8, 'threshold' => 10, 'show_on_cards' => true, 'show_on_detail' => true ],
    'cart_progress' => [ 'enabled' => false, 'threshold' => 500.00 ],

];

// Brevo welcome / discount popup — disabled until a discount code is wired.
$config['popup'] = [
    'enabled'       => false,
    'default_lang'  => 'es',
    'discount_code' => '',
    'image'         => '',
    'theme_color'   => '',
    'triggers' => [
        'time_seconds_desktop' => 0,
        'time_seconds_mobile'  => 20,
        'scroll_percent'       => 0,
        'exit_intent_desktop'  => true,
        'on_add_to_cart'       => false,
    ],
    'suppress_days_after_dismiss' => 7,
    'suppress_days_after_submit'  => 90,
    'phone_field'    => true,
    'phone_required' => false,
    'sms_opt_in'     => true,
    'name_field'     => false,
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

// Spin wheel — disabled until you set free_gift_product_id to a real product
// ID from data/products.json. Then flip `enabled` to true.
$config['spin_wheel'] = [
    'enabled'      => false,
    'default_lang' => 'es',

    'free_gift_product_id' => 'PRODUCT_ID_HERE',

    'show_after_seconds'   => 5,
    'total_spins'          => 3,
    'winning_spin'         => 3,
    'winner_segment_index' => 3,

    'segments' => [
        [ 'type' => 'nothing',   'label' => 'Nothing',   'color' => '#e5e7eb' ],
        [ 'type' => 'free_gift', 'label' => 'FREE GIFT', 'color' => '#10b981' ],
        [ 'type' => 'nothing',   'label' => 'Nothing',   'color' => '#d1d5db' ],
        [ 'type' => 'free_gift', 'label' => 'FREE GIFT', 'color' => '#22c55e', 'winner' => true ],
        [ 'type' => 'nothing',   'label' => 'Nothing',   'color' => '#e5e7eb' ],
        [ 'type' => 'free_gift', 'label' => 'FREE GIFT', 'color' => '#10b981' ],
        [ 'type' => 'nothing',   'label' => 'Nothing',   'color' => '#d1d5db' ],
        [ 'type' => 'nothing',   'label' => 'Nothing',   'color' => '#e5e7eb' ],
    ],

    'require_phone' => false,
    'sms_opt_in'    => true,

    'suppress_days_after_claim'   => 0,
    'suppress_days_after_dismiss' => 0,

    'bubble_position'  => 'bottom-left',
    'theme_color'      => '',

    'sounds_enabled'   => true,
    'sound_spin_start' => '',
    'sound_tick'       => '',
    'sound_win'        => '',
    'sound_lose'       => '',
    'sound_claim'      => '',

    'confetti_on_win'  => true,
];

return $config;
