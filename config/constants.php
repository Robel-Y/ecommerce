<?php
// Site Configuration
define('SITE_NAME', 'Modern Shop');
define('SITE_URL', 'http://localhost/ecommerce-system/');
define('SITE_EMAIL', 'support@modernshop.com');
define('ADMIN_EMAIL', 'admin@modernshop.com');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'modern_shop_db');
define('DB_CHARSET', 'utf8mb4');

// Session Configuration
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('SESSION_REGENERATE', true);

// Security Configuration
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes
define('MIN_PASSWORD_LENGTH', 8);
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour

// Path Configuration
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/assets/uploads/');
define('MAX_UPLOAD_SIZE', 5242880); // 5MB

// Timezone
date_default_timezone_set('America/New_York');

// Error Reporting (Set to 0 in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

// Start Session with Security Settings
function startSecureSession() {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    
    session_name('SECURE_SESSION');
    session_start();
    
    // Regenerate session ID periodically
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > SESSION_TIMEOUT) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// CSRF Token Generation
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

// CSRF Token Validation
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    if ($_SESSION['csrf_token'] !== $token) {
        return false;
    }
    
    // Check token expiry
    if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_EXPIRY) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return false;
    }
    
    return true;
}

// Clean URL
function cleanURL($url) {
    return filter_var($url, FILTER_SANITIZE_URL);
}

// Get Current URL
function currentURL() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    return $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

// Redirect with message
function redirectWithMessage($url, $type = 'success', $message = '') {
    if (!empty($message)) {
        $_SESSION['flash_message'] = [
            'type' => $type,
            'text' => $message
        ];
    }
    header("Location: " . cleanURL($url));
    exit();
}

// Get Flash Message
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Get user ID
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Logout function
function logout() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

// Start secure session on every page
startSecureSession();
?>