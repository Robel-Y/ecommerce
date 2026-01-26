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

$supported_statuses = get_orders_supported_statuses();
if ($order_id <= 0 || $status === '' || !in_array($status, $supported_statuses, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request (unsupported status for this database)']);
    exit;
}

// Ensure order exists + get current status
if (!db_query('SELECT id, status FROM orders WHERE id = :id')) {
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

$current_status = (string) ($order['status'] ?? '');
$supports_processing = in_array('processing', $supported_statuses, true);

// Enforce step-based transitions
$allowed_transitions = [];
if ($supports_processing) {
    $allowed_transitions = [
        'pending' => ['processing', 'cancelled'],
        'processing' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];
} else {
    // Schema doesn't support processing: allow pending -> completed/cancelled.
    $allowed_transitions = [
        'pending' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];
}

$next_allowed = $allowed_transitions[$current_status] ?? [];
if (!in_array($status, $next_allowed, true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid status transition',
    ]);
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
