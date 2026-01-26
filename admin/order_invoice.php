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

$order_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$order = null;
$items = [];

if ($order_id > 0) {
    db_query("SELECT o.*, u.name AS customer_name, u.email AS customer_email
              FROM orders o
              LEFT JOIN users u ON o.user_id = u.id
              WHERE o.id = :id
              LIMIT 1");
    db_bind(':id', $order_id, PDO::PARAM_INT);
    $order = db_single();

    if ($order) {
        db_query("SELECT oi.*, p.name AS product_name
                  FROM order_items oi
                  LEFT JOIN products p ON oi.product_id = p.id
                  WHERE oi.order_id = :order_id
                  ORDER BY oi.id ASC");
        db_bind(':order_id', $order_id, PDO::PARAM_INT);
        $items = db_result_set();
    }
}

$total = $order ? (float) ($order['total'] ?? $order['total_amount'] ?? 0) : 0;
$status = $order['status'] ?? 'pending';
$invoice_no = $order ? generate_invoice_number((int) $order['id']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?php echo htmlspecialchars($invoice_no); ?> | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/modern.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>admin/css/admin.css">
    <style>
        .invoice-wrap{max-width:900px;margin:24px auto;background:var(--white);border:1px solid var(--light-200);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);overflow:hidden}
        .invoice-head{padding:20px 24px;border-bottom:1px solid var(--light-200);display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
        .invoice-title{margin:0;font-size:1.25rem}
        .invoice-meta{color:var(--text-muted);font-size:.95rem}
        .invoice-body{padding:18px 24px}
        .invoice-actions{display:flex;gap:10px;justify-content:flex-end;padding:16px 24px;border-top:1px solid var(--light-200)}
        @media print{.invoice-actions{display:none}.invoice-wrap{box-shadow:none;border:0;margin:0;max-width:none}}
    </style>
</head>
<body style="background: var(--light-color);">

<div class="invoice-wrap">
    <div class="invoice-head">
        <div>
            <h1 class="invoice-title">Invoice</h1>
            <div class="invoice-meta">
                <div><strong><?php echo htmlspecialchars($invoice_no); ?></strong></div>
                <div>Order #<?php echo (int) ($order['id'] ?? 0); ?> • <?php echo htmlspecialchars(format_datetime($order['created_at'] ?? '')); ?></div>
            </div>
        </div>
        <div style="text-align:right;">
            <div><strong><?php echo htmlspecialchars(SITE_NAME); ?></strong></div>
            <div class="invoice-meta"><?php echo htmlspecialchars($order['customer_name'] ?? ''); ?></div>
            <div class="invoice-meta"><?php echo htmlspecialchars($order['customer_email'] ?? ''); ?></div>
            <div style="margin-top:8px;">
                <span class="badge badge-<?php echo ($status === 'completed') ? 'success' : (($status === 'cancelled') ? 'danger' : 'warning'); ?>"><?php echo ucfirst($status); ?></span>
            </div>
        </div>
    </div>

    <div class="invoice-body">
        <?php if (!$order): ?>
            <strong>Order not found.</strong>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th style="width:140px;">Price</th>
                            <th style="width:100px;">Qty</th>
                            <th style="width:160px;">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                            <?php
                                $price = (float) ($it['price'] ?? 0);
                                $qty = (int) ($it['quantity'] ?? 0);
                                $line = $price * $qty;
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($it['product_name'] ?? 'Product'); ?></strong></td>
                                <td>$<?php echo number_format($price, 2); ?></td>
                                <td><?php echo $qty; ?></td>
                                <td><strong>$<?php echo number_format($line, 2); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="display:flex; justify-content:flex-end; margin-top: 14px;">
                <div style="min-width:280px; border:1px solid var(--light-200); border-radius: var(--radius-md); padding: 14px 16px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                        <span class="text-muted">Total</span>
                        <strong>$<?php echo number_format($total, 2); ?></strong>
                    </div>
                    <div class="text-muted" style="font-size:.9rem;">(Tax/shipping not itemized)</div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="invoice-actions">
        <button class="btn btn-outline btn-sm" onclick="window.close()">Close</button>
        <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
    </div>
</div>

</body>
</html>
