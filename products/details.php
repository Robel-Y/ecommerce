<?php
/* ============================================
   PRODUCT DETAILS PAGE - Procedural
   Display detailed product information
============================================ */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../config/constants.php';
    start_secure_session();
}

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/utilities.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    redirect_with_message('products/all.php', 'error', 'Invalid product');
}

// Get product details
$query = "SELECT p.*, c.name as category_name, c.id as category_id 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.id = ? AND p.status = 'active'";
$stmt = $db->prepare($query);
$stmt->bind_param('i', $product_id);
$stmt->execute();
$product_result = $stmt->get_result();
$product = $product_result->fetch_assoc();
$stmt->close();

if (!$product) {
    redirect_with_message('products/all.php', 'error', 'Product not found');
}

// Set page title
$page_title = $product['name'] . " | " . SITE_NAME;

// Get related products (same category, excluding current)
$related_query = "SELECT id, name, price, image_url, discount 
                  FROM products 
                  WHERE category_id = ? 
                  AND id != ? 
                  AND status = 'active' 
                  ORDER BY RAND() 
                  LIMIT 4";
$stmt = $db->prepare($related_query);
$stmt->bind_param('ii', $product['category_id'], $product_id);
$stmt->execute();
$related_result = $stmt->get_result();
$related_products = [];

while ($row = $related_result->fetch_assoc()) {
    $related_products[] = $row;
}
$stmt->close();

// Get product reviews
$reviews_query = "SELECT r.*, u.name as user_name 
                  FROM product_reviews r 
                  LEFT JOIN users u ON r.user_id = u.id 
                  WHERE r.product_id = ? AND r.status = 'approved' 
                  ORDER BY r.created_at DESC 
                  LIMIT 10";
$stmt = $db->prepare($reviews_query);
$stmt->bind_param('i', $product_id);
$stmt->execute();
$reviews_result = $stmt->get_result();
$reviews = [];

while ($row = $reviews_result->fetch_assoc()) {
    $reviews[] = $row;
}
$stmt->close();

// Calculate average rating
$rating_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
                 FROM product_reviews 
                 WHERE product_id = ? AND status = 'approved'";
$stmt = $db->prepare($rating_query);
$stmt->bind_param('i', $product_id);
$stmt->execute();
$rating_result = $stmt->get_result();
$rating_data = $rating_result->fetch_assoc();
$stmt->close();

$average_rating = $rating_data['avg_rating'] ?? 0;
$review_count = $rating_data['review_count'] ?? 0;

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
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="products/all.php">Products</a></li>
                    <?php if (!empty($product['category_name'])): ?>
                    <li><a href="products/category.php?id=<?php echo $product['category_id']; ?>">
                        <?php echo htmlspecialchars($product['category_name']); ?>
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
                    <img id="mainProductImage" 
                         src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         onerror="this.src='assets/images/products/default.jpg'">
                </div>
                
                <!-- Thumbnails -->
                <?php 
                // Get additional images if available
                $additional_images = [];
                if (!empty($product['additional_images'])) {
                    $additional_images = json_decode($product['additional_images'], true);
                }
                
                if (!empty($additional_images)):
                ?>
                <div class="image-thumbnails">
                    <div class="thumbnail active" onclick="changeMainImage('<?php echo htmlspecialchars($product['image_url']); ?>')">
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                             alt="Main">
                    </div>
                    <?php foreach ($additional_images as $index => $image_url): ?>
                    <div class="thumbnail" onclick="changeMainImage('<?php echo htmlspecialchars($image_url); ?>')">
                        <img src="<?php echo htmlspecialchars($image_url); ?>" 
                             alt="Product Image <?php echo $index + 1; ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
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
                            <span class="meta-value"><?php echo htmlspecialchars($product['sku']); ?></span>
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
                    <?php if ($product['discount'] > 0): 
                        $discounted_price = $product['price'] * (1 - $product['discount'] / 100);
                    ?>
                    <div class="price-current">$<?php echo number_format($discounted_price, 2); ?></div>
                    <div class="price-original">$<?php echo number_format($product['price'], 2); ?></div>
                    <div class="price-discount">Save <?php echo $product['discount']; ?>%</div>
                    <?php else: ?>
                    <div class="price-current">$<?php echo number_format($product['price'], 2); ?></div>
                    <?php endif; ?>
                </div>

                <!-- Short Description -->
                <?php if (!empty($product['short_description'])): ?>
                <div class="product-short-description">
                    <?php echo htmlspecialchars($product['short_description']); ?>
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
                        <input type="number" id="productQuantity" value="1" min="1" max="<?php echo $product['stock']; ?>" 
                               class="quantity-input">
                        <button class="quantity-btn increase" onclick="increaseQuantity()">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    
                    <!-- Add to Cart -->
                    <button class="btn btn-primary btn-add-to-cart" onclick="addToCart()">
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </button>
                    
                    <!-- Buy Now -->
                    <button class="btn btn-success btn-buy-now" onclick="buyNow()">
                        <i class="fas fa-bolt"></i> Buy Now
                    </button>
                    <?php else: ?>
                    <!-- Out of Stock -->
                    <button class="btn btn-outline" disabled>
                        <i class="fas fa-bell"></i> Notify When Available
                    </button>
                    <?php endif; ?>
                    
                    <!-- Wishlist -->
                    <button class="btn btn-wishlist" onclick="toggleWishlist()">
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
                    <?php
                    $specifications = [];
                    if (!empty($product['specifications'])) {
                        $specifications = json_decode($product['specifications'], true);
                    }
                    
                    if (!empty($specifications)):
                    ?>
                    <table class="specifications-table">
                        <?php foreach ($specifications as $key => $value): ?>
                        <tr>
                            <th><?php echo htmlspecialchars($key); ?></th>
                            <td><?php echo htmlspecialchars($value); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <?php else: ?>
                    <p>No specifications available for this product.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Reviews Tab -->
                <div class="tab-pane" id="reviews">
                    <!-- Review Summary -->
                    <div class="review-summary">
                        <div class="average-rating">
                            <div class="rating-number"><?php echo number_format($average_rating, 1); ?></div>
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
                            <div class="rating-text">Based on <?php echo $review_count; ?> reviews</div>
                        </div>
                        
                        <!-- Add Review Button -->
                        <?php if (is_logged_in()): ?>
                        <button class="btn btn-primary" onclick="showReviewForm()">
                            <i class="fas fa-edit"></i> Write a Review
                        </button>
                        <?php else: ?>
                        <a href="user/login.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Login to Review
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Review Form (Hidden by default) -->
                    <div class="review-form-container" id="reviewForm" style="display: none;">
                        <h3>Write a Review</h3>
                        <form id="submitReviewForm" method="POST" action="process/review_process.php">
                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            
                            <div class="form-group">
                                <label for="reviewRating">Rating</label>
                                <div class="rating-input">
                                    <div class="rating-stars-input">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="far fa-star" data-rating="<?php echo $i; ?>" 
                                           onmouseover="hoverStar(this)" 
                                           onmouseout="resetStars()" 
                                           onclick="selectRating(this)"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <input type="hidden" id="reviewRating" name="rating" value="0">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="reviewTitle">Review Title</label>
                                <input type="text" id="reviewTitle" name="title" class="form-control" 
                                       placeholder="Summarize your review" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="reviewContent">Your Review</label>
                                <textarea id="reviewContent" name="content" class="form-control" 
                                          rows="5" placeholder="Share your experience with this product" required></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Submit Review</button>
                                <button type="button" class="btn btn-outline" onclick="hideReviewForm()">Cancel</button>
                            </div>
                        </form>
                    </div>
                    
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
                                    <div class="reviewer-name"><?php echo htmlspecialchars($review['user_name']); ?></div>
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
                <div class="product-card-wrapper">
                    <div class="product-card">
                        <div class="product-image">
                            <a href="products/details.php?id=<?php echo $related['id']; ?>">
                                <img src="<?php echo htmlspecialchars($related['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($related['name']); ?>"
                                     onerror="this.src='assets/images/products/default.jpg'">
                            </a>
                        </div>
                        
                        <div class="product-info">
                            <h3 class="product-title">
                                <a href="products/details.php?id=<?php echo $related['id']; ?>">
                                    <?php echo htmlspecialchars($related['name']); ?>
                                </a>
                            </h3>
                            
                            <div class="product-price">
                                <?php if ($related['discount'] > 0): 
                                    $discounted_price = $related['price'] * (1 - $related['discount'] / 100);
                                ?>
                                <span class="current-price">$<?php echo number_format($discounted_price, 2); ?></span>
                                <span class="original-price">$<?php echo number_format($related['price'], 2); ?></span>
                                <?php else: ?>
                                <span class="current-price">$<?php echo number_format($related['price'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-actions">
                                <button class="btn btn-add-cart" 
                                        data-product-id="<?php echo $related['id']; ?>"
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
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tab functionality
    initTabs();
    
    // Initialize quantity controls
    initQuantityControls();
    
    // Initialize add to cart functionality
    initAddToCart();
});

function initTabs() {
    const tabNavs = document.querySelectorAll('.tabs-nav li');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabNavs.forEach(tab => {
        tab.addEventListener('click', function() {
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
    window.decreaseQuantity = function() {
        const input = document.getElementById('productQuantity');
        let value = parseInt(input.value);
        if (value > 1) {
            input.value = value - 1;
        }
    };
    
    window.increaseQuantity = function() {
        const input = document.getElementById('productQuantity');
        let value = parseInt(input.value);
        const max = parseInt(input.getAttribute('max'));
        if (value < max) {
            input.value = value + 1;
        }
    };
}

function initAddToCart() {
    window.addToCart = function() {
        const productId = <?php echo $product_id; ?>;
        const productName = "<?php echo addslashes($product['name']); ?>";
        const quantity = document.getElementById('productQuantity').value;
        
        // AJAX call to add to cart
        fetch('process/cart_process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'action=add&product_id=' + productId + '&quantity=' + quantity
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update cart count
                const cartCountElements = document.querySelectorAll('.cart-count');
                cartCountElements.forEach(el => {
                    el.textContent = data.cart_count;
                    el.style.display = 'inline-flex';
                });
                
                // Show success message
                showNotification(productName + ' added to cart!', 'success');
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Failed to add to cart', 'error');
        });
    };
    
    window.buyNow = function() {
        addToCart();
        // Redirect to checkout after a short delay
        setTimeout(() => {
            window.location.href = 'user/checkout.php';
        }, 1000);
    };
    
    // Related products add to cart
    document.querySelectorAll('.related-products .btn-add-cart').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name');
            
            fetch('process/cart_process.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'action=add&product_id=' + productId + '&quantity=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cartCountElements = document.querySelectorAll('.cart-count');
                    cartCountElements.forEach(el => {
                        el.textContent = data.cart_count;
                        el.style.display = 'inline-flex';
                    });
                    
                    showNotification(productName + ' added to cart!', 'success');
                } else {
                    showNotification(data.message, 'error');
                }
            });
        });
    });
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

// Review form functions
function showReviewForm() {
    document.getElementById('reviewForm').style.display = 'block';
    window.scrollTo({
        top: document.getElementById('reviewForm').offsetTop - 100,
        behavior: 'smooth'
    });
}

function hideReviewForm() {
    document.getElementById('reviewForm').style.display = 'none';
}

function hoverStar(star) {
    const rating = parseInt(star.getAttribute('data-rating'));
    const stars = star.parentElement.querySelectorAll('i');
    
    stars.forEach((s, index) => {
        if (index < rating) {
            s.classList.remove('far');
            s.classList.add('fas');
        }
    });
}

function resetStars() {
    const selectedRating = parseInt(document.getElementById('reviewRating').value);
    const stars = document.querySelectorAll('.rating-stars-input i');
    
    stars.forEach((star, index) => {
        if (selectedRating === 0) {
            star.classList.remove('fas');
            star.classList.add('far');
        } else if (index < selectedRating) {
            star.classList.remove('far');
            star.classList.add('fas');
        } else {
            star.classList.remove('fas');
            star.classList.add('far');
        }
    });
}

function selectRating(star) {
    const rating = parseInt(star.getAttribute('data-rating'));
    document.getElementById('reviewRating').value = rating;
    resetStars();
}

// Submit review form
document.getElementById('submitReviewForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('process/review_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Review submitted successfully!', 'success');
            hideReviewForm();
            // Reload page to show new review
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Failed to submit review', 'error');
    });
});

// Wishlist function
function toggleWishlist() {
    if (!<?php echo is_logged_in() ? 'true' : 'false'; ?>) {
        showNotification('Please login to add to wishlist', 'error');
        return;
    }
    
    const productId = <?php echo $product_id; ?>;
    const wishlistBtn = document.querySelector('.btn-wishlist');
    const icon = wishlistBtn.querySelector('i');
    
    fetch('process/wishlist_process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'action=toggle&product_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.in_wishlist) {
                icon.classList.remove('far');
                icon.classList.add('fas');
                showNotification('Added to wishlist!', 'success');
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
                showNotification('Removed from wishlist', 'info');
            }
        } else {
            showNotification(data.message, 'error');
        }
    });
}
</script>

<?php
// Include footer
include_once __DIR__ . '/../includes/footer.php';
?>