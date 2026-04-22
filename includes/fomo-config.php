<?php
/**
 * FOMO Config Output
 * ==================
 * Outputs FomoConfig JavaScript object with only the enabled features.
 * Included in footer.php before fomo.js loads.
 * $config must already be loaded.
 */
if (!isset($config)) {
    $config = require __DIR__ . '/../config.php';
}

$fomo = $config['fomo'] ?? [];
$fomoJs = [];

// Recent purchases — only send cities and timing if enabled
if (!empty($fomo['recent_purchases']['enabled'])) {
    $fomoJs['recent_purchases'] = [
        'delay_first'  => $fomo['recent_purchases']['delay_first'] ?? 8,
        'interval_min' => $fomo['recent_purchases']['interval_min'] ?? 25,
        'interval_max' => $fomo['recent_purchases']['interval_max'] ?? 45,
        'display_time' => $fomo['recent_purchases']['display_time'] ?? 5,
        'product_ids'  => $fomo['recent_purchases']['product_ids'] ?? [],
        'cities'       => $fomo['recent_purchases']['cities'] ?? [],
        'time_ago_min' => $fomo['recent_purchases']['time_ago_min'] ?? 1,
        'time_ago_max' => $fomo['recent_purchases']['time_ago_max'] ?? 45,
    ];
}

// Welcome popup
if (!empty($fomo['welcome_popup']['enabled'])) {
    $fomoJs['welcome_popup'] = [
        'delay'         => $fomo['welcome_popup']['delay'] ?? 5,
        'discount_code' => $fomo['welcome_popup']['discount_code'] ?? '',
        'show_once'     => $fomo['welcome_popup']['show_once'] ?? true,
    ];
}

// Exit intent
if (!empty($fomo['exit_intent']['enabled'])) {
    $fomoJs['exit_intent'] = [
        'discount_code'   => $fomo['exit_intent']['discount_code'] ?? '',
        'mobile_idle_sec' => $fomo['exit_intent']['mobile_idle_sec'] ?? 30,
        'show_once'       => $fomo['exit_intent']['show_once'] ?? true,
    ];
}

// Countdown
if (!empty($fomo['countdown']['enabled'])) {
    $fomoJs['countdown'] = [
        'hours'           => $fomo['countdown']['hours'] ?? 2,
        'show_on_home'    => $fomo['countdown']['show_on_home'] ?? true,
        'show_on_product' => $fomo['countdown']['show_on_product'] ?? true,
    ];
}

// Low stock
if (!empty($fomo['low_stock']['enabled'])) {
    $fomoJs['low_stock'] = [
        'min'            => $fomo['low_stock']['min'] ?? 2,
        'max'            => $fomo['low_stock']['max'] ?? 8,
        'threshold'      => $fomo['low_stock']['threshold'] ?? 10,
        'show_on_cards'  => $fomo['low_stock']['show_on_cards'] ?? true,
        'show_on_detail' => $fomo['low_stock']['show_on_detail'] ?? true,
    ];
}

// Cart progress
if (!empty($fomo['cart_progress']['enabled'])) {
    $fomoJs['cart_progress'] = [
        'threshold' => $fomo['cart_progress']['threshold'] ?? 500,
    ];
}

if (!empty($fomoJs)):
?>
<script>var FomoConfig = <?php echo json_encode($fomoJs); ?>;</script>
<?php endif; ?>
