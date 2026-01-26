<?php
/* ============================================
   AUTHENTICATION CHECK - Procedural
   User authentication and authorization functions
============================================ */

// Require necessary files
require_once __DIR__ . '/../functions/security.php';
require_once __DIR__ . '/../functions/validation.php';
require_once __DIR__ . '/../config/database.php'; // procedural DB functions

// Initialize PDO connection
$connection = db_connect(); // returns PDO or dies if failed

if (!$connection) {
    die("Database connection failed. Cannot continue authentication checks.");
}

/* ============================================
   USER SESSION HELPERS
============================================ */

/**
 * Check if user is logged in
 */
function is_logged_in()
{
    return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
}

/**
 * Check if user is admin
 */
function is_admin()
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Get current user ID
 */
function get_user_id()
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get user by ID
 */
function get_user_by_id($id)
{
    $query = "SELECT id, email, name, role, password, phone, address, city, state, zip, country, created_at, updated_at FROM users WHERE id = :id LIMIT 1";
    db_query($query);
    db_bind(':id', $id);
    return db_single() ?: [];
}



/**
 * Get current user data
 */
if (!function_exists('get_current_user')) {
    function get_current_user()
    {
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
}
/**
 * Require login
 */
function require_login($redirect = true)
{
    if (!is_logged_in()) {
        if ($redirect) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'] ?? '';
            $login_url = defined('SITE_URL') ? (rtrim(SITE_URL, '/') . '/user/login.php') : 'user/login.php';
            redirect_with_message($login_url, 'error', 'Please login to access this page');
        }
        return false;
    }
    return true;
}

/**
 * Require admin
 */
function require_admin($redirect = true)
{
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
 */
function prevent_authenticated($redirect = true)
{
    if (is_logged_in() && $redirect) {
        redirect_with_message('index.php', 'info', 'You are already logged in');
    }
}

/* ============================================
   AUTHENTICATION FUNCTIONS
============================================ */

/**
 * Login user
 */
function login_user($user, $remember = false)
{
    if (session_status() === PHP_SESSION_NONE) {
        start_secure_session();
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'] ?? $user['email'];
    $_SESSION['user_role'] = $user['role'] ?? 'user';
    $_SESSION['user_created'] = $user['created_at'] ?? date('Y-m-d H:i:s');
    $_SESSION['last_activity'] = time();
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Optional: Remember me
    if ($remember) {
        $token = generate_secure_token(32);
        $selector = generate_secure_token(16);
        $expiry = time() + (30 * 24 * 60 * 60); // 30 days
        $hashed_token = hash('sha256', $token);

        // Store token in cookie
        setcookie('remember', $selector . ':' . $token, $expiry, '/', '', true, true);

        // Store in session for demo
        $_SESSION['remember_token'] = [
            'selector' => $selector,
            'hashed_token' => $hashed_token,
            'expiry' => $expiry
        ];
    }

    return true;
}

/**
 * Check remember me cookie
 */
function check_remember_me()
{
    if (isset($_COOKIE['remember']) && !is_logged_in()) {
        $parts = explode(':', $_COOKIE['remember']);
        if (count($parts) !== 2)
            return false;

        list($selector, $token) = $parts;

        if (isset($_SESSION['remember_token']) && $_SESSION['remember_token']['selector'] === $selector) {
            $hashed_token = hash('sha256', $token);
            if (hash_equals($_SESSION['remember_token']['hashed_token'], $hashed_token)) {
                if (time() > $_SESSION['remember_token']['expiry']) {
                    logout_user();
                    return false;
                }

                // Auto-login demo user
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
 */
if (!function_exists('logout_user')) {
    function logout_user()
    {
        if (session_status() === PHP_SESSION_NONE) {
            start_secure_session();
        }

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();

        // Clear remember me cookie
        setcookie('remember', '', time() - 3600, '/', '', true, true);
    }
}
/* ============================================
   USER DATABASE FUNCTIONS
   Works only with PDO connection
============================================ */

/**
 * Get user by email
 */
function get_user_by_email($email)
{
    $query = "SELECT id, email, name, role, password, phone, address, city, state, zip, country, created_at, updated_at FROM users WHERE email = :email LIMIT 1";
    db_query($query);
    db_bind(':email', $email);
    return db_single() ?: [];
}

/**
 * Create new user
 */
function create_user($user_data)
{
    $required = ['email', 'password', 'name'];
    $missing = validate_required_fields($user_data, $required);
    if (!empty($missing)) {
        return ['success' => false, 'errors' => ['Missing fields: ' . implode(', ', $missing)]];
    }

    if (!validate_email($user_data['email'])) {
        return ['success' => false, 'errors' => ['Invalid email']];
    }

    $validation = validate_password($user_data['password']);
    if (!$validation['valid']) {
        return ['success' => false, 'errors' => $validation['errors']];
    }

    // Check existing email
    if (!empty(get_user_by_email($user_data['email']))) {
        return ['success' => false, 'errors' => ['Email already registered']];
    }

    $hashed_password = hash_password($user_data['password']);

    $query = "INSERT INTO users (email, password, name, role, created_at) VALUES (:email, :password, :name, 'user', NOW())";

    db_query($query);
    db_bind(':email', $user_data['email']);
    db_bind(':password', $hashed_password);
    db_bind(':name', $user_data['name']);

    if (db_execute()) {
        $user_id = db_last_insert_id();
        log_security_event('User registered', 'info', ['user_id' => $user_id, 'email' => $user_data['email']]);
        return [
            'success' => true,
            'user_id' => $user_id,
            'user' => [
                'id' => $user_id,
                'email' => $user_data['email'],
                'name' => $user_data['name'],
                'role' => 'user'
            ]
        ];
    }

    return ['success' => false, 'errors' => ['Failed to create user']];
}

/**
 * Verify login
 */
function verify_login($email, $password)
{
    $ip = get_client_ip();
    $user = get_user_by_email($email);

    if (empty($user) || !verify_password($password, $user['password'])) {
        record_failed_login($email, $ip);
        return ['success' => false, 'errors' => ['Invalid email or password']];
    }

    clear_login_attempts($email, $ip);

    return ['success' => true, 'user' => $user];
}

/* ============================================
   INITIALIZE SESSION AND REMEMBER ME
============================================ */
if (session_status() === PHP_SESSION_NONE) {
    start_secure_session();
}

if (!is_logged_in()) {
    check_remember_me();
}

if (is_logged_in() && !validate_session()) {
    logout_user();
    redirect_with_message('user/login.php', 'error', 'Session expired. Please login again.');
}

/**
 * Change user password
 */
function change_password($user_id, $current_password, $new_password)
{
    $user = get_user_by_id($user_id);
    if (!$user) {
        return ['success' => false, 'errors' => ['User not found']];
    }

    if (!verify_password($current_password, $user['password'])) {
        return ['success' => false, 'errors' => ['Incorrect current password']];
    }

    // validate_password should be in functions/validation.php
    if (function_exists('validate_password')) {
        $validation = validate_password($new_password);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
    }

    $hashed_password = hash_password($new_password);
    $query = "UPDATE users SET password = :password WHERE id = :id";
    db_query($query);
    db_bind(':password', $hashed_password);
    db_bind(':id', $user_id);

    if (db_execute()) {
        return ['success' => true, 'message' => 'Password updated successfully'];
    }

    return ['success' => false, 'errors' => ['Failed to update password']];
}

/**
 * Update user profile
 */
function update_user_profile($user_id, $data)
{
    // Filter allowed fields that actually exist in the DB
    $allowed_fields = ['name', 'email', 'phone', 'address', 'city', 'state', 'zip', 'country'];
    $updates = [];
    $params = [':id' => $user_id];

    foreach ($allowed_fields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = :$field";
            $params[":$field"] = $data[$field];
        }
    }

    if (empty($updates)) {
        return ['success' => true, 'message' => 'No changes to save'];
    }

    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";

    db_query($sql);

    foreach ($params as $key => $value) {
        db_bind($key, $value);
    }

    if (db_execute()) {
        // Update session data if changed
        if (isset($data['name'])) {
            $_SESSION['user_name'] = $data['name'];
        }
        if (isset($data['email'])) {
            $_SESSION['user_email'] = $data['email'];
        }
        return ['success' => true, 'message' => 'Profile updated successfully'];
    }

    return ['success' => false, 'errors' => ['Failed to update profile']];
}

?>