<?php
/* ============================================
   AUTHENTICATION CHECK - Procedural
   User authentication and authorization functions
============================================ */

// Require necessary files
require_once __DIR__ . '/../functions/security.php';
require_once __DIR__ . '/../functions/validation.php';

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
}

/**
 * Check if user is admin
 * 
 * @return bool True if user is admin
 */
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Get current user ID
 * 
 * @return int|null User ID or null if not logged in
 */
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data
 * 
 * @return array User data or empty array
 */
function get_current_user() {
    if (!is_logged_in()) {
        return [];
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
        'name' => $_SESSION['user_name'] ?? null,
        'role' => $_SESSION['user_role'] ?? 'user',
        'created_at' => $_SESSION['user_created'] ?? null
    ];
}

/**
 * Require user to be logged in
 * 
 * @param bool $redirect Whether to redirect to login page
 * @return bool True if user is logged in
 */
function require_login($redirect = true) {
    if (!is_logged_in()) {
        if ($redirect) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            redirect_with_message('user/login.php', 'error', 'Please login to access this page');
        }
        return false;
    }
    
    return true;
}

/**
 * Require user to be admin
 * 
 * @param bool $redirect Whether to redirect to home page
 * @return bool True if user is admin
 */
function require_admin($redirect = true) {
    if (!is_admin()) {
        if ($redirect) {
            redirect_with_message('index.php', 'error', 'Access denied. Admin privileges required.');
        }
        return false;
    }
    
    return true;
}

/**
 * Prevent authenticated users from accessing auth pages
 * 
 * @param bool $redirect Whether to redirect to home page
 * @return void
 */
function prevent_authenticated($redirect = true) {
    if (is_logged_in()) {
        if ($redirect) {
            redirect_with_message('index.php', 'info', 'You are already logged in');
        }
    }
}

/**
 * Login user
 * 
 * @param array $user User data
 * @param bool $remember Remember login
 * @return bool True if login successful
 */
function login_user($user, $remember = false) {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        start_secure_session();
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'] ?? $user['email'];
    $_SESSION['user_role'] = $user['role'] ?? 'user';
    $_SESSION['user_created'] = $user['created_at'] ?? date('Y-m-d H:i:s');
    $_SESSION['last_activity'] = time();
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Set remember me cookie
    if ($remember) {
        $token = generate_secure_token(32);
        $selector = generate_secure_token(16);
        $expiry = time() + (30 * 24 * 60 * 60); // 30 days
        
        // Hash the token for storage
        $hashed_token = hash('sha256', $token);
        
        // Store in database (simplified for example)
        // In real implementation, store in database
        setcookie('remember', $selector . ':' . $token, $expiry, '/', '', true, true);
        
        // Store in session for demo
        $_SESSION['remember_token'] = [
            'selector' => $selector,
            'hashed_token' => $hashed_token,
            'expiry' => $expiry
        ];
    }
    
    // Clear login attempts
    $ip = get_client_ip();
    clear_login_attempts($user['email'], $ip);
    
    // Log security event
    log_security_event('User login', 'info', [
        'user_id' => $user['id'],
        'email' => $user['email']
    ]);
    
    return true;
}

/**
 * Check remember me cookie and auto-login
 * 
 * @return bool True if auto-login successful
 */
function check_remember_me() {
    if (isset($_COOKIE['remember']) && !is_logged_in()) {
        list($selector, $token) = explode(':', $_COOKIE['remember']);
        
        // Validate token (simplified for example)
        // In real implementation, check against database
        if (isset($_SESSION['remember_token']) && 
            $_SESSION['remember_token']['selector'] === $selector) {
            
            $hashed_token = hash('sha256', $token);
            if (hash_equals($_SESSION['remember_token']['hashed_token'], $hashed_token)) {
                
                // Check expiry
                if (time() > $_SESSION['remember_token']['expiry']) {
                    // Token expired
                    logout_user();
                    return false;
                }
                
                // Auto-login user
                // In real implementation, fetch user from database
                $user = [
                    'id' => $_SESSION['user_id'] ?? 1,
                    'email' => $_SESSION['user_email'] ?? 'user@example.com',
                    'name' => $_SESSION['user_name'] ?? 'User',
                    'role' => $_SESSION['user_role'] ?? 'user'
                ];
                
                return login_user($user, false);
            }
        }
    }
    
    return false;
}

/**
 * Logout user
 * 
 * @return void
 */
function logout_user() {
    // Clear remember me cookie
    if (isset($_COOKIE['remember'])) {
        setcookie('remember', '', time() - 3600, '/', '', true, true);
        unset($_COOKIE['remember']);
    }
    
    // Clear remember token from session
    if (isset($_SESSION['remember_token'])) {
        unset($_SESSION['remember_token']);
    }
    
    // Log security event
    if (isset($_SESSION['user_id'])) {
        log_security_event('User logout', 'info', [
            'user_id' => $_SESSION['user_id']
        ]);
    }
    
    // Destroy session
    logout_user_session();
}

/**
 * Check user permissions
 * 
 * @param string $permission Permission to check
 * @return bool True if user has permission
 */
function has_permission($permission) {
    if (!is_logged_in()) {
        return false;
    }
    
    $role = $_SESSION['user_role'] ?? 'user';
    
    // Define permissions for each role
    $permissions = [
        'admin' => [
            'view_dashboard',
            'manage_products',
            'manage_orders',
            'manage_users',
            'manage_settings',
            'view_reports'
        ],
        'user' => [
            'view_profile',
            'edit_profile',
            'place_orders',
            'view_orders'
        ]
    ];
    
    // Check if role exists and has permission
    if (isset($permissions[$role])) {
        return in_array($permission, $permissions[$role]);
    }
    
    return false;
}

/**
 * Require specific permission
 * 
 * @param string $permission Permission to require
 * @param bool $redirect Whether to redirect on failure
 * @return bool True if user has permission
 */
function require_permission($permission, $redirect = true) {
    if (!has_permission($permission)) {
        if ($redirect) {
            redirect_with_message('index.php', 'error', 'You do not have permission to access this page');
        }
        return false;
    }
    
    return true;
}

/**
 * Update user session data
 * 
 * @param array $data User data to update
 * @return bool True if update successful
 */
function update_user_session($data) {
    if (!is_logged_in()) {
        return false;
    }
    
    foreach ($data as $key => $value) {
        if (strpos($key, 'user_') === 0) {
            $_SESSION[$key] = $value;
        }
    }
    
    return true;
}

/**
 * Change user password
 * 
 * @param int $user_id User ID
 * @param string $current_password Current password
 * @param string $new_password New password
 * @param mysqli $connection Database connection
 * @return array Result array
 */
function change_password($user_id, $current_password, $new_password, $connection) {
    // Validate new password
    $validation = validate_password($new_password);
    if (!$validation['valid']) {
        return [
            'success' => false,
            'errors' => $validation['errors']
        ];
    }
    
    // Get current password hash from database
    $query = "SELECT password FROM users WHERE id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($current_hash);
    $stmt->fetch();
    $stmt->close();
    
    // Verify current password
    if (!verify_password($current_password, $current_hash)) {
        return [
            'success' => false,
            'errors' => ['Current password is incorrect']
        ];
    }
    
    // Hash new password
    $new_hash = hash_password($new_password);
    
    // Update password in database
    $query = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('si', $new_hash, $user_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Log security event
        log_security_event('Password changed', 'info', ['user_id' => $user_id]);
        
        return [
            'success' => true,
            'message' => 'Password changed successfully'
        ];
    }
    
    $stmt->close();
    return [
        'success' => false,
        'errors' => ['Failed to update password']
    ];
}

/**
 * Get user by ID
 * 
 * @param int $user_id User ID
 * @param mysqli $connection Database connection
 * @return array User data or empty array
 */
function get_user_by_id($user_id, $connection) {
    $query = "SELECT id, email, name, role, created_at, updated_at FROM users WHERE id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    $stmt->close();
    
    return $user ?: [];
}

/**
 * Get user by email
 * 
 * @param string $email User email
 * @param mysqli $connection Database connection
 * @return array User data or empty array
 */
function get_user_by_email($email, $connection) {
    $query = "SELECT id, email, name, role, password, created_at, updated_at FROM users WHERE email = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    $stmt->close();
    
    return $user ?: [];
}

/**
 * Create new user
 * 
 * @param array $user_data User data
 * @param mysqli $connection Database connection
 * @return array Result array
 */
function create_user($user_data, $connection) {
    // Validate required fields
    $required = ['email', 'password', 'name'];
    $missing = validate_required_fields($user_data, $required);
    
    if (!empty($missing)) {
        return [
            'success' => false,
            'errors' => ['Missing required fields: ' . implode(', ', $missing)]
        ];
    }
    
    // Validate email
    if (!validate_email($user_data['email'])) {
        return [
            'success' => false,
            'errors' => ['Invalid email address']
        ];
    }
    
    // Validate password
    $validation = validate_password($user_data['password']);
    if (!$validation['valid']) {
        return [
            'success' => false,
            'errors' => $validation['errors']
        ];
    }
    
    // Check if email already exists
    $existing_user = get_user_by_email($user_data['email'], $connection);
    if (!empty($existing_user)) {
        return [
            'success' => false,
            'errors' => ['Email already registered']
        ];
    }
    
    // Hash password
    $hashed_password = hash_password($user_data['password']);
    
    // Insert user
    $query = "INSERT INTO users (email, password, name, role, created_at) VALUES (?, ?, ?, 'user', NOW())";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('sss', $user_data['email'], $hashed_password, $user_data['name']);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        $stmt->close();
        
        // Log security event
        log_security_event('User registered', 'info', [
            'user_id' => $user_id,
            'email' => $user_data['email']
        ]);
        
        return [
            'success' => true,
            'user_id' => $user_id,
            'message' => 'User created successfully'
        ];
    }
    
    $stmt->close();
    return [
        'success' => false,
        'errors' => ['Failed to create user']
    ];
}

/**
 * Update user profile
 * 
 * @param int $user_id User ID
 * @param array $user_data User data to update
 * @param mysqli $connection Database connection
 * @return array Result array
 */
function update_user_profile($user_id, $user_data, $connection) {
    $allowed_fields = ['name', 'email', 'phone', 'address', 'city', 'state', 'zip', 'country'];
    $updates = [];
    $params = [];
    $types = '';
    
    foreach ($user_data as $field => $value) {
        if (in_array($field, $allowed_fields) && !empty($value)) {
            $updates[] = "$field = ?";
            $params[] = $value;
            $types .= 's';
        }
    }
    
    if (empty($updates)) {
        return [
            'success' => false,
            'errors' => ['No valid fields to update']
        ];
    }
    
    // If updating email, validate it
    if (isset($user_data['email'])) {
        if (!validate_email($user_data['email'])) {
            return [
                'success' => false,
                'errors' => ['Invalid email address']
            ];
        }
        
        // Check if email is already used by another user
        $query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = $connection->prepare($query);
        $stmt->bind_param('si', $user_data['email'], $user_id);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->close();
            return [
                'success' => false,
                'errors' => ['Email already in use']
            ];
        }
        $stmt->close();
    }
    
    // Build query
    $updates[] = "updated_at = NOW()";
    $query = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
    $params[] = $user_id;
    $types .= 'i';
    
    $stmt = $connection->prepare($query);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Update session if current user
        if ($user_id == get_user_id()) {
            update_user_session([
                'user_name' => $user_data['name'] ?? $_SESSION['user_name'],
                'user_email' => $user_data['email'] ?? $_SESSION['user_email']
            ]);
        }
        
        // Log security event
        log_security_event('Profile updated', 'info', ['user_id' => $user_id]);
        
        return [
            'success' => true,
            'message' => 'Profile updated successfully'
        ];
    }
    
    $stmt->close();
    return [
        'success' => false,
        'errors' => ['Failed to update profile']
    ];
}

/**
 * Verify user login credentials
 * 
 * @param string $email User email
 * @param string $password User password
 * @param mysqli $connection Database connection
 * @return array Result array
 */
function verify_login($email, $password, $connection) {
    $ip = get_client_ip();
    
    // Check login attempts
    if (!check_login_attempts($email, $ip)) {
        return [
            'success' => false,
            'errors' => ['Too many login attempts. Please try again later.']
        ];
    }
    
    // Get user by email
    $user = get_user_by_email($email, $connection);
    if (empty($user)) {
        record_failed_login($email, $ip);
        return [
            'success' => false,
            'errors' => ['Invalid email or password']
        ];
    }
    
    // Verify password
    if (!verify_password($password, $user['password'])) {
        record_failed_login($email, $ip);
        return [
            'success' => false,
            'errors' => ['Invalid email or password']
        ];
    }
    
    // Check if account is active
    if (isset($user['status']) && $user['status'] !== 'active') {
        return [
            'success' => false,
            'errors' => ['Account is disabled. Please contact support.']
        ];
    }
    
    // Clear login attempts
    clear_login_attempts($email, $ip);
    
    return [
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'] ?? $user['email'],
            'role' => $user['role'] ?? 'user',
            'created_at' => $user['created_at']
        ]
    ];
}

// Initialize authentication check
if (session_status() === PHP_SESSION_NONE) {
    start_secure_session();
}

// Check remember me cookie
if (!is_logged_in()) {
    check_remember_me();
}

// Validate session
if (is_logged_in() && !validate_session()) {
    logout_user();
    redirect_with_message('user/login.php', 'error', 'Session expired. Please login again.');
}
?>