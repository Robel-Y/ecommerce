<?php
/* ============================================
   LOGOUT PROCESS - Procedural
   Handle user logout
============================================ */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../config/constants.php';
    start_secure_session();
}

// Include required files
require_once __DIR__ . '/../functions/security.php';

// Log security event
if (isset($_SESSION['user_id'])) {
    log_security_event('User logout', 'info', [
        'user_id' => $_SESSION['user_id']
    ]);
}

// Logout user
logout_user();

// Redirect to home page with success message
redirect_with_message('index.php', 'success', 'You have been logged out successfully.');
?>