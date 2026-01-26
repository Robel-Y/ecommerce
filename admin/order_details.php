<?php
$page_title = 'Order Details';

require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/utilities.php';

$order_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$order = null;
$order_items = [];

if ($order_id > 0) {
    db_query("SELECT o.*, u.name AS customer_name, u.email AS customer_email
              FROM orders o
              LEFT JOIN users u ON o.user_id = u.id
              WHERE o.id = :id
              LIMIT 1");
    db_bind(':id', $order_id, PDO::PARAM_INT);
    $order = db_single();

    if ($order) {
        db_query("SELECT oi.*, p.name AS product_name, p.image AS product_image
                  FROM order_items oi
                  LEFT JOIN products p ON oi.product_id = p.id
                  WHERE oi.order_id = :order_id
                  ORDER BY oi.id ASC");
        db_bind(':order_id', $order_id, PDO::PARAM_INT);
        $order_items = db_result_set();
    }
}

?>

<div class="page-header-admin">
    <h1>Order Details</h1>
    <p>View order information and items</p>
</div>

<?php if (!$order): ?>
    <div class="data-table-container" style="padding: var(--space-lg);">
        <strong>Order not found.</strong>
        <div style="margin-top: var(--space-md);">
            <a href="orders.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to Orders</a>
        </div>
    </div>
<?php else: ?>
    <?php
        $order_total = (float) ($order['total'] ?? $order['total_amount'] ?? 0);
        $status = $order['status'] ?? 'pending';
        $status_class = 'info';
        if ($status === 'completed') $status_class = 'success';
        elseif ($status === 'cancelled') $status_class = 'danger';
        elseif ($status === 'processing') $status_class = 'warning';

        $payment_status = $order['payment_status'] ?? ($status === 'completed' ? 'paid' : 'unpaid');
        $payment_class = ($payment_status === 'paid') ? 'success' : 'warning';
    ?>

    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        <div class="stat-card">
            <div class="stat-title">Order ID</div>
            <div class="stat-value">#<?php echo (int) $order['id']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-title">Customer</div>
            <div class="stat-value" style="font-size: 1.1rem; font-weight: 600;">
                <?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?>
            </div>
            <div class="text-muted" style="margin-top: 6px;">
                <?php echo htmlspecialchars($order['customer_email'] ?? ''); ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-title">Total</div>
            <div class="stat-value">$<?php echo number_format($order_total, 2); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-title">Status</div>
            <div style="margin-top: 10px;">
                <span class="badge badge-<?php echo $status_class; ?>"><?php echo ucfirst($status); ?></span>
                <span class="badge badge-<?php echo $payment_class; ?>" style="margin-left: 8px;">
                    <?php echo ucfirst($payment_status); ?>
                </span>
            </div>
            <div class="text-muted" style="margin-top: 10px;">
                <?php echo htmlspecialchars(format_datetime($order['created_at'] ?? '')); ?>
            </div>
        </div>
    </div>

    <div class="data-table-container">
        <div class="table-header">
            <h2 class="table-title">Items</h2>
            <div class="table-actions">
                <a href="order_invoice.php?id=<?php echo (int) $order['id']; ?>" target="_blank" class="btn btn-outline btn-sm">
                    <i class="fas fa-file-invoice"></i> Invoice
                </a>
                <a href="orders.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($order_items)): ?>
                        <tr>
                            <td colspan="4" class="text-center">No items found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($order_items as $item): ?>
                            <?php
                                $img = $item['product_image'] ?? '';
                                $img_src = $img ? htmlspecialchars($img) : (SITE_URL . 'assets/images/products/default.jpg');
                                $price = (float) ($item['price'] ?? 0);
                                $qty = (int) ($item['quantity'] ?? 0);
                                $line_total = $price * $qty;
                            ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <img src="<?php echo $img_src; ?>" alt="" style="width:44px; height:44px; object-fit:cover; border-radius:10px; border:1px solid var(--light-200);" onerror="this.src='<?php echo SITE_URL; ?>assets/images/products/default.jpg'">
                                        <strong><?php echo htmlspecialchars($item['product_name'] ?? 'Product'); ?></strong>
                                    </div>
                                </td>
                                <td>$<?php echo number_format($price, 2); ?></td>
                                <td><?php echo $qty; ?></td>
                                <td><strong>$<?php echo number_format($line_total, 2); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
