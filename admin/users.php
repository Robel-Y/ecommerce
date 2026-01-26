<?php
/* ============================================
   ADMIN USERS MANAGEMENT
   View and manage system users
============================================ */

$page_title = 'Users Management';

// Include admin header and sidebar
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/utilities.php';

// Pagination setup
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Search and filter
$search = isset($_GET['search']) ? sanitize_input($_GET['search'], 'string') : '';
$role_filter = isset($_GET['role']) ? sanitize_input($_GET['role'], 'string') : '';

// Build query
// Build query
$where_clauses = [];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(name LIKE :search_name OR email LIKE :search_email)";
    $search_param = "%$search%";
    $params[':search_name'] = $search_param;
    $params[':search_email'] = $search_param;
}

if (!empty($role_filter)) {
    $where_clauses[] = "role = :role";
    $params[':role'] = $role_filter;
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM users $where_sql";
db_query($count_query);
foreach ($params as $key => $value) {
    db_bind($key, $value);
}
$total_users = db_single()['total'] ?? 0;

// Get users
$query = "SELECT * FROM users $where_sql ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
db_query($query);
foreach ($params as $key => $value) {
    db_bind($key, $value);
}
db_bind(':limit', $per_page, PDO::PARAM_INT);
db_bind(':offset', $offset, PDO::PARAM_INT);
$users = db_result_set();

// Generate pagination
$pagination = generate_pagination($total_users, $page, $per_page);

// Get statistics
$stats_query = "SELECT 
                    COUNT(CASE WHEN role = 'user' THEN 1 END) as user_count,
                    COUNT(CASE WHEN role = 'admin' THEN 1 END) as admin_count
                FROM users";
db_query($stats_query);
$stats = db_single();
?>

<!-- Users Management Content -->
<div class="page-header-admin">
    <h1>Users Management</h1>
    <p>Manage user accounts and permissions</p>
</div>

<!-- User Statistics -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
    <div class="stat-card">
        <div class="stat-title">Total Users</div>
        <div class="stat-value"><?php echo number_format($total_users); ?></div>
    </div>
    <div class="stat-card success">
        <div class="stat-title">Regular Users</div>
        <div class="stat-value"><?php echo number_format($stats['user_count'] ?? 0); ?></div>
    </div>
    <div class="stat-card warning">
        <div class="stat-title">Administrators</div>
        <div class="stat-value"><?php echo number_format($stats['admin_count'] ?? 0); ?></div>
    </div>
</div>

<!-- Users Table -->
<div class="data-table-container">
    <div class="table-header">
        <h2 class="table-title">All Users</h2>
        <div class="table-actions">
            <form class="search-box" method="GET" action="users.php">
                <i class="fas fa-search"></i>
                <button type="submit" class="search-submit" aria-label="Search"></button>
                <?php if (!empty($role_filter)): ?>
                    <input type="hidden" name="role" value="<?php echo htmlspecialchars($role_filter); ?>">
                <?php endif; ?>
                <input type="text" id="searchInput" name="search" placeholder="Search users..."
                    value="<?php echo htmlspecialchars($search); ?>">
            </form>

            <select class="form-control" style="width: auto;"
                onchange="window.location.href='users.php?role='+this.value">
                <option value="">All Roles</option>
                <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>Users</option>
                <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admins</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Orders</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No users found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div class="user-avatar">
                                        <i class="fas fa-user-circle"></i>
                                    </div>
                                    <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $user['role'] === 'admin' ? 'danger' : 'info'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo format_date($user['created_at']); ?></td>
                            <td>
                                <?php
                                // Get order count for user
                                $orders_query = "SELECT COUNT(*) as count FROM orders WHERE user_id = :user_id";
                                db_query($orders_query);
                                db_bind(':user_id', $user['id']);
                                $order_count = db_single()['count'] ?? 0;
                                echo $order_count . ' order(s)';
                                ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button onclick="viewUser(<?php echo $user['id']; ?>)" class="btn btn-sm btn-outline"
                                        title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button
                                            onclick="changeUserRole(<?php echo $user['id']; ?>, '<?php echo $user['role'] === 'admin' ? 'user' : 'admin'; ?>')"
                                            class="btn btn-sm btn-warning" title="Change Role">
                                            <i class="fas fa-user-shield"></i>
                                        </button>
                                        <button
                                            onclick="if(confirmDelete('Delete this user?')) deleteUser(<?php echo $user['id']; ?>)"
                                            class="btn btn-sm btn-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
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
        <div class="table-footer" style="padding: var(--space-lg); border-top: 1px solid var(--light-200);">
            <?php echo get_pagination_html($pagination, 'users.php', 'page'); ?>
        </div>
    <?php endif; ?>
</div>

<script>
    function viewUser(userId) {
        showNotification('User details view coming soon', 'info');
    }

    function changeUserRole(userId, newRole) {
        if (confirm('Change user role to ' + newRole + '?')) {
            fetch('user_change_role.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'user_id=' + userId + '&role=' + newRole
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('User role updated successfully', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification(data.message || 'Failed to update role', 'error');
                    }
                })
                .catch(error => {
                    showNotification('An error occurred', 'error');
                });
        }
    }

    function deleteUser(userId) {
        fetch('user_delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'user_id=' + userId
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('User deleted successfully', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message || 'Failed to delete user', 'error');
                }
            })
            .catch(error => {
                showNotification('An error occurred', 'error');
            });
    }
</script>

<?php
// Include admin footer
require_once __DIR__ . '/includes/admin_footer.php';
?>