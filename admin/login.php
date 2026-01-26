<?php
/* ============================================
   ADMIN LOGIN PAGE
   Separate login for admin access
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

// If already logged in as admin, redirect to dashboard
if (is_logged_in() && is_admin()) {
    redirect('dashboard.php');
}

// Handle login submission
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? sanitize_input($_POST['email'], 'email') : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
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
        require_once __DIR__ . '/../config/database.php';
        
        // Verify login
        $login_result = verify_login($email, $password, $db->connection ?? null);
        
        if ($login_result['success']) {
            $user = $login_result['user'];
            
            // Check if user is admin
            if ($user['role'] !== 'admin') {
                $errors[] = 'Access denied. Admin privileges required.';
                log_security_event('Unauthorized admin login attempt', 'warning', [
                    'email' => $email,
                    'role' => $user['role']
                ]);
            } else {
                // Login successful
                login_user($user, isset($_POST['remember_me']));
                redirect_with_message('dashboard.php', 'success', 'Welcome back, ' . $user['name'] . '!');
            }
        } else {
            $errors = $login_result['errors'];
        }
    }
}

$page_title = 'Admin Login';
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: var(--space-lg);
        }
        
        .login-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            padding: var(--space-3xl);
            max-width: 450px;
            width: 100%;
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
        
        .login-header {
            text-align: center;
            margin-bottom: var(--space-xl);
        }
        
        .login-logo {
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
        
        .login-header h1 {
            font-size: 1.75rem;
            margin-bottom: var(--space-xs);
        }
        
        .login-header p {
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
        
        .back-to-site {
            text-align: center;
            margin-top: var(--space-lg);
            padding-top: var(--space-lg);
            border-top: 1px solid var(--light-200);
        }
        
        .back-to-site a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-to-site a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h1>Admin Login</h1>
                <p>Access the administration panel</p>
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
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="admin@example.com" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Enter your password" required>
                </div>
                
                <div class="form-check">
                    <input type="checkbox" id="remember_me" name="remember_me" class="form-check-input">
                    <label for="remember_me" class="form-check-label">Remember me</label>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-sign-in-alt"></i> Login to Dashboard
                </button>
            </form>
            
            <div class="back-to-site">
                <a href="<?php echo SITE_URL; ?>index.php">
                    <i class="fas fa-arrow-left"></i> Back to Main Site
                </a>
            </div>
        </div>
    </div>
</body>
</html>
