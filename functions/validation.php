<?php
/* ============================================
   VALIDATION FUNCTIONS - Procedural
   Server-side validation for forms and data
============================================ */

/**
 * Sanitize input data
 * 
 * @param mixed $data The input data to sanitize
 * @param string $type The type of sanitization (string, email, int, float, url, html)
 * @return mixed Sanitized data
 */
function sanitize_input($data, $type = 'string') {
    if (empty($data)) {
        return $data;
    }
    
    switch ($type) {
        case 'email':
            $data = filter_var(trim($data), FILTER_SANITIZE_EMAIL);
            break;
            
        case 'int':
            $data = filter_var($data, FILTER_SANITIZE_NUMBER_INT);
            $data = (int)$data;
            break;
            
        case 'float':
            $data = filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $data = (float)$data;
            break;
            
        case 'url':
            $data = filter_var(trim($data), FILTER_SANITIZE_URL);
            break;
            
        case 'html':
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
            break;
            
        case 'string':
        default:
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
            break;
    }
    
    return $data;
}

/**
 * Validate email address
 * 
 * @param string $email Email to validate
 * @return bool True if valid email
 */
function validate_email($email) {
    if (empty($email)) {
        return false;
    }
    
    // Remove illegal characters
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    
    // Validate format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // Check domain
    $domain = explode('@', $email)[1];
    if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
        return false;
    }
    
    return true;
}

/**
 * Validate phone number
 * 
 * @param string $phone Phone number to validate
 * @param string $country Country code (default: US)
 * @return bool True if valid phone
 */
function validate_phone($phone, $country = 'US') {
    if (empty($phone)) {
        return false;
    }
    
    // Remove all non-digit characters
    $clean_phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Basic length check
    if (strlen($clean_phone) < 10 || strlen($clean_phone) > 15) {
        return false;
    }
    
    // US phone validation
    if ($country === 'US') {
        $pattern = '/^(\+1\s?)?(\([0-9]{3}\)|[0-9]{3})[\s\-]?[0-9]{3}[\s\-]?[0-9]{4}$/';
        return preg_match($pattern, $phone);
    }
    
    // International phone (basic validation)
    return preg_match('/^\+?[0-9\s\-\(\)]{10,15}$/', $phone);
}

/**
 * Validate password strength
 * 
 * @param string $password Password to validate
 * @return array Array with 'valid' boolean and 'errors' array
 */
function validate_password($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Password must contain at least one special character';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validate credit card number
 * 
 * @param string $card_number Credit card number
 * @return array Array with 'valid' boolean and 'type' string
 */
function validate_credit_card($card_number) {
    // Remove all non-digit characters
    $card_number = preg_replace('/\D/', '', $card_number);
    
    if (empty($card_number) || strlen($card_number) < 13) {
        return ['valid' => false, 'type' => 'unknown'];
    }
    
    // Check card type
    $card_type = 'unknown';
    
    if (preg_match('/^4[0-9]{12}(?:[0-9]{3})?$/', $card_number)) {
        $card_type = 'visa';
    } elseif (preg_match('/^5[1-5][0-9]{14}$/', $card_number)) {
        $card_type = 'mastercard';
    } elseif (preg_match('/^3[47][0-9]{13}$/', $card_number)) {
        $card_type = 'amex';
    } elseif (preg_match('/^6(?:011|5[0-9]{2})[0-9]{12}$/', $card_number)) {
        $card_type = 'discover';
    }
    
    // Luhn algorithm check
    $valid = validate_luhn($card_number);
    
    return [
        'valid' => $valid,
        'type' => $card_type
    ];
}

/**
 * Validate using Luhn algorithm
 * 
 * @param string $number Number to validate
 * @return bool True if valid Luhn number
 */
function validate_luhn($number) {
    $number = strrev(preg_replace('/[^0-9]/', '', $number));
    $sum = 0;
    
    for ($i = 0, $j = strlen($number); $i < $j; $i++) {
        if (($i % 2) == 0) {
            $val = $number[$i];
        } else {
            $val = $number[$i] * 2;
            if ($val > 9) {
                $val -= 9;
            }
        }
        $sum += $val;
    }
    
    return (($sum % 10) == 0);
}

/**
 * Validate expiration date (MM/YY format)
 * 
 * @param string $expiry_date Expiry date in MM/YY format
 * @return bool True if valid future date
 */
function validate_expiry_date($expiry_date) {
    if (!preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $expiry_date, $matches)) {
        return false;
    }
    
    $month = $matches[1];
    $year = $matches[2];
    
    // Add century to year
    $year = '20' . $year;
    
    // Check if date is in the future
    $expiry = DateTime::createFromFormat('m/Y', $month . '/' . $year);
    $now = new DateTime();
    
    if ($expiry < $now) {
        return false;
    }
    
    return true;
}

/**
 * Validate CVV code
 * 
 * @param string $cvv CVV code
 * @param string $card_type Credit card type
 * @return bool True if valid CVV
 */
function validate_cvv($cvv, $card_type = 'unknown') {
    if (!preg_match('/^[0-9]{3,4}$/', $cvv)) {
        return false;
    }
    
    $length = strlen($cvv);
    
    // American Express uses 4-digit CVV
    if ($card_type === 'amex' && $length !== 4) {
        return false;
    }
    
    // Other cards use 3-digit CVV
    if ($card_type !== 'amex' && $length !== 3) {
        return false;
    }
    
    return true;
}

/**
 * Validate ZIP code
 * 
 * @param string $zip ZIP code
 * @param string $country Country code
 * @return bool True if valid ZIP
 */
function validate_zip($zip, $country = 'US') {
    if ($country === 'US') {
        return preg_match('/^[0-9]{5}(-[0-9]{4})?$/', $zip);
    }
    
    // Basic international validation
    return preg_match('/^[A-Z0-9\-\s]{4,10}$/i', $zip);
}

/**
 * Validate URL
 * 
 * @param string $url URL to validate
 * @return bool True if valid URL
 */
function validate_url($url) {
    if (empty($url)) {
        return false;
    }
    
    // Add protocol if missing
    if (!preg_match('/^https?:\/\//i', $url)) {
        $url = 'http://' . $url;
    }
    
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Validate date format
 * 
 * @param string $date Date string
 * @param string $format Expected format
 * @return bool True if valid date in specified format
 */
function validate_date($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Validate file upload
 * 
 * @param array $file $_FILES array element
 * @param array $options Validation options
 * @return array Array with 'valid' boolean and 'errors' array
 */
function validate_file_upload($file, $options = []) {
    $errors = [];
    
    // Default options
    $defaults = [
        'max_size' => 5242880, // 5MB
        'allowed_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'],
        'required' => false
    ];
    
    $options = array_merge($defaults, $options);
    
    // Check if file was uploaded
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        if ($options['required']) {
            $errors[] = 'File is required';
        }
        return ['valid' => empty($errors), 'errors' => $errors];
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errors[] = 'File is too large';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errors[] = 'File was only partially uploaded';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $errors[] = 'Missing temporary folder';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $errors[] = 'Failed to write file to disk';
                break;
            default:
                $errors[] = 'Unknown upload error';
        }
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Check file size
    if ($file['size'] > $options['max_size']) {
        $max_size_mb = $options['max_size'] / 1024 / 1024;
        $errors[] = "File must be less than {$max_size_mb}MB";
    }
    
    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $options['allowed_types'])) {
        $errors[] = 'File type not allowed';
    }
    
    // Check file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $options['allowed_extensions'])) {
        $errors[] = 'File extension not allowed';
    }
    
    // Additional security checks for images
    if (strpos($mime_type, 'image/') === 0) {
        $image_info = getimagesize($file['tmp_name']);
        if ($image_info === false) {
            $errors[] = 'Invalid image file';
        }
    }
    
    return ['valid' => empty($errors), 'errors' => $errors];
}

/**
 * Validate required fields in array
 * 
 * @param array $data Data array
 * @param array $required_fields List of required field names
 * @return array Array with missing fields
 */
function validate_required_fields($data, $required_fields) {
    $missing = [];
    
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missing[] = $field;
        }
    }
    
    return $missing;
}

/**
 * Validate form data
 * 
 * @param array $form_data Form data array
 * @param array $validation_rules Validation rules array
 * @return array Array with validation results
 */
function validate_form_data($form_data, $validation_rules) {
    $errors = [];
    $validated_data = [];
    
    foreach ($validation_rules as $field => $rules) {
        $value = isset($form_data[$field]) ? $form_data[$field] : '';
        
        // Skip validation if field is empty and not required
        if (empty($value) && !in_array('required', $rules)) {
            continue;
        }
        
        foreach ($rules as $rule) {
            $rule_parts = explode(':', $rule);
            $rule_name = $rule_parts[0];
            $rule_param = isset($rule_parts[1]) ? $rule_parts[1] : null;
            
            $is_valid = true;
            $error_message = '';
            
            switch ($rule_name) {
                case 'required':
                    if (empty($value)) {
                        $is_valid = false;
                        $error_message = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                    }
                    break;
                    
                case 'email':
                    if (!validate_email($value)) {
                        $is_valid = false;
                        $error_message = 'Invalid email address';
                    }
                    break;
                    
                case 'min_length':
                    if (strlen($value) < $rule_param) {
                        $is_valid = false;
                        $error_message = "Must be at least {$rule_param} characters";
                    }
                    break;
                    
                case 'max_length':
                    if (strlen($value) > $rule_param) {
                        $is_valid = false;
                        $error_message = "Must be at most {$rule_param} characters";
                    }
                    break;
                    
                case 'numeric':
                    if (!is_numeric($value)) {
                        $is_valid = false;
                        $error_message = 'Must be a number';
                    }
                    break;
                    
                case 'alpha':
                    if (!ctype_alpha(str_replace(' ', '', $value))) {
                        $is_valid = false;
                        $error_message = 'Must contain only letters';
                    }
                    break;
                    
                case 'alphanumeric':
                    if (!ctype_alnum(str_replace([' ', '-', '_'], '', $value))) {
                        $is_valid = false;
                        $error_message = 'Must contain only letters and numbers';
                    }
                    break;
                    
                case 'match':
                    $match_field = $rule_param;
                    if (!isset($form_data[$match_field]) || $value !== $form_data[$match_field]) {
                        $is_valid = false;
                        $error_message = 'Fields do not match';
                    }
                    break;
                    
                case 'regex':
                    if (!preg_match($rule_param, $value)) {
                        $is_valid = false;
                        $error_message = 'Invalid format';
                    }
                    break;
            }
            
            if (!$is_valid) {
                if (!isset($errors[$field])) {
                    $errors[$field] = [];
                }
                $errors[$field][] = $error_message;
                break;
            }
        }
        
        if (!isset($errors[$field])) {
            $validated_data[$field] = $value;
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'data' => $validated_data
    ];
}

/**
 * Generate CSRF token
 * 
 * @return string CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 * 
 * @param string $token Token to validate
 * @return bool True if valid token
 */
function validate_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Token must match
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    
    // Check token expiry (1 hour)
    if (time() - $_SESSION['csrf_token_time'] > 3600) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return false;
    }
    
    return true;
}

/**
 * Escape output for HTML
 * 
 * @param string $string String to escape
 * @return string Escaped string
 */
function escape_html($string) {
    return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Escape output for JavaScript
 * 
 * @param string $string String to escape
 * @return string Escaped string
 */
function escape_js($string) {
    return addslashes($string);
}

/**
 * Escape output for SQL (use prepared statements instead)
 * 
 * @param mysqli $connection Database connection
 * @param string $string String to escape
 * @return string Escaped string
 */
function escape_sql($connection, $string) {
    return mysqli_real_escape_string($connection, $string);
}

/**
 * Generate random string
 * 
 * @param int $length Length of random string
 * @return string Random string
 */
function generate_random_string($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Validate reCAPTCHA response
 * 
 * @param string $recaptcha_response reCAPTCHA response
 * @param string $secret_key Secret key
 * @return bool True if valid reCAPTCHA
 */
function validate_recaptcha($recaptcha_response, $secret_key) {
    if (empty($recaptcha_response)) {
        return false;
    }
    
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => $secret_key,
        'response' => $recaptcha_response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $response = json_decode($result, true);
    
    return isset($response['success']) && $response['success'] === true;
}
?>