<?php
// This file is included in header.php, so no separate navigation file needed
// All navigation code is integrated into header.php for better performance
?>

<!-- Breadcrumbs Component -->
<div class="breadcrumbs">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol>
                <?php
                // Dynamically generate breadcrumbs based on current page
                $current_page = basename($_SERVER['PHP_SELF']);
                $breadcrumbs = [
                    'Home' => 'index.php'
                ];
                
                // Add additional breadcrumbs based on page
                switch ($current_page) {
                    case 'products.php':
                        $breadcrumbs['Products'] = '';
                        break;
                    case 'cart.php':
                        $breadcrumbs['Cart'] = '';
                        break;
                    case 'checkout.php':
                        $breadcrumbs['Cart'] = 'cart.php';
                        $breadcrumbs['Checkout'] = '';
                        break;
                    case 'profile.php':
                        $breadcrumbs['My Account'] = '';
                        $breadcrumbs['Profile'] = '';
                        break;
                    case 'orders.php':
                        $breadcrumbs['My Account'] = 'profile.php';
                        $breadcrumbs['Orders'] = '';
                        break;
                }
                
                // Output breadcrumbs
                $i = 1;
                foreach ($breadcrumbs as $text => $url) {
                    $is_last = ($i === count($breadcrumbs));
                    ?>
                    <li class="<?php echo $is_last ? 'active' : ''; ?>">
                        <?php if (!$is_last && $url): ?>
                            <a href="<?php echo $url; ?>">
                                <?php echo htmlspecialchars($text); ?>
                            </a>
                        <?php else: ?>
                            <?php echo htmlspecialchars($text); ?>
                        <?php endif; ?>
                    </li>
                    <?php
                    $i++;
                }
                ?>
            </ol>
        </nav>
    </div>
</div>