<?php
/* ============================================
   ADMIN ORDERS MANAGEMENT
   View and manage customer orders
============================================ */

$page_title = 'Orders Management';

// Include admin  header and sidebar
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/utilities.php';

$supports_processing = orders_supports_status('processing');

// Pagination setup
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Filter by status
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status'], 'string') : '';

// Build query
// Build query
$where_sql = '';
$params = [];
if (!empty($status_filter)) {
    $where_sql = "WHERE o.status = :status";
    $params[':status'] = $status_filter;
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM orders o $where_sql";
db_query($count_query);
foreach ($params as $key => $value) {
    db_bind($key, $value);
}
$total_orders = db_single()['total'] ?? 0;

// Get orders
$query = "SELECT o.*, u.name as customer_name, u.email as customer_email 
          FROM orders o 
          LEFT JOIN users u ON o.user_id = u.id 
          $where_sql 
          ORDER BY o.created_at DESC 
          LIMIT :limit OFFSET :offset";

db_query($query);
foreach ($params as $key => $value) {
    db_bind($key, $value);
}
db_bind(':limit', $per_page, PDO::PARAM_INT);
db_bind(':offset', $offset, PDO::PARAM_INT);
$orders = db_result_set();

// Generate pagination
$pagination = generate_pagination($total_orders, $page, $per_page);

// Get order statistics
$stats_query_total = "SELECT 
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                    COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_count,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_count,
                    SUM(CASE WHEN status IN ('completed', 'processing') THEN total ELSE 0 END) as total_revenue
                FROM orders";

$stats_query_total_amount = "SELECT 
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                    COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_count,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_count,
                    SUM(CASE WHEN status IN ('completed', 'processing') THEN total_amount ELSE 0 END) as total_revenue
                FROM orders";

$stats = [];
if (db_query($stats_query_total)) {
    $stats = db_single() ?: [];
} elseif (db_query($stats_query_total_amount)) {
    $stats = db_single() ?: [];
}
?>

<!-- Orders Management Content -->
<div class="page-header-admin">
    <h1>Orders Management</h1>
    <p>View and manage customer orders</p>
</div>

<!-- Order Statistics -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
    <div class="stat-card warning">
        <div class="stat-title">Pending Orders</div>
        <div class="stat-value"><?php echo number_format($stats['pending_count'] ?? 0); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Processing</div>
        <div class="stat-value"><?php echo number_format($stats['processing_count'] ?? 0); ?></div>
    </div>
    <div class="stat-card success">
        <div class="stat-title">Completed</div>
        <div class="stat-value"><?php echo number_format($stats['completed_count'] ?? 0); ?></div>
    </div>
    <div class="stat-card danger">
        <div class="stat-title">Cancelled</div>
        <div class="stat-value"><?php echo number_format($stats['cancelled_count'] ?? 0); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Total Revenue</div>
        <div class="stat-value">$<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div>
    </div>
</div>

<!-- Orders Table -->
<div class="data-table-container">
    <div class="table-header">
        <h2 class="table-title">All Orders</h2>
        <div class="table-actions">
            <select class="form-control" style="width: auto;"
                onchange="window.location.href='orders.php?status='+this.value">
                <option value="">All Statuses</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <?php if ($supports_processing): ?>
                    <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                <?php endif; ?>
                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed
                </option>
                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled
                </option>
            </select>

            <button class="btn btn-outline btn-sm" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>

            <!-- <button class="btn btn-outline btn-sm" onclick="exportOrders()">
                <i class="fas fa-download"></i> Export
            </button> -->
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No orders found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong>#<?php echo $order['id']; ?></strong></td>
                            <td>
                                <div><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                            </td>
                            <td><?php echo format_datetime($order['created_at']); ?></td>
                            <td>
                                        <?php
                                        // Get order items count
                                        $items_query = "SELECT COUNT(*) as count FROM order_items WHERE order_id = :order_id";
                                        db_query($items_query);
                                        db_bind(':order_id', $order['id']);
                                        $items_count = db_single()['count'] ?? 0;
                                        echo $items_count . ' item(s)';
                                        ?>
                                    </td>
                            <?php
                                $order_total = (float) ($order['total'] ?? $order['total_amount'] ?? 0);
                                $payment_status = $order['payment_status'] ?? ((($order['status'] ?? '') === 'completed') ? 'paid' : 'unpaid');
                                $payment_badge = ($payment_status === 'paid') ? 'success' : 'warning';
                            ?>
                            <td><strong>$<?php echo number_format($order_total, 2); ?></strong></td>
                            <td>
                                <span class="badge badge-<?php echo $payment_badge; ?>">
                                    <?php echo ucfirst($payment_status); ?>
                                </span>
                            </td>
                            <td>
                                        <?php
                                        $status_class = 'info';
                                        if ($order['status'] === 'completed')
                                            $status_class = 'success';
                                        elseif ($order['status'] === 'cancelled')
                                            $status_class = 'danger';
                                        elseif ($order['status'] === 'processing')
                                            $status_class = 'warning';
                                        ?>
                                <span class="badge badge-<?php echo $status_class; ?>">
                                 <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button onclick="viewOrderDetails(<?php echo $order['id']; ?>)"
                                        class="btn btn-sm btn-outline" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="printInvoice(<?php echo $order['id']; ?>)" class="btn btn-sm btn-outline"
                                        title="Print Invoice">
                                        <i class="fas fa-file-invoice"></i>
                                    </button>

                                    <?php if (($order['status'] ?? '') === 'pending'): ?>
                                        <?php if ($supports_processing): ?>
                                            <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'processing')"
                                                class="btn btn-sm btn-primary" title="Move to Processing">
                                                <i class="fas fa-hourglass-half"></i>
                                            </button>
                                        <?php else: ?>
                                            <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'completed')"
                                                class="btn btn-sm btn-primary" title="Mark as Completed">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>

                                        <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'cancelled')"
                                            class="btn btn-sm btn-outline" title="Cancel Order">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php elseif (($order['status'] ?? '') === 'processing'): ?>
                                        <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'completed')"
                                            class="btn btn-sm btn-primary" title="Mark as Completed">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'cancelled')"
                                            class="btn btn-sm btn-outline" title="Cancel Order">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="table-footer" style="padding: var(--space-lg); border-top: 1px solid var(--light-200);">
       <?php echo get_pagination_html($pagination, 'orders.php', 'page'); ?>
        </div>
    <?php endif; ?>
</div>

<script>
    function viewOrderDetails(orderId) {
        window.location.href = 'order_details.php?id=' + orderId;
    }

    function printInvoice(orderId) {
        window.open('order_invoice.php?id=' + orderId, '_blank');
    }

    function updateOrderStatus(orderId, status) {
        if (confirm('Update order status to ' + status + '?')) {
            fetch('order_update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'order_id=' + orderId + '&status=' + status
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Order status updated successfully', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification(data.message || 'Failed to update status', 'error');
                    }
                })
                .catch(error => {
                    showNotification('An error occurred', 'error');
                });
        }
    }

    function exportOrders() {
        showNotification('Export feature coming soon', 'info');
    }
</script>

<?php
// Include admin footer
require_once __DIR__ . '/includes/admin_footer.php';
?>