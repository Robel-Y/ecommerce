<?php
/* ============================================
   USER REGISTRATION PAGE - Procedural PDO
============================================ */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php'; // procedural DB functions
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../functions/security.php';
require_once __DIR__ . '/../functions/utilities.php';
require_once __DIR__ . '/../functions/validation.php';

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    start_secure_session();
}

// Redirect if already logged in
if (is_logged_in()) {
    redirect('profile.php');
}

// Initialize database connection (procedural)
$connection = db_connect(); // <--- this is your PDO connection

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $agree_terms = isset($_POST['agree_terms']);

    // Validate inputs
    if (empty($name)) $errors[] = 'Name is required';
    elseif (strlen($name) < 2) $errors[] = 'Name must be at least 2 characters';

    if (empty($email)) $errors[] = 'Email is required';
    elseif (!validate_email($email)) $errors[] = 'Invalid email address';

    if (empty($password)) $errors[] = 'Password is required';
    else {
        $validation = validate_password($password);
        if (!$validation['valid']) $errors = array_merge($errors, $validation['errors']);
    }

    if (!empty($password) && $password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }

    if (!$agree_terms) $errors[] = 'You must agree to the terms and conditions';

    // If no errors, create user
    if (empty($errors)) {
        $user_data = [
            'name' => $name,
            'email' => $email,
            'password' => $password
        ];

        $register_result = create_user($user_data, $connection); // pass PDO connection

        if ($register_result['success']) {
            $user = $register_result['user'];
            login_user($user, false); // auto-login
            redirect_with_message('profile.php', 'success', 'Registration successful! Welcome to ' . SITE_NAME . '!');
        } else {
            $errors = $register_result['errors'];
        }
    }
}

$page_title = 'Create Account';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | <?php echo SITE_NAME; ?></title>

    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/forms.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/modern.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--space-lg);
        }

        .auth-container {
            max-width: 500px;
            width: 100%;
        }

        .auth-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            padding: var(--space-3xl);
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-header {
            text-align: center;
            margin-bottom: var(--space-xl);
        }

        .auth-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto var(--space-md);
            background: linear-gradient(135deg, #f093fb, #f5576c);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--white);
        }

        .auth-header h1 {
            font-size: 2rem;
            margin-bottom: var(--space-xs);
        }

        .auth-header p {
            color: var(--text-muted);
            margin-bottom: 0;
        }

        .error-list {
            background: rgba(249, 65, 68, 0.1);
            border: 1px solid var(--danger-color);
            border-radius: var(--radius-md);
            padding: var(--space-md);
            margin-bottom: var(--space-lg);
            color: var(--danger-color);
        }

        .error-list ul {
            margin: 0;
            padding-left: var(--space-lg);
        }

        .password-strength {
            margin-top: var(--space-xs);
            height: 4px;
            background: var(--light-200);
            border-radius: var(--radius-full);
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all var(--transition-normal);
        }

        .strength-weak {
            width: 33%;
            background: var(--danger-color);
        }

        .strength-medium {
            width: 66%;
            background: var(--warning-color);
        }

        .strength-strong {
            width: 100%;
            background: var(--success-color);
        }

        .auth-footer {
            text-align: center;
            margin-top: var(--space-lg);
            padding-top: var(--space-lg);
            border-top: 1px solid var(--light-200);
        }

        .password-requirements {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: var(--space-xs);
        }

        .password-requirements ul {
            margin: var(--space-xs) 0 0 0;
            padding-left: var(--space-lg);
        }

        .password-requirements li {
            margin: 2px 0;
        }

        .requirement-met {
            color: var(--success-color);
        }
    </style>
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1>Create Account</h1>
                <p>Join us today and start shopping!</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="error-list">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="form-group">
                    <label for="name" class="form-label">
                        <i class="fas fa-user"></i> Full Name *
                    </label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="John Doe" required
                        autofocus value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i> Email Address *
                    </label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="your@email.com"
                        required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password *
                    </label>
                    <input type="password" id="password" name="password" class="form-control"
                        placeholder="Create a strong password" required oninput="checkPasswordStrength(this.value)">
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="password-requirements">
                        <ul id="requirements">
                            <li id="req-length">At least 8 characters</li>
                            <li id="req-uppercase">One uppercase letter</li>
                            <li id="req-lowercase">One lowercase letter</li>
                            <li id="req-number">One number</li>
                            <li id="req-special">One special character</li>
                        </ul>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">
                        <i class="fas fa-lock"></i> Confirm Password *
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                        placeholder="Confirm your password" required>
                </div>

                <div class="form-check">
                    <input type="checkbox" id="agree_terms" name="agree_terms" class="form-check-input" required>
                    <label for="agree_terms" class="form-check-label">
                        I agree to the <a href="<?php echo SITE_URL; ?>terms.php" target="_blank">Terms of Service</a>
                        and <a href="<?php echo SITE_URL; ?>privacy.php" target="_blank">Privacy Policy</a>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary w-100" style="margin-top: var(--space-lg);">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="login.php"
                        style="color: var(--primary-color); font-weight: 600;">Login</a></p>
                <a href="<?php echo SITE_URL; ?>index.php" style="color: var(--text-muted); font-size: 0.875rem;">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </div>
        </div>
    </div>

    <script>
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('strengthBar');
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^A-Za-z0-9]/.test(password)
            };

            // Update requirement list
            document.getElementById('req-length').className = requirements.length ? 'requirement-met' : '';
            document.getElementById('req-uppercase').className = requirements.uppercase ? 'requirement-met' : '';
            document.getElementById('req-lowercase').className = requirements.lowercase ? 'requirement-met' : '';
            document.getElementById('req-number').className = requirements.number ? 'requirement-met' : '';
            document.getElementById('req-special').className = requirements.special ? 'requirement-met' : '';

            // Calculate strength
            const metCount = Object.values(requirements).filter(v => v).length;

            strengthBar.className = 'password-strength-bar';
            if (metCount <= 2) {
                strengthBar.classList.add('strength-weak');
            } else if (metCount <= 4) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        }
    </script>
</body>

</html>