
<?php
/* ============================================
   PRODUCT DETAILS PAGE - Procedural
============================================ */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../functions/security.php'; // start_secure_session()
require_once __DIR__ . '/../includes/auth_check.php';
    // is_logged_in()
require_once __DIR__ . '/../functions/utilities.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    start_secure_session();
}

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($product_id <= 0) {
    redirect_with_message('products/all.php', 'error', 'Invalid product');
}

// Get product details (schema: products.category + products.image)
$query = "SELECT id, name, description, price, category, image AS image_url, stock, created_at
          FROM products
          WHERE id = :id
          LIMIT 1";
db_query($query);
db_bind(':id', $product_id);
$product = db_single();

if (!$product) {
    redirect_with_message('products/all.php', 'error', 'Product not found');
}

$placeholder_image = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="900" height="700"><rect width="100%" height="100%" fill="%23f3f4f6"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="%239ca3af" font-family="Arial" font-size="22">No Image</text></svg>';
$raw_image = trim($product['image_url'] ?? '');
if ($raw_image === '') {
    $main_image_url = $placeholder_image;
} elseif (strpos($raw_image, 'data:') === 0) {
    $main_image_url = $raw_image;
} elseif (filter_var($raw_image, FILTER_VALIDATE_URL)) {
    $main_image_url = $raw_image;
} else {
    $main_image_url = rtrim(SITE_URL, '/') . '/' . ltrim($raw_image, '/');
}

$short_desc = strip_tags($product['description'] ?? '');
if (strlen($short_desc) > 160) {
    $short_desc = substr($short_desc, 0, 157) . '...';
}

// Set page title
$page_title = $product['name'] . " | " . SITE_NAME;

// Get related products (same category, excluding current)
if (!empty($product['category'])) {
        $related_query = "SELECT id, name, price, image AS image_url
                                            FROM products
                                            WHERE category = :category
                                                AND id != :prod_id
                                            ORDER BY RAND()
                                            LIMIT 4";
        db_query($related_query);
        db_bind(':category', $product['category']);
        db_bind(':prod_id', $product_id);
        $related_products = db_result_set();
} else {
        $related_query = "SELECT id, name, price, image AS image_url
                                            FROM products
                                            WHERE id != :prod_id
                                            ORDER BY created_at DESC
                                            LIMIT 4";
        db_query($related_query);
        db_bind(':prod_id', $product_id);
        $related_products = db_result_set();
}

// Reviews are not available in the current DB schema (no product_reviews table)
$reviews = [];
$average_rating = 0;
$review_count = 0;

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<!-- Product Details Page Content -->
<div class="product-details">
    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumbs">
            <nav aria-label="breadcrumb">
                <ol>
                    <li><a href="<?php echo SITE_URL; ?>index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="<?php echo SITE_URL; ?>products/all.php">Products</a></li>
                    <?php if (!empty($product['category'])): ?>
                        <li><a href="<?php echo SITE_URL; ?>products/all.php?category=<?php echo urlencode($product['category']); ?>">
                                <?php echo htmlspecialchars($product['category']); ?>
                            </a></li>
                    <?php endif; ?>
                    <li class="active"><?php echo htmlspecialchars($product['name']); ?></li>
                </ol>
            </nav>
        </div>

        <!-- Product Main Section -->
        <div class="product-main">
            <!-- Product Images -->
            <div class="product-images">
                <!-- Main Image -->
                <div class="main-image">
                    <img id="mainProductImage" src="<?php echo htmlspecialchars($main_image_url); ?>"
                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                        onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($placeholder_image, ENT_QUOTES); ?>'">
                </div>

                <!-- Thumbnails -->
                <!-- Additional images not available in current schema -->
            </div>

            <!-- Product Info -->
            <div class="product-info">
                <!-- Product Header -->
                <div class="product-header">
                    <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>

                    <!-- Rating -->
                    <div class="product-rating">
                        <div class="stars">
                            <?php
                            $full_stars = floor($average_rating);
                            $half_star = $average_rating - $full_stars >= 0.5;

                            for ($i = 1; $i <= 5; $i++):
                                if ($i <= $full_stars):
                                    ?>
                                    <i class="fas fa-star"></i>
                                <?php elseif ($i == $full_stars + 1 && $half_star): ?>
                                    <i class="fas fa-star-half-alt"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; endfor; ?>
                        </div>
                        <span class="rating-value"><?php echo number_format($average_rating, 1); ?></span>
                        <span class="review-count">(<?php echo $review_count; ?> reviews)</span>
                        <a href="#reviews" class="review-link">Write a review</a>
                    </div>

                    <!-- SKU and Availability -->
                    <div class="product-meta">
                        <div class="meta-item">
                            <span class="meta-label">SKU:</span>
                            <span class="meta-value"><?php echo htmlspecialchars('SKU-' . $product['id']); ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Availability:</span>
                            <span class="meta-value <?php echo $product['stock'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                                <?php echo $product['stock'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                            </span>
                        </div>
                        <?php if ($product['stock'] > 0): ?>
                            <div class="meta-item">
                                <span class="meta-label">Only:</span>
                                <span class="meta-value"><?php echo $product['stock']; ?> left</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Price -->
                <div class="product-price">
                    <div class="price-current">$<?php echo number_format($product['price'], 2); ?></div>
                </div>

                <!-- Short Description -->
                <?php if (!empty($product['description'])): ?>
                    <div class="product-short-description">
                        <?php echo htmlspecialchars($short_desc); ?>
                    </div>
                <?php endif; ?>

                <!-- Product Actions -->
                <div class="product-actions">
                    <?php if ($product['stock'] > 0): ?>
                        <!-- Quantity -->
                        <div class="quantity-control">
                            <button class="quantity-btn decrease" onclick="decreaseQuantity()">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" id="productQuantity" value="1" min="1"
                                max="<?php echo $product['stock']; ?>" class="quantity-input">
                            <button class="quantity-btn increase" onclick="increaseQuantity()">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>

                        <!-- Add to Cart -->
                        <button class="btn btn-primary btn-add-to-cart" data-product-id="<?php echo $product_id; ?>">
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>

                        <!-- Buy Now -->
                        <button class="btn btn-success btn-buy-now" data-product-id="<?php echo $product_id; ?>">
                            <i class="fas fa-bolt"></i> Buy Now
                        </button>
                    <?php else: ?>
                        <!-- Out of Stock -->
                        <button class="btn btn-outline" disabled>
                            <i class="fas fa-bell"></i> Notify When Available
                        </button>
                    <?php endif; ?>

                    <!-- Wishlist -->
                    <button class="btn btn-wishlist" data-product-id="<?php echo $product_id; ?>" data-product-name="<?php echo htmlspecialchars($product['name']); ?>" onclick="toggleWishlist(this)">
                        <i class="far fa-heart"></i> Add to Wishlist
                    </button>
                </div>

                <!-- Product Features -->
                <div class="product-features">
                    <div class="feature">
                        <i class="fas fa-shipping-fast"></i>
                        <span>Free Shipping</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-undo-alt"></i>
                        <span>30-Day Returns</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-shield-alt"></i>
                        <span>1 Year Warranty</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-headset"></i>
                        <span>24/7 Support</span>
                    </div>
                </div>

                <!-- Share Product -->
                <div class="product-share">
                    <span class="share-label">Share:</span>
                    <div class="share-buttons">
                        <a href="#" class="share-btn facebook" title="Share on Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="share-btn twitter" title="Share on Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="share-btn pinterest" title="Share on Pinterest">
                            <i class="fab fa-pinterest-p"></i>
                        </a>
                        <a href="#" class="share-btn email" title="Share via Email">
                            <i class="fas fa-envelope"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Tabs -->
        <div class="product-tabs">
            <ul class="tabs-nav">
                <li class="active" data-tab="description">Description</li>
                <li data-tab="specifications">Specifications</li>
                <li data-tab="reviews">Reviews (<?php echo $review_count; ?>)</li>
                <li data-tab="shipping">Shipping & Returns</li>
            </ul>

            <div class="tabs-content">
                <!-- Description Tab -->
                <div class="tab-pane active" id="description">
                    <div class="product-description">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </div>
                </div>

                <!-- Specifications Tab -->
                <div class="tab-pane" id="specifications">
                    <p>No specifications available for this product.</p>
                </div>

                <!-- Reviews Tab -->
                <div class="tab-pane" id="reviews">
                    <!-- Review Summary -->
                    <div class="review-summary">
                        <div class="average-rating">
                            <div class="rating-number" data-rating-number><?php echo number_format($average_rating, 1); ?></div>
                            <div class="rating-stars">
                                <?php
                                for ($i = 1; $i <= 5; $i++):
                                    if ($i <= $full_stars):
                                        ?>
                                        <i class="fas fa-star"></i>
                                    <?php elseif ($i == $full_stars + 1 && $half_star): ?>
                                        <i class="fas fa-star-half-alt"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; endfor; ?>
                            </div>
                            <div class="rating-text">Based on <span data-review-count><?php echo $review_count; ?></span> reviews</div>
                        </div>

                        <!-- Add Review Button -->
                        <?php if (is_logged_in()): ?>
                            <button class="btn btn-primary" type="button" data-toggle-review-form>
                                <i class="fas fa-edit"></i> Write a Review
                            </button>
                        <?php else: ?>
                            <a href="<?php echo SITE_URL; ?>user/login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Login to Review
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Review form (client-side; DB has no reviews table) -->
                    <?php if (is_logged_in()): ?>
                        <form class="review-form" data-review-form style="display:none;" autocomplete="off">
                            <div class="form-row">
                                <label class="form-label">Your rating</label>
                                <div class="rating-input" data-rating-input>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <button type="button" class="star" data-value="<?php echo $i; ?>" aria-label="Rate <?php echo $i; ?> stars">
                                            <i class="far fa-star"></i>
                                        </button>
                                    <?php endfor; ?>
                                </div>
                                <input type="hidden" name="rating" value="0" data-rating-value>
                            </div>
                            <div class="form-row">
                                <label class="form-label" for="reviewTitle">Title</label>
                                <input id="reviewTitle" class="form-control" name="title" maxlength="80" placeholder="Short summary" required>
                            </div>
                            <div class="form-row">
                                <label class="form-label" for="reviewContent">Review</label>
                                <textarea id="reviewContent" class="form-control" name="content" rows="4" maxlength="1000" placeholder="Write your review" required></textarea>
                            </div>
                            <div class="form-row form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Submit Review
                                </button>
                                <button type="button" class="btn btn-outline" data-cancel-review>
                                    Cancel
                                </button>
                            </div>
                            <div class="form-hint">Saved locally in this browser.</div>
                        </form>
                    <?php endif; ?>

                    <!-- Reviews List -->
                    <div class="reviews-list">
                        <?php if (empty($reviews)): ?>
                            <div class="no-reviews">
                                <p>No reviews yet. Be the first to review this product!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item">
                                    <div class="review-header">
                                        <div class="reviewer-info">
                                            <div class="reviewer-name"><?php echo htmlspecialchars($review['user_name']); ?>
                                            </div>
                                            <div class="review-date"><?php echo format_date($review['created_at']); ?></div>
                                        </div>
                                        <div class="review-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>

                                    <div class="review-title"><?php echo htmlspecialchars($review['title']); ?></div>
                                    <div class="review-content"><?php echo nl2br(htmlspecialchars($review['content'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Shipping Tab -->
                <div class="tab-pane" id="shipping">
                    <div class="shipping-info">
                        <h3>Shipping Information</h3>
                        <ul>
                            <li>Free shipping on orders over $50</li>
                            <li>Standard shipping: 3-5 business days</li>
                            <li>Express shipping: 1-2 business days (additional charge)</li>
                            <li>International shipping available</li>
                        </ul>

                        <h3>Return Policy</h3>
                        <ul>
                            <li>30-day return policy</li>
                            <li>Items must be in original condition</li>
                            <li>Free returns for defective items</li>
                            <li>Refund processed within 5-7 business days</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <?php if (!empty($related_products)): ?>
            <div class="related-products">
                <h2>Related Products</h2>
                <div class="products-grid">
                    <?php foreach ($related_products as $related): ?>
                        <?php
                        $related_raw_image = trim($related['image_url'] ?? '');
                        if ($related_raw_image === '') {
                            $related_image_url = $placeholder_image;
                        } elseif (strpos($related_raw_image, 'data:') === 0) {
                            $related_image_url = $related_raw_image;
                        } elseif (filter_var($related_raw_image, FILTER_VALIDATE_URL)) {
                            $related_image_url = $related_raw_image;
                        } else {
                            $related_image_url = rtrim(SITE_URL, '/') . '/' . ltrim($related_raw_image, '/');
                        }
                        ?>
                        <div class="product-card-wrapper">
                            <div class="product-card">
                                <div class="product-image">
                                    <a href="<?php echo SITE_URL; ?>products/details.php?id=<?php echo $related['id']; ?>">
                                        <img src="<?php echo htmlspecialchars($related_image_url); ?>"
                                            alt="<?php echo htmlspecialchars($related['name']); ?>"
                                            onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($placeholder_image, ENT_QUOTES); ?>'">
                                    </a>
                                </div>

                                <div class="product-info">
                                    <h3 class="product-title">
                                        <a href="<?php echo SITE_URL; ?>products/details.php?id=<?php echo $related['id']; ?>">
                                            <?php echo htmlspecialchars($related['name']); ?>
                                        </a>
                                    </h3>

                                    <div class="product-price">
                                        <span class="current-price">$<?php echo number_format($related['price'], 2); ?></span>
                                    </div>

                                    <div class="product-actions">
                                        <button class="btn btn-add-cart" data-product-id="<?php echo $related['id']; ?>"
                                            data-product-name="<?php echo htmlspecialchars($related['name']); ?>">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Product Details JavaScript
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize tab functionality
        initTabs();

        // Initialize quantity controls
        initQuantityControls();

        // If user clicks "Write a review" near the title, switch to Reviews tab and open the form.
        const reviewLink = document.querySelector('.review-link');
        if (reviewLink) {
            reviewLink.addEventListener('click', function (e) {
                e.preventDefault();
                const reviewsTab = document.querySelector('.tabs-nav li[data-tab="reviews"]');
                if (reviewsTab) reviewsTab.click();

                const formToggle = document.querySelector('[data-toggle-review-form]');
                const form = document.querySelector('[data-review-form]');
                if (formToggle) {
                    formToggle.click();
                } else if (form) {
                    form.style.display = 'block';
                    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        }
    });

    function initTabs() {
        const tabNavs = document.querySelectorAll('.tabs-nav li');
        const tabPanes = document.querySelectorAll('.tab-pane');

        tabNavs.forEach(tab => {
            tab.addEventListener('click', function () {
                const tabId = this.getAttribute('data-tab');

                // Update active tab
                tabNavs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                // Show active content
                tabPanes.forEach(pane => {
                    pane.classList.remove('active');
                    if (pane.id === tabId) {
                        pane.classList.add('active');
                    }
                });
            });
        });
    }

    function initQuantityControls() {
        window.decreaseQuantity = function () {
            const input = document.getElementById('productQuantity');
            let value = parseInt(input.value);
            if (value > 1) {
                input.value = value - 1;
            }
        };

        window.increaseQuantity = function () {
            const input = document.getElementById('productQuantity');
            let value = parseInt(input.value);
            const max = parseInt(input.getAttribute('max'));
            if (value < max) {
                input.value = value + 1;
            }
        };
    }



    // Image gallery functions
    function changeMainImage(imageUrl) {
        const mainImage = document.getElementById('mainProductImage');
        const thumbnails = document.querySelectorAll('.image-thumbnails .thumbnail');

        mainImage.src = imageUrl;

        // Update active thumbnail
        thumbnails.forEach(thumb => {
            thumb.classList.remove('active');
            if (thumb.querySelector('img').src === imageUrl) {
                thumb.classList.add('active');
            }
        });
    }

    // Reviews disabled (no product_reviews table in DB)

</script>

<?php
// Include footer
include_once __DIR__ . '/../includes/footer.php';
?>