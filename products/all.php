<?php
/* ============================================
   PRODUCT LISTING PAGE - Procedural
   Display all products with filtering and pagination
============================================ */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../config/constants.php';
    start_secure_session();
}

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/utilities.php';

// Set page title
$page_title = "Products | " . SITE_NAME;

// Get filter parameters
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search_query = isset($_GET['search']) ? sanitize_input($_GET['search'], 'string') : '';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 1000;
$sort_by = isset($_GET['sort']) ? sanitize_input($_GET['sort'], 'string') : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;

// Validate page number
if ($page < 1) {
    $page = 1;
}

// Calculate offset
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where_clause = "WHERE p.status = 'active'";
$params = [];
$param_types = '';

if ($category_id > 0) {
    $where_clause .= " AND p.category_id = ?";
    $params[] = $category_id;
    $param_types .= 'i';
}

if (!empty($search_query)) {
    $where_clause .= " AND (p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

if ($min_price > 0) {
    $where_clause .= " AND p.price >= ?";
    $params[] = $min_price;
    $param_types .= 'd';
}

if ($max_price > 0 && $max_price < 10000) {
    $where_clause .= " AND p.price <= ?";
    $params[] = $max_price;
    $param_types .= 'd';
}

// Build ORDER BY clause
$order_by = "ORDER BY ";
switch ($sort_by) {
    case 'price_low':
        $order_by .= "p.price ASC";
        break;
    case 'price_high':
        $order_by .= "p.price DESC";
        break;
    case 'name':
        $order_by .= "p.name ASC";
        break;
    case 'popular':
        $order_by .= "p.sales_count DESC";
        break;
    case 'newest':
    default:
        $order_by .= "p.created_at DESC";
        break;
}

// Get total products count
$count_query = "SELECT COUNT(*) as total FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                $where_clause";

$stmt = $db->prepare($count_query);

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$count_result = $stmt->get_result();
$total_products = $count_result->fetch_assoc()['total'];
$stmt->close();

// Get products with pagination
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          $where_clause 
          $order_by 
          LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;
$param_types .= 'ii';

$stmt = $db->prepare($query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$products_result = $stmt->get_result();
$products = [];

while ($row = $products_result->fetch_assoc()) {
    $products[] = $row;
}

$stmt->close();

// Get categories for filter
$categories_query = "SELECT id, name, COUNT(p.id) as product_count 
                     FROM categories c 
                     LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
                     WHERE c.status = 'active'
                     GROUP BY c.id, c.name 
                     ORDER BY c.name";
$categories_result = $db->query($categories_query);
$categories = [];

while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row;
}

// Generate pagination
$pagination = generate_pagination($total_products, $page, $per_page, 'products/all.php');

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<!-- Products Page Content -->
<div class="product-listing">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>Our Products</h1>
            <p>Discover our amazing collection of products</p>
        </div>

        <div class="product-layout">
            <!-- Sidebar Filters -->
            <div class="product-sidebar">
                <!-- Search Form -->
                <div class="sidebar-section">
                    <h3 class="sidebar-title">Search</h3>
                    <form class="filter-form" method="GET" action="products/all.php">
                        <div class="form-group">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search products..." 
                                   value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Search</button>
                    </form>
                </div>

                <!-- Categories Filter -->
                <div class="sidebar-section">
                    <h3 class="sidebar-title">Categories</h3>
                    <div class="filter-options">
                        <div class="filter-option">
                            <a href="products/all.php" class="<?php echo $category_id == 0 ? 'active' : ''; ?>">
                                All Categories
                                <span class="filter-count">(<?php echo $total_products; ?>)</span>
                            </a>
                        </div>
                        <?php foreach ($categories as $category): ?>
                        <div class="filter-option">
                            <a href="products/all.php?category=<?php echo $category['id']; ?>" 
                               class="<?php echo $category_id == $category['id'] ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                                <span class="filter-count">(<?php echo $category['product_count']; ?>)</span>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Price Filter -->
                <div class="sidebar-section">
                    <h3 class="sidebar-title">Price Range</h3>
                    <form class="filter-form" method="GET" action="products/all.php">
                        <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
                        
                        <div class="price-range-slider">
                            <input type="range" class="form-range" min="0" max="1000" step="10" 
                                   value="<?php echo $min_price; ?>" id="minPrice" name="min_price">
                            <input type="range" class="form-range" min="0" max="1000" step="10" 
                                   value="<?php echo $max_price; ?>" id="maxPrice" name="max_price">
                        </div>
                        
                        <div class="price-range-values">
                            <span>$<span id="minPriceValue"><?php echo $min_price; ?></span></span>
                            <span> - </span>
                            <span>$<span id="maxPriceValue"><?php echo $max_price; ?></span></span>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-sm mt-3">Apply Filter</button>
                    </form>
                </div>

                <!-- Clear Filters -->
                <?php if ($category_id > 0 || !empty($search_query) || $min_price > 0 || $max_price < 1000): ?>
                <div class="sidebar-section">
                    <a href="products/all.php" class="btn btn-outline btn-sm">Clear All Filters</a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Main Content -->
            <div class="product-grid-main">
                <!-- Sorting and View Options -->
                <div class="product-sorting">
                    <div class="sort-left">
                        <span class="sort-label">Sort by:</span>
                        <select class="sort-select" onchange="window.location.href=this.value">
                            <option value="products/all.php?sort=newest<?php echo build_query_string(['sort' => '']); ?>" 
                                    <?php echo $sort_by == 'newest' ? 'selected' : ''; ?>>Newest</option>
                            <option value="products/all.php?sort=price_low<?php echo build_query_string(['sort' => '']); ?>" 
                                    <?php echo $sort_by == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="products/all.php?sort=price_high<?php echo build_query_string(['sort' => '']); ?>" 
                                    <?php echo $sort_by == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="products/all.php?sort=name<?php echo build_query_string(['sort' => '']); ?>" 
                                    <?php echo $sort_by == 'name' ? 'selected' : ''; ?>>Name</option>
                            <option value="products/all.php?sort=popular<?php echo build_query_string(['sort' => '']); ?>" 
                                    <?php echo $sort_by == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                        </select>
                    </div>
                    
                    <div class="sort-right">
                        <span class="results-count">
                            Showing <?php echo ($offset + 1); ?>-<?php echo min($offset + $per_page, $total_products); ?> 
                            of <?php echo $total_products; ?> products
                        </span>
                    </div>
                </div>

                <!-- Products Grid -->
                <?php if (empty($products)): ?>
                <div class="no-products">
                    <div class="no-products-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>No products found</h3>
                    <p>Try adjusting your search or filter to find what you're looking for.</p>
                    <a href="products/all.php" class="btn btn-primary">Clear Filters</a>
                </div>
                <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                    <div class="product-card-wrapper">
                        <div class="product-card">
                            <div class="product-image">
                                <a href="products/details.php?id=<?php echo $product['id']; ?>">
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         onerror="this.src='assets/images/products/default.jpg'">
                                </a>
                                <?php if ($product['stock'] == 0): ?>
                                <span class="product-badge out-of-stock">Out of Stock</span>
                                <?php elseif ($product['discount'] > 0): ?>
                                <span class="product-badge sale"><?php echo $product['discount']; ?>% OFF</span>
                                <?php elseif (strtotime($product['created_at']) > strtotime('-7 days')): ?>
                                <span class="product-badge new">New</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-info">
                                <div class="product-category">
                                    <?php echo htmlspecialchars($product['category_name']); ?>
                                </div>
                                
                                <h3 class="product-title">
                                    <a href="products/details.php?id=<?php echo $product['id']; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </h3>
                                
                                <div class="product-description">
                                    <?php echo truncate_text($product['description'], 100); ?>
                                </div>
                                
                                <div class="product-price">
                                    <?php if ($product['discount'] > 0): 
                                        $discounted_price = $product['price'] * (1 - $product['discount'] / 100);
                                    ?>
                                    <span class="current-price">$<?php echo number_format($discounted_price, 2); ?></span>
                                    <span class="original-price">$<?php echo number_format($product['price'], 2); ?></span>
                                    <?php else: ?>
                                    <span class="current-price">$<?php echo number_format($product['price'], 2); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-rating">
                                    <div class="stars">
                                        <?php
                                        $rating = $product['rating'] ?? 0;
                                        $full_stars = floor($rating);
                                        $half_star = $rating - $full_stars >= 0.5;
                                        
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
                                    <span class="rating-count">(<?php echo $product['review_count'] ?? 0; ?>)</span>
                                </div>
                                
                                <div class="product-actions">
                                    <?php if ($product['stock'] > 0): ?>
                                    <button class="btn btn-add-cart" 
                                            data-product-id="<?php echo $product['id']; ?>"
                                            data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                            data-product-price="<?php echo $product['price']; ?>"
                                            data-product-image="<?php echo htmlspecialchars($product['image_url']); ?>">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                    <?php else: ?>
                                    <button class="btn btn-outline" disabled>
                                        <i class="fas fa-bell"></i> Notify Me
                                    </button>
                                    <?php endif; ?>
                                    <a href="products/details.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-view">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                <div class="pagination-container">
                    <?php echo get_pagination_html($pagination, 'products/all.php'); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Price range slider functionality
document.addEventListener('DOMContentLoaded', function() {
    const minPriceSlider = document.getElementById('minPrice');
    const maxPriceSlider = document.getElementById('maxPrice');
    const minPriceValue = document.getElementById('minPriceValue');
    const maxPriceValue = document.getElementById('maxPriceValue');
    
    if (minPriceSlider && maxPriceSlider) {
        function updatePriceValues() {
            minPriceValue.textContent = minPriceSlider.value;
            maxPriceValue.textContent = maxPriceSlider.value;
            
            // Ensure min doesn't exceed max
            if (parseInt(minPriceSlider.value) > parseInt(maxPriceSlider.value)) {
                minPriceSlider.value = maxPriceSlider.value;
            }
        }
        
        minPriceSlider.addEventListener('input', updatePriceValues);
        maxPriceSlider.addEventListener('input', updatePriceValues);
        updatePriceValues();
    }
    
    // Add to cart functionality
    document.querySelectorAll('.btn-add-cart').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name');
            
            // AJAX call to add to cart
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
        });
    });
    
    // Helper function for query string
    window.build_query_string = function(exclude) {
        const params = new URLSearchParams(window.location.search);
        
        // Remove excluded parameters
        if (exclude) {
            Object.keys(exclude).forEach(key => {
                if (exclude[key] === '') {
                    params.delete(key);
                }
            });
        }
        
        const queryString = params.toString();
        return queryString ? '&' + queryString : '';
    };
});
</script>

<?php
// Include footer
include_once __DIR__ . '/../includes/footer.php';
?>