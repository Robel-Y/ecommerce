<?php
// Admin Authentication Check
require_once '../config/constants.php';
require_once 'auth_check.php';

/**
 * Admin-specific authentication check
 * This should be included at the top of all admin pages
 */

// First, check if user is authenticated
if (!requireAuth(true)) {
    exit; // Redirect already happened
}

// Then check if user is admin
if (!requireAdmin(true)) {
    exit; // Redirect already happened
}

// Additional admin session validation
if (!isset($_SESSION['admin_verified'])) {
    // For extra security, admins need to re-verify occasionally
    $_SESSION['admin_verified'] = time();
}

// Check admin re-verification (every 15 minutes)
$admin_verification_timeout = 900; // 15 minutes
if (time() - $_SESSION['admin_verified'] > $admin_verification_timeout) {
    // Store current page to return after verification
    $_SESSION['return_to'] = currentURL();
    
    // Redirect to admin verification page
    redirectWithMessage('admin/verify.php', 'info', 'Admin verification required');
}

/**
 * Log admin activity for security auditing
 * 
 * @param string $action Action performed
 * @param string $details Additional details
 */
function logAdminActivity($action, $details = '') {
    $db = $GLOBALS['db'] ?? null;
    
    if (!$db) {
        return; // Database not available
    }
    
    try {
        $db->query("
            INSERT INTO admin_logs 
            (admin_id, action, details, ip_address, user_agent) 
            VALUES (:admin_id, :action, :details, :ip, :ua)
        ");
        
        $db->bind(':admin_id', $_SESSION['user_id']);
        $db->bind(':action', $action);
        $db->bind(':details', $details);
        $db->bind(':ip', $_SERVER['REMOTE_ADDR']);
        $db->bind(':ua', $_SERVER['HTTP_USER_AGENT']);
        
        $db->execute();
    } catch (Exception $e) {
        // Silently fail logging - don't break the application
        error_log("Failed to log admin activity: " . $e->getMessage());
    }
}

/**
 * Get admin dashboard statistics
 * 
 * @return array Dashboard stats
 */
function getAdminStats() {
    $db = $GLOBALS['db'] ?? null;
    $stats = [
        'total_users' => 0,
        'total_orders' => 0,
        'total_products' => 0,
        'total_revenue' => 0,
        'pending_orders' => 0
    ];
    
    if (!$db) {
        return $stats;
    }
    
    try {
        // Total users
        $db->query("SELECT COUNT(*) as count FROM users WHERE deleted_at IS NULL");
        $result = $db->single();
        $stats['total_users'] = $result['count'];
        
        // Total orders
        $db->query("SELECT COUNT(*) as count FROM orders WHERE status != 'cancelled'");
        $result = $db->single();
        $stats['total_orders'] = $result['count'];
        
        // Total products
        $db->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
        $result = $db->single();
        $stats['total_products'] = $result['count'];
        
        // Total revenue
        $db->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'");
        $result = $db->single();
        $stats['total_revenue'] = $result['total'] ?? 0;
        
        // Pending orders
        $db->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
        $result = $db->single();
        $stats['pending_orders'] = $result['count'];
        
    } catch (Exception $e) {
        error_log("Failed to get admin stats: " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Check if user has specific admin permission
 * 
 * @param string $permission Permission to check
 * @return bool True if user has permission
 */
function hasAdminPermission($permission) {
    // For this demo, all admins have all permissions
    // In a real system, you would check against user roles/permissions
    return isAdmin();
}

/**
 * Generate admin menu items based on permissions
 * 
 * @return array Admin menu items
 */
function getAdminMenu() {
    $menu = [
        [
            'title' => 'Dashboard',
            'icon' => 'fas fa-tachometer-alt',
            'url' => 'dashboard.php',
            'permission' => 'view_dashboard'
        ],
        [
            'title' => 'Products',
            'icon' => 'fas fa-box',
            'url' => 'products.php',
            'permission' => 'manage_products',
            'submenu' => [
                ['title' => 'All Products', 'url' => 'products.php'],
                ['title' => 'Add New', 'url' => 'products.php?action=add'],
                ['title' => 'Categories', 'url' => 'categories.php']
            ]
        ],
        [
            'title' => 'Orders',
            'icon' => 'fas fa-shopping-cart',
            'url' => 'orders.php',
            'permission' => 'manage_orders'
        ],
        [
            'title' => 'Users',
            'icon' => 'fas fa-users',
            'url' => 'users.php',
            'permission' => 'manage_users'
        ],
        [
            'title' => 'Settings',
            'icon' => 'fas fa-cog',
            'url' => 'settings.php',
            'permission' => 'manage_settings'
        ]
    ];
    
    // Filter menu based on permissions
    $filtered_menu = [];
    foreach ($menu as $item) {
        if (hasAdminPermission($item['permission'])) {
            $filtered_menu[] = $item;
        }
    }
    
    return $filtered_menu;
}

// Log page access
$current_action = basename($_SERVER['PHP_SELF']) . (isset($_GET['action']) ? '?action=' . $_GET['action'] : '');
logAdminActivity('Page Access', $current_action);

// Set admin page flag
define('IS_ADMIN_PAGE', true);
?>