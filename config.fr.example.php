<?php

$config = [];

$config['default_lang']    = 'fr';
$config['available_langs'] = ['en', 'es', 'fr', 'de', 'it'];

// Currency: code only — symbol/decimals/separators come from
// data/currencies.json. Money values below (discount fixed_price,
// checkout_urls max) are in EUR.
$config['currency_code'] = 'EUR';

$config['traffic'] = [

    'fb' => [
        'products_file'     => '/data/products.json',

        // Single fallback URL (used if checkout_urls below is empty/unmatched).
        'checkout_url'      => 'https://letsgo.fruityapples.sbs/click?key=34b2be9ae12e464490297ad0461a6b48',

        // OPTIONAL tier-based routing. `max` is in EUR. Fill in the URLs for
        // each tier per shop, or comment this block out to keep using the
        // single `checkout_url` above.
        // 'checkout_urls' => [
        //     [ 'max' => 4.95,  'url' => '...' ],
        //     [ 'max' => 9.99,  'url' => '...' ],
        //     [ 'max' => 19.99, 'url' => '...' ],
        //     [ 'max' => 29.99, 'url' => '...' ],
        //     [ 'max' => 39.99, 'url' => '...' ],
        //     [ 'max' => 49.99, 'url' => '...' ],
        //     [ 'max' => 59.99, 'url' => '...' ],
        //     [ 'max' => 69.99, 'url' => '...' ],
        //     [ 'max' => null,  'url' => '...' ],
        // ],

        'allowed_countries' => ['FR', 'ES'],

        'discount_codes' => [
            'marchsale1'  => [ 'label' => 'March Sale',        'fixed_price' => 19.99, 'active' => true  ],
            'springsale2' => [ 'label' => 'Spring Special',    'fixed_price' => 9.99,  'active' => true  ],
            'springsale3' => [ 'label' => 'VIP Discount 2026', 'fixed_price' => 19.99, 'active' => true  ],
            'offer4'      => [ 'label' => 'Big offer 4',       'fixed_price' => 9.99,  'active' => false ],
        ],
    ],

    'nonfb' => [
        'products_file' => '/data/products.json',
        'checkout_url'  => '/checkout_form.php', // unused by new billing flow; legacy fallback

        'discount_codes' => [
            'marchsale1'  => [ 'label' => 'March Sale',        'fixed_price' => 19.99, 'active' => true  ],
            'springsale2' => [ 'label' => 'Spring Special',    'fixed_price' => 9.99,  'active' => true  ],
            'springsale3' => [ 'label' => 'VIP Discount 2026', 'fixed_price' => 19.99, 'active' => true  ],
            'offer4'      => [ 'label' => 'Big offer 4',       'fixed_price' => 9.99,  'active' => false ],
        ],
    ],

];

$config['site_url']      = '__SITE_URL__';
$config['site_name']     = '__SITE_NAME__';
$config['support_email'] = '__SUPPORT_EMAIL__';

$config['country'] = 'FR';

$config['tracking'] = [
    'sitebehaviour_secret' => '',
    'facebook_pixels'      => ['1330454688906233', '935616556009940'],
    'yandex_metrika_ids'   => ['108900669'],
];

$config['bundle_image'] = [
    'width'      => 400,
    'height'     => 400,
    'background' => '#ffffff',
];

$config['reviewer_pools'] = [

    'FR' => [
        'names'  => ['Marie G.', 'Lucas R.', 'Camille L.', 'Thomas M.', 'Chloé S.', 'Julien P.', 'Sophie T.', 'Nicolas F.', 'Emma V.', 'Antoine B.', 'Laura N.', 'Maxime H.'],
        'cities' => ['Paris', 'Marseille', 'Lyon', 'Toulouse', 'Nice', 'Nantes', 'Strasbourg', 'Montpellier', 'Bordeaux', 'Lille'],
    ],

    'ES' => [
        'names'  => ['María G.', 'Carlos R.', 'Ana L.', 'Pablo M.', 'Lucía S.', 'Javier P.', 'Carmen T.', 'David F.', 'Elena V.', 'Miguel B.', 'Laura N.', 'Sergio H.'],
        'cities' => ['Madrid', 'Barcelona', 'Valencia', 'Sevilla', 'Málaga', 'Bilbao', 'Zaragoza', 'Granada', 'Alicante', 'Palma de Mallorca'],
    ],

    'default' => [
        'names'  => ['Marie G.', 'Lucas R.', 'Camille L.', 'Thomas M.', 'Chloé S.', 'Julien P.', 'Sophie T.', 'Nicolas F.', 'Emma V.', 'Antoine B.', 'Laura N.', 'Maxime H.'],
        'cities' => ['Paris', 'Marseille', 'Lyon', 'Toulouse', 'Nice', 'Nantes', 'Strasbourg', 'Montpellier', 'Bordeaux', 'Lille'],
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
            'Paris', 'Marseille', 'Lyon', 'Toulouse',
            'Nice', 'Nantes', 'Strasbourg', 'Montpellier',
            'Bordeaux', 'Lille', 'Rennes', 'Reims',
            'Le Havre', 'Saint-Étienne', 'Toulon',
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

$config['popup'] = [
    'enabled'       => false,
    'default_lang'  => 'fr',
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
        'fr' => [
            'headline'          => '10% de réduction sur votre première commande !',
            'subhead'           => 'Inscrivez-vous et recevez des offres exclusives par e-mail.',
            'email_label'       => 'E-mail',
            'email_placeholder' => 'vous@exemple.com',
            'phone_label'       => 'Téléphone',
            'phone_placeholder' => '+33...',
            'phone_hint'        => "Incluez l'indicatif du pays",
            'name_label'        => 'Prénom',
            'sms_opt_in_label'  => 'Je veux aussi recevoir des offres par SMS',
            'submit_button'     => 'Obtenir ma réduction',
            'submitting'        => 'Envoi...',
            'success_headline'  => 'Votre code de réduction !',
            'success_body'      => 'Utilisez ce code au paiement :',
            'success_close'     => 'Continuer mes achats',
            'error_generic'     => "Une erreur est survenue. Veuillez réessayer.",
            'fineprint'         => "En vous inscrivant vous acceptez de recevoir des e-mails promotionnels. Vous pouvez vous désinscrire à tout moment.",
            'aria_close'        => 'Fermer',
        ],
        'en' => [
            'headline'          => '10% off your first order!',
            'subhead'           => 'Subscribe and get exclusive deals in your inbox.',
            'email_label'       => 'Email',
            'email_placeholder' => 'you@example.com',
            'phone_label'       => 'Phone',
            'phone_placeholder' => '+33...',
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
    'default_lang' => 'fr',

    'free_gift_product_id' => 'ado-travel-comfort-gift-set',

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
