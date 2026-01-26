<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../functions/utilities.php';
require_once __DIR__ . '/../functions/security.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    start_secure_session();
}

if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
$new_role = isset($_POST['role']) ? trim((string) $_POST['role']) : '';

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user.']);
    exit;
}

if ($new_role !== 'admin' && $new_role !== 'user') {
    echo json_encode(['success' => false, 'message' => 'Invalid role.']);
    exit;
}

$current_user_id = (int) ($_SESSION['user_id'] ?? 0);
if ($current_user_id > 0 && $user_id === $current_user_id) {
    echo json_encode(['success' => false, 'message' => 'You cannot change your own role.']);
    exit;
}

if (!db_query('SELECT id, role FROM users WHERE id = ? LIMIT 1')) {
    echo json_encode(['success' => false, 'message' => 'Failed to load user.']);
    exit;
}
db_bind(1, $user_id, PDO::PARAM_INT);
$user = db_single();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

$old_role = (string) ($user['role'] ?? 'user');

// Prevent removing the last admin
if ($old_role === 'admin' && $new_role !== 'admin') {
    if (db_query("SELECT COUNT(*) AS c FROM users WHERE role = 'admin'")) {
        $row = db_single();
        $admin_count = (int) ($row['c'] ?? 0);
        if ($admin_count <= 1) {
            echo json_encode(['success' => false, 'message' => 'Cannot remove the last admin.']);
            exit;
        }
    }
}

if (!db_query('UPDATE users SET role = ? WHERE id = ?')) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare update.']);
    exit;
}
db_bind(1, $new_role);
db_bind(2, $user_id, PDO::PARAM_INT);

if (!db_execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to update role.']);
    exit;
}

echo json_encode(['success' => true]);
