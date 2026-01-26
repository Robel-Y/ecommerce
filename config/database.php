<?php
/* ============================================
   DATABASE CONNECTION & FUNCTIONS - Procedural
   Uses PDO with prepared statements
============================================ */

require_once 'constants.php';
require_once __DIR__ . '/../functions/validation.php';


/* ============================================
   INITIALIZATION
============================================ */

$db_connection = null;
$db_stmt = null;
$db_error_log = BASE_PATH . '/logs/database_errors.log';

// Ensure logs directory exists
$log_dir = dirname($db_error_log);
if (!is_dir($log_dir)) {
    if (!mkdir($log_dir, 0755, true)) {
        die("Unable to create logs directory: $log_dir. Check permissions.");
    }
}

/* ============================================
   CONNECTION
============================================ */
function db_connect()
{
    global $db_connection, $db_error_log;

    if ($db_connection !== null)
        return $db_connection;

    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
    ];

    try {
        $db_connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $db_connection;
    } catch (PDOException $e) {
        db_log_error($e->getMessage());
        die("Database connection failed. Check your configuration.");
    }
}

/* ============================================
   LOGGING
============================================ */
function db_log_error($message)
{
    global $db_error_log;
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Database Error: $message" . PHP_EOL;
    @file_put_contents($db_error_log, $logMessage, FILE_APPEND | LOCK_EX);
}

/* ============================================
   QUERY FUNCTIONS
============================================ */
function db_query($sql)
{
    global $db_connection, $db_stmt;
    $conn = db_connect();
    // Always reset the last statement so a failed prepare can't leave a stale PDOStatement.
    $db_stmt = null;
    try {
        $db_stmt = $conn->prepare($sql);
        return true;
    } catch (PDOException $e) {
        $db_stmt = null;
        db_log_error($e->getMessage());
        return false;
    }
}

function db_bind($param, $value, $type = null)
{
    global $db_stmt;
    if (!$db_stmt)
        return false;

    if ($type === null) {
        switch (true) {
            case is_int($value):
                $type = PDO::PARAM_INT;
                break;
            case is_bool($value):
                $type = PDO::PARAM_BOOL;
                break;
            case is_null($value):
                $type = PDO::PARAM_NULL;
                break;
            default:
                $type = PDO::PARAM_STR;
        }
    }
    return $db_stmt->bindValue($param, $value, $type);
}

function db_execute()
{
    global $db_stmt;
    if (!$db_stmt)
        return false;

    try {
        return $db_stmt->execute();
    } catch (PDOException $e) {
        db_log_error($e->getMessage());
        return false;
    }
}

function db_result_set()
{
    global $db_stmt;
    db_execute();
    return $db_stmt ? $db_stmt->fetchAll() : [];
}

function db_single()
{
    global $db_stmt;
    db_execute();
    return $db_stmt ? $db_stmt->fetch() : false;
}

function db_row_count()
{
    global $db_stmt;
    return $db_stmt ? $db_stmt->rowCount() : 0;
}

function db_last_insert_id()
{
    $conn = db_connect();
    return $conn->lastInsertId();
}

/* ============================================
   TRANSACTIONS
============================================ */
function db_begin_transaction()
{
    return db_connect()->beginTransaction();
}
function db_commit()
{
    return db_connect()->commit();
}
function db_rollback()
{
    return db_connect()->rollBack();
}

/* ============================================
   SANITIZATION & UTILITY
============================================ */


function db_generate_uuid()
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

/* ============================================
   INITIALIZE CONNECTION
============================================ */
db_connect();
?>