<?php
/* ============================================
   REGISTRATION PROCESS - Procedural
   Handle user registration
============================================ */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../config/constants.php';
    start_secure_session();
}

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/validation.php';
require_once __DIR__ . '/../functions/security.php';
require_once __DIR__ . '/../functions/request.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_message('user/register.php', 'error', 'Invalid request method');
}



// Get form data
$name = request_post_string('name', 'string', '');
$email = request_post_string('email', 'email', '');
$password = (string) ($_POST['password'] ?? '');
$confirm_password = (string) ($_POST['confirm_password'] ?? '');
$phone = request_post_string('phone', 'string', '');
$address = request_post_string('address', 'string', '');
$city = request_post_string('city', 'string', '');
$state = request_post_string('state', 'string', '');
$zip = request_post_string('zip', 'string', '');
$country = request_post_string('country', 'string', '');
$agree_terms = request_post_bool('agree_terms');

// Store form data in session for repopulating on error
$_SESSION['form_data'] = [
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'address' => $address,
    'city' => $city,
    'state' => $state,
    'zip' => $zip,
    'country' => $country
];

// Validation rules
$validation_rules = [
    'name' => ['required', 'min_length:2', 'max_length:100'],
    'email' => ['required', 'email'],
    'password' => ['required', 'min_length:8', 'max_length:255'],
    'confirm_password' => ['required', 'match:password'],
    'phone' => ['required', 'regex:/^[0-9\s\-\(\)]{10,15}$/'],
    'address' => ['required', 'min_length:5', 'max_length:200'],
    'city' => ['required', 'min_length:2', 'max_length:50'],
    'state' => ['required', 'min_length:2', 'max_length:50'],
    'zip' => ['required', 'regex:/^[0-9\-]{5,10}$/'],
    'country' => ['required', 'min_length:2', 'max_length:50']
];

// Validate form data (sanitized)
$validation_result = request_validate(
    [
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'confirm_password' => $confirm_password,
        'phone' => $phone,
        'address' => $address,
        'city' => $city,
        'state' => $state,
        'zip' => $zip,
        'country' => $country,
    ],
    $validation_rules,
    [
        'name' => 'string',
        'email' => 'email',
        'phone' => 'string',
        'address' => 'string',
        'city' => 'string',
        'state' => 'string',
        'zip' => 'string',
        'country' => 'string',
    ]
);

if (!$validation_result['valid']) {
    $_SESSION['validation_errors'] = $validation_result['errors'];
    redirect('user/register.php');
}

// Check if user agreed to terms
if (!$agree_terms) {
    $_SESSION['validation_errors']['agree_terms'] = ['You must agree to the terms and conditions'];
    redirect('user/register.php');
}

// Check password strength
$password_strength = validate_password($password);
if (!$password_strength['valid']) {
    $_SESSION['validation_errors']['password'] = $password_strength['errors'];
    redirect('user/register.php');
}

// Check if email already exists
$existing_user = get_user_by_email($email);
if (!empty($existing_user)) {
    $_SESSION['validation_errors']['email'] = ['Email address already registered'];
    redirect('user/register.php');
}

// Prepare user data
$user_data = [
    'email' => $email,
    'password' => $password,
    'name' => $name,
    'phone' => $phone,
    'address' => $address,
    'city' => $city,
    'state' => $state,
    'zip' => $zip,
    'country' => $country
];

// Create user
$result = create_user($user_data);

if (!$result['success']) {
    $_SESSION['validation_errors']['general'] = $result['errors'];
    redirect('user/register.php');
}

// Get the newly created user
$user = get_user_by_id($result['user_id']);

// Auto-login the user
login_user([
    'id' => $user['id'],
    'email' => $user['email'],
    'name' => $user['name'],
    'role' => $user['role'],
    'created_at' => $user['created_at']
]);

// Clear form data from session
unset($_SESSION['form_data']);
unset($_SESSION['validation_errors']);

// Send welcome email (optional)
$site_name = SITE_NAME;
$site_url = SITE_URL;
$subject = "Welcome to $site_name!";
$message = "
    <h2>Welcome to $site_name!</h2>
    <p>Thank you for registering with us. Your account has been successfully created.</p>
    <p>You can now log in to your account and start shopping.</p>
    <p><a href='$site_url'>Visit our store</a></p>
";

send_email($email, $subject, $message);

// Redirect to success page
redirect_with_message('user/profile.php', 'success', 'Registration successful! Welcome to our store.');
?>