<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../functions/utilities.php';
require_once __DIR__ . '/../functions/security.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    start_secure_session();
}

if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
$status = isset($_POST['status']) ? sanitize_input($_POST['status'], 'string') : '';

$allowed_statuses = ['pending', 'processing', 'completed', 'cancelled'];
if ($order_id <= 0 || $status === '' || !in_array($status, $allowed_statuses, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Ensure order exists
if (!db_query('SELECT id FROM orders WHERE id = :id')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

db_bind(':id', $order_id, PDO::PARAM_INT);
$order = db_single();
if (!$order) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

// Update status
if (!db_query('UPDATE orders SET status = :status WHERE id = :id')) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Failed to update order status (schema may not allow this status)']);
    exit;
}

db_bind(':status', $status);
db_bind(':id', $order_id, PDO::PARAM_INT);
$ok = db_execute();

if (!$ok) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

echo json_encode(['success' => true]);
