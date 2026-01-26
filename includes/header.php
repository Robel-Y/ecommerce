<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../config/constants.php';
}

// Load helpers for DB-driven categories
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/utilities.php';

// Get cart count from session (support both legacy and current cart session shapes)
$cart_count = 0;
if (isset($_SESSION['cart']['count'])) {
    $cart_count = (int) $_SESSION['cart']['count'];
} elseif (isset($_SESSION['cart_count'])) {
    $cart_count = (int) $_SESSION['cart_count'];
}

$nav_categories = [];
try {
    if (function_exists('get_distinct_categories')) {
        $nav_categories = get_distinct_categories(6);
    }
} catch (Throwable $e) {
    $nav_categories = [];
}

$category_icon_map = [
    'elect' => 'fa-laptop',
    'fashion' => 'fa-tshirt',
    'cloth' => 'fa-tshirt',
    'home' => 'fa-home',
    'garden' => 'fa-home',
    'sport' => 'fa-basketball-ball',
    'beaut' => 'fa-spa',
    'book' => 'fa-book',
];

function header_category_icon(string $category, array $map): string
{
    $key = mb_strtolower($category);
    foreach ($map as $needle => $icon) {
        if (strpos($key, $needle) !== false) {
            return $icon;
        }
    }
    return 'fa-tag';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Merkato Go'; ?></title>

    <script>
        (function() {
            try {
                var mode = localStorage.getItem('theme_mode');
                if (mode === 'light' || mode === 'dark') {
                    document.documentElement.setAttribute('data-theme', mode);
                } else {
                    document.documentElement.removeAttribute('data-theme');
                }
            } catch (e) {}
        })();
    </script>

    <!-- Icons & Fonts (used across UI) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="<?php echo SITE_URL; ?>assets/images/icons/favicon.ico" type="image/x-icon">
    
    <!-- Global Variables & Base Styles -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/variables.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/components.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/forms.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/buttons.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/notifications.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/footer.css">

    <!-- Component & Animation Styles -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/animations-header.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/animations-footer.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/animations.css">

    <!-- Responsive Design -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/responsive-header.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/responsive-footer.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/responsive.css">

    <!-- Modern theme overrides (keep last) -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/modern.css">

    <?php
    $current_path = $_SERVER['PHP_SELF'] ?? '';
    $current_file = basename($current_path);
    $is_products_area = strpos($current_path, '/products/') !== false;
    $is_home = ($current_file === 'index.php' || $current_file === '');
    ?>

    <!-- Products/Category styles (only where needed) -->
    <?php if ($is_products_area || $is_home): ?>
        <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/products.css">
        <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/responsive-products.css">
    <?php endif; ?>
    <?php if ($current_file === 'category.php'): ?>
        <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/category.css">
        <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/responsive-category.css">
    <?php endif; ?>

    <!-- Profile CSS (only on profile.php) -->
    <?php if ($current_file === 'profile.php'): ?>
        <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/profile.css">
    <?php endif; ?>

    <!-- Home Page CSS (only on index.php) -->
    <?php if ($is_home): ?>
        <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/main.css">
    <?php endif; ?>

    <script>
        // Keep both for compatibility: some scripts reference global SITE_URL, others use window.SITE_URL
        window.SITE_URL = '<?php echo SITE_URL; ?>';
        const SITE_URL = '<?php echo SITE_URL; ?>';
    </script>
</head>

<body>

    <!-- Header Section -->
    <header class="main-header">

        <div class="container">
            <!-- Main Navigation -->
            <nav class="main-nav">
                <!-- Logo -->
                <div class="logo">
                    <a href="<?php echo SITE_URL; ?>index.php">
                        <div class="logo-icon">
                            <img src="<?php echo SITE_URL; ?>assets/images/icons/favicon.ico" alt="Merkato Go">
                        </div>
                        <div class="logo-text">
                            <span class="logo-main">Merkato</span>
                            <span class="logo-sub">Go</span>
                        </div>
                    </a>
                </div>

                <!-- Search Bar -->
                <div class="search-container">
                    <form class="search-form" action="<?php echo SITE_URL; ?>products/all.php" method="GET">
                        <input type="text" name="search" placeholder="Search products..." class="search-input" autocomplete="off">
                        <button type="submit" class="search-button">
                            <i class="fas fa-search"></i>
                        </button>
                        <div class="search-suggestions" aria-label="Search suggestions" role="listbox" hidden></div>
                    </form>
                </div>

                <!-- User Actions -->
                <div class="user-actions">
                    <button type="button" class="theme-toggle" >
                        <i class="fas fa-circle-half-stroke"></i>
                    </button>

                    <!-- Cart -->
                    <div class="cart-icon">
                        <a href="<?php echo SITE_URL; ?>user/cart.php" class="cart-link">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-count" style="<?php echo ($cart_count > 0) ? '' : 'display:none;'; ?>">
                                <?php echo $cart_count; ?>
                            </span>
                        </a>
                    </div>

                    <!-- User Menu -->
                    <div class="user-menu">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="dropdown">
                                <button class="user-dropdown">
                                    <i class="fas fa-user-circle"></i>
                                    <span
                                        class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                <div class="dropdown-content">
                                    <a href="<?php echo SITE_URL; ?>user/profile.php"><i class="fas fa-user"></i> My
                                        Profile</a>
                                    <a href="<?php echo SITE_URL; ?>user/orders.php"><i class="fas fa-box"></i> My
                                        Orders</a>
                                    <a href="<?php echo SITE_URL; ?>user/logout.php"><i class="fas fa-sign-out-alt"></i>
                                        Logout</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <a href="<?php echo SITE_URL; ?>user/login.php" class="login-btn">
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
                    <?php if (!empty($nav_categories)): ?>
                        <?php foreach ($nav_categories as $cat): ?>
                            <?php $icon = header_category_icon($cat, $category_icon_map); ?>
                            <li>
                                <a href="<?php echo SITE_URL; ?>products/all.php?category=<?php echo rawurlencode($cat); ?>">
                                    <i class="fas <?php echo htmlspecialchars($icon); ?>"></i>
                                    <?php echo htmlspecialchars($cat); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><a href="<?php echo SITE_URL; ?>products/all.php?category=electronics"><i class="fas fa-laptop"></i> Electronics</a></li>
                        <li><a href="<?php echo SITE_URL; ?>products/all.php?category=fashion"><i class="fas fa-tshirt"></i> Fashion</a></li>
                        <li><a href="<?php echo SITE_URL; ?>products/all.php?category=home"><i class="fas fa-home"></i> Home & Garden</a></li>
                        <li><a href="<?php echo SITE_URL; ?>products/all.php?category=sports"><i class="fas fa-basketball-ball"></i> Sports</a></li>
                        <li><a href="<?php echo SITE_URL; ?>products/all.php?category=beauty"><i class="fas fa-spa"></i> Beauty</a></li>
                        <li><a href="<?php echo SITE_URL; ?>products/all.php?category=books"><i class="fas fa-book"></i> Books</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div class="mobile-menu">
            <div class="mobile-menu-header">
                <div class="mobile-logo">
                    <img src="assets/images/icons/favicon.ico" alt="Merkato" srcset="">
                    <span>Merkato Go</span>
                </div>
                <button class="mobile-menu-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="mobile-user-menu">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="mobile-user-info">
                        <i class="fas fa-user-circle"></i>
                        <div>
                            <span
                                class="mobile-user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                            <span
                                class="mobile-user-email"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></span>
                        </div>
                    </div>
                    <a href="<?php echo SITE_URL; ?>user/profile.php"><i class="fas fa-user"></i> My Profile</a>
                    <a href="<?php echo SITE_URL; ?>user/orders.php"><i class="fas fa-box"></i> My Orders</a>
                    <a href="<?php echo SITE_URL; ?>user/cart.php"><i class="fas fa-shopping-cart"></i> Cart
                        <span class="mobile-cart-count" style="<?php echo ($cart_count > 0) ? '' : 'display:none;'; ?>">
                            <?php echo $cart_count; ?>
                        </span>
                    </a>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <a href="<?php echo SITE_URL; ?>admin/dashboard.php"><i class="fas fa-cog"></i> Admin Panel</a>
                    <?php endif; ?>
                    <a href="<?php echo SITE_URL; ?>user/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>user/login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="<?php echo SITE_URL; ?>user/register.php"><i class="fas fa-user-plus"></i> Register</a>
                <?php endif; ?>
            </div>

            <div class="mobile-categories">
                <h3>Categories</h3>
                <?php if (!empty($nav_categories)): ?>
                    <?php foreach ($nav_categories as $cat): ?>
                        <?php $icon = header_category_icon($cat, $category_icon_map); ?>
                        <a href="<?php echo SITE_URL; ?>products/all.php?category=<?php echo rawurlencode($cat); ?>">
                            <i class="fas <?php echo htmlspecialchars($icon); ?>"></i>
                            <?php echo htmlspecialchars($cat); ?>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>products/all.php?category=electronics"><i class="fas fa-laptop"></i> Electronics</a>
                    <a href="<?php echo SITE_URL; ?>products/all.php?category=fashion"><i class="fas fa-tshirt"></i> Fashion</a>
                    <a href="<?php echo SITE_URL; ?>products/all.php?category=home"><i class="fas fa-home"></i> Home & Garden</a>
                    <a href="<?php echo SITE_URL; ?>products/all.php?category=sports"><i class="fas fa-basketball-ball"></i> Sports</a>
                    <a href="<?php echo SITE_URL; ?>products/all.php?category=beauty"><i class="fas fa-spa"></i> Beauty</a>
                    <a href="<?php echo SITE_URL; ?>products/all.php?category=books"><i class="fas fa-book"></i> Books</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main>