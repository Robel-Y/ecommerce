<?php
/* ============================================
   ADMIN PRODUCTS MANAGEMENT
   CRUD operations for products
============================================ */

$page_title = 'Products Management';

// Include admin header and sidebar
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/utilities.php';
require_once __DIR__ . '/../functions/validation.php';

// Pagination setup
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Search and filter
$search = isset($_GET['search']) ? sanitize_input($_GET['search'], 'string') : '';
$filter = isset($_GET['filter']) ? sanitize_input($_GET['filter'], 'string') : '';

// Build query (schema-tolerant)
$params = [];
$search_param = '';
$search_variants = [''];
if (!empty($search)) {
    $search_param = "%$search%";
    $params[':search'] = $search_param;
    $search_variants = [
        "(p.name LIKE :search OR p.sku LIKE :search OR p.description LIKE :search)",
        "(p.name LIKE :search OR p.description LIKE :search)",
        "(p.name LIKE :search)"
    ];
}

$status_clause = '';
if ($filter === 'active') {
    $status_clause = "p.status = 'active'";
} elseif ($filter === 'inactive') {
    $status_clause = "p.status = 'inactive'";
}

$stock_clause = '';
if ($filter === 'low_stock') {
    $stock_clause = "p.stock < 10 AND p.stock > 0";
} elseif ($filter === 'out_of_stock') {
    $stock_clause = "p.stock = 0";
}

$where_sql = '';
$count_query = '';
$total_products = 0;

// Try combinations until we find a schema-compatible query
$status_variants = $status_clause !== '' ? [$status_clause, ''] : [''];
foreach ($search_variants as $search_clause) {
    foreach ($status_variants as $status_try) {
        $where_parts = [];
        if ($search_clause !== '') {
            $where_parts[] = $search_clause;
        }
        if ($stock_clause !== '') {
            $where_parts[] = $stock_clause;
        }
        if ($status_try !== '') {
            $where_parts[] = $status_try;
        }
        $where_sql = !empty($where_parts) ? ('WHERE ' . implode(' AND ', $where_parts)) : '';
        $count_query = "SELECT COUNT(*) as total FROM products p $where_sql";
        if (db_query($count_query)) {
            foreach ($params as $key => $value) {
                db_bind($key, $value);
            }
            $total_products = (int) (db_single()['total'] ?? 0);
            break 2;
        }
    }
}

// Get products
$products = [];
$query_with_categories = "SELECT p.*, c.name as category_name 
                          FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          $where_sql 
                          ORDER BY p.created_at DESC 
                          LIMIT :limit OFFSET :offset";

$query_no_categories = "SELECT p.*, p.category as category_name 
                        FROM products p 
                        $where_sql 
                        ORDER BY p.created_at DESC 
                        LIMIT :limit OFFSET :offset";

if (db_query($query_with_categories) || db_query($query_no_categories)) {
    foreach ($params as $key => $value) {
        db_bind($key, $value);
    }
    db_bind(':limit', $per_page, PDO::PARAM_INT);
    db_bind(':offset', $offset, PDO::PARAM_INT);
    $products = db_result_set();
}

// Generate pagination
$pagination = generate_pagination($total_products, $page, $per_page);
?>

<!-- Products Management Content -->
<div class="page-header-admin">
    <h1>Products Management</h1>
    <p>Manage your product catalog</p>
</div>

<!-- Quick Stats -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
    <div class="stat-card">
        <div class="stat-title">Total Products</div>
        <div class="stat-value"><?php echo number_format($total_products); ?></div>
    </div>
    <div class="stat-card warning">
        <div class="stat-title">Low Stock</div>
        <div class="stat-value">
            <?php
            db_query("SELECT COUNT(*) as total FROM products WHERE stock < 10 AND stock > 0");
            echo number_format(db_single()['total'] ?? 0);
            ?>
        </div>
    </div>
    <div class="stat-card danger">
        <div class="stat-title">Out of Stock</div>
        <div class="stat-value">
            <?php
            db_query("SELECT COUNT(*) as total FROM products WHERE stock = 0");
            echo number_format(db_single()['total'] ?? 0);
            ?>
        </div>
    </div>
</div>

<!-- Products Table -->
<div class="data-table-container">
    <div class="table-header">
        <h2 class="table-title">All Products</h2>
        <div class="table-actions">
            <form class="search-box" method="GET" action="products.php">
                <i class="fas fa-search"></i>
                <button type="submit" class="search-submit" aria-label="Search"></button>
                <?php if (!empty($filter)): ?>
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                <?php endif; ?>
                <input type="text" id="searchInput" name="search" placeholder="Search products..."
                    value="<?php echo htmlspecialchars($search); ?>">
            </form>

            <select class="form-control" style="width: auto;"
                onchange="(function(sel){
                    var params = new URLSearchParams(window.location.search);
                    if (sel.value) params.set('filter', sel.value); else params.delete('filter');
                    params.delete('page');
                    window.location.href = 'products.php' + (params.toString() ? ('?' + params.toString()) : '');
                })(this)">
                <option value="">All Products</option>
                <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>Active Only</option>
                <option value="inactive" <?php echo $filter === 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
                <option value="low_stock" <?php echo $filter === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                <option value="out_of_stock" <?php echo $filter === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock
                </option>
            </select>

            <a href="product_add.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add Product
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No products found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <?php
                            $product_id = (int) ($product['id'] ?? 0);
                            $product_name = (string) ($product['name'] ?? '');
                            $product_image = (string) ($product['image_url'] ?? $product['image'] ?? '');
                            $product_sku = (string) ($product['sku'] ?? '');
                            $product_category = (string) ($product['category_name'] ?? $product['category'] ?? 'N/A');
                            $product_price = (float) ($product['price'] ?? 0);
                            $product_stock = (int) ($product['stock'] ?? 0);
                            $product_discount = (float) ($product['discount'] ?? 0);
                            $product_status = $product['status'] ?? null;
                            $status_text = $product_status ? ucfirst((string) $product_status) : 'N/A';
                            $status_badge = $product_status ? (($product_status === 'active') ? 'success' : 'danger') : 'info';
                        ?>
                        <tr>
                            <td><?php echo $product_id; ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <img src="<?php echo htmlspecialchars($product_image); ?>"
                                        alt="<?php echo htmlspecialchars($product_name); ?>"
                                        style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;"
                                        onerror="this.src='<?php echo SITE_URL; ?>assets/images/products/default.jpg'">
                                    <strong><?php echo htmlspecialchars($product_name); ?></strong>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($product_sku); ?></td>
                            <td><?php echo htmlspecialchars($product_category); ?></td>
                            <td>
                                <strong>$<?php echo number_format($product_price, 2); ?></strong>
                                <?php if ($product_discount > 0): ?>
                                    <br><small class="text-danger"><?php echo $product_discount; ?>% OFF</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $stock_class = 'success';
                                if ($product_stock == 0)
                                    $stock_class = 'danger';
                                elseif ($product_stock < 10)
                                    $stock_class = 'warning';
                                ?>
                                <span class="badge badge-<?php echo $stock_class; ?>">
                                    <?php echo $product_stock; ?> units
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $status_badge; ?>">
                                    <?php echo htmlspecialchars($status_text); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="product_edit.php?id=<?php echo $product_id; ?>" class="btn btn-sm btn-outline"
                                        title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?php echo SITE_URL; ?>products/details.php?id=<?php echo $product_id; ?>"
                                        class="btn btn-sm btn-outline" title="View" target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button
                                        onclick="if(confirmDelete('Delete this product?')) window.location.href='product_delete.php?id=<?php echo $product_id; ?>'"
                                        class="btn btn-sm btn-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
        <?php
            $base_params = [];
            if (!empty($search)) {
                $base_params['search'] = $search;
            }
            if (!empty($filter)) {
                $base_params['filter'] = $filter;
            }

            $build_url = function (int $targetPage) use ($base_params): string {
                $params = $base_params;
                $params['page'] = $targetPage;
                return 'products.php?' . http_build_query($params);
            };
        ?>
        <div class="table-footer" style="padding: var(--space-lg); border-top: 1px solid var(--light-200);">
            <div class="pagination-container" style="margin:0; padding:0; border-top:0;">
                <nav class="pagination">
                    <?php if ($pagination['has_previous']): ?>
                        <a class="prev" href="<?php echo htmlspecialchars($build_url((int) $pagination['previous_page'])); ?>">
                            <i class="fas fa-chevron-left"></i> Prev
                        </a>
                    <?php else: ?>
                        <span class="prev disabled"><i class="fas fa-chevron-left"></i> Prev</span>
                    <?php endif; ?>

                    <div class="page-numbers">
                        <?php foreach ($pagination['pages'] as $p): ?>
                            <?php if ((int) $p === (int) $pagination['current_page']): ?>
                                <span class="current"><?php echo (int) $p; ?></span>
                            <?php else: ?>
                                <a href="<?php echo htmlspecialchars($build_url((int) $p)); ?>"><?php echo (int) $p; ?></a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($pagination['has_next']): ?>
                        <a class="next" href="<?php echo htmlspecialchars($build_url((int) $pagination['next_page'])); ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="next disabled">Next <i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                </nav>
                <div class="pagination-info" style="margin-top:10px;">
                    Showing <strong><?php echo (int) ($pagination['first_item'] ?? 0); ?>-<?php echo (int) ($pagination['last_item'] ?? 0); ?></strong>
                    of <strong><?php echo (int) ($pagination['total_items'] ?? 0); ?></strong>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Include admin footer
require_once __DIR__ . '/includes/admin_footer.php';
?>