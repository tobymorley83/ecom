<?php
/**
 * Order Request Handler
 * =====================
 * Receives order form submissions from checkout_form.php (non-FB traffic).
 * Saves order data as a JSON file in /data/orders/ for review.
 *
 * In production you would also send an email notification here.
 */

$config = require __DIR__ . '/config.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['firstname']) || empty($input['email'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// ── Sanitize input ───────────────────────────────────────────────────
$order = [
    'order_id'     => 'ORD-' . strtoupper(bin2hex(random_bytes(6))),
    'date'         => date('Y-m-d H:i:s'),
    'status'       => 'pending_review',

    'customer'     => [
        'firstname' => trim($input['firstname'] ?? ''),
        'lastname'  => trim($input['lastname'] ?? ''),
        'email'     => trim($input['email'] ?? ''),
        'phone'     => trim($input['phone'] ?? ''),
    ],

    'shipping'     => [
        'address'   => trim($input['address'] ?? ''),
        'address2'  => trim($input['address2'] ?? ''),
        'city'      => trim($input['city'] ?? ''),
        'state'     => trim($input['state'] ?? ''),
        'zip'       => trim($input['zip'] ?? ''),
        'country'   => trim($input['country'] ?? ''),
    ],

    'order_details' => [
        'productname'  => trim($input['productname'] ?? ''),
        'totalprice'   => trim($input['totalprice'] ?? ''),
        'currency'     => trim($input['currency'] ?? ''),
        'productimage' => trim($input['productimage'] ?? ''),
    ],

    'notes'        => trim($input['notes'] ?? ''),
];

// ── Save order to file ───────────────────────────────────────────────
$ordersDir = __DIR__ . '/data/orders';
if (!is_dir($ordersDir)) {
    mkdir($ordersDir, 0755, true);
}

$filename = $ordersDir . '/' . $order['order_id'] . '.json';
file_put_contents($filename, json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// ── Optional: Send email notification ────────────────────────────────
// Uncomment and configure to receive email alerts for new orders.
//
// $to      = $config['support_email'];
// $subject = 'New Order Request: ' . $order['order_id'];
// $body    = "New order request from {$order['customer']['firstname']} {$order['customer']['lastname']}\n"
//          . "Email: {$order['customer']['email']}\n"
//          . "Phone: {$order['customer']['phone']}\n"
//          . "Product: {$order['order_details']['productname']}\n"
//          . "Total: {$order['order_details']['currency']} {$order['order_details']['totalprice']}\n"
//          . "\nFull order saved to: {$filename}";
// $headers = "From: ' . 'noreply@' . parse_url($config['site_url'], PHP_URL_HOST) . '\r\nReply-To: {$order['customer']['email']}";
// mail($to, $subject, $body, $headers);

// ── Response ─────────────────────────────────────────────────────────
header('Content-Type: application/json');
echo json_encode([
    'success'  => true,
    'order_id' => $order['order_id'],
    'message'  => 'Order request received. Our team will contact you shortly.',
]);
