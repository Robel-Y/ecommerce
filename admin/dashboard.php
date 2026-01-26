<?php
/* ============================================
   ADMIN DASHBOARD - Main Overview Page
   Displays statistics, recent orders, and alerts
============================================ */

$page_title = 'Dashboard';

// Include admin header and sidebar
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/utilities.php';

// Get dashboard statistics
// Total Orders
db_query("SELECT COUNT(*) as total FROM orders");
$total_orders = (int) (db_single()['total'] ?? 0);

// Total Revenue
db_query("SELECT SUM(total) as revenue FROM orders WHERE status IN ('completed', 'processing')");
$row = db_single();
if ($row === false) {
    $row = [];
}
if (!array_key_exists('revenue', $row)) {
    // Fallback for older schemas
    db_query("SELECT SUM(total_amount) as revenue FROM orders WHERE status IN ('completed', 'processing')");
    $row = db_single() ?: [];
}
$total_revenue = (float) ($row['revenue'] ?? 0);

// Total Users
db_query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$total_users = (int) (db_single()['total'] ?? 0);

// Pending Orders
db_query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
$pending_orders = (int) (db_single()['total'] ?? 0);

// Low Stock Products
db_query("SELECT COUNT(*) as total FROM products WHERE stock < 10 AND stock > 0");
$low_stock_count = (int) (db_single()['total'] ?? 0);

// Recent Orders (Last 10)
$recent_orders = [];
if (db_query("SELECT o.*, u.name as customer_name, u.email as customer_email \
              FROM orders o \
              LEFT JOIN users u ON o.user_id = u.id \
              ORDER BY o.created_at DESC \
              LIMIT 10")) {
    $recent_orders = db_result_set();
}

// Top Selling Products
$top_products = [];
$top_products_query_image = "SELECT p.id, p.name, p.image, p.price, COALESCE(SUM(oi.quantity), 0) as units_sold \
                             FROM products p \
                             INNER JOIN order_items oi ON p.id = oi.product_id \
                             GROUP BY p.id \
                             ORDER BY units_sold DESC \
                             LIMIT 5";

$top_products_query_image_url = "SELECT p.id, p.name, p.image_url, p.price, COALESCE(SUM(oi.quantity), 0) as units_sold \
                                 FROM products p \
                                 INNER JOIN order_items oi ON p.id = oi.product_id \
                                 GROUP BY p.id \
                                 ORDER BY units_sold DESC \
                                 LIMIT 5";

if (db_query($top_products_query_image)) {
    $top_products = db_result_set();
} elseif (db_query($top_products_query_image_url)) {
    $top_products = db_result_set();
}
?>

<!-- Dashboard Content -->
<div class="page-header-admin">
    <h1>Dashboard</h1>
    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! Here's what's happening today.</p>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-title">Total Revenue</div>
                <div class="stat-value">$<?php echo number_format($total_revenue, 2); ?></div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> 12.5% from last month
                </div>
            </div>
            <div class="stat-icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-header">
            <div>
                <div class="stat-title">Total Orders</div>
                <div class="stat-value"><?php echo number_format($total_orders); ?></div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> 8.3% from last month
                </div>
            </div>
            <div class="stat-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
        </div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-header">
            <div>
                <div class="stat-title">Pending Orders</div>
                <div class="stat-value"><?php echo number_format($pending_orders); ?></div>
                <div class="stat-change">
                    <i class="fas fa-clock"></i> Needs attention
                </div>
            </div>
            <div class="stat-icon">
                <i class="fas fa-hourglass-half"></i>
            </div>
        </div>
    </div>
    
    <div class="stat-card danger">
        <div class="stat-header">
            <div>
                <div class="stat-title">Total Users</div>
                <div class="stat-value"><?php echo number_format($total_users); ?></div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> 156 new this month
                </div>
            </div>
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>
</div>

<!-- Alert for Low Stock -->
<?php if ($low_stock_count > 0): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i>
    <strong>Low Stock Alert!</strong> You have <?php echo $low_stock_count; ?> product(s) with low inventory.
    <a href="products.php?filter=low_stock" class="alert-link">View Products</a>
</div>
<?php endif; ?>

<!-- Recent Orders Table -->
<div class="data-table-container">
    <div class="table-header">
        <h2 class="table-title">Recent Orders</h2>
        <div class="table-actions">
            <a href="orders.php" class="btn btn-outline btn-sm">
                <i class="fas fa-list"></i> View All Orders
            </a>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_orders)): ?>
                <tr>
                    <td colspan="6" class="text-center">No orders found</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($recent_orders as $order): ?>
                    <tr>
                        <td><strong>#<?php echo $order['id']; ?></strong></td>
                        <td>
                            <div><?php echo htmlspecialchars($order['customer_name'] ?? ''); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($order['customer_email'] ?? ''); ?></small>
                        </td>
                        <td><?php echo format_date($order['created_at'] ?? ''); ?></td>
                        <?php $order_total = (float) ($order['total'] ?? $order['total_amount'] ?? 0); ?>
                        <td><strong>$<?php echo number_format($order_total, 2); ?></strong></td>
                        <td>
                            <?php
                            $order_status = $order['status'] ?? $order['order_status'] ?? 'pending';
                            $status_class = 'info';
                            if ($order_status === 'completed') $status_class = 'success';
                            elseif ($order_status === 'cancelled') $status_class = 'danger';
                            elseif ($order_status === 'processing') $status_class = 'warning';
                            ?>
                            <span class="badge badge-<?php echo $status_class; ?>">
                                <?php echo ucfirst($order_status); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="orders.php?view=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Top Selling Products -->
<div class="data-table-container">
    <div class="table-header">
        <h2 class="table-title">Top Selling Products</h2>
    </div>
    
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Sales</th>
                    <th>Price</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($top_products)): ?>
                <tr>
                    <td colspan="4" class="text-center">No sales data available</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($top_products as $product): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <?php
                                    $product_name = (string) ($product['name'] ?? $product['product_name'] ?? '');
                                    $product_image = (string) ($product['image'] ?? $product['image_url'] ?? '');
                                    $units_sold = (int) ($product['units_sold'] ?? $product['sold'] ?? 0);
                                    $product_price = (float) ($product['price'] ?? 0);
                                ?>
                                <img src="<?php echo htmlspecialchars($product_image); ?>" 
                                     alt="<?php echo htmlspecialchars($product_name); ?>" 
                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                <strong><?php echo htmlspecialchars($product_name); ?></strong>
                            </div>
                        </td>
                        <td><?php echo number_format($units_sold); ?> units</td>
                        <td>$<?php echo number_format($product_price, 2); ?></td>
                        <td><strong>$<?php echo number_format($product_price * $units_sold, 2); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.alert {
    padding: 16px 20px;
    border-radius: var(--radius-md);
    margin-bottom: var(--space-lg);
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideIn 0.3s ease;
}

.alert-warning {
    background: rgba(248, 150, 30, 0.1);
    border: 1px solid var(--warning-color);
    color: var(--warning-dark);
}

.alert i {
    font-size: 1.2rem;
}

.alert-link {
    margin-left: auto;
    color: inherit;
    font-weight: 600;
    text-decoration: underline;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<?php
// Include admin footer
require_once __DIR__ . '/includes/admin_footer.php';
?>
