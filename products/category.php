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
$category_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($category_id <= 0) {
    redirect_with_message('products/all.php', 'error', 'Invalid category');
}

// Get category details
// Get category details
$query = "SELECT id, name, description, image_url FROM categories WHERE id = :id AND status = 'active'";
db_query($query);
db_bind(':id', $category_id);
$category = db_single();

if (!$category) {
    redirect_with_message('products/all.php', 'error', 'Category not found');
}

// Set page title
$page_title = $category['name'] . " | " . SITE_NAME;

// Get filter parameters
$search_query = isset($_GET['search']) ? sanitize_input($_GET['search'], 'string') : '';
$min_price = isset($_GET['min_price']) ? (float) $_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float) $_GET['max_price'] : 1000;
$sort_by = isset($_GET['sort']) ? sanitize_input($_GET['sort'], 'string') : 'newest';
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$per_page = 12;

// Validate page number
if ($page < 1) {
    $page = 1;
}

// Calculate offset
$offset = ($page - 1) * $per_page;

// Build WHERE clause
// Build WHERE clause
$where_clause = "WHERE p.status = 'active' AND p.category_id = ?";
$params = [$category_id];

if (!empty($search_query)) {
    $where_clause .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($min_price > 0) {
    $where_clause .= " AND p.price >= ?";
    $params[] = $min_price;
}

if ($max_price > 0 && $max_price < 10000) {
    $where_clause .= " AND p.price <= ?";
    $params[] = $max_price;
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
// Get total products count
$count_query = "SELECT COUNT(*) as total FROM products p $where_clause";
db_query($count_query);
$bind_index = 1;
foreach ($params as $value) {
    db_bind($bind_index++, $value);
}
$total_products = db_single()['total'];

// Get products with pagination
// Get products with pagination
$query = "SELECT p.* FROM products p $where_clause $order_by LIMIT :limit OFFSET :offset";

db_query($query);
$bind_index = 1;
foreach ($params as $value) {
    db_bind($bind_index++, $value);
}
db_bind(':limit', $per_page, PDO::PARAM_INT);
db_bind(':offset', $offset, PDO::PARAM_INT);
$products = db_result_set();

// Get sibling categories
// Get sibling categories
$siblings_query = "SELECT id, name FROM categories WHERE parent_id = (SELECT parent_id FROM categories WHERE id = :id1) AND status = 'active' AND id != :id2";
db_query($siblings_query);
db_bind(':id1', $category_id);
db_bind(':id2', $category_id);
$sibling_categories = db_result_set();

// Get subcategories
// Get subcategories
$subcategories_query = "SELECT id, name FROM categories WHERE parent_id = :id AND status = 'active'";
db_query($subcategories_query);
db_bind(':id', $category_id);
$subcategories = db_result_set();

// Generate pagination
$pagination = generate_pagination($total_products, $page, $per_page, SITE_URL . "products/category.php?id=$category_id");

// Include header
include_once __DIR__ . '/../includes/header.php';
?>
<!-- Page specific JS can still be here if not in header -->
<script src="<?php echo SITE_URL; ?>assets/js/category-script.js"></script>
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
                                <a href="<?php echo SITE_URL; ?>products/category.php?id=<?php echo $sibling['id']; ?>"
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
                                <a href="<?php echo SITE_URL; ?>products/category.php?id=<?php echo $subcategory['id']; ?>"
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
                    <form class="filter-form" method="GET" action="<?php echo SITE_URL; ?>products/category.php">
                        <input type="hidden" name="id" value="<?php echo $category_id; ?>">
                        <div class="form-group">
                            <input type="text" name="search" class="form-control" placeholder="Search products..."
                                value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Search</button>
                    </form>
                </div>

                <!-- Price Filter -->
                <div class="sidebar-section">
                    <h3 class="sidebar-title">Price Range</h3>
                    <form class="filter-form" method="GET" action="<?php echo SITE_URL; ?>products/category.php">
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
                        <a href="<?php echo SITE_URL; ?>products/category.php?id=<?php echo $category_id; ?>"
                            class="btn btn-outline btn-sm">
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
                            <option
                                value="<?php echo SITE_URL; ?>products/category.php?id=<?php echo $category_id; ?>&sort=newest"
                                <?php echo $sort_by == 'newest' ? 'selected' : ''; ?>>Newest</option>
                            <option
                                value="<?php echo SITE_URL; ?>products/category.php?id=<?php echo $category_id; ?>&sort=price_low"
                                <?php echo $sort_by == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option
                                value="<?php echo SITE_URL; ?>products/category.php?id=<?php echo $category_id; ?>&sort=price_high"
                                <?php echo $sort_by == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option
                                value="<?php echo SITE_URL; ?>products/category.php?id=<?php echo $category_id; ?>&sort=name"
                                <?php echo $sort_by == 'name' ? 'selected' : ''; ?>>Name</option>
                            <option
                                value="<?php echo SITE_URL; ?>products/category.php?id=<?php echo $category_id; ?>&sort=popular"
                                <?php echo $sort_by == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                        </select>
                    </div>

                    <div class="sort-right">
                        <span class="results-count">
                            Showing
                            <?php echo ($offset + 1); ?>-<?php echo min($offset + $per_page, $total_products); ?>
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
                        <a href="<?php echo SITE_URL; ?>products/all.php" class="btn btn-primary">Browse All Products</a>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card-wrapper">
                                <div class="product-card">
                                    <div class="product-image">
                                        <a href="<?php echo SITE_URL; ?>products/details.php?id=<?php echo $product['id']; ?>">
                                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>"
                                                alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                onerror="this.src='<?php echo SITE_URL; ?>assets/images/products/default.jpg'">
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
                                            <a
                                                href="<?php echo SITE_URL; ?>products/details.php?id=<?php echo $product['id']; ?>">
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
                                                <span
                                                    class="current-price">$<?php echo number_format($discounted_price, 2); ?></span>
                                                <span
                                                    class="original-price">$<?php echo number_format($product['price'], 2); ?></span>
                                            <?php else: ?>
                                                <span
                                                    class="current-price">$<?php echo number_format($product['price'], 2); ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="product-actions">
                                            <?php if ($product['stock'] > 0): ?>
                                                <button class="btn btn-add-cart" data-product-id="<?php echo $product['id']; ?>"
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
                        <?php echo get_pagination_html($pagination, SITE_URL . "products/category.php?id=$category_id"); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Price range slider functionality
    document.addEventListener('DOMContentLoaded', function () {
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
    });
</script>

<?php
// Include footer
include_once __DIR__ . '/../includes/footer.php';
?>