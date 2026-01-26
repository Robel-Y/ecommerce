<?php
/* ============================================
   ADMIN HEADER - Reusable Component
   Includes admin navigation and user info
============================================ */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../functions/utilities.php';
require_once __DIR__ . '/../../functions/security.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    start_secure_session();
}

// Check if user is admin
if (!is_admin()) {
    $home = defined('SITE_URL') ? (rtrim(SITE_URL, '/') . '/index.php') : '../index.php';
    redirect_with_message($home, 'error', 'Access denied. Admin privileges required.');
}

// Get current page for active menu
$current_page = basename($_SERVER['PHP_SELF']);

function admin_table_has_column(string $table, string $column): bool
{
    $sql = 'SELECT 1 AS ok
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
            LIMIT 1';

    if (!db_query($sql)) {
        return false;
    }
    db_bind(1, DB_NAME);
    db_bind(2, $table);
    db_bind(3, $column);
    return (bool) db_single();
}

$pending_orders_count = 0;
$low_stock_count = 0;

if (db_query('SELECT COUNT(*) AS c FROM orders WHERE status = ?')) {
    db_bind(1, 'pending');
    $row = db_single();
    $pending_orders_count = (int) ($row['c'] ?? 0);
}

if (admin_table_has_column('products', 'stock') && db_query('SELECT COUNT(*) AS c FROM products WHERE stock <= ?')) {
    db_bind(1, 5);
    $row = db_single();
    $low_stock_count = (int) ($row['c'] ?? 0);
}

$notification_count = $pending_orders_count + $low_stock_count;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Admin Panel' : 'Admin Panel'; ?> | <?php echo SITE_NAME; ?></title>

    <script>
        (function() {
            try {
                var mode = localStorage.getItem('theme_mode');
                if (mode === 'light' || mode === 'dark') {
                    document.documentElement.setAttribute('data-theme', mode);
                } else {
                    document.documentElement.removeAttribute('data-theme');
                }
            } catch (e) {}
        })();
    </script>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/modern.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>admin/css/admin.css">
    <link rel="shortcut icon" href="<?php echo SITE_URL; ?>assets/images/icons/favicon.ico" type="image/x-icon">
    
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="admin-body">
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="admin-header-left">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="admin-logo">
                <img src="<?php echo SITE_URL; ?>assets/images/icons/favicon.ico" alt="Merkato Go" class="admin-logo-img">
                <span>Merkato Go</span>
            </div>
        </div>
        
        <div class="admin-header-right">
            <!-- Theme Toggle -->
            <div class="header-item">
                <button type="button" class="theme-toggle" aria-label="Theme: System">
                    <i class="fas fa-circle-half-stroke"></i>
                </button>
            </div>

            <!-- Notifications -->
            <div class="header-item notifications" id="notificationsMenu">
                <button class="notification-btn" id="notificationBtn" type="button" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                    <?php if ($notification_count > 0): ?>
                        <span class="notification-badge"><?php echo (int) $notification_count; ?></span>
                    <?php endif; ?>
                </button>

                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-dropdown-header">Notifications</div>

                    <?php if ($pending_orders_count > 0): ?>
                        <a class="notification-item" href="<?php echo SITE_URL; ?>admin/orders.php">
                            <i class="fas fa-receipt"></i>
                            <span>Pending orders: <?php echo (int) $pending_orders_count; ?></span>
                        </a>
                    <?php endif; ?>

                    <?php if ($low_stock_count > 0): ?>
                        <a class="notification-item" href="<?php echo SITE_URL; ?>admin/products.php">
                            <i class="fas fa-box-open"></i>
                            <span>Low stock products: <?php echo (int) $low_stock_count; ?></span>
                        </a>
                    <?php endif; ?>

                    <?php if ($notification_count === 0): ?>
                        <div class="notification-empty">No notifications</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- View Site Link -->
            <div class="header-item">
                <a href="<?php echo SITE_URL; ?>index.php" class="view-site-btn" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    <span>View Site</span>
                </a>
            </div>
            
            <!-- Admin User Menu -->
            <div class="header-item admin-user-menu">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="user-details">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                        <span class="user-role">Administrator</span>
                    </div>
                    <i class="fas fa-chevron-down"></i>
                </div>
                
                <div class="user-dropdown">
                    <a href="<?php echo SITE_URL; ?>user/profile.php">
                        <i class="fas fa-user"></i> My Profile
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="<?php echo SITE_URL; ?>user/logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Flash Message Display -->
    <?php
    $flash_message = get_flash_message();
    if ($flash_message):
    ?>
    <div class="admin-flash-message flash-<?php echo $flash_message['type']; ?>">
        <div class="flash-content">
            <i class="fas fa-<?php echo $flash_message['type'] === 'success' ? 'check-circle' : ($flash_message['type'] === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
            <span><?php echo htmlspecialchars($flash_message['text']); ?></span>
        </div>
        <button class="flash-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <?php endif; ?>

    <!-- Admin Layout Container -->
    <div class="admin-container">
