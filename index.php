<?php
session_start();
require_once 'config/constants.php';
require_once 'config/database.php';
$page_title = "Home | " . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- Hero Banner -->
    <section class="hero-banner">
        <div class="hero-content">
            <h1>Discover Amazing Products</h1>
            <p>Shop the latest trends with exclusive deals and fast delivery</p>
            <a href="products/all.php" class="btn btn-primary">Shop Now <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="hero-image">
            <img src="https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1770&q=80" alt="Modern E-Commerce">
        </div>
    </section>

    <!-- Featured Products -->
    <section class="featured-products">
        <div class="section-header">
            <h2><i class="fas fa-star"></i> Featured Products</h2>
            <a href="products/all.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="products-grid">
            <!-- Product Card 1 -->
            <div class="product-card">
                <div class="product-image">
                    <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1770&q=80" alt="Wireless Headphones">
                    <span class="product-badge">Bestseller</span>
                </div>
                <div class="product-info">
                    <h3>Wireless Headphones</h3>
                    <p class="product-description">Premium noise-cancelling headphones with 30-hour battery</p>
                    <div class="product-price">
                        <span class="current-price">$129.99</span>
                        <span class="original-price">$199.99</span>
                    </div>
                    <div class="product-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                        <span class="rating-count">(245)</span>
                    </div>
                    <button class="btn btn-add-cart" onclick="addToCart(1)">
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </button>
                </div>
            </div>

            <!-- Product Card 2 -->
            <div class="product-card">
                <div class="product-image">
                    <img src="https://images.unsplash.com/photo-1546868871-7041f2a55e12?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1064&q=80" alt="Smart Watch">
                    <span class="product-badge new">New</span>
                </div>
                <div class="product-info">
                    <h3>Smart Watch Pro</h3>
                    <p class="product-description">Fitness tracking with heart rate monitor and GPS</p>
                    <div class="product-price">
                        <span class="current-price">$249.99</span>
                    </div>
                    <div class="product-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <span class="rating-count">(189)</span>
                    </div>
                    <button class="btn btn-add-cart" onclick="addToCart(2)">
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </button>
                </div>
            </div>

            <!-- Product Card 3 -->
            <div class="product-card">
                <div class="product-image">
                    <img src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1770&q=80" alt="Running Shoes">
                    <span class="product-badge sale">Sale</span>
                </div>
                <div class="product-info">
                    <h3>Running Shoes</h3>
                    <p class="product-description">Lightweight running shoes with premium cushioning</p>
                    <div class="product-price">
                        <span class="current-price">$89.99</span>
                        <span class="original-price">$129.99</span>
                    </div>
                    <div class="product-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="far fa-star"></i>
                        <span class="rating-count">(312)</span>
                    </div>
                    <button class="btn btn-add-cart" onclick="addToCart(3)">
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </button>
                </div>
            </div>

            <!-- Product Card 4 -->
            <div class="product-card">
                <div class="product-image">
                    <img src="https://images.unsplash.com/photo-1600003263720-95b45a4035d5?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1770&q=80" alt="Gaming Laptop">
                </div>
                <div class="product-info">
                    <h3>Gaming Laptop</h3>
                    <p class="product-description">High-performance laptop with RTX graphics</p>
                    <div class="product-price">
                        <span class="current-price">$1,499.99</span>
                    </div>
                    <div class="product-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                        <span class="rating-count">(98)</span>
                    </div>
                    <button class="btn btn-add-cart" onclick="addToCart(4)">
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </button>
                </div>
            </div>
        </div>
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

    <!-- JavaScript -->
    <script>
        // Cart functionality
        function addToCart(productId) {
            fetch('process/cart_process.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=add&product_id=' + productId + '&quantity=1'
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Update cart count
                    const cartCount = document.querySelector('.cart-count');
                    if(cartCount) {
                        cartCount.textContent = data.cart_count;
                        cartCount.style.display = 'inline-flex';
                    }
                    
                    // Show success message
                    showNotification('Product added to cart!', 'success');
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred', 'error');
            });
        }

        // Notification function
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${message}
            `;
            
            document.body.appendChild(notification);
            
            // Show notification
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            
            // Hide and remove after 3 seconds
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    if(notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        // Newsletter form submission
        document.querySelector('.newsletter-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            
            // Simple validation
            if(!validateEmail(email)) {
                showNotification('Please enter a valid email address', 'error');
                return;
            }
            
            // Simulate subscription
            showNotification('Thank you for subscribing!', 'success');
            this.reset();
        });

        // Email validation
        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
    </script>
</body>
</html>