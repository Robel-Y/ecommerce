<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    require_once '../config/constants.php';
}

// Get cart count from session
$cart_count = isset($_SESSION['cart_count']) ? $_SESSION['cart_count'] : 0;
?>
<!-- Header Section -->
<header class="main-header">
    <div class="container">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="top-bar-left">
                <span><i class="fas fa-phone-alt"></i> +1 (555) 123-4567</span>
                <span><i class="fas fa-envelope"></i> support@modernshop.com</span>
            </div>
            <div class="top-bar-right">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>!</span>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <a href="admin/dashboard.php" class="admin-link">
                            <i class="fas fa-cog"></i> Admin Panel
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="user/register.php" class="register-link">Create Account</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Navigation -->
        <nav class="main-nav">
            <!-- Logo -->
            <div class="logo">
                <a href="<?php echo SITE_URL; ?>index.php">
                    <div class="logo-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="logo-text">
                        <span class="logo-main">Modern</span>
                        <span class="logo-sub">Shop</span>
                    </div>
                </a>
            </div>

            <!-- Search Bar -->
            <div class="search-container">
                <form class="search-form" action="products/all.php" method="GET">
                    <input type="text" name="search" placeholder="Search products..." class="search-input">
                    <button type="submit" class="search-button">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            <!-- User Actions -->
            <div class="user-actions">
                <!-- Cart -->
                <div class="cart-icon">
                    <a href="user/cart.php" class="cart-link">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-count"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                </div>

                <!-- User Menu -->
                <div class="user-menu">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="dropdown">
                            <button class="user-dropdown">
                                <i class="fas fa-user-circle"></i>
                                <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-content">
                                <a href="user/profile.php"><i class="fas fa-user"></i> My Profile</a>
                                <a href="user/orders.php"><i class="fas fa-box"></i> My Orders</a>
                                <a href="user/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="user/login.php" class="login-btn">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" aria-label="Toggle menu">
                <i class="fas fa-bars"></i>
            </button>
        </nav>

        <!-- Categories Navigation -->
        <div class="categories-nav">
            <ul class="categories-list">
                <li><a href="products/all.php?category=electronics"><i class="fas fa-laptop"></i> Electronics</a></li>
                <li><a href="products/all.php?category=fashion"><i class="fas fa-tshirt"></i> Fashion</a></li>
                <li><a href="products/all.php?category=home"><i class="fas fa-home"></i> Home & Garden</a></li>
                <li><a href="products/all.php?category=sports"><i class="fas fa-basketball-ball"></i> Sports</a></li>
                <li><a href="products/all.php?category=beauty"><i class="fas fa-spa"></i> Beauty</a></li>
                <li><a href="products/all.php?category=books"><i class="fas fa-book"></i> Books</a></li>
            </ul>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div class="mobile-menu">
        <div class="mobile-menu-header">
            <div class="mobile-logo">
                <i class="fas fa-shopping-bag"></i>
                <span>Modern Shop</span>
            </div>
            <button class="mobile-menu-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="mobile-search">
            <form action="products/all.php" method="GET">
                <input type="text" name="search" placeholder="Search products...">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
        
        <div class="mobile-user-menu">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="mobile-user-info">
                    <i class="fas fa-user-circle"></i>
                    <div>
                        <span class="mobile-user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                        <span class="mobile-user-email"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></span>
                    </div>
                </div>
                <a href="user/profile.php"><i class="fas fa-user"></i> My Profile</a>
                <a href="user/orders.php"><i class="fas fa-box"></i> My Orders</a>
                <a href="user/cart.php"><i class="fas fa-shopping-cart"></i> Cart 
                    <?php if ($cart_count > 0): ?>
                        <span class="mobile-cart-count"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin/dashboard.php"><i class="fas fa-cog"></i> Admin Panel</a>
                <?php endif; ?>
                <a href="user/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
                <a href="user/login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="user/register.php"><i class="fas fa-user-plus"></i> Register</a>
            <?php endif; ?>
        </div>
        
        <div class="mobile-categories">
            <h3>Categories</h3>
            <a href="products/all.php?category=electronics"><i class="fas fa-laptop"></i> Electronics</a>
            <a href="products/all.php?category=fashion"><i class="fas fa-tshirt"></i> Fashion</a>
            <a href="products/all.php?category=home"><i class="fas fa-home"></i> Home & Garden</a>
            <a href="products/all.php?category=sports"><i class="fas fa-basketball-ball"></i> Sports</a>
            <a href="products/all.php?category=beauty"><i class="fas fa-spa"></i> Beauty</a>
            <a href="products/all.php?category=books"><i class="fas fa-book"></i> Books</a>
        </div>
    </div>
</header>

<!-- Flash Message Display -->
<?php
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
    ?>
    <div class="flash-message flash-<?php echo $message['type']; ?>">
        <div class="flash-content">
            <i class="fas fa-<?php echo $message['type'] === 'success' ? 'check-circle' : ($message['type'] === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
            <span><?php echo htmlspecialchars($message['text']); ?></span>
        </div>
        <button class="flash-close"><i class="fas fa-times"></i></button>
    </div>
    <?php
}
?>

<!-- Main Content Wrapper -->
<main class="main-content">
    <!-- Page Header if needed -->
    <?php if (isset($page_title) && !isset($hide_page_header)): ?>
    <div class="page-header">
        <div class="container">
            <h1 class="page-title"><?php echo $page_title; ?></h1>
            <?php if (isset($page_description)): ?>
                <p class="page-description"><?php echo $page_description; ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Breadcrumbs if needed -->
    <?php if (isset($breadcrumbs)): ?>
    <div class="breadcrumbs">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol>
                    <li><a href="<?php echo SITE_URL; ?>"><i class="fas fa-home"></i></a></li>
                    <?php foreach ($breadcrumbs as $text => $url): ?>
                        <?php if ($url): ?>
                            <li><a href="<?php echo $url; ?>"><?php echo htmlspecialchars($text); ?></a></li>
                        <?php else: ?>
                            <li class="active"><?php echo htmlspecialchars($text); ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </nav>
        </div>
    </div>
    <?php endif; ?>