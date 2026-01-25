<?php
// Authentication Check
require_once '../config/constants.php';

/**
 * Check if user is authenticated
 * Redirects to login page if not authenticated
 * 
 * @param bool $redirect Whether to redirect to login page
 * @return bool True if authenticated, false otherwise
 */
function requireAuth($redirect = true) {
    if (!isLoggedIn()) {
        if ($redirect) {
            redirectWithMessage('user/login.php', 'error', 'Please login to access this page');
        }
        return false;
    }
    return true;
}

/**
 * Check if user is admin
 * Redirects to home page if not admin
 * 
 * @param bool $redirect Whether to redirect to home page
 * @return bool True if admin, false otherwise
 */
function requireAdmin($redirect = true) {
    if (!isAdmin()) {
        if ($redirect) {
            redirectWithMessage('index.php', 'error', 'Access denied. Admin privileges required.');
        }
        return false;
    }
    return true;
}

/**
 * Prevent logged-in users from accessing auth pages
 * Redirects to home page if already logged in
 */
function preventAuthenticated() {
    if (isLoggedIn()) {
        redirectWithMessage('index.php', 'info', 'You are already logged in');
    }
}

/**
 * Validate user session
 * Checks session expiration and activity
 * 
 * @return bool True if session is valid, false otherwise
 */
function validateSession() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Check session expiration
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        logout();
        return false;
    }
    
    // Check user agent for session hijacking protection
    $currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (isset($_SESSION['user_agent']) && 
        $_SESSION['user_agent'] !== $currentUserAgent) {
        logout();
        return false;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Check login attempts and apply rate limiting
 * 
 * @param string $email User email
 * @return bool True if allowed to login, false if blocked
 */
function checkLoginAttempts($email) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = 'login_attempts_' . md5($email . $ip);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 0,
            'first_attempt' => time()
        ];
    }
    
    $attempts = $_SESSION[$key];
    
    // Reset attempts if lockout time has passed
    if (time() - $attempts['first_attempt'] > LOGIN_LOCKOUT_TIME) {
        unset($_SESSION[$key]);
        return true;
    }
    
    // Check if max attempts reached
    if ($attempts['attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $remaining_time = LOGIN_LOCKOUT_TIME - (time() - $attempts['first_attempt']);
        if ($remaining_time > 0) {
            throw new Exception("Too many login attempts. Please try again in " . 
                ceil($remaining_time / 60) . " minutes.");
        }
        unset($_SESSION[$key]);
    }
    
    return true;
}

/**
 * Record failed login attempt
 * 
 * @param string $email User email
 */
function recordFailedLogin($email) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = 'login_attempts_' . md5($email . $ip);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 1,
            'first_attempt' => time()
        ];
    } else {
        $_SESSION[$key]['attempts']++;
    }
}

/**
 * Clear login attempts on successful login
 * 
 * @param string $email User email
 */
function clearLoginAttempts($email) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = 'login_attempts_' . md5($email . $ip);
    unset($_SESSION[$key]);
}

/**
 * Generate secure password hash
 * 
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 * 
 * @param string $password Plain text password
 * @param string $hash Hashed password
 * @return bool True if password matches
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Validate password strength
 * 
 * @param string $password Password to validate
 * @return array Array of errors, empty if valid
 */
function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < MIN_PASSWORD_LENGTH) {
        $errors[] = "Password must be at least " . MIN_PASSWORD_LENGTH . " characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return $errors;
}

/**
 * Generate password reset token
 * 
 * @param int $userId User ID
 * @return array Token and expiry time
 */
function generateResetToken($userId) {
    $token = bin2hex(random_bytes(32));
    $expiry = time() + 3600; // 1 hour
    
    // Store token in session for demo purposes
    // In production, store in database
    $_SESSION['reset_token_' . $userId] = [
        'token' => $token,
        'expiry' => $expiry
    ];
    
    return [
        'token' => $token,
        'expiry' => $expiry
    ];
}

/**
 * Validate password reset token
 * 
 * @param int $userId User ID
 * @param string $token Token to validate
 * @return bool True if token is valid
 */
function validateResetToken($userId, $token) {
    $key = 'reset_token_' . $userId;
    
    if (!isset($_SESSION[$key])) {
        return false;
    }
    
    $storedToken = $_SESSION[$key];
    
    // Check token and expiry
    if (!hash_equals($storedToken['token'], $token)) {
        unset($_SESSION[$key]);
        return false;
    }
    
    if (time() > $storedToken['expiry']) {
        unset($_SESSION[$key]);
        return false;
    }
    
    return true;
}

/**
 * Clear password reset token
 * 
 * @param int $userId User ID
 */
function clearResetToken($userId) {
    $key = 'reset_token_' . $userId;
    unset($_SESSION[$key]);
}

// Initialize session validation on every page
if (isLoggedIn()) {
    if (!validateSession()) {
        redirectWithMessage('user/login.php', 'error', 'Session expired. Please login again.');
    }
}
?>