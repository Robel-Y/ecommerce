<?php
/* ============================================
   USER PROFILE PAGE - Enhanced Version
   View and edit user profile information
============================================ */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../config/constants.php';
    require_once __DIR__ . '/../functions/security.php';
    start_secure_session();
}

// Include required files
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/utilities.php';
require_once __DIR__ . '/../functions/validation.php';

// Require login
require_login();

$page_title = 'My Profile';
$user_id = get_user_id();

// Get user data
$user = get_user_by_id($user_id);

// Handle profile update
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'update_profile') {
        // Update profile information
        $name = sanitize_input($_POST['name'] ?? '', 'string');
        $email = sanitize_input($_POST['email'] ?? '', 'email');
        $phone = sanitize_input($_POST['phone'] ?? '', 'string');
        $address = sanitize_input($_POST['address'] ?? '', 'string');
        $city = sanitize_input($_POST['city'] ?? '', 'string');
        $state = sanitize_input($_POST['state'] ?? '', 'string');
        $zip = sanitize_input($_POST['zip'] ?? '', 'string');
        $country = sanitize_input($_POST['country'] ?? '', 'string');

        $update_data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'country' => $country
        ];

        $result = update_user_profile($user_id, $update_data);

        if ($result['success']) {
            $success = $result['message'];
            // Refresh user data
            $user = get_user_by_id($user_id);
        } else {
            $errors = $result['errors'];
        }
    } elseif ($action === 'change_password') {
        // Change password
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match';
        } else {
            $result = change_password($user_id, $current_password, $new_password);

            if ($result['success']) {
                $success = $result['message'];
            } else {
                $errors = $result['errors'];
            }
        }
    }
}

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container section-padding">
    <div class="profile-container">
        <!-- Profile Sidebar -->
        <aside class="profile-sidebar">
            <div class="profile-card">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h3><?php echo htmlspecialchars($user['name'] ?? ($_SESSION['user_name'] ?? 'User')); ?></h3>
                <p class="text-muted"><?php echo htmlspecialchars($user['email'] ?? ($_SESSION['user_email'] ?? '')); ?>
                </p>
                <p class="badge badge-<?php echo $user['role'] === 'admin' ? 'danger' : 'info'; ?>">
                    <?php echo ucfirst($user['role']); ?>
                </p>
            </div>

            <nav class="profile-nav">
                <a href="#profile" class="active" onclick="showTab('profile')">
                    <i class="fas fa-user"></i> Profile Information
                </a>
                <a href="#password" onclick="showTab('password')">
                    <i class="fas fa-lock"></i> Change Password
                </a>
                <a href="<?php echo SITE_URL; ?>user/orders.php">
                    <i class="fas fa-box"></i> My Orders
                </a>
                <a href="<?php echo SITE_URL; ?>user/cart.php">
                    <i class="fas fa-shopping-cart"></i> Shopping Cart
                </a>
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="<?php echo SITE_URL; ?>admin/dashboard.php">
                        <i class="fas fa-cog"></i> Admin Panel
                    </a>
                <?php endif; ?>
                <a href="<?php echo SITE_URL; ?>user/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- Profile Content -->
        <div class="profile-content">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Profile Information Tab -->
            <div class="profile-tab active" id="profile-tab">
                <div class="card">
                    <div class="card-header">
                        <h2>Profile Information</h2>
                        <p class="text-muted">Update your personal information</p>
                    </div>

                    <form method="POST" action="<?php echo SITE_URL; ?>user/profile.php">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Username *</label>
                                <input type="text" id="name" name="name" class="form-control"
                                    value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" class="form-control"
                                    value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control"
                                value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="address">Street Address</label>
                            <input type="text" id="address" name="address" class="form-control"
                                value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" id="city" name="city" class="form-control"
                                    value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="state">State/Province</label>
                                <input type="text" id="state" name="state" class="form-control"
                                    value="<?php echo htmlspecialchars($user['state'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="zip">ZIP/Postal Code</label>
                                <input type="text" id="zip" name="zip" class="form-control"
                                    value="<?php echo htmlspecialchars($user['zip'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="country">Country</label>
                                <input type="text" id="country" name="country" class="form-control"
                                    value="<?php echo htmlspecialchars($user['country'] ?? 'United States'); ?>">
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Change Password Tab -->
            <div class="profile-tab" id="password-tab">
                <div class="card">
                    <div class="card-header">
                        <h2>Change Password</h2>
                        <p class="text-muted">Update your account password</p>
                    </div>

                    <form method="POST" action="<?php echo SITE_URL; ?>user/profile.php">
                        <input type="hidden" name="action" value="change_password">
                        <div class="form-group">
                            <label for="current_password">Current Password *</label>
                            <input type="password" id="current_password" name="current_password" class="form-control"
                                required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password *</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required>
                            <small class="form-text">
                                Password must be at least 8 characters and include uppercase, lowercase, number, and
                                special character.
                            </small>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                                required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function showTab(tabName) {
        document.querySelectorAll('.profile-tab').forEach(tab => tab.classList.remove('active'));
        document.querySelectorAll('.profile-nav a').forEach(link => link.classList.remove('active'));
        document.getElementById(tabName + '-tab').classList.add('active');
        event.target.closest('a').classList.add('active');
    }
</script>

<?php
// Include footer
include_once __DIR__ . '/../includes/footer.php';
?>