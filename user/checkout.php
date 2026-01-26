<?php
/* ============================================
   CHECKOUT PAGE - Enhanced Version
   Multi-step checkout process
============================================ */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../config/constants.php';
    require_once __DIR__ . '/../functions/security.php';
    start_secure_session();
}

// Include required files
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/utilities.php';
require_once __DIR__ . '/../functions/validation.php';

// Require login for checkout
require_login();

$user_id = get_user_id();
$user = get_user_by_id($user_id);

// Check if cart is empty
if (empty($_SESSION['cart']) || (isset($_SESSION['cart']['items']) && empty($_SESSION['cart']['items']))) {
    redirect_with_message(SITE_URL . 'user/cart.php', 'error', 'Your cart is empty!');
}

// Get cart items (supports both the newer structured cart and older legacy format)
$cart_items = [];
$cart_total = 0;

if (isset($_SESSION['cart']['items']) && is_array($_SESSION['cart']['items'])) {
    foreach ($_SESSION['cart']['items'] as $item) {
        $qty = (int) ($item['quantity'] ?? 0);
        if ($qty <= 0) {
            continue;
        }

        $product = [
            'id' => (int) ($item['id'] ?? 0),
            'name' => (string) ($item['name'] ?? ''),
            'price' => (float) ($item['price'] ?? 0),
            'image_url' => (string) ($item['image'] ?? ''),
        ];

        $subtotal = $product['price'] * $qty;
        $cart_total += $subtotal;

        $cart_items[] = [
            'product' => $product,
            'quantity' => $qty,
            'subtotal' => $subtotal
        ];
    }
} else if (is_array($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    // Legacy cart: product_id => quantity
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));

    $query = "SELECT * FROM products WHERE id IN ($placeholders)";
    db_query($query);

    $index = 1;
    foreach ($product_ids as $id) {
        db_bind($index++, $id);
    }

    $products = db_result_set();

    foreach ($products as $product) {
        $quantity = (int) ($_SESSION['cart'][$product['id']] ?? 0);
        if ($quantity <= 0) {
            continue;
        }

        // Normalize image_url for the template
        if (!isset($product['image_url'])) {
            $product['image_url'] = $product['image'] ?? '';
        }

        $subtotal = $product['price'] * $quantity;
        $cart_total += $subtotal;

        $cart_items[] = [
            'product' => $product,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
    }
}

if (empty($cart_items)) {
    redirect_with_message(SITE_URL . 'user/cart.php', 'error', 'Your cart is empty!');
}

// Calculate totals
$shipping = 0; // Free shipping
$tax_rate = 0.08;
$tax_amount = $cart_total * $tax_rate;
$total_amount = $cart_total + $shipping + $tax_amount;

function luhn_is_valid($number)
{
    $number = preg_replace('/\D+/', '', (string) $number);
    if ($number === '' || strlen($number) < 13 || strlen($number) > 19) {
        return false;
    }

    $sum = 0;
    $alt = false;
    for ($i = strlen($number) - 1; $i >= 0; $i--) {
        $n = (int) $number[$i];
        if ($alt) {
            $n *= 2;
            if ($n > 9) {
                $n -= 9;
            }
        }
        $sum += $n;
        $alt = !$alt;
    }
    return ($sum % 10) === 0;
}

function parse_expiry_mm_yy($value)
{
    $value = trim((string) $value);
    if (!preg_match('/^(\d{2})\/(\d{2})$/', $value, $m)) {
        return null;
    }
    $mm = (int) $m[1];
    $yy = (int) $m[2];
    if ($mm < 1 || $mm > 12) {
        return null;
    }
    // interpret as 20YY
    $year = 2000 + $yy;
    return [$mm, $year];
}

$order_success = false;
$order_id = null;
$payment_summary = null;

// Handle checkout submission
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {

    // Process checkout
    $shipping_address = sanitize_input($_POST['shipping_address'] ?? '', 'string');
    $shipping_city = sanitize_input($_POST['shipping_city'] ?? '', 'string');
    $shipping_state = sanitize_input($_POST['shipping_state'] ?? '', 'string');
    $shipping_zip = sanitize_input($_POST['shipping_zip'] ?? '', 'string');
    $phone = sanitize_input($_POST['phone'] ?? '', 'string');
    $payment_method = sanitize_input($_POST['payment_method'] ?? '', 'string');

    if ($shipping_address === '' || $shipping_city === '' || $shipping_state === '' || $shipping_zip === '' || $phone === '') {
        $errors[] = 'Please complete your shipping information.';
    }

    $phone_digits = preg_replace('/\D+/', '', (string) $phone);
    if ($phone !== '' && (strlen($phone_digits) < 10 || strlen($phone_digits) > 15)) {
        $errors[] = 'Please enter a valid phone number.';
    }

    $allowed_methods = ['credit_card', 'paypal', 'cod'];
    if (!in_array($payment_method, $allowed_methods, true)) {
        $errors[] = 'Invalid payment method.';
    }

    $order_status = ($payment_method === 'cod') ? 'pending' : 'completed';

    // Test payment simulation for credit/debit card
    $card_last4 = null;
    if ($payment_method === 'credit_card') {
        $card_number = (string) ($_POST['card_number'] ?? '');
        $card_name = trim((string) ($_POST['card_name'] ?? ''));
        $expiry_date = (string) ($_POST['expiry_date'] ?? '');
        $cvv = (string) ($_POST['cvv'] ?? '');

        $digits = preg_replace('/\D+/', '', $card_number);
        if (!luhn_is_valid($digits)) {
            $errors[] = 'Invalid card number (test).';
        }
        if ($card_name === '' || strlen($card_name) < 3) {
            $errors[] = 'Cardholder name is required.';
        }
        $parsed = parse_expiry_mm_yy($expiry_date);
        if ($parsed === null) {
            $errors[] = 'Expiry date must be in MM/YY format.';
        } else {
            [$mm, $year] = $parsed;
            $nowY = (int) date('Y');
            $nowM = (int) date('n');
            if ($year < $nowY || ($year === $nowY && $mm < $nowM)) {
                $errors[] = 'Card is expired.';
            }
        }
        if (!preg_match('/^\d{3,4}$/', preg_replace('/\s+/', '', $cvv))) {
            $errors[] = 'CVV must be 3 or 4 digits.';
        }
        if ($digits !== '') {
            $card_last4 = substr($digits, -4);
        }
    }

    // PayPal simulation: require a valid PayPal email
    if ($payment_method === 'paypal') {
        $paypal_email = trim((string) ($_POST['paypal_email'] ?? ''));
        if ($paypal_email === '' || !filter_var($paypal_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid PayPal email.';
        }
    }

    // Cash on delivery: require a contact phone (can reuse shipping phone)
    if ($payment_method === 'cod') {
        $cod_phone = trim((string) ($_POST['cod_phone'] ?? ''));
        $cod_digits = preg_replace('/\D+/', '', $cod_phone);
        if ($cod_phone === '' || strlen($cod_digits) < 10 || strlen($cod_digits) > 15) {
            $errors[] = 'Please enter a valid phone number for cash on delivery.';
        }
    }

    if (empty($errors)) {
        // Create order (test simulation; no real payment gateway)
        db_begin_transaction();

        try {
            // Insert order
            $order_query = "INSERT INTO orders (user_id, total, status, created_at) VALUES (?, ?, ?, NOW())";
            db_query($order_query);
            db_bind(1, $user_id);
            db_bind(2, $total_amount);
            db_bind(3, $order_status);
            db_execute();

            $order_id = db_last_insert_id();

            // Insert order items
            foreach ($cart_items as $item) {
                $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                db_query($item_query);
                db_bind(1, $order_id);
                db_bind(2, $item['product']['id']);
                db_bind(3, $item['quantity']);
                db_bind(4, $item['product']['price']);
                db_execute();
            }

            db_commit();

            // Clear cart
            $_SESSION['cart'] = [
                'items' => [],
                'total' => 0,
                'count' => 0
            ];
            $_SESSION['cart_count'] = 0;

            $order_success = true;
            $payment_summary = [
                'method' => $payment_method,
                'status' => $order_status,
                'last4' => $card_last4,
                'total' => $total_amount
            ];
        } catch (Exception $e) {
            db_rollback();
            $errors[] = 'Failed to process order. Please try again.';
        }
    }
}

$page_title = 'Checkout';
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container section-padding">
    <div class="checkout-header">
        <h1><i class="fas fa-lock"></i> Secure Checkout</h1>
        <div class="checkout-steps">
            <div class="step active">
                <i class="fas fa-shopping-cart"></i>
                <span>Cart</span>
            </div>
            <div class="step active">
                <i class="fas fa-shipping-fast"></i>
                <span>Shipping</span>
            </div>
            <div class="step active">
                <i class="fas fa-credit-card"></i>
                <span>Payment</span>
            </div>
            <div class="step">
                <i class="fas fa-check-circle"></i>
                <span>Complete</span>
            </div>
        </div>
    </div>

    <?php if ($order_success): ?>
        <div class="alert alert-success" style="margin-bottom: var(--space-xl);">
            <strong>Payment processed (test).</strong>
            Your order was placed successfully. Order #<?php echo (int) $order_id; ?>
            <div style="margin-top: 10px; color: var(--text-muted);">
                Method: <?php echo htmlspecialchars($payment_summary['method']); ?>,
                Status: <?php echo htmlspecialchars($payment_summary['status']); ?>,
                Total: $<?php echo number_format((float) $payment_summary['total'], 2); ?>
                <?php if (!empty($payment_summary['last4'])): ?>
                    , Card: **** <?php echo htmlspecialchars($payment_summary['last4']); ?>
                <?php endif; ?>
            </div>
            <div style="margin-top: 14px; display:flex; gap:10px; flex-wrap:wrap;">
                <a class="btn btn-primary" href="<?php echo SITE_URL; ?>user/orders.php">Go to My Orders</a>
                <a class="btn btn-outline" href="<?php echo SITE_URL; ?>products/all.php">Continue Shopping</a>
            </div>
        </div>
    <?php elseif (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!$order_success): ?>
    <form method="POST" action="" class="checkout-form">

        <div class="checkout-container">
            <!-- Checkout Details -->
            <div class="checkout-main">
                <!-- Shipping Information -->
                <div class="checkout-section">
                    <h2><i class="fas fa-shipping-fast"></i> Shipping Information</h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="shipping_address">Street Address *</label>
                            <input type="text" id="shipping_address" name="shipping_address" class="form-control"
                                value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="shipping_city">City *</label>
                            <input type="text" id="shipping_city" name="shipping_city" class="form-control"
                                value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="shipping_state">State *</label>
                            <input type="text" id="shipping_state" name="shipping_state" class="form-control"
                                value="<?php echo htmlspecialchars($user['state'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="shipping_zip">ZIP Code *</label>
                            <input type="text" id="shipping_zip" name="shipping_zip" class="form-control"
                                value="<?php echo htmlspecialchars($user['zip'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" class="form-control"
                                value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="checkout-section">
                    <h2><i class="fas fa-credit-card"></i> Payment Method</h2>

                    <div class="alert" style="background: rgba(79, 70, 229, 0.08); border: 1px solid var(--light-200);">
                        <strong>Test payment only.</strong>
                        Do not enter a real card. Use this test card:
                        <div style="margin-top: 6px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;">
                            Card: 4242 4242 4242 4242 &nbsp; Name: Test User &nbsp; Exp: 12/30 &nbsp; CVV: 123
                        </div>
                    </div>

                    <div class="payment-methods">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="credit_card" checked>
                            <div class="payment-card">
                                <i class="fas fa-credit-card"></i>
                                <span>Credit/Debit Card</span>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="paypal">
                            <div class="payment-card">
                                <i class="fab fa-paypal"></i>
                                <span>PayPal</span>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="cod">
                            <div class="payment-card">
                                <i class="fas fa-money-bill-wave"></i>
                                <span>Cash on Delivery</span>
                            </div>
                        </label>
                    </div>

                    <div id="card-details" class="card-payment-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="card_number">Card Number</label>
                                <input type="text" id="card_number" name="card_number" class="form-control"
                                    placeholder="1234 5678 9012 3456" maxlength="19">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="card_name">Cardholder Name</label>
                                <input type="text" id="card_name" name="card_name" class="form-control" placeholder="Test User">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="expiry_date">Expiry Date</label>
                                <input type="text" id="expiry_date" name="expiry_date" class="form-control" placeholder="MM/YY"
                                    maxlength="5">
                            </div>

                            <div class="form-group">
                                <label for="cvv">CVV</label>
                                <input type="text" id="cvv" name="cvv" class="form-control" placeholder="123" maxlength="4">
                            </div>
                        </div>
                    </div>

                    <div id="paypal-details" class="card-payment-form" style="display:none;">
                        <div class="form-row">
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="paypal_email">PayPal Email</label>
                                <input type="email" id="paypal_email" name="paypal_email" class="form-control"
                                    placeholder="name@example.com" value="<?php echo htmlspecialchars($_POST['paypal_email'] ?? ($user['email'] ?? '')); ?>">
                            </div>
                        </div>
                    </div>

                    <div id="cod-details" class="card-payment-form" style="display:none;">
                        <div class="form-row">
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="cod_phone">Cash on Delivery Phone</label>
                                <input type="tel" id="cod_phone" name="cod_phone" class="form-control"
                                    placeholder="Your phone number" value="<?php echo htmlspecialchars($_POST['cod_phone'] ?? ($user['phone'] ?? '')); ?>">
                                <small class="text-muted">We will call to confirm delivery.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <aside class="checkout-summary">
                <div class="summary-card">
                    <h3>Order Summary</h3>

                    <div class="summary-items">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="summary-item">
                                <img src="<?php echo htmlspecialchars($item['product']['image_url']); ?>"
                                    alt="<?php echo htmlspecialchars($item['product']['name']); ?>">
                                <div class="item-info">
                                    <div class="item-name"><?php echo htmlspecialchars($item['product']['name']); ?></div>
                                    <div class="item-qty">Qty: <?php echo $item['quantity']; ?></div>
                                </div>
                                <div class="item-price">$<?php echo number_format($item['subtotal'], 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-divider"></div>

                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($cart_total, 2); ?></span>
                    </div>

                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span class="text-success">FREE</span>
                    </div>

                    <div class="summary-row">
                        <span>Tax (8%):</span>
                        <span>$<?php echo number_format($tax_amount, 2); ?></span>
                    </div>

                    <div class="summary-divider"></div>

                    <div class="summary-row summary-total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($total_amount, 2); ?></span>
                    </div>

                    <button type="submit" name="place_order" class="btn btn-primary w-100">
                        <i class="fas fa-lock"></i> Place Order
                    </button>

                    <div class="secure-checkout">
                        <i class="fas fa-shield-alt"></i>
                        <span>256-bit SSL Secure Checkout</span>
                    </div>
                </div>
            </aside>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
    // Show/hide card fields based on payment selection
    (function() {
        const cardBox = document.getElementById('card-details');
        const paypalBox = document.getElementById('paypal-details');
        const codBox = document.getElementById('cod-details');
        const radios = document.querySelectorAll('input[name="payment_method"]');
        const cardInputs = ['card_number','card_name','expiry_date','cvv'].map(id => document.getElementById(id)).filter(Boolean);
        const paypalInput = document.getElementById('paypal_email');
        const codInput = document.getElementById('cod_phone');

        function refresh() {
            const selected = document.querySelector('input[name="payment_method"]:checked')?.value || 'credit_card';
            const showCard = selected === 'credit_card';
            const showPaypal = selected === 'paypal';
            const showCod = selected === 'cod';

            if (cardBox) cardBox.style.display = showCard ? 'block' : 'none';
            if (paypalBox) paypalBox.style.display = showPaypal ? 'block' : 'none';
            if (codBox) codBox.style.display = showCod ? 'block' : 'none';

            cardInputs.forEach(function(input) {
                // Required only for credit card simulation
                input.required = showCard;
            });

            if (paypalInput) paypalInput.required = showPaypal;
            if (codInput) codInput.required = showCod;
        }

        radios.forEach(r => r.addEventListener('change', refresh));
        refresh();
    })();
</script>

<style>
    .checkout-header {
        text-align: center;
        margin-bottom: var(--space-3xl);
    }

    .checkout-header h1 {
        font-size: 2.5rem;
        margin-bottom: var(--space-xl);
    }

    .checkout-steps {
        display: flex;
        justify-content: center;
        gap: var(--space-xl);
        max-width: 600px;
        margin: 0 auto;
    }

    .step {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: var(--space-sm);
        color: var(--text-muted);
        opacity: 0.5;
    }

    .step.active {
        color: var(--primary-color);
        opacity: 1;
    }

    .step i {
        font-size: 2rem;
    }

    .checkout-container {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: var(--space-xl);
    }

    .checkout-main {
        display: flex;
        flex-direction: column;
        gap: var(--space-xl);
    }

    .checkout-section {
        background: var(--white);
        border-radius: var(--radius-lg);
        padding: var(--space-xl);
        box-shadow: var(--shadow-md);
    }

    .checkout-section h2 {
        margin-bottom: var(--space-lg);
        font-size: 1.5rem;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .checkout-section h2 i {
        color: var(--primary-color);
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--space-lg);
    }

    .payment-methods {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: var(--space-md);
        margin-bottom: var(--space-lg);
    }

    .payment-option {
        cursor: pointer;
    }

    .payment-option input[type="radio"] {
        display: none;
    }

    .payment-card {
        padding: var(--space-lg);
        border: 2px solid var(--light-300);
        border-radius: var(--radius-md);
        text-align: center;
        transition: all var(--transition-fast);
    }

    .payment-card i {
        font-size: 2rem;
        margin-bottom: var(--space-sm);
        display: block;
    }

    .payment-option input[type="radio"]:checked+.payment-card {
        border-color: var(--primary-color);
        background: rgba(67, 97, 238, 0.1);
    }

    .payment-option:hover .payment-card {
        border-color: var(--primary-color);
    }

    .card-payment-form {
        margin-top: var(--space-lg);
        padding-top: var(--space-lg);
        border-top: 1px solid var(--light-200);
    }

    .checkout-summary {
        position: sticky;
        top: 100px;
        height: fit-content;
    }

    .summary-card {
        background: var(--white);
        border-radius: var(--radius-lg);
        padding: var(--space-xl);
        box-shadow: var(--shadow-md);
    }

    .summary-card h3 {
        margin-bottom: var(--space-lg);
    }

    .summary-items {
        max-height: 300px;
        overflow-y: auto;
        margin-bottom: var(--space-lg);
    }

    .summary-item {
        display: flex;
        align-items: center;
        gap: var(--space-md);
        padding: var(--space-sm) 0;
    }

    .summary-item img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: var(--radius-sm);
    }

    .item-info {
        flex: 1;
    }

    .item-name {
        font-weight: 500;
        margin-bottom: 4px;
    }

    .item-qty {
        font-size: 0.85rem;
        color: var(--text-muted);
    }

    .item-price {
        font-weight: 600;
        color: var(--primary-color);
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
    }

    .summary-total {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--primary-color);
    }

    .summary-divider {
        height: 2px;
        background: var(--light-200);
        margin: var(--space-md) 0;
    }

    .secure-checkout {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-top: var(--space-lg);
        padding-top: var(--space-lg);
        border-top: 1px solid var(--light-200);
        color: var(--success-color);
        font-size: 0.9rem;
    }

    .alert {
        padding: 16px 20px;
        border-radius: var(--radius-md);
        margin-bottom: var(--space-lg);
    }

    .alert-danger {
        background: rgba(249, 65, 68, 0.1);
        border: 1px solid var(--danger-color);
        color: var(--danger-dark);
    }

    @media (max-width: 1024px) {
        .checkout-container {
            grid-template-columns: 1fr;
        }

        .checkout-summary {
            position: relative;
            top: 0;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .payment-methods {
            grid-template-columns: 1fr;
        }

        .checkout-steps {
            gap: var(--space-sm);
        }

        .step span {
            font-size: 0.75rem;
        }
    }
</style>

<?php
include_once __DIR__ . '/../includes/footer.php';
?>