<?php
/*
  One-time admin user seeder.
  - Creates an admin user with a hashed password.
  - Locks after first successful run.
  - Localhost only.

  Usage:
    http://localhost/ecommerce/tools/create_admin.php?run=1
*/

declare(strict_types=1);

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$allowed = ($ip === '127.0.0.1' || $ip === '::1');
if (!$allowed) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden\n";
    exit;
}

$lockFile = __DIR__ . '/.admin_seed.lock';
if (file_exists($lockFile)) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Already seeded. Delete tools/.admin_seed.lock to rerun (not recommended).\n";
    exit;
}

if (($_GET['run'] ?? '') !== '1') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Ready. Run with ?run=1\n";
    exit;
}

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/validation.php';
require_once __DIR__ . '/../functions/utilities.php';

// Credentials (simple as requested)
$name = 'admin';
$email = 'admin@gmail.com';
$passwordPlain = 'Admin@123';

$hash = password_hash($passwordPlain, PASSWORD_BCRYPT);
if (!$hash) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Failed to hash password\n";
    exit;
}

// If user already exists, do not overwrite by default.
db_query('SELECT id, role FROM users WHERE email = :email LIMIT 1');
db_bind(':email', $email);
$existing = db_single();

if ($existing && isset($existing['id'])) {
    http_response_code(409);
    header('Content-Type: text/plain; charset=utf-8');
    echo "User already exists: $email (id=" . (int)$existing['id'] . ")\n";
    echo "If you want to reset it, delete this user in DB and rerun.\n";
    exit;
}

// Insert admin
$dbOk = false;
try {
    db_query('INSERT INTO users (name, email, password, role, created_at) VALUES (:name, :email, :password, :role, NOW())');
    db_bind(':name', $name);
    db_bind(':email', $email);
    db_bind(':password', $hash);
    db_bind(':role', 'admin');
    db_execute();
    $dbOk = true;
} catch (Throwable $e) {
    $dbOk = false;
}

if (!$dbOk) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Failed to create admin user (check DB connection/schema).\n";
    exit;
}

file_put_contents($lockFile, 'seeded ' . date('c') . "\n", LOCK_EX);

header('Content-Type: text/plain; charset=utf-8');
echo "Admin user created successfully\n\n";
echo "Email:    $email\n";
echo "Password: $passwordPlain\n";
echo "Role:     admin\n\n";
echo "Security: delete tools/create_admin.php now (recommended).\n";
