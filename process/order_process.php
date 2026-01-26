<?php
/* ============================================
   ORDER PROCESS - Procedural
   Handle order placement and processing
============================================ */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../config/constants.php';
    start_secure_session();
}

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/validation.php';
require_once __DIR__ . '/../functions/security.php';
require_once __DIR__ . '/../functions/request.php';
require_once __DIR__ . '/../functions/utilities.php';
require_once __DIR__ . '/../functions/cart.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect_with_message('user/login.php', 'error', 'Please login to place an order');
}

// Check if cart is empty
if (empty($_SESSION['cart']['items'])) {
    redirect_with_message('user/cart.php', 'error', 'Your cart is empty');
}

// Handle different request methods
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    process_order();
} else {
    show_order_page();
}

/**
 * Process order placement
 */
function process_order()
{
    // Get form data
    $shipping_address = request_post_string('shipping_address', 'string', '');
    $shipping_city = request_post_string('shipping_city', 'string', '');
    $shipping_state = request_post_string('shipping_state', 'string', '');
    $shipping_zip = request_post_string('shipping_zip', 'string', '');
    $shipping_country = request_post_string('shipping_country', 'string', '');
    $shipping_method = request_post_string('shipping_method', 'string', 'standard');

    $billing_same = request_post_bool('billing_same');

    if ($billing_same) {
        $billing_address = $shipping_address;
        $billing_city = $shipping_city;
        $billing_state = $shipping_state;
        $billing_zip = $shipping_zip;
        $billing_country = $shipping_country;
    } else {
        $billing_address = request_post_string('billing_address', 'string', '');
        $billing_city = request_post_string('billing_city', 'string', '');
        $billing_state = request_post_string('billing_state', 'string', '');
        $billing_zip = request_post_string('billing_zip', 'string', '');
        $billing_country = request_post_string('billing_country', 'string', '');
    }

    $payment_method = request_post_string('payment_method', 'string', '');
    $notes = request_post_string('notes', 'string', '');

    // Validation rules
    $validation_rules = [
        'shipping_address' => ['required', 'min_length:5', 'max_length:200'],
        'shipping_city' => ['required', 'min_length:2', 'max_length:50'],
        'shipping_state' => ['required', 'min_length:2', 'max_length:50'],
        'shipping_zip' => ['required', 'regex:/^[0-9\-]{5,10}$/'],
        'shipping_country' => ['required', 'min_length:2', 'max_length:50'],
        'shipping_method' => ['required', 'in:standard,express,priority'],
        'payment_method' => ['required', 'in:credit_card,paypal,cod'],
        'notes' => ['max_length:500']
    ];

    if (!$billing_same) {
        $validation_rules['billing_address'] = ['required', 'min_length:5', 'max_length:200'];
        $validation_rules['billing_city'] = ['required', 'min_length:2', 'max_length:50'];
        $validation_rules['billing_state'] = ['required', 'min_length:2', 'max_length:50'];
        $validation_rules['billing_zip'] = ['required', 'regex:/^[0-9\-]{5,10}$/'];
        $validation_rules['billing_country'] = ['required', 'min_length:2', 'max_length:50'];
    }

    // Validate form data (sanitized)
    $input = [
        'shipping_address' => $shipping_address,
        'shipping_city' => $shipping_city,
        'shipping_state' => $shipping_state,
        'shipping_zip' => $shipping_zip,
        'shipping_country' => $shipping_country,
        'shipping_method' => $shipping_method,
        'payment_method' => $payment_method,
        'notes' => $notes,
    ];
    if (!$billing_same) {
        $input['billing_address'] = $billing_address;
        $input['billing_city'] = $billing_city;
        $input['billing_state'] = $billing_state;
        $input['billing_zip'] = $billing_zip;
        $input['billing_country'] = $billing_country;
    }

    $validation_result = request_validate($input, $validation_rules, [
        'shipping_address' => 'string',
        'shipping_city' => 'string',
        'shipping_state' => 'string',
        'shipping_zip' => 'string',
        'shipping_country' => 'string',
        'shipping_method' => 'string',
        'billing_address' => 'string',
        'billing_city' => 'string',
        'billing_state' => 'string',
        'billing_zip' => 'string',
        'billing_country' => 'string',
        'payment_method' => 'string',
        'notes' => 'string',
    ]);

    if (!$validation_result['valid']) {
        $_SESSION['validation_errors'] = $validation_result['errors'];
        $_SESSION['form_data'] = $validation_result['data'];
        redirect('user/checkout.php');
    }

    // Validate cart items and stock
    $cart_items = $_SESSION['cart']['items'];
    $validated_items = [];
    $subtotal = 0;

    foreach ($cart_items as $item) {
        // Check product exists and is active
        $product = get_product_by_id($item['id']);

        if (!$product) {
            redirect_with_message('user/cart.php', 'error', 'Product "' . $item['name'] . '" is no longer available');
        }

        // Check stock
        if ($product['stock'] < $item['quantity']) {
            redirect_with_message('user/cart.php', 'error', 'Insufficient stock for "' . $item['name'] . '". Only ' . $product['stock'] . ' available');
        }

        $item_total = $product['price'] * $item['quantity'];
        $subtotal += $item_total;

        $validated_items[] = [
            'product_id' => $product['id'],
            'product_name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $item['quantity'],
            'total' => $item_total
        ];
    }

    // Calculate shipping cost
    $shipping_cost = calculate_shipping_cost($shipping_method, $subtotal);

    // Calculate tax (10% for example)
    $tax_rate = 0.10;
    $tax_amount = $subtotal * $tax_rate;

    // Calculate total
    $total_amount = $subtotal + $shipping_cost + $tax_amount;

    // Generate order number
    $order_number = generate_order_number($_SESSION['user_id']);

    // Start database transaction
    db_begin_transaction();

    try {
        // Insert order
        $query = "INSERT INTO orders (user_id, order_number, total_amount, subtotal, tax_amount, shipping_cost, 
                  shipping_method, shipping_address, shipping_city, shipping_state, shipping_zip, shipping_country,
                  billing_address, billing_city, billing_state, billing_zip, billing_country,
                  payment_method, payment_status, status, notes, created_at)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, NOW())";

        db_query($query);
        db_bind(1, $_SESSION['user_id']);
        db_bind(2, $order_number);
        db_bind(3, $total_amount);
        db_bind(4, $subtotal);
        db_bind(5, $tax_amount);
        db_bind(6, $shipping_cost);
        db_bind(7, $shipping_method);
        db_bind(8, $shipping_address);
        db_bind(9, $shipping_city);
        db_bind(10, $shipping_state);
        db_bind(11, $shipping_zip);
        db_bind(12, $shipping_country);
        db_bind(13, $billing_address);
        db_bind(14, $billing_city);
        db_bind(15, $billing_state);
        db_bind(16, $billing_zip);
        db_bind(17, $billing_country);
        db_bind(18, $payment_method);
        db_bind(19, $notes);

        if (!db_execute()) {
            throw new Exception('Failed to create order');
        }

        $order_id = db_last_insert_id();

        // Insert order items and update stock
        foreach ($validated_items as $item) {
            // Insert order item
            $query = "INSERT INTO order_items (order_id, product_id, product_name, price, quantity, total)
                      VALUES (?, ?, ?, ?, ?, ?)";
            db_query($query);
            db_bind(1, $order_id);
            db_bind(2, $item['product_id']);
            db_bind(3, $item['product_name']);
            db_bind(4, $item['price']);
            db_bind(5, $item['quantity']);
            db_bind(6, $item['total']);

            if (!db_execute()) {
                throw new Exception('Failed to add order item');
            }

            // Update product stock
            $query = "UPDATE products SET stock = stock - ?, updated_at = NOW() WHERE id = ?";
            db_query($query);
            db_bind(1, $item['quantity']);
            db_bind(2, $item['product_id']);

            if (!db_execute()) {
                throw new Exception('Failed to update product stock');
            }
        }

        // Commit transaction
        db_commit();

        // Clear cart
        $_SESSION['cart'] = [
            'items' => [],
            'total' => 0,
            'count' => 0
        ];

        // Clear persistent DB cart too
        if (!empty($_SESSION['user_id'])) {
            cart_db_clear((int) $_SESSION['user_id']);
        }

        // Clear form data
        unset($_SESSION['form_data']);
        unset($_SESSION['validation_errors']);

        // Send order confirmation email
        send_order_confirmation_email($order_id, $order_number, $total_amount);

        // Redirect to order confirmation page
        $_SESSION['order_id'] = $order_id;
        redirect_with_message('user/order_success.php', 'success', 'Order placed successfully!');

    } catch (Exception $e) {
        // Rollback transaction on error
        db_rollback();

        // Log error
        error_log('Order processing error: ' . $e->getMessage());

        redirect_with_message('user/checkout.php', 'error', 'Failed to process order. ' . $e->getMessage());
    }
}

/**
 * Show order page
 */
function show_order_page()
{
    // This function would normally render the checkout page
    // For process file, we just redirect to checkout
    redirect('user/checkout.php');
}

/**
 * Calculate shipping cost
 */
function calculate_shipping_cost($method, $subtotal)
{
    $costs = [
        'standard' => 5.99,
        'express' => 12.99,
        'priority' => 8.99
    ];

    $base_cost = $costs[$method] ?? $costs['standard'];

    // Free shipping for orders over $50
    if ($subtotal > 50) {
        return 0;
    }

    return $base_cost;
}

/**
 * Get product by ID
 */
// get_product_by_id removed (available in functions/utilities.php)

/**
 * Send order confirmation email
 */
function send_order_confirmation_email($order_id, $order_number, $total_amount)
{
    $user_email = $_SESSION['user_email'] ?? '';
    $user_name = $_SESSION['user_name'] ?? 'Customer';

    if (empty($user_email)) {
        return;
    }

    $site_name = SITE_NAME;
    $site_url = SITE_URL;

    $subject = "Order Confirmation #$order_number";

    $message = "
        <h2>Thank you for your order!</h2>
        <p>Dear $user_name,</p>
        <p>Your order has been received and is being processed.</p>
        <p><strong>Order Number:</strong> $order_number</p>
        <p><strong>Order Total:</strong> $" . number_format($total_amount, 2) . "</p>
        <p>You can track your order status by logging into your account.</p>
        <p><a href='{$site_url}user/orders.php'>View Your Orders</a></p>
        <p>If you have any questions, please contact our support team.</p>
        <p>Thank you for shopping with $site_name!</p>
    ";

    send_email($user_email, $subject, $message);
}
?>