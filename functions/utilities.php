<?php
/* ============================================
   UTILITY FUNCTIONS - Procedural
   General helper functions for the system
============================================ */

/**
 * Redirect to another page
 */
function redirect($url, $status_code = 302)
{
    header("Location: $url", true, $status_code);
    exit();
}

/**
 * Sanitize input based on type
 *
 * @param mixed $value Input value
 * @param string $type Type of input: string, int, float, email, url, html
 * @return mixed Sanitized value
 */


/**
 * Get client IP address
 */
function get_client_ip()
{
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if (isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if (isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if (isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

/**
 * Redirect with flash message
 */
function redirect_with_message($url, $type, $message)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_message'] = [
        'type' => $type,
        'text' => $message
    ];
    redirect($url);
}

/**
 * Get and clear flash message
 */
function get_flash_message()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Format currency
 */
function format_currency($amount, $currency = 'USD')
{
    $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
    return $formatter->formatCurrency($amount, $currency);
}

/**
 * Format date
 */
function format_date($date, $format = 'F j, Y')
{
    if ($date === null || $date === '') {
        return '';
    }
    $timestamp = strtotime($date);
    if ($timestamp === false)
        return $date;
    return date($format, $timestamp);
}

/**
 * Format datetime
 */
function format_datetime($datetime, $format = 'F j, Y g:i A')
{
    if ($datetime === null || $datetime === '') {
        return '';
    }
    $timestamp = strtotime($datetime);
    if ($timestamp === false)
        return $datetime;
    return date($format, $timestamp);
}

/**
 * Format file size
 */
function format_file_size($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Truncate text
 */
function truncate_text($text, $length = 100, $suffix = '...')
{
    if (strlen($text) <= $length)
        return $text;
    $text = substr($text, 0, $length);
    $text = substr($text, 0, strrpos($text, ' '));
    return $text . $suffix;
}

/**
 * Generate slug
 */
function generate_slug($string)
{
    $slug = strtolower($string);
    $slug = preg_replace('/\s+/', '-', $slug);
    $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

/**
 * Generate random string
 */


/**
 * Generate order number
 */
function generate_order_number($user_id = null)
{
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
 */
function generate_invoice_number($order_id)
{
    $prefix = 'INV';
    $year = date('Y');
    $month = date('m');
    $sequence = str_pad($order_id, 6, '0', STR_PAD_LEFT);
    return "{$prefix}-{$year}{$month}-{$sequence}";
}

/**
 * Calculate age
 */
function calculate_age($birthdate)
{
    $birthday = new DateTime($birthdate);
    $today = new DateTime('today');
    return $birthday->diff($today)->y;
}

/**
 * Calculate distance
 */
function calculate_distance($lat1, $lon1, $lat2, $lon2, $unit = 'miles')
{
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;
    if ($unit == 'kilometers')
        return $miles * 1.609344;
    return $miles;
}

/**
 * Calculate tax
 */
function calculate_tax($amount, $tax_rate = 0)
{
    return $amount * ($tax_rate / 100);
}

/**
 * Calculate shipping
 */
function calculate_shipping($weight, $method = 'standard', $destination = 'US')
{
    $base_cost = 5.00;
    $weight_cost = $weight * 2.00;
    switch ($method) {
        case 'express':
            $base_cost += 10.00;
            break;
        case 'priority':
            $base_cost += 5.00;
            break;
    }
    if ($destination !== 'US')
        $base_cost += 15.00;
    return $base_cost + $weight_cost;
}

/**
 * Calculate discount
 */
function calculate_discount($amount, $discount, $type = 'percent')
{
    if ($type === 'percent')
        return $amount * ($discount / 100);
    return min($discount, $amount);
}

/**
 * Pagination
 */
function generate_pagination($total_items, $current_page, $per_page, $base_url = '')
{
    $total_pages = ceil($total_items / $per_page);
    $current_page = max(1, min($current_page, $total_pages));
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
    if ($current_page <= 3)
        $end_page = min(5, $total_pages);
    if ($current_page >= $total_pages - 2)
        $start_page = max(1, $total_pages - 4);
    $pages = [];
    for ($i = $start_page; $i <= $end_page; $i++)
        $pages[] = $i;
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

function get_pagination_html($pagination, $base_url = '', $query_param = 'page')
{
    if ($pagination['total_pages'] <= 1)
        return '';
    $html = '<nav class="pagination"><ul>';
    if ($pagination['has_previous']) {
        $prev_url = $base_url . '?' . $query_param . '=' . $pagination['previous_page'];
        $html .= '<li><a href="' . $prev_url . '">&laquo; Previous</a></li>';
    }
    foreach ($pagination['pages'] as $page) {
        $active = $page == $pagination['current_page'] ? ' class="active"' : '';
        $page_url = $base_url . '?' . $query_param . '=' . $page;
        $html .= '<li><a href="' . $page_url . '"' . $active . '>' . $page . '</a></li>';
    }
    if ($pagination['has_next']) {
        $next_url = $base_url . '?' . $query_param . '=' . $pagination['next_page'];
        $html .= '<li><a href="' . $next_url . '">Next &raquo;</a></li>';
    }
    $html .= '</ul></nav>';
    return $html;
}

/**
 * str_contains replacement only if not exists
 */
if (!function_exists('str_contains')) {
    function str_contains($string, $needle)
    {
        return strpos($string, $needle) !== false;
    }
}

/**
 * Helper functions for starts_with, ends_with, pluralize, time_ago
 */
if (!function_exists('starts_with')) {
    function starts_with($string, $start)
    {
        return strpos($string, $start) === 0;
    }
}
if (!function_exists('ends_with')) {
    function ends_with($string, $end)
    {
        $len = strlen($end);
        if ($len == 0)
            return true;
        return substr($string, -$len) === $end;
    }
}
if (!function_exists('pluralize')) {
    function pluralize($singular, $plural, $count)
    {
        return $count == 1 ? $singular : $plural;
    }
}
if (!function_exists('time_ago')) {
    function time_ago($time)
    {
        $time = strtotime($time);
        $now = time();
        $diff = $now - $time;
        if ($diff < 60)
            return 'just now';
        $intervals = [31536000 => 'year', 2592000 => 'month', 604800 => 'week', 86400 => 'day', 3600 => 'hour', 60 => 'minute'];
        foreach ($intervals as $seconds => $label) {
            $i = floor($diff / $seconds);
            if ($i >= 1)
                return $i . ' ' . pluralize($label, $label . 's', $i) . ' ago';
        }
        return 'just now';
    }
}

/**
 * Get product by ID
 * 
 * @param int $product_id
 * @param object $connection Database connection (Database class, PDO, or mysqli)
 * @return array|null
 */
function get_product_by_id($product_id)
{
    // DB schema uses `image` (not `image_url`) and has no `status` column.
    // We alias `image` to `image_url` to keep existing templates/JS working.
    $sql = "SELECT id, name, description, price, category, image AS image_url, stock, created_at FROM products WHERE id = :id LIMIT 1";
    db_query($sql);
    db_bind(':id', $product_id);
    return db_single() ?: null;
}

/**
 * Send email (Mock implementation if not defined)
 */
if (!function_exists('send_email')) {
    function send_email($to, $subject, $message)
    {
        // In a real app, use mail() or PHPMailer
        // For now, just log it
        error_log("Sending email to $to: $subject");
        return true;
    }
}
?>