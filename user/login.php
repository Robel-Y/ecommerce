<?php
/* ============================================
   USER LOGIN PAGE - Procedural & Secure
============================================ */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../config/constants.php';
    require_once __DIR__ . '/../functions/security.php';
    start_secure_session();
}

// Include required files
require_once __DIR__ . '/../functions/utilities.php';
require_once __DIR__ . '/../functions/validation.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php'; // DB connection

// If already logged in, redirect to profile
if (is_logged_in()) {
    redirect('profile.php');
}

// Handle login submission
$errors = [];
$success = '';

// Get PDO connection
$connection = db_connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? sanitize_input($_POST['email'], 'email') : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember_me = isset($_POST['remember_me']);

    // Validate inputs
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!validate_email($email)) {
        $errors[] = 'Invalid email format';
    }

    if (empty($password)) {
        $errors[] = 'Password is required';
    }

    if (empty($errors)) {
        // Verify login
        $login_result = verify_login($email, $password, $connection);

        if ($login_result['success']) {
            $user = $login_result['user'];

            // Login successful
            login_user($user, $remember_me);

            // Redirect back to originally requested protected page (e.g. checkout)
            $redirect_url = $_SESSION['redirect_url'] ?? ($_GET['redirect'] ?? 'profile.php');
            unset($_SESSION['redirect_url']);

            $redirect_url = (string) $redirect_url;
            // Basic safety: prevent external redirects
            if (preg_match('/\r|\n/', $redirect_url) || preg_match('/^https?:\/\//i', $redirect_url) || str_starts_with($redirect_url, '//')) {
                $redirect_url = 'profile.php';
            }

            redirect_with_message($redirect_url, 'success', 'Welcome back, ' . $user['name'] . '!');
        } else {
            $errors = $login_result['errors'];
        }
    }
}

$page_title = 'Login';
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--space-lg);
        }

        .auth-container {
            max-width: 480px;
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
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
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

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: var(--space-xl) 0;
            color: var(--text-muted);
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--light-200);
        }

        .divider span {
            padding: 0 var(--space-md);
        }

        .auth-footer {
            text-align: center;
            margin-top: var(--space-lg);
            padding-top: var(--space-lg);
            border-top: 1px solid var(--light-200);
        }

        .social-login {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-sm);
            margin-bottom: var(--space-lg);
        }

        .social-btn {
            padding: 12px;
            border: 2px solid var(--light-300);
            border-radius: var(--radius-md);
            background: var(--white);
            cursor: pointer;
            transition: all var(--transition-fast);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 500;
        }

        .social-btn:hover {
            border-color: var(--primary-color);
            background: var(--light-100);
        }

        .social-btn.google {
            color: #DB4437;
        }

        .social-btn.facebook {
            color: #4267B2;
        }
    </style>
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h1>Welcome Back!</h1>
                <p>Login to your account</p>
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

            <!-- Social Login (Optional - can be implemented later) -->
            <div class="social-login">
                <button class="social-btn google" onclick="showNotification('Google login coming soon', 'info')">
                    <i class="fab fa-google"></i> Google
                </button>
                <button class="social-btn facebook" onclick="showNotification('Facebook login coming soon', 'info')">
                    <i class="fab fa-facebook"></i> Facebook
                </button>
            </div>

            <div class="divider">
                <span>OR</span>
            </div>

            <form method="POST" action="">


                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="your@email.com"
                        required autofocus
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" class="form-control"
                        placeholder="Enter your password" required>
                </div>

                <div class="form-check" style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="remember_me" name="remember_me" class="form-check-input">
                        <label for="remember_me" class="form-check-label">Remember me</label>
                    </div>
                    <a href="forgot_password.php" style="font-size: 0.875rem;">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-primary w-100" style="margin-top: var(--space-lg);">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php"
                        style="color: var(--primary-color); font-weight: 600;">Sign Up</a></p>
                <a href="<?php echo SITE_URL; ?>index.php" style="color: var(--text-muted); font-size: 0.875rem;">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </div>
        </div>
    </div>

    <script>
        function showNotification(message, type) {
            alert(message);
        }
    </script>
</body>

</html>