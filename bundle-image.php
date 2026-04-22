<?php
/**
 * Bundle Image Generator
 * ======================
 * Generates a composite PNG image from multiple product images.
 *
 * Called as: bundle-image.php?q=fb,product-a,product-b,product-c
 *
 * The q parameter format is: trafficSource,productId1,productId2,...
 * First value is the traffic source (fb or nonfb), rest are product IDs.
 * Everything in one parameter so tracking redirects can't break it.
 *
 * Smart layout:
 *   1 product  → full canvas
 *   2 products → side by side
 *   3 products → two on top row, one wide on bottom row
 *   4+ products → 2×2 grid (first 4)
 *
 * Outputs image/png — can be used as <img src="..."> from any domain.
 */

$config = require __DIR__ . '/config.php';

// ── Parse the q parameter ────────────────────────────────────────────
// Format: q=source,id1,id2,id3
// First value = traffic source (fb/nonfb), rest = product IDs
$qParam = $_GET['q'] ?? '';
$qParts = array_filter(array_map('trim', explode(',', $qParam)));

$trafficSource = 'nonfb';
if (!empty($qParts) && in_array($qParts[0], ['fb', 'nonfb'])) {
    $trafficSource = array_shift($qParts);
}
$ids = $qParts;

if (empty($ids)) {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo 'Missing product IDs. Usage: bundle-image.php?q=fb,product1,product2';
    exit;
}

// ── Load product data ────────────────────────────────────────────────
$productsRelPath = $config['traffic'][$trafficSource]['products_file'] ?? '/data/products.json';
$productsFile = __DIR__ . $productsRelPath;

if (!file_exists($productsFile)) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'Products data not found.';
    exit;
}

$allProducts = json_decode(file_get_contents($productsFile), true);
$productMap = [];
foreach ($allProducts as $p) {
    $productMap[$p['id']] = $p;
}

// ── Collect image URLs ───────────────────────────────────────────────
$imageUrls = [];
foreach ($ids as $id) {
    if (isset($productMap[$id])) {
        $imageUrls[] = $productMap[$id]['image'];
    }
}

if (empty($imageUrls)) {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo 'No matching products found.';
    exit;
}

// Limit to 4 images max for the grid
$imageUrls = array_slice($imageUrls, 0, 4);
$count = count($imageUrls);

// ── Canvas settings ──────────────────────────────────────────────────
$canvasW = $config['bundle_image']['width'] ?? 800;
$canvasH = $config['bundle_image']['height'] ?? 800;
$bgHex   = $config['bundle_image']['background'] ?? '#ffffff';
$padding = 8; // pixels between tiles

// Parse background colour
$bgR = hexdec(substr($bgHex, 1, 2));
$bgG = hexdec(substr($bgHex, 3, 2));
$bgB = hexdec(substr($bgHex, 5, 2));

// ── Download images ──────────────────────────────────────────────────
function downloadImage($url) {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Mozilla/5.0 BundleImageGenerator/1.0',
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);

    $data = @file_get_contents($url, false, $ctx);
    if ($data === false) return null;

    $img = @imagecreatefromstring($data);
    return $img ?: null;
}

$images = [];
foreach ($imageUrls as $url) {
    $img = downloadImage($url);
    if ($img) {
        $images[] = $img;
    }
}

if (empty($images)) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'Failed to download product images.';
    exit;
}

$count = count($images);

// ── Create canvas ────────────────────────────────────────────────────
$canvas = imagecreatetruecolor($canvasW, $canvasH);
$bgColour = imagecolorallocate($canvas, $bgR, $bgG, $bgB);
imagefill($canvas, 0, 0, $bgColour);

// Enable alpha blending
imagealphablending($canvas, true);
imagesavealpha($canvas, true);

// ── Helper: fit & center an image into a slot ────────────────────────
function placeImage($canvas, $img, $slotX, $slotY, $slotW, $slotH) {
    $srcW = imagesx($img);
    $srcH = imagesy($img);

    // Calculate scale to fit (cover) the slot
    $scaleX = $slotW / $srcW;
    $scaleY = $slotH / $srcH;
    $scale  = max($scaleX, $scaleY); // cover — fill the slot

    $newW = (int)round($srcW * $scale);
    $newH = (int)round($srcH * $scale);

    // Center crop offset
    $offsetX = (int)round(($newW - $slotW) / 2);
    $offsetY = (int)round(($newH - $slotH) / 2);

    // Create temp scaled image
    $tmp = imagecreatetruecolor($newW, $newH);
    imagealphablending($tmp, true);
    imagesavealpha($tmp, true);
    imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

    // Copy the center-cropped portion to canvas
    imagecopy($canvas, $tmp, $slotX, $slotY, $offsetX, $offsetY, $slotW, $slotH);
    imagedestroy($tmp);
}

// ── Layout & compose ─────────────────────────────────────────────────
$p = $padding;
$halfP = (int)round($p / 2);

if ($count === 1) {
    // Full canvas
    placeImage($canvas, $images[0], 0, 0, $canvasW, $canvasH);

} elseif ($count === 2) {
    // Side by side
    $slotW = (int)round(($canvasW - $p) / 2);
    $slotH = $canvasH;

    placeImage($canvas, $images[0], 0, 0, $slotW, $slotH);
    placeImage($canvas, $images[1], $slotW + $p, 0, $slotW, $slotH);

} elseif ($count === 3) {
    // Two on top, one wide on bottom
    $topSlotW = (int)round(($canvasW - $p) / 2);
    $topSlotH = (int)round(($canvasH - $p) / 2);
    $botSlotW = $canvasW;
    $botSlotH = $canvasH - $topSlotH - $p;

    placeImage($canvas, $images[0], 0, 0, $topSlotW, $topSlotH);
    placeImage($canvas, $images[1], $topSlotW + $p, 0, $topSlotW, $topSlotH);
    placeImage($canvas, $images[2], 0, $topSlotH + $p, $botSlotW, $botSlotH);

} else {
    // 4+ → 2×2 grid
    $slotW = (int)round(($canvasW - $p) / 2);
    $slotH = (int)round(($canvasH - $p) / 2);

    placeImage($canvas, $images[0], 0, 0, $slotW, $slotH);
    placeImage($canvas, $images[1], $slotW + $p, 0, $slotW, $slotH);
    placeImage($canvas, $images[2], 0, $slotH + $p, $slotW, $slotH);
    placeImage($canvas, $images[3], $slotW + $p, $slotH + $p, $slotW, $slotH);
}

// ── Output PNG ───────────────────────────────────────────────────────
header('Content-Type: image/jpeg');
header('Cache-Control: public, max-age=86400'); // cache 24h
imagejpeg($canvas, null, 85);

// ── Cleanup ──────────────────────────────────────────────────────────
imagedestroy($canvas);
foreach ($images as $img) {
    imagedestroy($img);
}
