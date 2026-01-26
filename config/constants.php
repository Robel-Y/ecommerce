<?php
// Site Configuration
/**
 * SITE CONFIGURATION
 */
if (!defined('SITE_NAME'))
    define('SITE_NAME', 'Merkato Go');
if (!defined('SITE_URL'))
    define('SITE_URL', 'http://localhost/ecommerce/');
if (!defined('SITE_EMAIL'))
    define('SITE_EMAIL', 'support@merkato.com');
if (!defined('ADMIN_EMAIL'))
    define('ADMIN_EMAIL', 'admin@merkato.com');

// Common helpers (used by many endpoints immediately after requiring constants)
require_once __DIR__ . '/../functions/security.php';

/**
 * DATABASE CONFIGURATION
 */
if (!defined('DB_HOST'))
    define('DB_HOST', 'localhost');
if (!defined('DB_USER'))
    define('DB_USER', 'root');
if (!defined('DB_PASS'))
    define('DB_PASS', '');
if (!defined('DB_NAME'))
    define('DB_NAME', 'modern_shop_db');
if (!defined('DB_CHARSET'))
    define('DB_CHARSET', 'utf8mb4');

/**
 * SESSION CONFIGURATION
 */
if (!defined('SESSION_TIMEOUT'))
    define('SESSION_TIMEOUT', 1800); // 30 minutes
if (!defined('SESSION_REGENERATE'))
    define('SESSION_REGENERATE', true);

/**
 * SECURITY CONFIGURATION
 */
if (!defined('MAX_LOGIN_ATTEMPTS'))
    define('MAX_LOGIN_ATTEMPTS', 5);
if (!defined('LOGIN_LOCKOUT_TIME'))
    define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes


/**
 * PATH CONFIGURATION
 */
if (!defined('BASE_PATH'))
    define('BASE_PATH', dirname(__DIR__));
if (!defined('UPLOAD_PATH'))
    define('UPLOAD_PATH', BASE_PATH . '/assets/uploads/');
if (!defined('MAX_UPLOAD_SIZE'))
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
if (!function_exists('startSecureSession')) {
    function startSecureSession()
    {
        // Delegate to the unified session starter
        if (function_exists('start_secure_session')) {
            start_secure_session();
            return;
        }

        // Fallback (should rarely be used)
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SECURE_SESSION');
            session_start();
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            }
        }
    }
}




// Clean URL
if (!function_exists('cleanURL')) {
    function cleanURL($url)
    {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

// Get Current URL
if (!function_exists('currentURL')) {
    function currentURL()
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        return $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
}

// Redirect with message
if (!function_exists('redirectWithMessage')) {
    function redirectWithMessage($url, $type = 'success', $message = '')
    {
        if (!empty($message)) {
            $_SESSION['flash_message'] = [
                'type' => $type,
                'text' => $message
            ];
        }
        header("Location: " . cleanURL($url));
        exit();
    }
}

// Get Flash Message
if (!function_exists('getFlashMessage')) {
    function getFlashMessage()
    {
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $message;
        }
        return null;
    }
}

// Check if user is logged in
if (!function_exists('isLoggedIn')) {
    function isLoggedIn()
    {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
    }

    // Check if user is admin
    function isAdmin()
    {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}

// Get user ID
if (!function_exists('getUserId')) {
    function getUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }
}

// Logout function
if (!function_exists('logout')) {
    function logout()
    {
        $_SESSION = array();

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
    }
}
// Start secure session on every page
// Start secure session if not already started
if (session_status() === PHP_SESSION_NONE) {
    startSecureSession();
}

?>