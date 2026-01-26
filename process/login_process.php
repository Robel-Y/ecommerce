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
require_once __DIR__ . '/../functions/request.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_message('user/login.php', 'error', 'Invalid request method');
}



// Get form data
$email = request_post_string('email', 'email', '');
$password = (string) ($_POST['password'] ?? '');
$remember_me = request_post_bool('remember_me');

// Store email for repopulating
$_SESSION['login_email'] = $email;

// Validate inputs
$validation = request_validate(
    ['email' => $email, 'password' => $password],
    [
        'email' => ['required', 'email', 'max_length:254'],
        'password' => ['required', 'min_length:6', 'max_length:255'],
    ],
    ['email' => 'email']
);

if (!$validation['valid']) {
    redirect_with_message('user/login.php', 'error', 'Please check your email and password.');
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