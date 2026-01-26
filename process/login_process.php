<?php
/* ============================================
   LOGIN PROCESS - Procedural
   Handle user login
============================================ */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../config/constants.php';
    start_secure_session();
}

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/validation.php';
require_once __DIR__ . '/../functions/security.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_message('user/login.php', 'error', 'Invalid request method');
}



// Get form data
$email = sanitize_input($_POST['email'] ?? '', 'email');
$password = $_POST['password'] ?? '';
$remember_me = isset($_POST['remember_me']);

// Store email for repopulating
$_SESSION['login_email'] = $email;

// Validate inputs
if (empty($email) || empty($password)) {
    redirect_with_message('user/login.php', 'error', 'Please fill in all fields');
}

if (!validate_email($email)) {
    redirect_with_message('user/login.php', 'error', 'Invalid email format');
}

// Verify login credentials
$login_result = verify_login($email, $password);

if (!$login_result['success']) {
    // Record failed login attempt
    $ip = get_client_ip();
    record_failed_login($email, $ip);

    // Check if account is locked
    if (!check_login_attempts($email, $ip)) {
        redirect_with_message('user/login.php', 'error', 'Account locked due to too many failed attempts. Please try again later.');
    }

    redirect_with_message('user/login.php', 'error', $login_result['errors'][0] ?? 'Invalid credentials');
}

// Login successful
login_user($login_result['user'], $remember_me);

// Clear login email from session
unset($_SESSION['login_email']);

// Check for redirect URL
$redirect_url = $_SESSION['redirect_url'] ?? 'index.php';
unset($_SESSION['redirect_url']);

// Redirect to appropriate page
redirect_with_message($redirect_url, 'success', 'Login successful! Welcome back.');
?>