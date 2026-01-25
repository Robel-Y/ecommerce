<?php
/* ============================================
   SECURITY FUNCTIONS - Procedural
   Security-related functions for the system
============================================ */

/**
 * Start secure session
 * 
 * @return void
 */
function start_secure_session() {
    // Set session cookie parameters
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    // Start session
    session_start();
    
    // Regenerate session ID periodically
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

/**
 * Validate user session
 * 
 * @return bool True if valid session
 */
function validate_session() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Check session expiration
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        return false;
    }
    
    // Check user agent for session hijacking
    $current_user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $current_user_agent) {
        return false;
    }
    
    // Check IP address (optional, can cause issues with proxies)
    if (defined('CHECK_SESSION_IP') && CHECK_SESSION_IP) {
        $current_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== $current_ip) {
            return false;
        }
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Logout user and destroy session
 * 
 * @return void
 */
function logout_user() {
    // Unset all session variables
    $_SESSION = [];
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
}

/**
 * Hash password using bcrypt
 * 
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password against hash
 * 
 * @param string $password Plain text password
 * @param string $hash Hashed password
 * @return bool True if password matches
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if password needs rehashing
 * 
 * @param string $hash Hashed password
 * @return bool True if password needs rehash
 */
function password_needs_rehash($hash) {
    return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Generate password reset token
 * 
 * @param int $user_id User ID
 * @param int $expiry Expiry time in seconds
 * @return string Token
 */
function generate_reset_token($user_id, $expiry = 3600) {
    $token = bin2hex(random_bytes(32));
    $expiry_time = time() + $expiry;
    
    // Store token in database (simplified for example)
    // In real implementation, store in database with user_id and expiry
    $_SESSION['reset_tokens'][$user_id] = [
        'token' => hash('sha256', $token),
        'expiry' => $expiry_time
    ];
    
    return $token;
}

/**
 * Validate password reset token
 * 
 * @param int $user_id User ID
 * @param string $token Token to validate
 * @return bool True if valid token
 */
function validate_reset_token($user_id, $token) {
    if (!isset($_SESSION['reset_tokens'][$user_id])) {
        return false;
    }
    
    $stored = $_SESSION['reset_tokens'][$user_id];
    
    // Check expiry
    if (time() > $stored['expiry']) {
        unset($_SESSION['reset_tokens'][$user_id]);
        return false;
    }
    
    // Check token
    if (!hash_equals($stored['token'], hash('sha256', $token))) {
        return false;
    }
    
    return true;
}

/**
 * Clear password reset token
 * 
 * @param int $user_id User ID
 * @return void
 */
function clear_reset_token($user_id) {
    if (isset($_SESSION['reset_tokens'][$user_id])) {
        unset($_SESSION['reset_tokens'][$user_id]);
    }
}

/**
 * Check login attempts and prevent brute force
 * 
 * @param string $identifier Username or email
 * @param string $ip User IP address
 * @return bool True if allowed to attempt login
 */
function check_login_attempts($identifier, $ip) {
    $max_attempts = 5;
    $lockout_time = 900; // 15 minutes
    
    $key = 'login_attempts_' . md5($identifier . $ip);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 0,
            'first_attempt' => time()
        ];
    }
    
    $attempts = $_SESSION[$key];
    
    // Reset if lockout time has passed
    if (time() - $attempts['first_attempt'] > $lockout_time) {
        unset($_SESSION[$key]);
        return true;
    }
    
    // Check if max attempts reached
    if ($attempts['attempts'] >= $max_attempts) {
        return false;
    }
    
    return true;
}

/**
 * Record failed login attempt
 * 
 * @param string $identifier Username or email
 * @param string $ip User IP address
 * @return void
 */
function record_failed_login($identifier, $ip) {
    $key = 'login_attempts_' . md5($identifier . $ip);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 1,
            'first_attempt' => time()
        ];
    } else {
        $_SESSION[$key]['attempts']++;
    }
}

/**
 * Clear login attempts on successful login
 * 
 * @param string $identifier Username or email
 * @param string $ip User IP address
 * @return void
 */
function clear_login_attempts($identifier, $ip) {
    $key = 'login_attempts_' . md5($identifier . $ip);
    unset($_SESSION[$key]);
}

/**
 * Generate secure random bytes
 * 
 * @param int $length Length in bytes
 * @return string Random bytes
 */
function secure_random_bytes($length = 32) {
    if (function_exists('random_bytes')) {
        return random_bytes($length);
    }
    
    if (function_exists('openssl_random_pseudo_bytes')) {
        $bytes = openssl_random_pseudo_bytes($length, $strong);
        if ($strong === true) {
            return $bytes;
        }
    }
    
    // Fallback (less secure)
    $bytes = '';
    for ($i = 0; $i < $length; $i++) {
        $bytes .= chr(mt_rand(0, 255));
    }
    
    return $bytes;
}

/**
 * Generate secure token
 * 
 * @param int $length Token length
 * @return string Secure token
 */
function generate_secure_token($length = 32) {
    return bin2hex(secure_random_bytes($length / 2));
}

/**
 * Encrypt data
 * 
 * @param string $data Data to encrypt
 * @param string $key Encryption key
 * @return string Encrypted data
 */
function encrypt_data($data, $key) {
    $method = 'AES-256-CBC';
    $iv_length = openssl_cipher_iv_length($method);
    $iv = openssl_random_pseudo_bytes($iv_length);
    
    $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
    
    return base64_encode($iv . $encrypted);
}

/**
 * Decrypt data
 * 
 * @param string $data Encrypted data
 * @param string $key Encryption key
 * @return string|bool Decrypted data or false on failure
 */
function decrypt_data($data, $key) {
    $method = 'AES-256-CBC';
    $data = base64_decode($data);
    $iv_length = openssl_cipher_iv_length($method);
    $iv = substr($data, 0, $iv_length);
    $encrypted = substr($data, $iv_length);
    
    return openssl_decrypt($encrypted, $method, $key, 0, $iv);
}

/**
 * Sanitize filename
 * 
 * @param string $filename Filename to sanitize
 * @return string Sanitized filename
 */
function sanitize_filename($filename) {
    // Remove path information
    $filename = basename($filename);
    
    // Replace spaces with underscores
    $filename = str_replace(' ', '_', $filename);
    
    // Remove special characters
    $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $filename);
    
    // Remove multiple dots
    $filename = preg_replace('/\.+/', '.', $filename);
    
    // Ensure extension is safe
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'];
    
    if (!in_array($extension, $allowed_extensions)) {
        $filename = pathinfo($filename, PATHINFO_FILENAME) . '.txt';
    }
    
    return $filename;
}

/**
 * Validate file upload for security
 * 
 * @param array $file $_FILES array element
 * @param string $upload_dir Upload directory
 * @return array Array with validation results
 */
function secure_file_upload($file, $upload_dir) {
    $errors = [];
    
    // Check if upload directory exists
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            $errors[] = 'Upload directory does not exist and could not be created';
            return ['success' => false, 'errors' => $errors, 'filepath' => null];
        }
    }
    
    // Validate upload
    $validation = validate_file_upload($file);
    if (!$validation['valid']) {
        return ['success' => false, 'errors' => $validation['errors'], 'filepath' => null];
    }
    
    // Generate secure filename
    $original_name = $file['name'];
    $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    $filename = uniqid('file_', true) . '.' . $extension;
    $filepath = $upload_dir . DIRECTORY_SEPARATOR . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Set proper permissions
        chmod($filepath, 0644);
        
        return [
            'success' => true,
            'errors' => [],
            'filepath' => $filepath,
            'filename' => $filename,
            'original_name' => $original_name
        ];
    } else {
        $errors[] = 'Failed to move uploaded file';
        return ['success' => false, 'errors' => $errors, 'filepath' => null];
    }
}

/**
 * Prevent XSS attacks by cleaning output
 * 
 * @param string $string String to clean
 * @param bool $strip_tags Whether to strip HTML tags
 * @return string Cleaned string
 */
function prevent_xss($string, $strip_tags = false) {
    if ($strip_tags) {
        $string = strip_tags($string);
    }
    
    $string = htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // Remove JavaScript event handlers
    $string = preg_replace('/on\w+=\s*["\'][^"\']*["\']/i', '', $string);
    $string = preg_replace('/javascript:\s*[^"\']*/i', '', $string);
    
    return $string;
}

/**
 * Validate SQL query (basic injection prevention)
 * 
 * @param string $query SQL query
 * @return bool True if query appears safe
 */
function validate_sql_query($query) {
    // List of dangerous SQL keywords
    $dangerous_keywords = [
        'DROP', 'DELETE', 'TRUNCATE', 'ALTER', 'CREATE', 'EXEC', 'EXECUTE',
        'UNION', 'INSERT', 'UPDATE', 'GRANT', 'REVOKE', 'SHUTDOWN'
    ];
    
    $query_upper = strtoupper($query);
    
    foreach ($dangerous_keywords as $keyword) {
        if (strpos($query_upper, $keyword) !== false) {
            // Check if keyword is part of a larger word or in quotes
            $pattern = '/\b' . $keyword . '\b/i';
            if (preg_match($pattern, $query)) {
                return false;
            }
        }
    }
    
    return true;
}

/**
 * Log security event
 * 
 * @param string $event Event description
 * @param string $level Event level (info, warning, error, critical)
 * @param array $data Additional data
 * @return void
 */
function log_security_event($event, $level = 'info', $data = []) {
    $log_dir = dirname(__DIR__) . '/logs';
    
    // Create logs directory if it doesn't exist
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . '/security.log';
    
    $log_entry = sprintf(
        "[%s] %s: %s - IP: %s - User Agent: %s - Data: %s\n",
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $event,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        json_encode($data)
    );
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Check if request is from same origin
 * 
 * @return bool True if same origin
 */
function is_same_origin() {
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    if (!empty($origin)) {
        $server_host = $_SERVER['HTTP_HOST'];
        return parse_url($origin, PHP_URL_HOST) === $server_host;
    }
    
    if (!empty($referer)) {
        $server_host = $_SERVER['HTTP_HOST'];
        return parse_url($referer, PHP_URL_HOST) === $server_host;
    }
    
    return false;
}

/**
 * Set security headers
 * 
 * @return void
 */
function set_security_headers() {
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy (basic)
    $csp = "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data: https:;";
    header("Content-Security-Policy: $csp");
    
    // Feature Policy
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
    
    // Strict Transport Security (only if using HTTPS)
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

/**
 * Detect and prevent SQL injection
 * 
 * @param mysqli $connection Database connection
 * @param string $string String to check
 * @return bool True if string appears safe
 */
function detect_sql_injection($connection, $string) {
    // Check for SQL comments
    if (preg_match('/--|\/\*|\*\//', $string)) {
        return false;
    }
    
    // Check for semicolons (except at end)
    if (preg_match('/;.*[^;]$/', $string)) {
        return false;
    }
    
    // Check for UNION statements
    if (preg_match('/\bUNION\b/i', $string)) {
        return false;
    }
    
    // Check for stacked queries
    if (preg_match('/;.*\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER)\b/i', $string)) {
        return false;
    }
    
    return true;
}

/**
 * Rate limiting function
 * 
 * @param string $key Rate limit key
 * @param int $limit Maximum requests
 * @param int $window Time window in seconds
 * @return array Array with 'allowed' boolean and 'remaining' requests
 */
function rate_limit($key, $limit = 60, $window = 60) {
    $redis_key = "rate_limit:{$key}";
    
    // For simplicity, using session (in production, use Redis or similar)
    if (!isset($_SESSION['rate_limits'][$redis_key])) {
        $_SESSION['rate_limits'][$redis_key] = [
            'count' => 1,
            'start' => time()
        ];
    } else {
        $data = $_SESSION['rate_limits'][$redis_key];
        
        // Reset if window has passed
        if (time() - $data['start'] > $window) {
            $_SESSION['rate_limits'][$redis_key] = [
                'count' => 1,
                'start' => time()
            ];
        } else {
            // Increment count
            $_SESSION['rate_limits'][$redis_key]['count']++;
        }
    }
    
    $current = $_SESSION['rate_limits'][$redis_key]['count'];
    $remaining = max(0, $limit - $current);
    
    return [
        'allowed' => $current <= $limit,
        'remaining' => $remaining,
        'limit' => $limit,
        'reset' => $_SESSION['rate_limits'][$redis_key]['start'] + $window
    ];
}
?>