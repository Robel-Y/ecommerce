<?php
/* ============================================
   ADMIN SIDEBAR - Reusable Navigation Component
   Provides consistent navigation across admin pages
============================================ */

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Define menu items
$menu_items = [
    [
        'icon' => 'tachometer-alt',
        'label' => 'Dashboard',
        'file' => 'dashboard.php',
        'badge' => null
    ],
    [
        'icon' => 'box',
        'label' => 'Products',
        'file' => 'products.php',
        'badge' => null
    ],
    [
        'icon' => 'shopping-cart',
        'label' => 'Orders',
        'file' => 'orders.php',
        'badge' => 'new'
    ],
    [
        'icon' => 'users',
        'label' => 'Users',
        'file' => 'users.php',
        'badge' => null
    ]
];
?>

<!-- Admin Sidebar -->
<aside class="admin-sidebar" id="adminSidebar">
    <nav class="sidebar-nav">
        <ul class="sidebar-menu">
            <?php foreach ($menu_items as $item): ?>
            <li class="menu-item <?php echo $current_page === $item['file'] ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>admin/<?php echo $item['file']; ?>">
                    <i class="fas fa-<?php echo $item['icon']; ?>"></i>
                    <span><?php echo $item['label']; ?></span>
                    <?php if ($item['badge']): ?>
                    <span class="menu-badge"><?php echo $item['badge']; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        
        <div class="sidebar-footer">
            <div class="sidebar-info">
                <i class="fas fa-info-circle"></i>
                <small>v1.0.0</small>
            </div>
        </div>
    </nav>
</aside>

<!-- Main Content Area -->
<main class="admin-main">
