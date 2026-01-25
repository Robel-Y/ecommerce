<?php
/* ============================================
   UTILITY FUNCTIONS - Procedural
   General helper functions for the system
============================================ */

/**
 * Redirect to another page
 * 
 * @param string $url URL to redirect to
 * @param int $status_code HTTP status code
 * @return void
 */
function redirect($url, $status_code = 302) {
    header("Location: $url", true, $status_code);
    exit();
}

/**
 * Redirect with flash message
 * 
 * @param string $url URL to redirect to
 * @param string $type Message type (success, error, warning, info)
 * @param string $message Message content
 * @return void
 */
function redirect_with_message($url, $type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'text' => $message
    ];
    redirect($url);
}

/**
 * Get and clear flash message
 * 
 * @return array|null Flash message array or null
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Format currency
 * 
 * @param float $amount Amount to format
 * @param string $currency Currency code
 * @return string Formatted currency
 */
function format_currency($amount, $currency = 'USD') {
    $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
    return $formatter->formatCurrency($amount, $currency);
}

/**
 * Format date
 * 
 * @param string $date Date string
 * @param string $format Output format
 * @return string Formatted date
 */
function format_date($date, $format = 'F j, Y') {
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }
    return date($format, $timestamp);
}

/**
 * Format date with time
 * 
 * @param string $datetime Datetime string
 * @param string $format Output format
 * @return string Formatted datetime
 */
function format_datetime($datetime, $format = 'F j, Y g:i A') {
    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
        return $datetime;
    }
    return date($format, $timestamp);
}

/**
 * Format file size
 * 
 * @param int $bytes File size in bytes
 * @param int $precision Decimal precision
 * @return string Formatted file size
 */
function format_file_size($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Truncate text
 * 
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @param string $suffix Suffix to add if truncated
 * @return string Truncated text
 */
function truncate_text($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    $text = substr($text, 0, $length);
    $text = substr($text, 0, strrpos($text, ' '));
    
    return $text . $suffix;
}

/**
 * Generate slug from string
 * 
 * @param string $string String to slugify
 * @return string Slug
 */
function generate_slug($string) {
    // Convert to lowercase
    $slug = strtolower($string);
    
    // Replace spaces with hyphens
    $slug = preg_replace('/\s+/', '-', $slug);
    
    // Remove special characters
    $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
    
    // Remove multiple hyphens
    $slug = preg_replace('/-+/', '-', $slug);
    
    // Trim hyphens from start and end
    $slug = trim($slug, '-');
    
    return $slug;
}

/**
 * Generate random string
 * 
 * @param int $length Length of random string
 * @param string $charset Character set
 * @return string Random string
 */
function generate_random_string($length = 10, $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789') {
    $str = '';
    $count = strlen($charset);
    
    for ($i = 0; $i < $length; $i++) {
        $str .= $charset[rand(0, $count - 1)];
    }
    
    return $str;
}

/**
 * Generate order number
 * 
 * @param int $user_id User ID
 * @return string Order number
 */
function generate_order_number($user_id = null) {
    $prefix = 'ORD';
    $timestamp = date('YmdHis');
    $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
    
    if ($user_id) {
        $user_part = str_pad($user_id % 1000, 3, '0', STR_PAD_LEFT);
        return "{$prefix}-{$timestamp}-{$user_part}-{$random}";
    }
    
    return "{$prefix}-{$timestamp}-{$random}";
}

/**
 * Generate invoice number
 * 
 * @param int $order_id Order ID
 * @return string Invoice number
 */
function generate_invoice_number($order_id) {
    $prefix = 'INV';
    $year = date('Y');
    $month = date('m');
    $sequence = str_pad($order_id, 6, '0', STR_PAD_LEFT);
    
    return "{$prefix}-{$year}{$month}-{$sequence}";
}

/**
 * Calculate age from birthdate
 * 
 * @param string $birthdate Birthdate string
 * @return int Age
 */
function calculate_age($birthdate) {
    $birthday = new DateTime($birthdate);
    $today = new DateTime('today');
    $age = $birthday->diff($today)->y;
    
    return $age;
}

/**
 * Calculate distance between two coordinates
 * 
 * @param float $lat1 Latitude 1
 * @param float $lon1 Longitude 1
 * @param float $lat2 Latitude 2
 * @param float $lon2 Longitude 2
 * @param string $unit Unit (miles or kilometers)
 * @return float Distance
 */
function calculate_distance($lat1, $lon1, $lat2, $lon2, $unit = 'miles') {
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + 
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;
    
    if ($unit == 'kilometers') {
        return $miles * 1.609344;
    }
    
    return $miles;
}

/**
 * Calculate tax amount
 * 
 * @param float $amount Amount
 * @param float $tax_rate Tax rate as percentage
 * @return float Tax amount
 */
function calculate_tax($amount, $tax_rate = 0) {
    return $amount * ($tax_rate / 100);
}

/**
 * Calculate shipping cost
 * 
 * @param float $weight Weight in kg
 * @param string $method Shipping method
 * @param string $destination Destination country
 * @return float Shipping cost
 */
function calculate_shipping($weight, $method = 'standard', $destination = 'US') {
    $base_cost = 5.00;
    $weight_cost = $weight * 2.00;
    
    switch ($method) {
        case 'express':
            $base_cost += 10.00;
            break;
        case 'priority':
            $base_cost += 5.00;
            break;
        case 'standard':
        default:
            // No additional cost
            break;
    }
    
    // International shipping
    if ($destination !== 'US') {
        $base_cost += 15.00;
    }
    
    return $base_cost + $weight_cost;
}

/**
 * Calculate discount
 * 
 * @param float $amount Amount
 * @param mixed $discount Discount value or percentage
 * @param string $type Discount type (percent or fixed)
 * @return float Discount amount
 */
function calculate_discount($amount, $discount, $type = 'percent') {
    if ($type === 'percent') {
        return $amount * ($discount / 100);
    }
    
    return min($discount, $amount);
}

/**
 * Generate pagination links
 * 
 * @param int $total_items Total number of items
 * @param int $current_page Current page number
 * @param int $per_page Items per page
 * @param string $base_url Base URL for pagination
 * @return array Pagination data
 */
function generate_pagination($total_items, $current_page, $per_page, $base_url = '') {
    $total_pages = ceil($total_items / $per_page);
    
    // Ensure current page is within bounds
    $current_page = max(1, min($current_page, $total_pages));
    
    // Calculate start and end page numbers
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
    
    // Adjust if we're near the beginning
    if ($current_page <= 3) {
        $end_page = min(5, $total_pages);
    }
    
    // Adjust if we're near the end
    if ($current_page >= $total_pages - 2) {
        $start_page = max(1, $total_pages - 4);
    }
    
    // Generate page numbers array
    $pages = [];
    for ($i = $start_page; $i <= $end_page; $i++) {
        $pages[] = $i;
    }
    
    return [
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'total_items' => $total_items,
        'per_page' => $per_page,
        'pages' => $pages,
        'has_previous' => $current_page > 1,
        'has_next' => $current_page < $total_pages,
        'previous_page' => $current_page - 1,
        'next_page' => $current_page + 1,
        'first_item' => ($current_page - 1) * $per_page + 1,
        'last_item' => min($current_page * $per_page, $total_items)
    ];
}

/**
 * Get pagination HTML
 * 
 * @param array $pagination Pagination data
 * @param string $base_url Base URL
 * @param string $query_param Query parameter name
 * @return string HTML pagination
 */
function get_pagination_html($pagination, $base_url = '', $query_param = 'page') {
    if ($pagination['total_pages'] <= 1) {
        return '';
    }
    
    $html = '<nav class="pagination" aria-label="Page navigation">';
    $html .= '<ul class="pagination-list">';
    
    // Previous button
    if ($pagination['has_previous']) {
        $prev_url = $base_url . '?' . $query_param . '=' . $pagination['previous_page'];
        $html .= '<li><a href="' . $prev_url . '" class="pagination-link prev" aria-label="Previous">&laquo; Previous</a></li>';
    }
    
    // Page numbers
    foreach ($pagination['pages'] as $page) {
        $active = $page == $pagination['current_page'] ? ' active' : '';
        $page_url = $base_url . '?' . $query_param . '=' . $page;
        $html .= '<li><a href="' . $page_url . '" class="pagination-link' . $active . '">' . $page . '</a></li>';
    }
    
    // Next button
    if ($pagination['has_next']) {
        $next_url = $base_url . '?' . $query_param . '=' . $pagination['next_page'];
        $html .= '<li><a href="' . $next_url . '" class="pagination-link next" aria-label="Next">Next &raquo;</a></li>';
    }
    
    $html .= '</ul>';
    $html .= '</nav>';
    
    return $html;
}

/**
 * Get file extension
 * 
 * @param string $filename Filename
 * @return string File extension
 */
function get_file_extension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Get mime type from extension
 * 
 * @param string $extension File extension
 * @return string MIME type
 */
function get_mime_type($extension) {
    $mime_types = [
        'txt' => 'text/plain',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',
        
        // Images
        'png' => 'image/png',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',
        
        // Archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',
        
        // Audio/video
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',
        'mp4' => 'video/mp4',
        'avi' => 'video/x-msvideo',
        'wmv' => 'video/x-ms-wmv',
        
        // Adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',
        
        // MS Office
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        
        // Open Office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    ];
    
    $extension = strtolower($extension);
    return isset($mime_types[$extension]) ? $mime_types[$extension] : 'application/octet-stream';
}

/**
 * Get client IP address
 * 
 * @return string IP address
 */
function get_client_ip() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Check for forwarded IP
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    
    // Handle multiple IPs
    if (strpos($ip, ',') !== false) {
        $ips = explode(',', $ip);
        $ip = trim($ips[0]);
    }
    
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/**
 * Get browser information
 * 
 * @return array Browser info
 */
function get_browser_info() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $browser = 'Unknown';
    $version = '';
    $platform = 'Unknown';
    
    // Platform
    if (preg_match('/linux/i', $user_agent)) {
        $platform = 'Linux';
    } elseif (preg_match('/macintosh|mac os x/i', $user_agent)) {
        $platform = 'Mac';
    } elseif (preg_match('/windows|win32/i', $user_agent)) {
        $platform = 'Windows';
    }
    
    // Browser
    if (preg_match('/MSIE/i', $user_agent) && !preg_match('/Opera/i', $user_agent)) {
        $browser = 'Internet Explorer';
        $pattern = '/MSIE\s([0-9\.]+)/';
    } elseif (preg_match('/Firefox/i', $user_agent)) {
        $browser = 'Firefox';
        $pattern = '/Firefox\/([0-9\.]+)/';
    } elseif (preg_match('/Chrome/i', $user_agent)) {
        $browser = 'Chrome';
        $pattern = '/Chrome\/([0-9\.]+)/';
    } elseif (preg_match('/Safari/i', $user_agent)) {
        $browser = 'Safari';
        $pattern = '/Version\/([0-9\.]+)/';
    } elseif (preg_match('/Opera/i', $user_agent)) {
        $browser = 'Opera';
        $pattern = '/Opera\/([0-9\.]+)/';
    } elseif (preg_match('/Netscape/i', $user_agent)) {
        $browser = 'Netscape';
        $pattern = '/Netscape\/([0-9\.]+)/';
    }
    
    if (isset($pattern) && preg_match($pattern, $user_agent, $matches)) {
        $version = $matches[1];
    }
    
    return [
        'user_agent' => $user_agent,
        'browser' => $browser,
        'version' => $version,
        'platform' => $platform
    ];
}

/**
 * Get current URL
 * 
 * @param bool $include_query Include query string
 * @return string Current URL
 */
function get_current_url($include_query = true) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    
    if (!$include_query) {
        $uri = strtok($uri, '?');
    }
    
    return $protocol . '://' . $host . $uri;
}

/**
 * Get base URL
 * 
 * @return string Base URL
 */
function get_base_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    
    $path = dirname($script);
    if ($path === '/') {
        $path = '';
    }
    
    return $protocol . '://' . $host . $path;
}

/**
 * Send email
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email message
 * @param string $from Sender email
 * @param array $headers Additional headers
 * @return bool True if email sent successfully
 */
function send_email($to, $subject, $message, $from = '', $headers = []) {
    if (empty($from)) {
        $from = 'noreply@' . $_SERVER['HTTP_HOST'];
    }
    
    // Default headers
    $default_headers = [
        'From' => $from,
        'Reply-To' => $from,
        'X-Mailer' => 'PHP/' . phpversion(),
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/html; charset=UTF-8'
    ];
    
    // Merge headers
    $email_headers = array_merge($default_headers, $headers);
    
    // Build headers string
    $headers_string = '';
    foreach ($email_headers as $key => $value) {
        $headers_string .= "$key: $value\r\n";
    }
    
    // Send email
    return mail($to, $subject, $message, $headers_string);
}

/**
 * Generate email template
 * 
 * @param string $title Email title
 * @param string $content Email content
 * @param array $data Additional data for template
 * @return string Email HTML
 */
function generate_email_template($title, $content, $data = []) {
    $site_name = $data['site_name'] ?? 'Modern Shop';
    $site_url = $data['site_url'] ?? get_base_url();
    $logo_url = $data['logo_url'] ?? $site_url . '/assets/images/logo.png';
    
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$title</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .email-container { max-width: 600px; margin: 0 auto; background: #f9f9f9; }
        .email-header { background: #4361ee; color: white; padding: 20px; text-align: center; }
        .email-body { background: white; padding: 30px; }
        .email-footer { background: #f1f1f1; padding: 20px; text-align: center; font-size: 12px; color: #666; }
        .button { display: inline-block; padding: 12px 24px; background: #4361ee; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>$site_name</h1>
        </div>
        <div class="email-body">
            <h2>$title</h2>
            <div>$content</div>
        </div>
        <div class="email-footer">
            <p>&copy; $site_name. All rights reserved.</p>
            <p><a href="$site_url" style="color: #4361ee;">Visit our website</a></p>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Log message to file
 * 
 * @param string $message Log message
 * @param string $level Log level
 * @param string $file Log file name
 * @return void
 */
function log_message($message, $level = 'INFO', $file = 'application.log') {
    $log_dir = dirname(__DIR__) . '/logs';
    
    // Create logs directory if it doesn't exist
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . '/' . $file;
    
    $log_entry = sprintf(
        "[%s] %s: %s - IP: %s\n",
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $message,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    );
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Debug function
 * 
 * @param mixed $data Data to debug
 * @param bool $die Stop execution after output
 * @return void
 */
function debug($data, $die = false) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    
    if ($die) {
        die();
    }
}

/**
 * Check if string is JSON
 * 
 * @param string $string String to check
 * @return bool True if valid JSON
 */
function is_json($string) {
    if (!is_string($string)) {
        return false;
    }
    
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

/**
 * Get array value with default
 * 
 * @param array $array Array
 * @param mixed $key Key
 * @param mixed $default Default value
 * @return mixed Value or default
 */
function array_get($array, $key, $default = null) {
    if (isset($array[$key])) {
        return $array[$key];
    }
    
    return $default;
}

/**
 * Convert array to HTML attributes
 * 
 * @param array $attributes Array of attributes
 * @return string HTML attributes string
 */
function array_to_attributes($attributes) {
    $html = '';
    
    foreach ($attributes as $key => $value) {
        if ($value !== null && $value !== false) {
            if ($value === true) {
                $html .= ' ' . $key;
            } else {
                $html .= ' ' . $key . '="' . htmlspecialchars($value, ENT_QUOTES) . '"';
            }
        }
    }
    
    return $html;
}

/**
 * Get current timestamp
 * 
 * @return string Current timestamp
 */
function now() {
    return date('Y-m-d H:i:s');
}

/**
 * Get today's date
 * 
 * @return string Today's date
 */
function today() {
    return date('Y-m-d');
}

/**
 * Get yesterday's date
 * 
 * @return string Yesterday's date
 */
function yesterday() {
    return date('Y-m-d', strtotime('-1 day'));
}

/**
 * Get tomorrow's date
 * 
 * @return string Tomorrow's date
 */
function tomorrow() {
    return date('Y-m-d', strtotime('+1 day'));
}

/**
 * Check if string starts with substring
 * 
 * @param string $string String to check
 * @param string $start Substring to check for
 * @return bool True if string starts with substring
 */
function starts_with($string, $start) {
    return strpos($string, $start) === 0;
}

/**
 * Check if string ends with substring
 * 
 * @param string $string String to check
 * @param string $end Substring to check for
 * @return bool True if string ends with substring
 */
function ends_with($string, $end) {
    $length = strlen($end);
    if ($length == 0) {
        return true;
    }
    
    return substr($string, -$length) === $end;
}

/**
 * Check if string contains substring
 * 
 * @param string $string String to check
 * @param string $needle Substring to check for
 * @return bool True if string contains substring
 */
function str_contains($string, $needle) {
    return strpos($string, $needle) !== false;
}

/**
 * Pluralize word based on count
 * 
 * @param string $singular Singular form
 * @param string $plural Plural form
 * @param int $count Count
 * @return string Pluralized word
 */
function pluralize($singular, $plural, $count) {
    return $count == 1 ? $singular : $plural;
}

/**
 * Get human-readable time difference
 * 
 * @param string $time Time string
 * @return string Human-readable time difference
 */
function time_ago($time) {
    $time = strtotime($time);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'just now';
    }
    
    $intervals = [
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute'
    ];
    
    foreach ($intervals as $seconds => $label) {
        $interval = floor($diff / $seconds);
        if ($interval >= 1) {
            return $interval . ' ' . pluralize($label, $label . 's', $interval) . ' ago';
        }
    }
    
    return 'just now';
}
?>