<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/functions/security.php';
require_once __DIR__ . '/config/database.php';

$page_title = "Home | " . SITE_NAME;

include 'includes/header.php';

// Featured + Latest products from database
$featured_sql = "SELECT * FROM products ORDER BY created_at DESC LIMIT 4";
db_query($featured_sql);
$featured_products = db_result_set();

$products_sql = "SELECT * FROM products ORDER BY created_at DESC LIMIT 8 OFFSET 4";
db_query($products_sql);
$normal_products = db_result_set();
?>

<!-- Hero Banner -->
<section class="hero-banner">
    <div class="hero-content">
        <h1>Discover Amazing Products</h1>
        <p>Shop the latest trends with exclusive deals and fast delivery</p>
        <a href="products/all.php" class="btn btn-primary">Shop Now <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="hero-image">
        <img src="https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1770&q=80"
            alt="Modern E-Commerce">
    </div>
</section>

<!-- Featured Products -->
<section class="featured-products">
    <div class="section-header">
        <h2><i class="fas fa-star"></i> Featured Products</h2>
        <a href="products/all.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
    </div>

    <div class="products-grid">
        <?php if (!empty($featured_products)): ?>
            <?php foreach ($featured_products as $product): ?>
                <?php
                $image_url = !empty($product['image'])
                    ? $product['image']
                    : 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="600" height="600"><rect width="100%" height="100%" fill="%23f3f4f6"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="%239ca3af" font-family="Arial" font-size="20">No Image</text></svg>';
                if (strpos($image_url, 'data:') !== 0 && !filter_var($image_url, FILTER_VALIDATE_URL)) {
                    $image_url = rtrim(SITE_URL, '/') . '/' . ltrim($image_url, '/');
                }
                $stock = (int) ($product['stock'] ?? 0);
                $desc = strip_tags($product['description'] ?? '');
                if (strlen($desc) > 90) {
                    $desc = substr($desc, 0, 87) . '...';
                }
                ?>
                <div class="product-card">
                    <div class="product-image">
                        <a href="<?php echo SITE_URL; ?>products/details.php?id=<?php echo (int) $product['id']; ?>">
                            <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </a>
                        <?php if ($stock <= 0): ?>
                            <span class="product-badge out-of-stock">Out of Stock</span>
                        <?php elseif ($stock < 5): ?>
                            <span class="product-badge low-stock">Low Stock</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <h3>
                            <a href="<?php echo SITE_URL; ?>products/details.php?id=<?php echo (int) $product['id']; ?>" style="color: inherit; text-decoration: none;">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </a>
                        </h3>
                        <p class="product-description"><?php echo htmlspecialchars($desc); ?></p>
                        <div class="product-price">
                            <span class="current-price">$<?php echo number_format($product['price'], 2); ?></span>
                        </div>
                        <div class="product-rating">
                            <i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                        </div>
                        <button class="btn btn-add-cart" data-product-id="<?php echo (int) $product['id']; ?>" <?php echo ($stock <= 0) ? 'disabled' : ''; ?>>
                            <i class="fas fa-cart-plus"></i> <?php echo ($stock <= 0) ? 'Out of Stock' : 'Add to Cart'; ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-products"><p>No featured products available.</p></div>
        <?php endif; ?>
    </div>
</section>

<!-- Normal Products from Database -->
<section class="normal-products">
    <div class="section-header">
        <h2><i class="fas fa-box"></i> Latest Products</h2>
        <a href="products/all.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
    </div>

    <?php if (!empty($normal_products)): ?>
        <div class="products-grid">
            <?php foreach ($normal_products as $product): ?>
                <?php 
                // Handle image URL
                $image_url = !empty($product['image']) ? $product['image'] : 'assets/images/placeholder.jpg';
                if (filter_var($image_url, FILTER_VALIDATE_URL)) {
                    // It's a URL, use as is
                } else {
                    // It's a local path, prepend site URL if needed
                    $image_url = SITE_URL . '/' . ltrim($image_url, '/');
                }
                ?>
                
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?php echo htmlspecialchars($image_url); ?>"
                            alt="<?php echo htmlspecialchars($product['name']); ?>">
                        
                        <?php if (($product['stock'] ?? 0) <= 0): ?>
                            <span class="product-badge out-of-stock">Out of Stock</span>
                        <?php elseif (($product['stock'] ?? 0) < 5): ?>
                            <span class="product-badge low-stock">Low Stock</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-info">
                        <h3>
                            <a href="<?php echo SITE_URL; ?>products/details.php?id=<?php echo (int) $product['id']; ?>" style="color: inherit; text-decoration: none;">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </a>
                        </h3>
                        <p class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                        
                        <div class="product-price">
                            <span class="current-price">$<?php echo number_format($product['price'], 2); ?></span>
                        </div>
                        
                        <div class="product-rating">
                            <i class="far fa-star"></i>
                            <i class="far fa-star"></i>
                            <i class="far fa-star"></i>
                            <i class="far fa-star"></i>
                            <i class="far fa-star"></i>
                        </div>
                        
                        <button class="btn btn-add-cart" 
                                data-product-id="<?php echo $product['id']; ?>"
                                <?php echo (($product['stock'] ?? 0) <= 0) ? 'disabled' : ''; ?>>
                            <i class="fas fa-cart-plus"></i> 
                            <?php echo (($product['stock'] ?? 0) <= 0) ? 'Out of Stock' : 'Add to Cart'; ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-products">
            <p>No products available at the moment.</p>
        </div>
    <?php endif; ?>
</section>

<!-- Features Section -->
<section class="features-section">
    <div class="feature-card">
        <div class="feature-icon">
            <i class="fas fa-shipping-fast"></i>
        </div>
        <h3>Free Shipping</h3>
        <p>On orders over $50</p>
    </div>

    <div class="feature-card">
        <div class="feature-icon">
            <i class="fas fa-undo-alt"></i>
        </div>
        <h3>30-Day Returns</h3>
        <p>Easy returns policy</p>
    </div>

    <div class="feature-card">
        <div class="feature-icon">
            <i class="fas fa-shield-alt"></i>
        </div>
        <h3>Secure Payment</h3>
        <p>100% secure & safe</p>
    </div>

    <div class="feature-card">
        <div class="feature-icon">
            <i class="fas fa-headset"></i>
        </div>
        <h3>24/7 Support</h3>
        <p>Dedicated support</p>
    </div>
</section>

<!-- Newsletter Section -->
<section class="newsletter-section">
    <div class="newsletter-content">
        <h2>Stay Updated</h2>
        <p>Subscribe to our newsletter for the latest updates and exclusive offers</p>
        <form class="newsletter-form">
            <input type="email" placeholder="Enter your email" required>
            <button type="submit" class="btn btn-primary">Subscribe</button>
        </form>
    </div>
</section>

<?php include 'includes/footer.php'; ?>