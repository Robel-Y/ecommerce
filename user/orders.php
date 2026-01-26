<?php
/* ============================================
   USER ORDERS PAGE - Enhanced Version
   View order history and track orders
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

// Require login
require_login();

$page_title = 'My Orders';
$user_id = get_user_id();

// Pagination
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total orders count
$count_query = "SELECT COUNT(*) as total FROM orders WHERE user_id = :user_id";
db_query($count_query);
db_bind(':user_id', $user_id);
$total_orders = db_single()['total'] ?? 0;

// Get user orders
$query = "SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
db_query($query);
db_bind(':user_id', $user_id);
$orders = db_result_set();

// Generate pagination
$pagination = generate_pagination($total_orders, $page, $per_page, SITE_URL . 'user/orders.php');

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container section-padding">
    <div class="page-header-user">
        <h1><i class="fas fa-box"></i> My Orders</h1>
        <p>View and track your order history</p>
    </div>

    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <i class="fas fa-shopping-bag"></i>
            <h3>No Orders Yet</h3>
            <p>You haven't placed any orders. Start shopping now!</p>
            <a href="<?php echo SITE_URL; ?>products/all.php" class="btn btn-primary">
                <i class="fas fa-shopping-cart"></i> Browse Products
            </a>
        </div>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-info">
                            <h3>Order #<?php echo $order['id']; ?></h3>
                            <p class="text-muted">
                                Placed on <?php echo format_datetime($order['created_at']); ?>
                            </p>
                        </div>
                        <div class="order-status">
                            <?php
                            $status_class = 'info';
                            if ($order['status'] === 'completed')
                                $status_class = 'success';
                            elseif ($order['status'] === 'cancelled')
                                $status_class = 'danger';
                            elseif ($order['status'] === 'processing')
                                $status_class = 'warning';
                            ?>
                            <span class="badge badge-<?php echo $status_class; ?> badge-lg">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="order-body">
                        <?php
                        // Get order items
                        $items_query = "SELECT oi.*, p.name, p.image AS image_url
                                FROM order_items oi 
                                JOIN products p ON oi.product_id = p.id 
                                WHERE oi.order_id = :order_id";
                        db_query($items_query);
                        db_bind(':order_id', $order['id']);
                        $order_items = db_result_set();
                        ?>

                        <div class="order-items">
                            <?php foreach ($order_items as $item): ?>
                                <div class="order-item">
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>"
                                        alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <div class="item-details">
                                        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                        <p class="text-muted">Quantity: <?php echo $item['quantity']; ?></p>
                                    </div>
                                    <div class="item-price">
                                        $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="order-footer">
                        <div class="order-total">
                            <strong>Total:</strong>
                            <span class="total-amount">$<?php echo number_format($order['total'], 2); ?></span>
                        </div>
                        <div class="order-actions">
                            <button type="button" class="btn btn-outline btn-sm btn-view-order" data-order-id="<?php echo (int) $order['id']; ?>">
                                <i class="fas fa-eye"></i> View Details
                            </button>
                        </div>
                    </div>

                    <div class="order-details" id="order-details-<?php echo (int) $order['id']; ?>" style="display:none;">
                        <div class="order-details-head">
                            <div>
                                <div class="order-details-title">Order #<?php echo (int) $order['id']; ?></div>
                                <div class="order-details-sub">Placed on <?php echo format_datetime($order['created_at']); ?></div>
                            </div>
                            <div class="order-details-total">$<?php echo number_format($order['total'], 2); ?></div>
                        </div>

                        <div class="order-details-items">
                            <?php foreach ($order_items as $item): ?>
                                <div class="order-details-item">
                                    <img class="order-details-img" src="<?php echo htmlspecialchars($item['image_url'] ?? ''); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <div class="order-details-meta">
                                        <div class="order-details-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <div class="order-details-line">
                                            <span>Unit: $<?php echo number_format((float) $item['price'], 2); ?></span>
                                            <span>Qty: <?php echo (int) $item['quantity']; ?></span>
                                        </div>
                                    </div>
                                    <div class="order-details-subtotal">
                                        $<?php echo number_format(((float) $item['price']) * ((int) $item['quantity']), 2); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="order-details-foot">
                            <div class="order-details-note">Status: <strong><?php echo htmlspecialchars($order['status']); ?></strong></div>
                            <div class="order-details-actions">
                                <a class="btn btn-primary btn-sm" href="<?php echo SITE_URL; ?>products/all.php">Shop More</a>
                                <button type="button" class="btn btn-outline btn-sm btn-hide-order" data-order-id="<?php echo (int) $order['id']; ?>">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="pagination-container">
                <?php echo get_pagination_html($pagination, SITE_URL . 'user/orders.php', 'page'); ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
    (function() {
        function toggle(orderId, forceOpen) {
            const panel = document.getElementById('order-details-' + orderId);
            const btn = document.querySelector('.btn-view-order[data-order-id="' + orderId + '"]');
            if (!panel) return;

            const isOpen = panel.style.display !== 'none';
            const nextOpen = (typeof forceOpen === 'boolean') ? forceOpen : !isOpen;
            panel.style.display = nextOpen ? 'block' : 'none';

            if (btn) {
                btn.innerHTML = nextOpen
                    ? '<i class="fas fa-eye-slash"></i> Hide Details'
                    : '<i class="fas fa-eye"></i> View Details';
            }

            if (nextOpen) {
                panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        document.querySelectorAll('.btn-view-order').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-order-id');
                if (id) toggle(id);
            });
        });

        document.querySelectorAll('.btn-hide-order').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-order-id');
                if (id) toggle(id, false);
            });
        });
    })();
</script>

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

    .empty-state {
        text-align: center;
        padding: var(--space-3xl);
        background: var(--white);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-md);
    }

    .empty-state i {
        font-size: 5rem;
        color: var(--text-muted);
        margin-bottom: var(--space-lg);
    }

    .empty-state h3 {
        font-size: 1.5rem;
        margin-bottom: var(--space-sm);
    }

    .orders-list {
        display: flex;
        flex-direction: column;
        gap: var(--space-lg);
    }

    .order-card {
        background: var(--white);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-md);
        overflow: hidden;
        transition: all var(--transition-normal);
    }

    .order-card:hover {
        box-shadow: var(--shadow-lg);
        transform: translateY(-2px);
    }

    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--space-lg);
        border-bottom: 1px solid var(--light-200);
        background: var(--light-100);
    }

    .order-header h3 {
        margin-bottom: 4px;
        font-size: 1.25rem;
    }

    .badge-lg {
        padding: 8px 16px;
        font-size: 0.875rem;
    }

    .order-body {
        padding: var(--space-lg);
    }

    .order-items {
        display: flex;
        flex-direction: column;
        gap: var(--space-md);
    }

    .order-item {
        display: flex;
        align-items: center;
        gap: var(--space-md);
        padding: var(--space-md);
        background: var(--light-100);
        border-radius: var(--radius-md);
    }

    .order-item img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: var(--radius-sm);
    }

    .item-details {
        flex: 1;
    }

    .item-details h4 {
        margin-bottom: 4px;
        font-size: 1rem;
    }

    .item-price {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--primary-color);
    }

    .order-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--space-lg);
        border-top: 1px solid var(--light-200);
        background: var(--light-100);
    }

    .order-total {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .total-amount {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary-color);
    }

    .order-actions {
        display: flex;
        gap: var(--space-sm);
    }

    .pagination-container {
        margin-top: var(--space-xl);
        display: flex;
        justify-content: center;
    }

    @media (max-width: 768px) {

        .order-header,
        .order-footer {
            flex-direction: column;
            align-items: flex-start;
            gap: var(--space-md);
        }

        .order-actions {
            width: 100%;
        }

        .order-actions .btn {
            flex: 1;
        }
    }
</style>

<script>
    function viewOrderDetails(orderId) {
        showNotification('Order details view coming soon', 'info');
    }

    function reorder(orderId) {
        if (confirm('Add all items from this order to your cart?')) {
            fetch('<?php echo SITE_URL; ?>process/reorder_process.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'order_id=' + orderId
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Items added to cart!', 'success');
                        setTimeout(() => window.location.href = '<?php echo SITE_URL; ?>user/cart.php', 1500);
                    } else {
                        showNotification(data.message || 'Failed to reorder', 'error');
                    }
                })
                .catch(error => {
                    showNotification('An error occurred', 'error');
                });
        }
    }
</script>

<?php
// Include footer
include_once __DIR__ . '/../includes/footer.php';
?>