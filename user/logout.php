<?php
/* ============================================
   USER LOGOUT HANDLER
   Securely logout and destroy session
============================================ */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../config/constants.php';
    require_once __DIR__ . '/../functions/security.php';
    start_secure_session();
}

// Include required files
require_once __DIR__ . '/../functions/utilities.php';

// Perform logout
if (session_status() === PHP_SESSION_ACTIVE) {
    require_once __DIR__ . '/../includes/auth_check.php';
    logout_user();
}

// Redirect to home page with message
redirect_with_message(SITE_URL . 'index.php', 'success', 'You have been successfully logged out.');
?>