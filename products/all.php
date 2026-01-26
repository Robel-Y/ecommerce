<?php
/* ============================================
   PRODUCT LISTING PAGE - Procedural
   Display all products with filtering and pagination
============================================ */
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../functions/security.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/utilities.php';

// Start secure session if not started
if (session_status() === PHP_SESSION_NONE) {
    if (function_exists('start_secure_session')) {
        start_secure_session();
    } else {
        session_start();
    }
}

// Set page title
$page_title = "Products | " . SITE_NAME;

// ---------------------------
// Get filter parameters
// ---------------------------
$category = isset($_GET['category']) ? trim((string) $_GET['category']) : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$min_price = isset($_GET['min_price']) ? (float) $_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float) $_GET['max_price'] : 10000;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$per_page = 12;

if ($page < 1)
    $page = 1;

// ---------------------------
// Build WHERE clause
// ---------------------------
// Using '1=1' allows simpler appending of AND clauses
$where_clauses = ["1=1"];
// Initialize procedural connection
$conn = db_connect();

// Fetch DB-driven categories for sidebar + canonical matching
$category_options = function_exists('get_distinct_categories') ? get_distinct_categories() : [];
if ($category !== '' && !empty($category_options)) {
    $cat_lc = mb_strtolower($category);
    foreach ($category_options as $opt) {
        if (mb_strtolower($opt) === $cat_lc) {
            $category = $opt;
            break;
        }
    }
}

$sql_conditions = [];
$params = [];

if (!empty($category)) {
    // Case-insensitive match to keep links working regardless of stored casing
    $where_clauses[] = "LOWER(category) = :category_lc";
    $params[':category_lc'] = mb_strtolower($category);
}

if (!empty($search_query)) {
    $where_clauses[] = "(LOWER(name) LIKE :search)";
    $params[':search'] = '%' . mb_strtolower($search_query) . '%';
}

if ($min_price > 0) {
    $where_clauses[] = "price >= :min_price";
    $params[':min_price'] = $min_price;
}

if ($max_price < 10000) {
    $where_clauses[] = "price <= :max_price";
    $params[':max_price'] = $max_price;
}

$where_sql = implode(" AND ", $where_clauses);

// ---------------------------
// Build ORDER BY
// ---------------------------
switch ($sort_by) {
    case 'price_low':
        $order_sql = "ORDER BY price ASC";
        break;
    case 'price_high':
        $order_sql = "ORDER BY price DESC";
        break;
    case 'name':
        $order_sql = "ORDER BY name ASC";
        break;
    case 'newest':
    default:
        $order_sql = "ORDER BY created_at DESC";
        break;
}

// If searching, prefer prefix matches first for more accurate live typing
if (!empty($search_query)) {
    $order_sql = "ORDER BY CASE WHEN LOWER(name) LIKE :search_prefix THEN 0 ELSE 1 END, name ASC";
    $params[':search_prefix'] = mb_strtolower($search_query) . '%';
}

// Count query does not use :search_prefix (ORDER BY param), so exclude it to avoid HY093.
$count_params = $params;
unset($count_params[':search_prefix']);

// ---------------------------
// Get total product count
// ---------------------------
$count_sql = "SELECT COUNT(*) AS total FROM products WHERE $where_sql";
$total_products = 0;
if (db_query($count_sql)) {
    foreach ($count_params as $key => $value) {
        db_bind($key, $value);
    }
    $total_result = db_single();
    $total_products = $total_result ? (int) ($total_result['total'] ?? 0) : 0;
}

// ---------------------------
// Get products with pagination
// ---------------------------
$offset = ($page - 1) * $per_page;
$products_sql = "SELECT * FROM products WHERE $where_sql $order_sql LIMIT :limit OFFSET :offset";

$products = [];
if (db_query($products_sql)) {
    foreach ($params as $key => $value) {
        db_bind($key, $value);
    }
    db_bind(':limit', $per_page, PDO::PARAM_INT);
    db_bind(':offset', $offset, PDO::PARAM_INT);
    $products = db_result_set();
}

// ---------------------------
// Generate pagination
// ---------------------------
$pagination = generate_pagination($total_products, $page, $per_page, SITE_URL . 'products/all.php');

// Build query strings for filter links (preserve current filters except category/page)
$current_params = $_GET;
unset($current_params['page']);

function build_products_all_url(array $params): string
{
    $qs = http_build_query(array_filter($params, static function ($value) {
        return $value !== '' && $value !== null;
    }));
    return $qs ? ('?' . $qs) : '';
}

// ---------------------------
// AJAX: return partial results for live search
// ---------------------------
$is_ajax = isset($_GET['ajax']) && $_GET['ajax'] === '1'
    && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest');

if ($is_ajax) {
    header('Content-Type: application/json');

    // Ensure ajax param does not leak into generated links
    $ajax_params = $_GET;
    unset($ajax_params['ajax']);
    unset($ajax_params['page']);

    // Build header title (same logic as page)
    if (!empty($category)) {
        $header_title = (string) $category;
    } elseif (!empty($search_query)) {
        $header_title = 'Search: "' . $search_query . '"';
    } else {
        $header_title = 'All Products';
    }

    ob_start();
    if (empty($products)) {
        ?>
        <div class="no-products text-center py-5">
            <div class="mb-md">
                <i class="fas fa-search fa-3x text-muted"></i>
            </div>
            <h3>No products found</h3>
            <p class="text-muted">Try adjusting your filters or search query.</p>
            <a href="<?php echo SITE_URL; ?>products/all.php" class="btn btn-secondary mt-md">Clear Filters</a>
        </div>
        <?php
    } else {
        ?>
        <div class="products-grid-3">
            <?php foreach ($products as $product): ?>
                <div class="product-card h-100">
                    <div class="product-image">
                        <?php
                        $image_url = !empty($product['image']) ? $product['image'] : 'assets/images/placeholder.jpg';
                        if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
                            $image_url = SITE_URL . $image_url;
                        }
                        ?>
                        <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php if (($product['stock'] ?? 0) < 5): ?>
                            <span class="product-badge" style="background: var(--danger-color);">Low Stock</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title">
                            <a href="<?php echo SITE_URL; ?>products/details.php?id=<?php echo (int) $product['id']; ?>">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </a>
                        </h3>
                        <div class="product-rating">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star-half-alt text-warning"></i>
                            <span class="rating-count">(4.5)</span>
                        </div>
                        <p class="product-price">
                            <span class="current-price">$<?php echo number_format((float) $product['price'], 2); ?></span>
                        </p>
                        <button class="btn btn-add-cart" data-product-id="<?php echo (int) $product['id']; ?>">
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="pagination-container mt-xl text-center">
                <nav class="pagination">
                    <?php if ($pagination['has_previous']): ?>
                        <a href="<?php echo build_products_all_url(array_merge($ajax_params, ['page' => $pagination['previous_page']])); ?>"
                            class="btn btn-secondary btn-sm">&laquo; Prev</a>
                    <?php endif; ?>

                    <span class="mx-2">Page <?php echo (int) $page; ?> of <?php echo (int) $pagination['total_pages']; ?></span>

                    <?php if ($pagination['has_next']): ?>
                        <a href="<?php echo build_products_all_url(array_merge($ajax_params, ['page' => $pagination['next_page']])); ?>"
                            class="btn btn-secondary btn-sm">Next &raquo;</a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
        <?php
    }
    $results_html = ob_get_clean();

    echo json_encode([
        'success' => true,
        'title' => $header_title,
        'count' => (int) $total_products,
        'resultsHtml' => $results_html,
    ]);
    exit;
}


// Include header
include_once __DIR__ . '/../includes/header.php';
?>
<!-- Page specific JS can still be here if not in header -->
<script src="<?php echo SITE_URL; ?>assets/js/products-script.js"></script>
<!-- Products Page Content -->
<div class="product-listing section-padding">
    <div class="container">
        <div class="listing-header mb-xl">
            <h1>
                <?php
                if (!empty($category)) {
                    echo htmlspecialchars($category);
                } elseif (!empty($search_query)) {
                    echo 'Search: "' . htmlspecialchars($search_query) . '"';
                } else {
                    echo 'All Products';
                }
                ?>
            </h1>
            <p class="text-muted"><?php echo $total_products; ?> products found</p>
        </div>

        <div class="row">
            <!-- Sidebar Filters -->
            <aside class="col-3 filters-sidebar">
                <div class="card mb-lg">
                    <div class="card-header">
                        <h3 class="card-title">Categories</h3>
                    </div>
                    <div class="card-body">
                        <ul class="filter-list">
                            <li>
                                <a href="<?php echo SITE_URL; ?>products/all.php"
                                    class="<?php echo empty($category) ? 'active' : ''; ?>">
                                    All Categories
                                </a>
                            </li>

                            <?php foreach ($category_options as $cat): ?>
                                <?php $is_active = (!empty($category) && mb_strtolower($category) === mb_strtolower($cat)); ?>
                                <li>
                                    <a href="<?php echo build_products_all_url(array_merge($current_params, ['category' => $cat])); ?>"
                                        class="<?php echo $is_active ? 'active' : ''; ?>">
                                        <?php echo htmlspecialchars($cat); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Price Range</h3>
                    </div>
                    <div class="card-body">
                        <form action="" method="GET">
                            <?php if (!empty($category))
                                echo '<input type="hidden" name="category" value="' . htmlspecialchars($category) . '">'; ?>
                            <?php if (!empty($search_query))
                                echo '<input type="hidden" name="search" value="' . htmlspecialchars($search_query) . '">'; ?>

                            <div class="form-group mb-sm">
                                <label for="min_price">Min Price</label>
                                <input type="number" name="min_price" id="min_price" class="form-control"
                                    value="<?php echo $min_price; ?>" min="0">
                            </div>
                            <div class="form-group mb-md">
                                <label for="max_price">Max Price</label>
                                <input type="number" name="max_price" id="max_price" class="form-control"
                                    value="<?php echo $max_price; ?>">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                        </form>
                    </div>
                </div>
            </aside>

            <!-- Product Grid -->
            <div class="col-9 product-content">
                <?php if (empty($products)): ?>
                    <div class="no-products text-center py-5">
                        <div class="mb-md">
                            <i class="fas fa-search fa-3x text-muted"></i>
                        </div>
                        <h3>No products found</h3>
                        <p class="text-muted">Try adjusting your filters or search query.</p>
                        <a href="<?php echo SITE_URL; ?>products/all.php" class="btn btn-secondary mt-md">Clear Filters</a>
                    </div>
                <?php else: ?>
                    <div class="products-grid-3">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card h-100">
                                <div class="product-image">
                                    <?php
                                    $image_url = !empty($product['image']) ? $product['image'] : 'assets/images/placeholder.jpg';
                                    if (filter_var($image_url, FILTER_VALIDATE_URL)) {
                                        // It's a URL
                                    } else {
                                        // It's a local path
                                        $image_url = SITE_URL . $image_url;
                                    }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($image_url); ?>"
                                        alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php if ($product['stock'] < 5): ?>
                                        <span class="product-badge" style="background: var(--danger-color);">Low Stock</span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h3 class="product-title">
                                        <a href="<?php echo SITE_URL; ?>products/details.php?id=<?php echo $product['id']; ?>">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </a>
                                    </h3>
                                    <div class="product-rating">
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="fas fa-star-half-alt text-warning"></i>
                                        <span class="rating-count">(4.5)</span>
                                    </div>
                                    <p class="product-price">
                                        <span class="current-price">$<?php echo number_format($product['price'], 2); ?></span>
                                    </p>
                                    <button class="btn btn-add-cart" data-product-id="<?php echo $product['id']; ?>">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <!-- Simplified pagination display -->
                    <?php if ($pagination['total_pages'] > 1): ?>
                        <div class="pagination-container mt-xl text-center">
                            <nav class="pagination">
                                <?php if ($pagination['has_previous']): ?>
                                    <a href="<?php echo build_products_all_url(array_merge($current_params, ['page' => $pagination['previous_page']])); ?>"
                                        class="btn btn-secondary btn-sm">&laquo; Prev</a>
                                <?php endif; ?>

                                <span class="mx-2">Page <?php echo $page; ?> of <?php echo $pagination['total_pages']; ?></span>

                                <?php if ($pagination['has_next']): ?>
                                    <a href="<?php echo build_products_all_url(array_merge($current_params, ['page' => $pagination['next_page']])); ?>"
                                        class="btn btn-secondary btn-sm">Next &raquo;</a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .products-grid-3 {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: var(--space-lg);
    }

    .filters-sidebar .filter-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .filters-sidebar .filter-list li {
        margin-bottom: var(--space-xs);
    }

    .filters-sidebar .filter-list a {
        display: block;
        padding: 8px 12px;
        border-radius: var(--radius-sm);
        color: var(--text-secondary);
        transition: all var(--transition-fast);
    }

    .filters-sidebar .filter-list a:hover,
    .filters-sidebar .filter-list a.active {
        background-color: var(--light-100);
        color: var(--primary-color);
        font-weight: 500;
    }

    .product-title a {
        color: var(--text-primary);
        text-decoration: none;
    }

    .product-title a:hover {
        color: var(--primary-color);
    }

    .row {
        display: flex;
        flex-wrap: wrap;
        margin-right: -15px;
        margin-left: -15px;
    }

    .col-3 {
        flex: 0 0 25%;
        max-width: 25%;
        padding: 0 15px;
    }

    .col-9 {
        flex: 0 0 75%;
        max-width: 75%;
        padding: 0 15px;
    }

    @media (max-width: 991px) {

        .col-3,
        .col-9 {
            flex: 0 0 100%;
            max-width: 100%;
        }

        .filters-sidebar {
            margin-bottom: var(--space-xl);
        }
    }
</style>

<?php
include_once __DIR__ . '/../includes/footer.php';
?>