<?php
/* ============================================
   PRODUCTS BY CATEGORY - Procedural
   Display products filtered by category
============================================ */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../config/constants.php';
    start_secure_session();
}

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/utilities.php';

// Get category ID from URL
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($category_id <= 0) {
    redirect_with_message('products/all.php', 'error', 'Invalid category');
}

// Get category details
$query = "SELECT id, name, description, image_url FROM categories WHERE id = ? AND status = 'active'";
$stmt = $db->prepare($query);
$stmt->bind_param('i', $category_id);
$stmt->execute();
$category_result = $stmt->get_result();
$category = $category_result->fetch_assoc();
$stmt->close();

if (!$category) {
    redirect_with_message('products/all.php', 'error', 'Category not found');
}

// Set page title
$page_title = $category['name'] . " | " . SITE_NAME;

// Get filter parameters
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
$where_clause = "WHERE p.status = 'active' AND p.category_id = ?";
$params = [$category_id];
$param_types = 'i';

if (!empty($search_query)) {
    $where_clause .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'ss';
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
$count_query = "SELECT COUNT(*) as total FROM products p $where_clause";
$stmt = $db->prepare($count_query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$count_result = $stmt->get_result();
$total_products = $count_result->fetch_assoc()['total'];
$stmt->close();

// Get products with pagination
$query = "SELECT p.* FROM products p $where_clause $order_by LIMIT ? OFFSET ?";
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

// Get sibling categories
$siblings_query = "SELECT id, name FROM categories WHERE parent_id = (SELECT parent_id FROM categories WHERE id = ?) AND status = 'active' AND id != ?";
$stmt = $db->prepare($siblings_query);
$stmt->bind_param('ii', $category_id, $category_id);
$stmt->execute();
$siblings_result = $stmt->get_result();
$sibling_categories = [];

while ($row = $siblings_result->fetch_assoc()) {
    $sibling_categories[] = $row;
}
$stmt->close();

// Get subcategories
$subcategories_query = "SELECT id, name FROM categories WHERE parent_id = ? AND status = 'active'";
$stmt = $db->prepare($subcategories_query);
$stmt->bind_param('i', $category_id);
$stmt->execute();
$subcategories_result = $stmt->get_result();
$subcategories = [];

while ($row = $subcategories_result->fetch_assoc()) {
    $subcategories[] = $row;
}
$stmt->close();

// Generate pagination
$pagination = generate_pagination($total_products, $page, $per_page, "products/category.php?id=$category_id");

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<!-- Category Page Content -->
<div class="category-page">
    <div class="container">
        <!-- Category Header -->
        <div class="category-header">
            <?php if (!empty($category['image_url'])): ?>
            <div class="category-banner">
                <img src="<?php echo htmlspecialchars($category['image_url']); ?>" 
                     alt="<?php echo htmlspecialchars($category['name']); ?>">
            </div>
            <?php endif; ?>
            
            <div class="category-info">
                <h1><?php echo htmlspecialchars($category['name']); ?></h1>
                <?php if (!empty($category['description'])): ?>
                <p class="category-description"><?php echo htmlspecialchars($category['description']); ?></p>
                <?php endif; ?>
                <div class="category-meta">
                    <span class="product-count"><?php echo $total_products; ?> Products</span>
                </div>
            </div>
        </div>

        <!-- Category Navigation -->
        <?php if (!empty($sibling_categories) || !empty($subcategories)): ?>
        <div class="category-navigation">
            <?php if (!empty($sibling_categories)): ?>
            <div class="sibling-categories">
                <h3>Related Categories</h3>
                <div class="categories-list">
                    <?php foreach ($sibling_categories as $sibling): ?>
                    <a href="products/category.php?id=<?php echo $sibling['id']; ?>" 
                       class="category-link">
                        <?php echo htmlspecialchars($sibling['name']); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($subcategories)): ?>
            <div class="subcategories">
                <h3>Subcategories</h3>
                <div class="categories-list">
                    <?php foreach ($subcategories as $subcategory): ?>
                    <a href="products/category.php?id=<?php echo $subcategory['id']; ?>" 
                       class="category-link">
                        <?php echo htmlspecialchars($subcategory['name']); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="product-layout">
            <!-- Sidebar Filters -->
            <div class="product-sidebar">
                <!-- Search Form -->
                <div class="sidebar-section">
                    <h3 class="sidebar-title">Search in Category</h3>
                    <form class="filter-form" method="GET" action="products/category.php">
                        <input type="hidden" name="id" value="<?php echo $category_id; ?>">
                        <div class="form-group">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search products..." 
                                   value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Search</button>
                    </form>
                </div>

                <!-- Price Filter -->
                <div class="sidebar-section">
                    <h3 class="sidebar-title">Price Range</h3>
                    <form class="filter-form" method="GET" action="products/category.php">
                        <input type="hidden" name="id" value="<?php echo $category_id; ?>">
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
                <?php if (!empty($search_query) || $min_price > 0 || $max_price < 1000): ?>
                <div class="sidebar-section">
                    <a href="products/category.php?id=<?php echo $category_id; ?>" class="btn btn-outline btn-sm">
                        Clear Filters
                    </a>
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
                            <option value="products/category.php?id=<?php echo $category_id; ?>&sort=newest" 
                                    <?php echo $sort_by == 'newest' ? 'selected' : ''; ?>>Newest</option>
                            <option value="products/category.php?id=<?php echo $category_id; ?>&sort=price_low" 
                                    <?php echo $sort_by == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="products/category.php?id=<?php echo $category_id; ?>&sort=price_high" 
                                    <?php echo $sort_by == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="products/category.php?id=<?php echo $category_id; ?>&sort=name" 
                                    <?php echo $sort_by == 'name' ? 'selected' : ''; ?>>Name</option>
                            <option value="products/category.php?id=<?php echo $category_id; ?>&sort=popular" 
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
                    <h3>No products found in this category</h3>
                    <p>Try adjusting your search or filter to find what you're looking for.</p>
                    <a href="products/all.php" class="btn btn-primary">Browse All Products</a>
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
                                
                                <div class="product-actions">
                                    <?php if ($product['stock'] > 0): ?>
                                    <button class="btn btn-add-cart" 
                                            data-product-id="<?php echo $product['id']; ?>"
                                            data-product-name="<?php echo htmlspecialchars($product['name']); ?>">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                    <?php else: ?>
                                    <button class="btn btn-outline" disabled>
                                        <i class="fas fa-bell"></i> Notify Me
                                    </button>
                                    <?php endif; ?>
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
                    <?php echo get_pagination_html($pagination, "products/category.php?id=$category_id"); ?>
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
});
</script>

<?php
// Include footer
include_once __DIR__ . '/../includes/footer.php';
?>