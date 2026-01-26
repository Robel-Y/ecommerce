<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../functions/utilities.php';
require_once __DIR__ . '/../functions/security.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    start_secure_session();
}

if (!is_admin()) {
    $home = defined('SITE_URL') ? (rtrim(SITE_URL, '/') . '/index.php') : '../index.php';
    redirect_with_message($home, 'error', 'Access denied. Admin privileges required.');
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    redirect_with_message(SITE_URL . 'admin/products.php', 'error', 'Invalid product ID.');
}

// Ensure product exists
if (!db_query('SELECT id FROM products WHERE id = ? LIMIT 1')) {
    redirect_with_message(SITE_URL . 'admin/products.php', 'error', 'Failed to load product.');
}
db_bind(1, $id, PDO::PARAM_INT);
$product = db_single();
if (!$product) {
    redirect_with_message(SITE_URL . 'admin/products.php', 'error', 'Product not found.');
}

if (!db_query('DELETE FROM products WHERE id = ?')) {
    redirect_with_message(SITE_URL . 'admin/products.php', 'error', 'Failed to delete product.');
}
db_bind(1, $id, PDO::PARAM_INT);

if (db_execute()) {
    redirect_with_message(SITE_URL . 'admin/products.php', 'success', 'Product deleted successfully.');
}

redirect_with_message(SITE_URL . 'admin/products.php', 'error', 'Failed to delete product.');
