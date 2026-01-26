<?php
/* ============================================
   SHOPPING CART PAGE - Enhanced Version
   View and manage cart items
============================================ */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../config/constants.php';
    require_once __DIR__ . '/../functions/security.php';
    start_secure_session();
}

// Include required files
require_once __DIR__ . '/../functions/utilities.php';
require_once __DIR__ . '/../config/database.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [
        'items' => [],
        'total' => 0,
        'count' => 0
    ];
}

$cart_items = $_SESSION['cart']['items'] ?? [];
$cart_total = $_SESSION['cart']['total'] ?? 0;

$page_title = 'Shopping Cart';
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container section-padding">
    <div class="page-header-user">
        <h1><i class="fas fa-shopping-cart"></i> Shopping Cart</h1>
        <p><?php echo count($cart_items); ?> item(s) in your cart</p>
    </div>

    <?php if (empty($cart_items)): ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <h3>Your cart is empty</h3>
            <p>Add some products to get started!</p>
            <a href="<?php echo SITE_URL; ?>products/all.php" class="btn btn-primary">
                <i class="fas fa-shopping-bag"></i> Continue Shopping
            </a>
        </div>
    <?php else: ?>
        <div class="cart-container">
            <!-- Cart Items -->
            <div class="cart-items-section">
                <div class="cart-items-header">
                    <h2>Cart Items</h2>
                    <button class="btn btn-outline btn-sm clear-cart">
                        <i class="fas fa-trash"></i> Clear Cart
                    </button>
                </div>

                <div class="cart-items-list">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item" data-product-id="<?php echo $item['id']; ?>">
                            <div class="item-image">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>"
                                    alt="<?php echo htmlspecialchars($item['name']); ?>"
                                    onerror="this.src='<?php echo SITE_URL; ?>assets/images/products/default.jpg'">
                            </div>

                            <div class="item-details">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <div class="item-price">
                                    $<?php echo number_format($item['price'], 2); ?> each
                                </div>
                            </div>

                            <div class="item-quantity">
                                <label>Quantity:</label>
                                <div class="quantity-controls">
                                    <button class="qty-btn quantity-btn" data-action="decrease"
                                        data-product-id="<?php echo $item['id']; ?>">-</button>
                                    <input type="number" value="<?php echo $item['quantity']; ?>" min="1"
                                        max="<?php echo $item['stock'] ?? 99; ?>" data-product-id="<?php echo $item['id']; ?>"
                                        class="qty-input quantity-input">
                                    <button class="qty-btn quantity-btn" data-action="increase"
                                        data-product-id="<?php echo $item['id']; ?>">+</button>
                                </div>
                            </div>

                            <div class="item-subtotal">
                                <div class="subtotal-label">Subtotal:</div>
                                <div class="subtotal-amount">
                                    $<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                            </div>

                            <button class="item-remove remove-item" data-product-id="<?php echo $item['id']; ?>" title="Remove">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Cart Summary -->
            <aside class="cart-summary">
                <div class="summary-card">
                    <h3>Order Summary</h3>

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
                        <span>$<?php echo number_format($cart_total * 0.08, 2); ?></span>
                    </div>

                    <div class="summary-divider"></div>

                    <div class="summary-row summary-total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($cart_total * 1.08, 2); ?></span>
                    </div>

                    <a href="<?php echo SITE_URL; ?>user/checkout.php" class="btn btn-primary w-100">
                        <i class="fas fa-lock"></i> Proceed to Checkout
                    </a>

                    <a href="<?php echo SITE_URL; ?>products/all.php" class="btn btn-outline w-100"
                        style="margin-top: var(--space-sm);">
                        <i class="fas fa-arrow-left"></i> Continue Shopping
                    </a>

                    <div class="secure-checkout">
                        <i class="fas fa-shield-alt"></i>
                        <span>Secure Checkout</span>
                    </div>
                </div>

                <!-- Coupon Code -->
                <div class="coupon-card">
                    <h4>Have a coupon?</h4>
                    <form onsubmit="applyCoupon(event)" class="coupon-form">
                        <input type="text" placeholder="Enter coupon code" class="form-control">
                        <button type="submit" class="btn btn-outline btn-sm">Apply</button>
                    </form>
                </div>
            </aside>
        </div>
    <?php endif; ?>
</div>

<style>
    .page-header-user {
        text-align: center;
        margin-bottom: var(--space-3xl);
    }

    .page-header-user h1 {
        font-size: 2.5rem;
        margin-bottom: var(--space-sm);
    }

    .page-header-user i {
        color: var(--primary-color);
    }

    .empty-cart {
        text-align: center;
        padding: var(--space-3xl);
        background: var(--white);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-md);
    }

    .empty-cart i {
        font-size: 6rem;
        color: var(--text-muted);
        margin-bottom: var(--space-lg);
    }

    .empty-cart h3 {
        font-size: 1.75rem;
        margin-bottom: var(--space-sm);
    }

    .cart-container {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: var(--space-xl);
    }

    .cart-items-section {
        background: var(--white);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-md);
        padding: var(--space-xl);
    }

    .cart-items-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: var(--space-lg);
        padding-bottom: var(--space-lg);
        border-bottom: 2px solid var(--light-200);
    }

    .cart-items-list {
        display: flex;
        flex-direction: column;
        gap: var(--space-lg);
    }

    .cart-item {
        display: grid;
        grid-template-columns: 120px 1fr auto auto auto;
        gap: var(--space-lg);
        align-items: center;
        padding: var(--space-lg);
        background: var(--light-100);
        border-radius: var(--radius-md);
        position: relative;
        transition: all var(--transition-normal);
    }

    .cart-item:hover {
        box-shadow: var(--shadow-sm);
    }

    .item-image img {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: var(--radius-md);
    }

    .item-details h3 {
        font-size: 1.1rem;
        margin-bottom: var(--space-xs);
    }

    .item-price {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--primary-color);
        margin-top: var(--space-sm);
    }

    .item-quantity {
        text-align: center;
    }

    .quantity-controls {
        display: flex;
        align-items: center;
        gap: 4px;
        margin: var(--space-sm) 0;
    }

    .qty-btn {
        width: 32px;
        height: 32px;
        border: 2px solid var(--primary-color);
        background: var(--white);
        color: var(--primary-color);
        border-radius: var(--radius-sm);
        cursor: pointer;
        font-weight: 600;
        transition: all var(--transition-fast);
    }

    .qty-btn:hover {
        background: var(--primary-color);
        color: var(--white);
    }

    .qty-input {
        width: 60px;
        height: 32px;
        text-align: center;
        border: 2px solid var(--light-300);
        border-radius: var(--radius-sm);
        font-weight: 600;
    }

    .item-subtotal {
        text-align: right;
    }

    .subtotal-label {
        font-size: 0.875rem;
        color: var(--text-muted);
        margin-bottom: 4px;
    }

    .subtotal-amount {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--primary-color);
    }

    .item-remove {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 32px;
        height: 32px;
        border: none;
        background: var(--danger-color);
        color: var(--white);
        border-radius: 50%;
        cursor: pointer;
        transition: all var(--transition-fast);
    }

    .item-remove:hover {
        transform: scale(1.1);
    }

    .cart-summary {
        position: sticky;
        top: 100px;
        height: fit-content;
    }

    .summary-card,
    .coupon-card {
        background: var(--white);
        border-radius: var(--radius-lg);
        padding: var(--space-xl);
        box-shadow: var(--shadow-md);
        margin-bottom: var(--space-lg);
    }

    .summary-card h3 {
        margin-bottom: var(--space-lg);
        font-size: 1.5rem;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        font-size: 1rem;
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

    .coupon-form {
        display: flex;
        gap: var(--space-sm);
        margin-top: var(--space-md);
    }

    .coupon-form input {
        flex: 1;
    }

    @media (max-width: 1024px) {
        .cart-container {
            grid-template-columns: 1fr;
        }

        .cart-summary {
            position: relative;
            top: 0;
        }

        .cart-item {
            grid-template-columns: 1fr;
            gap: var(--space-md);
        }

        .item-image {
            text-align: center;
        }

        .item-subtotal,
        .item-quantity {
            text-align: left;
        }
    }
</style>

<script>
    function applyCoupon(event) {
        event.preventDefault();
        const couponCode = event.target.querySelector('input').value;
        alert('Coupon feature coming soon! Code: ' + couponCode);
    }
</script>

<?php
include_once __DIR__ . '/../includes/footer.php';
?>