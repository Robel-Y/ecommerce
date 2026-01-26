<?php
$page_title = 'Edit Product';

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../functions/utilities.php';
require_once __DIR__ . '/../functions/security.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/validation.php';
require_once __DIR__ . '/../functions/request.php';

if (session_status() === PHP_SESSION_NONE) {
    start_secure_session();
}

if (!is_admin()) {
    $home = defined('SITE_URL') ? (rtrim(SITE_URL, '/') . '/index.php') : '../index.php';
    redirect_with_message($home, 'error', 'Access denied. Admin privileges required.');
}

function products_has_column(string $column): bool
{
    // Use INFORMATION_SCHEMA because SHOW COLUMNS + prepared statements can fail
    $sql = 'SELECT 1 AS ok
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
            LIMIT 1';

    if (!db_query($sql)) {
        return false;
    }
    db_bind(1, DB_NAME);
    db_bind(2, 'products');
    db_bind(3, $column);
    return (bool) db_single();
}

$has_image = products_has_column('image');
$has_image_url = products_has_column('image_url');
$has_category = products_has_column('category');
$has_description = products_has_column('description');
$has_stock = products_has_column('stock');

$id = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    redirect_with_message(SITE_URL . 'admin/products.php', 'error', 'Invalid product ID.');
}

// Load product
if (!db_query('SELECT * FROM products WHERE id = ? LIMIT 1')) {
    redirect_with_message(SITE_URL . 'admin/products.php', 'error', 'Failed to load product.');
}
db_bind(1, $id, PDO::PARAM_INT);
$product = db_single();
if (!$product) {
    redirect_with_message(SITE_URL . 'admin/products.php', 'error', 'Product not found.');
}

$common_categories = [
    'Electronics',
    'Fashion',
    'Beauty',
    'Home & Kitchen',
    'Sports',
    'Books',
    'Toys',
    'Groceries',
    'Accessories',
];

$category_options = $common_categories;
if ($has_category && db_query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category <> '' ORDER BY category")) {
    $rows = db_result_set();
    foreach ($rows as $row) {
        $cat = trim((string) ($row['category'] ?? ''));
        if ($cat !== '' && !in_array($cat, $category_options, true)) {
            $category_options[] = $cat;
        }
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = request_post_string('name', 'string', '');
    $description = request_post_string('description', 'string', '');
    $price_raw = (string) ($_POST['price'] ?? '');
    $category = request_post_string('category', 'string', '');
    $image_value = request_post_string('image', 'string', '');
    $stock_raw = (string) ($_POST['stock'] ?? '0');

    $rules = [
        'name' => ['required', 'min_length:2', 'max_length:150'],
        'price' => ['required', 'numeric', 'min:0'],
        'category' => ['max_length:80'],
        'image' => ['max_length:255'],
        'description' => ['max_length:2000'],
    ];
    if ($has_stock) {
        $rules['stock'] = ['required', 'int', 'min:0'];
    }

    $validation = request_validate(
        [
            'name' => $name,
            'price' => $price_raw,
            'category' => $category,
            'image' => $image_value,
            'description' => $description,
            'stock' => $stock_raw,
        ],
        $rules,
        [
            'name' => 'string',
            'price' => 'float',
            'category' => 'string',
            'image' => 'string',
            'description' => 'string',
            'stock' => 'int',
        ]
    );

    if (!$validation['valid']) {
        foreach ($validation['errors'] as $fieldErrors) {
            foreach ($fieldErrors as $msg) {
                $errors[] = $msg;
            }
        }
    }

    if ($has_category && $category !== '' && !in_array($category, $category_options, true)) {
        $errors[] = 'Please select a valid category.';
    }

    $price = (float) sanitize_input($price_raw, 'float');
    $stock = (int) sanitize_input($stock_raw, 'int');

    if (empty($errors)) {
        $sets = ['name = ?', 'price = ?'];
        $values = [$name, $price];

        if ($has_description) {
            $sets[] = 'description = ?';
            $values[] = $description;
        }
        if ($has_category) {
            $sets[] = 'category = ?';
            $values[] = $category;
        }
        if ($has_image) {
            $sets[] = 'image = ?';
            $values[] = $image_value;
        } elseif ($has_image_url) {
            $sets[] = 'image_url = ?';
            $values[] = $image_value;
        }
        if ($has_stock) {
            $sets[] = 'stock = ?';
            $values[] = $stock;
        }

        $sql = 'UPDATE products SET ' . implode(', ', $sets) . ' WHERE id = ?';
        $values[] = $id;

        if (!db_query($sql)) {
            $errors[] = 'Failed to prepare update query.';
        } else {
            $i = 1;
            foreach ($values as $val) {
                db_bind($i++, $val);
            }
            if (db_execute()) {
                redirect_with_message(SITE_URL . 'admin/products.php', 'success', 'Product updated successfully.');
            }
            $errors[] = 'Failed to update product.';
        }
    }

    // Re-load for form
    if (db_query('SELECT * FROM products WHERE id = ? LIMIT 1')) {
        db_bind(1, $id, PDO::PARAM_INT);
        $product = db_single() ?: $product;
    }
}

$product_name = (string) ($product['name'] ?? '');
$product_price = (string) ($product['price'] ?? '');
$product_category = (string) (($product['category'] ?? '') ?: ($product['category_name'] ?? ''));
$product_stock = (string) ($product['stock'] ?? '0');
$product_image = (string) (($product['image'] ?? '') ?: ($product['image_url'] ?? ''));
$product_description = (string) ($product['description'] ?? '');

$selected_category = sanitize_input($_POST['category'] ?? $product_category, 'string');
if ($selected_category !== '' && !in_array($selected_category, $category_options, true)) {
    $category_options[] = $selected_category;
    sort($category_options, SORT_NATURAL | SORT_FLAG_CASE);
}

require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
?>

<div class="page-header-admin">
    <h1>Edit Product</h1>
    <p>Update product information</p>
</div>

<?php if (!empty($errors)): ?>
    <div class="admin-flash-message flash-error" style="position: static; transform:none; max-width:none; width:100%;">
        <div class="flash-content">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars(implode(' ', $errors)); ?></span>
        </div>
    </div>
<?php endif; ?>

<div class="admin-form-card">
    <form method="post" class="admin-form">
        <input type="hidden" name="id" value="<?php echo (int) $id; ?>">

        <div class="form-row">
            <div class="form-group">
                <label for="name">Name *</label>
                <input id="name" name="name" class="form-control" type="text" required value="<?php echo htmlspecialchars($_POST['name'] ?? $product_name); ?>">
            </div>
            <div class="form-group">
                <label for="price">Price *</label>
                <input id="price" name="price" class="form-control" type="number" step="0.01" min="0" required value="<?php echo htmlspecialchars($_POST['price'] ?? $product_price); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="category">Category</label>
                <?php if ($has_category): ?>
                    <select id="category" name="category" class="form-control">
                        <option value="">Select category</option>
                        <?php foreach ($category_options as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($selected_category === $cat) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input id="category" name="category" class="form-control" type="text" value="<?php echo htmlspecialchars($_POST['category'] ?? $product_category); ?>">
                <?php endif; ?>
            </div>
            <?php if ($has_stock): ?>
                <div class="form-group">
                    <label for="stock">Stock / Quantity</label>
                    <input id="stock" name="stock" class="form-control" type="number" min="0" step="1" required value="<?php echo htmlspecialchars($_POST['stock'] ?? $product_stock); ?>">
                </div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-group span-full">
                <label for="image">Image URL / Path</label>
                <input id="image" name="image" class="form-control" type="text" placeholder="assets/images/products/your.jpg or https://..." value="<?php echo htmlspecialchars($_POST['image'] ?? $product_image); ?>">
            </div>
        </div>

        <?php if ($has_description): ?>
            <div class="form-row">
                <div class="form-group span-full">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="4"><?php echo htmlspecialchars($_POST['description'] ?? $product_description); ?></textarea>
                </div>
            </div>
        <?php endif; ?>

        <div class="admin-form-actions">
            <a class="btn btn-outline btn-sm" href="products.php">Cancel</a>
            <button class="btn btn-primary btn-sm" type="submit"><i class="fas fa-save"></i> Save Changes</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
