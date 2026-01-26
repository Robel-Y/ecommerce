<?php
$page_title = 'Add Product';

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../functions/utilities.php';
require_once __DIR__ . '/../functions/security.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/validation.php';

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

$selected_category = sanitize_input($_POST['category'] ?? '', 'string');
if ($selected_category !== '' && !in_array($selected_category, $category_options, true)) {
    $category_options[] = $selected_category;
    sort($category_options, SORT_NATURAL | SORT_FLAG_CASE);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name'] ?? '', 'string');
    $description = sanitize_input($_POST['description'] ?? '', 'string');
    $price = (float) sanitize_input($_POST['price'] ?? 0, 'float');
    $category = sanitize_input($_POST['category'] ?? '', 'string');
    $image_value = sanitize_input($_POST['image'] ?? '', 'string');
    $stock = (int) sanitize_input($_POST['stock'] ?? 0, 'int');

    if ($name === '') {
        $errors[] = 'Product name is required.';
    }
    if ($price < 0) {
        $errors[] = 'Price must be a valid number.';
    }
    if ($has_stock && $stock < 0) {
        $errors[] = 'Stock must be 0 or greater.';
    }

    if (empty($errors)) {
        $columns = ['name', 'price'];
        $placeholders = ['?', '?'];
        $values = [$name, $price];

        if ($has_description) {
            $columns[] = 'description';
            $placeholders[] = '?';
            $values[] = $description;
        }
        if ($has_category) {
            $columns[] = 'category';
            $placeholders[] = '?';
            $values[] = $category;
        }
        if ($has_image) {
            $columns[] = 'image';
            $placeholders[] = '?';
            $values[] = $image_value;
        } elseif ($has_image_url) {
            $columns[] = 'image_url';
            $placeholders[] = '?';
            $values[] = $image_value;
        }
        if ($has_stock) {
            $columns[] = 'stock';
            $placeholders[] = '?';
            $values[] = $stock;
        }

        $sql = 'INSERT INTO products (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        if (!db_query($sql)) {
            $errors[] = 'Failed to prepare insert query.';
        } else {
            $i = 1;
            foreach ($values as $val) {
                db_bind($i++, $val);
            }
            if (db_execute()) {
                redirect_with_message(SITE_URL . 'admin/products.php', 'success', 'Product added successfully.');
            }
            $errors[] = 'Failed to add product.';
        }
    }
}

require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
?>

<div class="page-header-admin">
    <h1>Add Product</h1>
    <p>Create a new product</p>
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
        <div class="form-row">
            <div class="form-group">
                <label for="name">Name *</label>
                <input id="name" name="name" class="form-control" type="text" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="price">Price *</label>
                <input id="price" name="price" class="form-control" type="number" step="0.01" min="0" required value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="category">Category</label>
                <?php if ($has_category): ?>
                    <select id="category" name="category" class="form-control">
                        <option value="">Select category</option>
                        <?php foreach ($category_options as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo (($selected_category !== '' ? $selected_category : (string) ($_POST['category'] ?? '')) === $cat) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input id="category" name="category" class="form-control" type="text" value="<?php echo htmlspecialchars($_POST['category'] ?? ''); ?>">
                <?php endif; ?>
            </div>
            <?php if ($has_stock): ?>
                <div class="form-group">
                    <label for="stock">Stock / Quantity</label>
                    <input id="stock" name="stock" class="form-control" type="number" min="0" step="1" required value="<?php echo htmlspecialchars($_POST['stock'] ?? '0'); ?>">
                </div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-group span-full">
                <label for="image">Image URL / Path</label>
                <input id="image" name="image" class="form-control" type="text" placeholder="assets/images/products/your.jpg or https://..." value="<?php echo htmlspecialchars($_POST['image'] ?? ''); ?>">
            </div>
        </div>

        <?php if ($has_description): ?>
            <div class="form-row">
                <div class="form-group span-full">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="4"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
            </div>
        <?php endif; ?>

        <div class="admin-form-actions">
            <a class="btn btn-outline btn-sm" href="products.php">Cancel</a>
            <button class="btn btn-primary btn-sm" type="submit"><i class="fas fa-plus"></i> Add Product</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
