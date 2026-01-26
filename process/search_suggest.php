<?php
/* ============================================
   SEARCH SUGGEST - Procedural (JSON)
   Returns product suggestions for live search
============================================ */

if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../config/constants.php';
    start_secure_session();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/validation.php';

header('Content-Type: application/json');

$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$q = sanitize_input($q, 'string');

// Minimal input to avoid heavy queries
if ($q === '' || mb_strlen($q) < 1) {
    echo json_encode(['success' => true, 'items' => []]);
    exit;
}

// Limit length
if (mb_strlen($q) > 64) {
    $q = mb_substr($q, 0, 64);
}

$likeLower = '%' . mb_strtolower($q) . '%';
$prefixLower = mb_strtolower($q) . '%';

db_query("SELECT id, name, price, image AS image_url
          FROM products
          WHERE LOWER(name) LIKE :contains
          ORDER BY CASE WHEN LOWER(name) LIKE :prefix THEN 0 ELSE 1 END, name ASC
          LIMIT 8");

db_bind(':contains', $likeLower);
db_bind(':prefix', $prefixLower);
$items = db_result_set();

// Normalize image URLs
foreach ($items as &$item) {
    if (!empty($item['image_url']) && !filter_var($item['image_url'], FILTER_VALIDATE_URL)) {
        $item['image_url'] = SITE_URL . ltrim($item['image_url'], '/');
    }
}

echo json_encode(['success' => true, 'items' => $items]);
