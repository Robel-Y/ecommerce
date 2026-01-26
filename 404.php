<?php
/* ============================================
   404 - Not Found
============================================ */

http_response_code(404);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/functions/utilities.php';

$page_title = 'Page Not Found | ' . (defined('SITE_NAME') ? SITE_NAME : '');
include_once __DIR__ . '/includes/header.php';
?>

<main class="section-padding">
    <div class="container">
        <div class="card" style="max-width: 720px; margin: 0 auto;">
            <div class="card-body" style="padding: var(--space-2xl); text-align:center;">
                <div style="font-size: 56px; line-height: 1; font-weight: 800; color: var(--primary-color); margin-bottom: var(--space-md);">404</div>
                <h1 style="margin-bottom: var(--space-sm);">Page not found</h1>
                <p class="text-muted" style="margin-bottom: var(--space-xl);">
                    The page you requested doesn’t exist or was moved.
                </p>
                <div style="display:flex; gap: 10px; justify-content:center; flex-wrap:wrap;">
                    <a class="btn btn-primary" href="<?php echo SITE_URL; ?>index.php">Go to Home</a>
                    <a class="btn btn-outline" href="<?php echo SITE_URL; ?>products/all.php">Browse Products</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
