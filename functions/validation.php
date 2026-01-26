<?php
/* ============================================
   VALIDATION FUNCTIONS - Procedural
   Server-side validation for forms and data
============================================ */

/**
 * Sanitize input data
 * Wrapped in function_exists to prevent "Fatal error: Cannot redeclare"
 */
if (!function_exists('sanitize_input')) {
    function sanitize_input($data, $type = 'string')
    {
        if (empty($data)) {
            return $data;
        }

        switch ($type) {
            case 'email':
                $data = filter_var(trim($data), FILTER_SANITIZE_EMAIL);
                break;

            case 'int':
                $data = filter_var($data, FILTER_SANITIZE_NUMBER_INT);
                $data = (int) $data;
                break;

            case 'float':
                $data = filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $data = (float) $data;
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
}

/**
 * Treat null/empty-string as blank, but allow "0".
 */
function is_blank($value)
{
    if ($value === null) {
        return true;
    }

    if (is_string($value)) {
        return trim($value) === '';
    }

    return false;
}

/**
 * Validate email address
 */
function validate_email($email)
{
    if (empty($email)) {
        return false;
    }

    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $domain = explode('@', $email)[1];
    // Note: checkdnsrr might be slow on some local server setups
    // if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
    //     return false;
    // }

    return true;
}

/**
 * Validate phone number
 */
function validate_phone($phone, $country = 'US')
{
    if (empty($phone)) {
        return false;
    }

    $clean_phone = preg_replace('/[^0-9]/', '', $phone);

    if (strlen($clean_phone) < 10 || strlen($clean_phone) > 15) {
        return false;
    }

    if ($country === 'US') {
        $pattern = '/^(\+1\s?)?(\([0-9]{3}\)|[0-9]{3})[\s\-]?[0-9]{3}[\s\-]?[0-9]{4}$/';
        return preg_match($pattern, $phone);
    }

    return preg_match('/^\+?[0-9s\-\(\)]{10,15}$/', $phone);
}

/**
 * Validate password strength
 */
function validate_password($password)
{
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
 * Validate credit card number (Luhn + Type)
 */
function validate_credit_card($card_number)
{
    $card_number = preg_replace('/\D/', '', $card_number);

    if (empty($card_number) || strlen($card_number) < 13) {
        return ['valid' => false, 'type' => 'unknown'];
    }

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

    return [
        'valid' => validate_luhn($card_number),
        'type' => $card_type
    ];
}

function validate_luhn($number)
{
    $number = strrev(preg_replace('/[^0-9]/', '', $number));
    $sum = 0;
    for ($i = 0, $j = strlen($number); $i < $j; $i++) {
        $val = $number[$i];
        if (($i % 2) != 0) {
            $val *= 2;
            if ($val > 9)
                $val -= 9;
        }
        $sum += $val;
    }
    return (($sum % 10) == 0);
}

/**
 * Validate expiration date (MM/YY)
 */
function validate_expiry_date($expiry_date)
{
    if (!preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $expiry_date, $matches)) {
        return false;
    }

    $expiry = DateTime::createFromFormat('m/y', $expiry_date);
    $now = new DateTime('first day of this month');

    return $expiry && $expiry >= $now;
}

/**
 * File Upload Validation
 */
function validate_file_upload($file, $options = [])
{
    $errors = [];
    $defaults = [
        'max_size' => 5242880,
        'allowed_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'],
        'required' => false
    ];
    $options = array_merge($defaults, $options);

    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        if ($options['required'])
            $errors[] = 'File is required';
        return ['valid' => empty($errors), 'errors' => $errors];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Upload error occurred';
        return ['valid' => false, 'errors' => $errors];
    }

    if ($file['size'] > $options['max_size']) {
        $errors[] = 'File is too large';
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $options['allowed_types'])) {
        $errors[] = 'File type not allowed';
    }

    return ['valid' => empty($errors), 'errors' => $errors];
}





/**
 * Validate CVV code
 * 
 * @param string $cvv CVV code
 * @param string $card_type Credit card type
 * @return bool True if valid CVV
 */
function validate_cvv($cvv, $card_type = 'unknown')
{
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
function validate_zip($zip, $country = 'US')
{
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
function validate_url($url)
{
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
function validate_date($date, $format = 'Y-m-d')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}


/**
 * Validate required fields in array
 * 
 * @param array $data Data array
 * @param array $required_fields List of required field names
 * @return array Array with missing fields
 */
function validate_required_fields($data, $required_fields)
{
    $missing = [];

    foreach ($required_fields as $field) {
        if (!array_key_exists($field, $data) || is_blank($data[$field])) {
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
function validate_form_data($form_data, $validation_rules)
{
    $errors = [];
    $validated_data = [];

    foreach ($validation_rules as $field => $rules) {
        $value = array_key_exists($field, $form_data) ? $form_data[$field] : '';

        // Normalize string values
        if (is_string($value)) {
            $value = trim($value);
        }

        // Skip validation if field is blank and not required
        if (is_blank($value) && !in_array('required', $rules, true)) {
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
                    if (is_blank($value)) {
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

                case 'phone':
                    if (!validate_phone($value, 'US')) {
                        $is_valid = false;
                        $error_message = 'Invalid phone number';
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

                case 'integer':
                case 'int':
                    if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                        $is_valid = false;
                        $error_message = 'Must be an integer';
                    }
                    break;

                case 'float':
                    if (filter_var($value, FILTER_VALIDATE_FLOAT) === false) {
                        $is_valid = false;
                        $error_message = 'Must be a valid number';
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

                case 'in':
                    $allowed = array_filter(array_map('trim', explode(',', (string) $rule_param)), 'strlen');
                    if (!in_array((string) $value, $allowed, true)) {
                        $is_valid = false;
                        $error_message = 'Invalid value';
                    }
                    break;

                case 'min':
                    if (!is_numeric($value) || (float) $value < (float) $rule_param) {
                        $is_valid = false;
                        $error_message = "Must be at least {$rule_param}";
                    }
                    break;

                case 'max':
                    if (!is_numeric($value) || (float) $value > (float) $rule_param) {
                        $is_valid = false;
                        $error_message = "Must be at most {$rule_param}";
                    }
                    break;

                case 'url':
                    if (filter_var($value, FILTER_VALIDATE_URL) === false) {
                        $is_valid = false;
                        $error_message = 'Invalid URL';
                    }
                    break;

                case 'date':
                    $format = $rule_param ?: 'Y-m-d';
                    if (!validate_date((string) $value, $format)) {
                        $is_valid = false;
                        $error_message = 'Invalid date';
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
 * Escape output for HTML
 * 
 * @param string $string String to escape
 * @return string Escaped string
 */
function escape_html($string)
{
    return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Escape output for JavaScript
 * 
 * @param string $string String to escape
 * @return string Escaped string
 */
function escape_js($string)
{
    return addslashes($string);
}

/**
 * Escape output for SQL (use prepared statements instead)
 * 
 * @param mysqli $connection Database connection
 * @param string $string String to escape
 * @return string Escaped string
 */
function escape_sql($connection, $string)
{
    return mysqli_real_escape_string($connection, $string);
}

/**
 * Generate random string
 * 
 * @param int $length Length of random string
 * @return string Random string
 */
if (!function_exists('generate_random_string')) {
    function generate_random_string($length = 32)
    {
        return bin2hex(random_bytes($length / 2));
    }
}


/**
 * Validate reCAPTCHA response
 * 
 * @param string $recaptcha_response reCAPTCHA response
 * @param string $secret_key Secret key
 * @return bool True if valid reCAPTCHA
 */
function validate_recaptcha($recaptcha_response, $secret_key)
{
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