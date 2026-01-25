<?php
/* ============================================
   ADMIN CHECK - Procedural
   Admin-specific authentication and authorization
============================================ */

// Require authentication check
require_once __DIR__ . '/auth_check.php';

/**
 * Check if current page is admin page
 * 
 * @return bool True if admin page
 */
function is_admin_page() {
    $current_page = $_SERVER['PHP_SELF'] ?? '';
    return strpos($current_page, '/admin/') !== false;
}

/**
 * Require admin access
 * 
 * @param bool $redirect Whether to redirect on failure
 * @return bool True if admin access granted
 */
function require_admin_access($redirect = true) {
    // First, check if user is logged in
    if (!require_login($redirect)) {
        return false;
    }
    
    // Then, check if user is admin
    if (!require_admin($redirect)) {
        return false;
    }
    
    return true;
}

/**
 * Get admin dashboard stats
 * 
 * @param mysqli $connection Database connection
 * @return array Dashboard statistics
 */
function get_admin_stats($connection) {
    $stats = [
        'total_users' => 0,
        'total_orders' => 0,
        'total_products' => 0,
        'total_revenue' => 0.00,
        'pending_orders' => 0,
        'active_products' => 0,
        'today_orders' => 0,
        'today_revenue' => 0.00
    ];
    
    // Total users
    $query = "SELECT COUNT(*) as count FROM users WHERE role = 'user'";
    $result = $connection->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_users'] = $row['count'] ?? 0;
        $result->free();
    }
    
    // Total orders
    $query = "SELECT COUNT(*) as count FROM orders";
    $result = $connection->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_orders'] = $row['count'] ?? 0;
        $result->free();
    }
    
    // Total products
    $query = "SELECT COUNT(*) as count FROM products WHERE status = 'active'";
    $result = $connection->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_products'] = $row['count'] ?? 0;
        $result->free();
    }
    
    // Total revenue
    $query = "SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'";
    $result = $connection->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_revenue'] = $row['total'] ?? 0.00;
        $result->free();
    }
    
    // Pending orders
    $query = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
    $result = $connection->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['pending_orders'] = $row['count'] ?? 0;
        $result->free();
    }
    
    // Active products
    $query = "SELECT COUNT(*) as count FROM products WHERE status = 'active' AND stock > 0";
    $result = $connection->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['active_products'] = $row['count'] ?? 0;
        $result->free();
    }
    
    // Today's orders
    $query = "SELECT COUNT(*) as count, SUM(total_amount) as total FROM orders WHERE DATE(created_at) = CURDATE()";
    $result = $connection->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['today_orders'] = $row['count'] ?? 0;
        $stats['today_revenue'] = $row['total'] ?? 0.00;
        $result->free();
    }
    
    return $stats;
}

/**
 * Get recent orders
 * 
 * @param mysqli $connection Database connection
 * @param int $limit Number of orders to return
 * @return array Recent orders
 */
function get_recent_orders($connection, $limit = 10) {
    $orders = [];
    
    $query = "SELECT o.*, u.name as customer_name 
              FROM orders o 
              LEFT JOIN users u ON o.user_id = u.id 
              ORDER BY o.created_at DESC 
              LIMIT ?";
    
    $stmt = $connection->prepare($query);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    $stmt->close();
    return $orders;
}

/**
 * Get recent users
 * 
 * @param mysqli $connection Database connection
 * @param int $limit Number of users to return
 * @return array Recent users
 */
function get_recent_users($connection, $limit = 10) {
    $users = [];
    
    $query = "SELECT id, email, name, role, created_at 
              FROM users 
              WHERE role = 'user' 
              ORDER BY created_at DESC 
              LIMIT ?";
    
    $stmt = $connection->prepare($query);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    $stmt->close();
    return $users;
}

/**
 * Get admin navigation menu
 * 
 * @param string $current_page Current page
 * @return array Navigation menu
 */
function get_admin_nav_menu($current_page = '') {
    $menu = [
        [
            'title' => 'Dashboard',
            'icon' => 'fas fa-tachometer-alt',
            'url' => 'dashboard.php',
            'active' => $current_page === 'dashboard'
        ],
        [
            'title' => 'Products',
            'icon' => 'fas fa-box',
            'url' => '#',
            'active' => strpos($current_page, 'products') !== false,
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
            'active' => $current_page === 'orders'
        ],
        [
            'title' => 'Users',
            'icon' => 'fas fa-users',
            'url' => 'users.php',
            'active' => $current_page === 'users'
        ],
        [
            'title' => 'Settings',
            'icon' => 'fas fa-cog',
            'url' => 'settings.php',
            'active' => $current_page === 'settings'
        ]
    ];
    
    return $menu;
}

/**
 * Log admin activity
 * 
 * @param string $action Action performed
 * @param string $details Additional details
 * @param mysqli $connection Database connection
 * @return void
 */
function log_admin_activity($action, $details = '', $connection = null) {
    if (!isset($_SESSION['user_id'])) {
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    $ip_address = get_client_ip();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Log to database if connection provided
    if ($connection) {
        $query = "INSERT INTO admin_logs (admin_id, action, details, ip_address, user_agent) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $connection->prepare($query);
        $stmt->bind_param('issss', $user_id, $action, $details, $ip_address, $user_agent);
        $stmt->execute();
        $stmt->close();
    }
    
    // Also log to file
    $log_dir = dirname(__DIR__, 2) . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . '/admin.log';
    $log_entry = sprintf(
        "[%s] Admin Activity - User ID: %d - Action: %s - Details: %s - IP: %s\n",
        date('Y-m-d H:i:s'),
        $user_id,
        $action,
        $details,
        $ip_address
    );
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Get admin activity logs
 * 
 * @param mysqli $connection Database connection
 * @param int $limit Number of logs to return
 * @param string $filter Filter by action
 * @return array Activity logs
 */
function get_admin_activity_logs($connection, $limit = 50, $filter = '') {
    $logs = [];
    
    $query = "SELECT al.*, u.name as admin_name 
              FROM admin_logs al 
              LEFT JOIN users u ON al.admin_id = u.id";
    
    if (!empty($filter)) {
        $query .= " WHERE al.action LIKE ?";
        $filter_param = "%$filter%";
    }
    
    $query .= " ORDER BY al.created_at DESC LIMIT ?";
    
    $stmt = $connection->prepare($query);
    
    if (!empty($filter)) {
        $stmt->bind_param('si', $filter_param, $limit);
    } else {
        $stmt->bind_param('i', $limit);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    $stmt->close();
    return $logs;
}

/**
 * Export data to CSV
 * 
 * @param array $data Data to export
 * @param string $filename Export filename
 * @return void
 */
function export_to_csv($data, $filename = 'export.csv') {
    if (empty($data)) {
        return;
    }
    
    // Set headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add headers
    if (!empty($data[0])) {
        fputcsv($output, array_keys($data[0]));
    }
    
    // Add data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}

/**
 * Export data to Excel
 * 
 * @param array $data Data to export
 * @param string $filename Export filename
 * @return void
 */
function export_to_excel($data, $filename = 'export.xls') {
    if (empty($data)) {
        return;
    }
    
    // Set headers
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Start HTML table
    echo '<table border="1">';
    
    // Add headers
    if (!empty($data[0])) {
        echo '<tr>';
        foreach (array_keys($data[0]) as $header) {
            echo '<th>' . htmlspecialchars($header) . '</th>';
        }
        echo '</tr>';
    }
    
    // Add data
    foreach ($data as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . htmlspecialchars($cell) . '</td>';
        }
        echo '</tr>';
    }
    
    echo '</table>';
    exit();
}

/**
 * Backup database
 * 
 * @param mysqli $connection Database connection
 * @param string $backup_dir Backup directory
 * @return array Result array
 */
function backup_database($connection, $backup_dir) {
    // Create backup directory if it doesn't exist
    if (!is_dir($backup_dir)) {
        if (!mkdir($backup_dir, 0755, true)) {
            return [
                'success' => false,
                'error' => 'Failed to create backup directory'
            ];
        }
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    $backup_file = $backup_dir . '/backup_' . $timestamp . '.sql';
    
    // Get all tables
    $tables = [];
    $result = $connection->query('SHOW TABLES');
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    $result->free();
    
    // Open backup file
    $handle = fopen($backup_file, 'w');
    if (!$handle) {
        return [
            'success' => false,
            'error' => 'Failed to create backup file'
        ];
    }
    
    // Write SQL header
    fwrite($handle, "-- Database Backup\n");
    fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
    fwrite($handle, "-- Host: " . $connection->host_info . "\n");
    fwrite($handle, "-- Database: " . $connection->query("SELECT DATABASE()")->fetch_row()[0] . "\n\n");
    
    // Backup each table
    foreach ($tables as $table) {
        // Drop table if exists
        fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n\n");
        
        // Get create table statement
        $create_result = $connection->query("SHOW CREATE TABLE `$table`");
        $create_row = $create_result->fetch_row();
        fwrite($handle, $create_row[1] . ";\n\n");
        $create_result->free();
        
        // Get table data
        $data_result = $connection->query("SELECT * FROM `$table`");
        if ($data_result->num_rows > 0) {
            fwrite($handle, "LOCK TABLES `$table` WRITE;\n");
            
            while ($row = $data_result->fetch_assoc()) {
                $columns = implode('`, `', array_keys($row));
                $values = array_map(function($value) use ($connection) {
                    if ($value === null) {
                        return 'NULL';
                    }
                    return "'" . $connection->real_escape_string($value) . "'";
                }, array_values($row));
                $values = implode(', ', $values);
                
                fwrite($handle, "INSERT INTO `$table` (`$columns`) VALUES ($values);\n");
            }
            
            fwrite($handle, "UNLOCK TABLES;\n\n");
        }
        $data_result->free();
    }
    
    fclose($handle);
    
    // Log activity
    log_admin_activity('Database backup created', "File: $backup_file");
    
    return [
        'success' => true,
        'file' => $backup_file,
        'size' => filesize($backup_file),
        'message' => 'Backup created successfully'
    ];
}

/**
 * Restore database from backup
 * 
 * @param mysqli $connection Database connection
 * @param string $backup_file Backup file path
 * @return array Result array
 */
function restore_database($connection, $backup_file) {
    if (!file_exists($backup_file)) {
        return [
            'success' => false,
            'error' => 'Backup file not found'
        ];
    }
    
    // Read backup file
    $sql = file_get_contents($backup_file);
    if ($sql === false) {
        return [
            'success' => false,
            'error' => 'Failed to read backup file'
        ];
    }
    
    // Execute SQL
    if ($connection->multi_query($sql)) {
        do {
            // Store result (required for multi_query)
            if ($result = $connection->store_result()) {
                $result->free();
            }
        } while ($connection->more_results() && $connection->next_result());
    }
    
    if ($connection->error) {
        return [
            'success' => false,
            'error' => 'Restore failed: ' . $connection->error
        ];
    }
    
    // Log activity
    log_admin_activity('Database restored', "File: $backup_file");
    
    return [
        'success' => true,
        'message' => 'Database restored successfully'
    ];
}

/**
 * Clean old backup files
 * 
 * @param string $backup_dir Backup directory
 * @param int $max_age_days Maximum age in days
 * @return array Result array
 */
function clean_old_backups($backup_dir, $max_age_days = 30) {
    if (!is_dir($backup_dir)) {
        return [
            'success' => false,
            'error' => 'Backup directory not found'
        ];
    }
    
    $deleted = [];
    $kept = [];
    $now = time();
    $max_age = $max_age_days * 24 * 60 * 60;
    
    // Scan backup directory
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $filepath = $backup_dir . '/' . $file;
        if (is_file($filepath) && pathinfo($filepath, PATHINFO_EXTENSION) === 'sql') {
            $file_age = $now - filemtime($filepath);
            
            if ($file_age > $max_age) {
                if (unlink($filepath)) {
                    $deleted[] = $file;
                }
            } else {
                $kept[] = $file;
            }
        }
    }
    
    // Log activity
    if (!empty($deleted)) {
        log_admin_activity('Old backups cleaned', 'Deleted: ' . count($deleted) . ' files');
    }
    
    return [
        'success' => true,
        'deleted' => $deleted,
        'kept' => $kept,
        'message' => 'Cleaned ' . count($deleted) . ' old backup files'
    ];
}

/**
 * Get system information
 * 
 * @return array System information
 */
function get_system_info() {
    $info = [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
        'server_addr' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
        'server_port' => $_SERVER['SERVER_PORT'] ?? 'Unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
        'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'Unknown',
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'Unknown',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
        'request_time' => $_SERVER['REQUEST_TIME'] ?? 'Unknown',
        'request_time_float' => $_SERVER['REQUEST_TIME_FLOAT'] ?? 'Unknown',
        'http_host' => $_SERVER['HTTP_HOST'] ?? 'Unknown',
        'http_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'http_accept' => $_SERVER['HTTP_ACCEPT'] ?? 'Unknown',
        'http_accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'Unknown',
        'http_accept_encoding' => $_SERVER['HTTP_ACCEPT_ENCODING'] ?? 'Unknown',
        'http_connection' => $_SERVER['HTTP_CONNECTION'] ?? 'Unknown',
        'http_upgrade_insecure_requests' => $_SERVER['HTTP_UPGRADE_INSECURE_REQUESTS'] ?? 'Unknown',
        'php_ini_loaded_file' => php_ini_loaded_file(),
        'php_ini_scanned_files' => php_ini_scanned_files(),
        'php_extension_dir' => PHP_EXTENSION_DIR,
        'php_include_path' => get_include_path(),
        'php_max_execution_time' => ini_get('max_execution_time'),
        'php_memory_limit' => ini_get('memory_limit'),
        'php_upload_max_filesize' => ini_get('upload_max_filesize'),
        'php_post_max_size' => ini_get('post_max_size'),
        'php_max_input_time' => ini_get('max_input_time'),
        'php_max_input_vars' => ini_get('max_input_vars'),
        'php_display_errors' => ini_get('display_errors'),
        'php_error_reporting' => error_reporting(),
        'php_session_save_path' => session_save_path(),
        'php_session_name' => session_name(),
        'php_session_cache_limiter' => session_cache_limiter(),
        'php_session_cache_expire' => session_cache_expire(),
        'php_session_gc_maxlifetime' => ini_get('session.gc_maxlifetime'),
        'php_session_cookie_lifetime' => ini_get('session.cookie_lifetime'),
        'php_session_cookie_path' => ini_get('session.cookie_path'),
        'php_session_cookie_domain' => ini_get('session.cookie_domain'),
        'php_session_cookie_secure' => ini_get('session.cookie_secure'),
        'php_session_cookie_httponly' => ini_get('session.cookie_httponly'),
        'php_session_sid' => session_id(),
        'php_session_status' => session_status(),
        'php_session_module_name' => session_module_name(),
        'php_session_save_handler' => session_save_handler(),
    ];
    
    // Add database info if available
    if (isset($GLOBALS['connection']) && $GLOBALS['connection'] instanceof mysqli) {
        $info['database_server_info'] = $GLOBALS['connection']->server_info;
        $info['database_host_info'] = $GLOBALS['connection']->host_info;
        $info['database_protocol_version'] = $GLOBALS['connection']->protocol_version;
        $info['database_client_info'] = $GLOBALS['connection']->client_info;
        $info['database_client_version'] = $GLOBALS['connection']->client_version;
        $info['database_server_version'] = $GLOBALS['connection']->server_version;
        $info['database_stat'] = $GLOBALS['connection']->stat();
    }
    
    return $info;
}

// Initialize admin check
if (is_admin_page()) {
    // Require admin access for all admin pages
    if (!require_admin_access(true)) {
        exit();
    }
    
    // Set admin verification time
    if (!isset($_SESSION['admin_verified'])) {
        $_SESSION['admin_verified'] = time();
    }
    
    // Check admin re-verification (every 15 minutes)
    $admin_verification_timeout = 900; // 15 minutes
    if (time() - $_SESSION['admin_verified'] > $admin_verification_timeout) {
        // Store current page to return after verification
        $_SESSION['return_to'] = $_SERVER['REQUEST_URI'];
        
        // Redirect to admin verification page
        redirect_with_message('admin/verify.php', 'info', 'Admin verification required');
    }
    
    // Log admin page access
    $current_action = basename($_SERVER['PHP_SELF']) . (isset($_GET['action']) ? '?action=' . $_GET['action'] : '');
    log_admin_activity('Page Access', $current_action);
}
?>