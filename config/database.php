<?php
require_once 'constants.php';

class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    private $charset = DB_CHARSET;
    
    private $connection;
    private $stmt;
    private $error;
    
    public function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
        
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
        );
        
        try {
            $this->connection = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            $this->logError($e->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }
    
    // Prepare statement with query
    public function query($sql) {
        $this->stmt = $this->connection->prepare($sql);
    }
    
    // Bind values
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
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
        
        $this->stmt->bindValue($param, $value, $type);
    }
    
    // Execute the prepared statement
    public function execute() {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            return false;
        }
    }
    
    // Get result set as array of objects
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }
    
    // Get single record as object
    public function single() {
        $this->execute();
        return $this->stmt->fetch();
    }
    
    // Get row count
    public function rowCount() {
        return $this->stmt->rowCount();
    }
    
    // Get last inserted ID
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    // Begin transaction
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    // End transaction
    public function endTransaction() {
        return $this->connection->commit();
    }
    
    // Cancel transaction
    public function cancelTransaction() {
        return $this->connection->rollBack();
    }
    
    // Debug dump parameters
    public function debugDumpParams() {
        return $this->stmt->debugDumpParams();
    }
    
    // Close connection
    public function close() {
        $this->connection = null;
        $this->stmt = null;
    }
    
    // Error logging
    private function logError($error) {
        $logMessage = "[" . date('Y-m-d H:i:s') . "] Database Error: " . $error . PHP_EOL;
        $logFile = BASE_PATH . '/logs/database_errors.log';
        
        // Create logs directory if it doesn't exist
        if (!is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }
        
        // Log error (append to file)
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    // Get connection status
    public function isConnected() {
        return $this->connection !== null;
    }
    
    // Sanitize input
    public static function sanitize($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
    
    // Generate unique ID
    public static function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

// Create global database instance
$db = new Database();

// Test connection
if (!$db->isConnected()) {
    die("Database connection failed. Please check your configuration.");
}
?>