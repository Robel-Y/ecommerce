<?php
/* ============================================
   CART PROCESS - Procedural
   Handle shopping cart operations via AJAX/Form
============================================ */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../config/constants.php';
    start_secure_session();
}

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/validation.php';
require_once __DIR__ . '/../functions/utilities.php';
require_once __DIR__ . '/../functions/cart.php';

// Set JSON header for AJAX responses
header('Content-Type: application/json');

// Check if request is AJAX
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Initialize response array
$response = [
    'success' => false,
    'message' => 'Invalid request',
    'cart_count' => 0,
    'cart_total' => 0
];

// Get action
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Initialize cart session if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [
        'items' => [],
        'total' => 0,
        'count' => 0
    ];
}

// If logged in, hydrate session cart from DB once per session
$user_id = (int) ($_SESSION['user_id'] ?? 0);
if ($user_id > 0) {
    cart_session_ensure();
    $has_items = !empty($_SESSION['cart']['items']);
    $already_loaded = !empty($_SESSION['cart_loaded_from_db']);
    if (!$has_items && !$already_loaded) {
        cart_load_db_to_session($user_id);
    }
}

// Handle different actions
switch ($action) {
    case 'add':
        $response = handle_add_to_cart();
        break;

    case 'update':
        $response = handle_update_cart();
        break;

    case 'remove':
        $response = handle_remove_from_cart();
        break;

    case 'clear':
        $response = handle_clear_cart();
        break;

    case 'get':
        $response = handle_get_cart();
        break;

    default:
        $response['message'] = 'Invalid action';
        break;
}

// Update cart totals
update_cart_totals();

// Persist cart for logged-in users (DB cart survives logout/login)
if ($user_id > 0 && in_array($action, ['add', 'update', 'remove', 'clear'], true)) {
    cart_db_replace_from_session($user_id);
}

// Return JSON response for AJAX, otherwise redirect
if ($is_ajax) {
    echo json_encode($response);
    exit();
} else {
    // For non-AJAX requests, redirect with message
    $message_type = $response['success'] ? 'success' : 'error';
    redirect_with_message('user/cart.php', $message_type, $response['message']);
}

/* ========== CART HANDLER FUNCTIONS ========== */

/**
 * Handle add to cart action
 */
function handle_add_to_cart()
{
    global $db;

    // Validate required fields
    if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
        return [
            'success' => false,
            'message' => 'Missing required fields'
        ];
    }

    $product_id = (int) sanitize_input($_POST['product_id'], 'int');
    $quantity = (int) sanitize_input($_POST['quantity'], 'int');

    if ($product_id <= 0 || $quantity <= 0) {
        return [
            'success' => false,
            'message' => 'Invalid product or quantity'
        ];
    }

    // Get product details from database
    $product = get_product_by_id($product_id);

    if (!$product) {
        return [
            'success' => false,
            'message' => 'Product not found'
        ];
    }

    // Check stock availability
    if ($product['stock'] < $quantity) {
        return [
            'success' => false,
            'message' => 'Insufficient stock. Only ' . $product['stock'] . ' available'
        ];
    }

    // Check if product is already in cart
    $cart_items = $_SESSION['cart']['items'];
    $existing_item_index = -1;

    foreach ($cart_items as $index => $item) {
        if ($item['id'] == $product_id) {
            $existing_item_index = $index;
            break;
        }
    }

    // Add or update item
    if ($existing_item_index >= 0) {
        // Update existing item quantity
        $new_quantity = $cart_items[$existing_item_index]['quantity'] + $quantity;

        // Check stock again with new quantity
        if ($product['stock'] < $new_quantity) {
            return [
                'success' => false,
                'message' => 'Cannot add more than available stock'
            ];
        }

        $_SESSION['cart']['items'][$existing_item_index]['quantity'] = $new_quantity;
        $message = 'Quantity updated in cart';
    } else {
        // Add new item to cart
        $cart_item = [
            'id' => $product_id,
            'name' => $product['name'],
            'price' => (float) $product['price'],
            'image' => $product['image_url'] ?? '',
            'quantity' => $quantity,
            'stock' => $product['stock']
        ];

        $_SESSION['cart']['items'][] = $cart_item;
        $message = 'Product added to cart';
    }

    // Update cart totals
    update_cart_totals();

    return [
        'success' => true,
        'message' => $message,
        'cart_count' => $_SESSION['cart']['count'],
        'cart_total' => format_currency($_SESSION['cart']['total'])
    ];
}

/**
 * Handle update cart action
 */
function handle_update_cart()
{
    // Validate required fields
    if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
        return [
            'success' => false,
            'message' => 'Missing required fields'
        ];
    }

    $product_id = (int) sanitize_input($_POST['product_id'], 'int');
    $quantity = (int) sanitize_input($_POST['quantity'], 'int');

    if ($product_id <= 0) {
        return [
            'success' => false,
            'message' => 'Invalid product'
        ];
    }

    // Find item in cart
    $cart_items = $_SESSION['cart']['items'];
    $item_index = -1;

    foreach ($cart_items as $index => $item) {
        if ($item['id'] == $product_id) {
            $item_index = $index;
            break;
        }
    }

    if ($item_index < 0) {
        return [
            'success' => false,
            'message' => 'Product not found in cart'
        ];
    }

    // Handle quantity
    if ($quantity <= 0) {
        // Remove item if quantity is 0 or negative
        return handle_remove_item($product_id);
    }

    // Check stock availability
    global $db;
    $product = get_product_by_id($product_id);

    if (!$product) {
        return [
            'success' => false,
            'message' => 'Product not found'
        ];
    }

    if ($product['stock'] < $quantity) {
        return [
            'success' => false,
            'message' => 'Insufficient stock. Only ' . $product['stock'] . ' available'
        ];
    }

    // Update quantity
    $_SESSION['cart']['items'][$item_index]['quantity'] = $quantity;

    // Update cart totals
    update_cart_totals();

    return [
        'success' => true,
        'message' => 'Cart updated',
        'cart_count' => $_SESSION['cart']['count'],
        'cart_total' => format_currency($_SESSION['cart']['total']),
        'item_total' => format_currency($product['price'] * $quantity)
    ];
}

/**
 * Handle remove from cart action
 */
function handle_remove_from_cart()
{
    // Validate required fields
    if (!isset($_POST['product_id'])) {
        return [
            'success' => false,
            'message' => 'Missing product ID'
        ];
    }

    $product_id = (int) sanitize_input($_POST['product_id'], 'int');

    return handle_remove_item($product_id);
}

/**
 * Handle clear cart action
 */
function handle_clear_cart()
{
    $_SESSION['cart'] = [
        'items' => [],
        'total' => 0,
        'count' => 0
    ];

    return [
        'success' => true,
        'message' => 'Cart cleared',
        'cart_count' => 0,
        'cart_total' => format_currency(0)
    ];
}

/**
 * Handle get cart action
 */
function handle_get_cart()
{
    $cart_data = [
        'items' => $_SESSION['cart']['items'],
        'total' => $_SESSION['cart']['total'],
        'count' => $_SESSION['cart']['count']
    ];

    return [
        'success' => true,
        'message' => 'Cart data retrieved',
        'cart' => $cart_data
    ];
}

/**
 * Remove item from cart
 */
function handle_remove_item($product_id)
{
    $cart_items = $_SESSION['cart']['items'];
    $new_items = [];

    foreach ($cart_items as $item) {
        if ($item['id'] != $product_id) {
            $new_items[] = $item;
        }
    }

    $_SESSION['cart']['items'] = $new_items;

    // Update cart totals
    update_cart_totals();

    return [
        'success' => true,
        'message' => 'Product removed from cart',
        'cart_count' => $_SESSION['cart']['count'],
        'cart_total' => format_currency($_SESSION['cart']['total'])
    ];
}

/**
 * Update cart totals
 */
function update_cart_totals()
{
    $total = 0;
    $count = 0;

    foreach ($_SESSION['cart']['items'] as $item) {
        $item_total = $item['price'] * $item['quantity'];
        $total += $item_total;
        $count += $item['quantity'];
    }

    $_SESSION['cart']['total'] = $total;
    $_SESSION['cart']['count'] = $count;

    // Legacy convenience key used by some templates
    $_SESSION['cart_count'] = $count;
}

/**
 * Get product by ID from database
 */
// get_product_by_id removed (available in functions/utilities.php)

/**
 * Format currency
 */
// format_currency removed (available in functions/utilities.php)
?>