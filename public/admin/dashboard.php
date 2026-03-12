<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireLogin();

$stats = [
    'pottery'   => Database::fetchOne("SELECT COUNT(*) as n FROM pottery")['n'] ?? 0,
    'products'  => Database::fetchOne("SELECT COUNT(*) as n FROM products")['n'] ?? 0,
    'featured'  => Database::fetchOne("SELECT COUNT(*) as n FROM pottery WHERE featured = 1")['n'] ?? 0,
    'available' => Database::fetchOne("SELECT COUNT(*) as n FROM products WHERE status = 'available'")['n'] ?? 0,
    'orders_new'=> Database::fetchOne("SELECT COUNT(*) as n FROM orders WHERE status = 'paid'")['n'] ?? 0,
    'revenue'   => Database::fetchOne("SELECT COALESCE(SUM(product_price * quantity),0) as n FROM orders WHERE status IN ('paid','shipped')")['n'] ?? 0,
];
$recent = Database::fetchAll("SELECT * FROM pottery ORDER BY created_at DESC LIMIT 5");
$user = Auth::getUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Admin</title>
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="admin-main">
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="admin-content">
        <div class="admin-page-header">
            <h1>Dashboard</h1>
            <p>Welcome back, <?= e($user['name'] ?? 'Admin') ?>!</p>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card__num"><?= $stats['pottery'] ?></div>
                <div class="stat-card__label">Portfolio Pieces</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__num"><?= $stats['available'] ?></div>
                <div class="stat-card__label">Items for Sale</div>
            </div>
            <div class="stat-card <?= $stats['orders_new'] > 0 ? 'stat-card--alert' : '' ?>">
                <div class="stat-card__num"><?= $stats['orders_new'] ?></div>
                <div class="stat-card__label">New Orders <small>(needs shipping)</small></div>
            </div>
            <div class="stat-card">
                <div class="stat-card__num">$<?= number_format($stats['revenue'], 0) ?></div>
                <div class="stat-card__label">Total Revenue</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2>Quick Add</h2>
            <div class="quick-actions__grid">
                <a href="/admin/orders/index.php" class="quick-action-btn">
                    <span class="quick-action-btn__icon">📦</span>
                    <span>View Orders</span>
                </a>
                <a href="/admin/pottery/add.php" class="quick-action-btn">
                    <span class="quick-action-btn__icon">🏺</span>
                    <span>Add Pottery Piece</span>
                </a>
                <a href="/admin/shop/add-product.php" class="quick-action-btn">
                    <span class="quick-action-btn__icon">🛒</span>
                    <span>Add Shop Product</span>
                </a>
                <a href="/admin/social/index.php" class="quick-action-btn">
                    <span class="quick-action-btn__icon">📸</span>
                    <span>Manage Social Posts</span>
                </a>
                <a href="/admin/settings/index.php" class="quick-action-btn">
                    <span class="quick-action-btn__icon">⚙️</span>
                    <span>Site Settings</span>
                </a>
            </div>
        </div>

        <!-- Recent Pottery -->
        <?php if (!empty($recent)): ?>
        <div class="admin-section">
            <div class="admin-section__header">
                <h2>Recent Pieces</h2>
                <a href="/admin/pottery/index.php" class="admin-link">Manage all →</a>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Technique</th>
                            <th>Featured</th>
                            <th>Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $p): ?>
                        <tr>
                            <td>
                                <img src="/uploads/<?= e($p['image_thumb'] ?? $p['image_path']) ?>"
                                     alt="<?= e($p['title']) ?>" class="admin-table__thumb">
                            </td>
                            <td><?= e($p['title']) ?></td>
                            <td><?= e($p['technique'] ?? '—') ?></td>
                            <td><?= $p['featured'] ? '⭐' : '—' ?></td>
                            <td><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                            <td>
                                <a href="/admin/pottery/edit.php?id=<?= $p['id'] ?>" class="admin-btn admin-btn--sm">Edit</a>
                                <a href="/admin/pottery/delete.php?id=<?= $p['id'] ?>" class="admin-btn admin-btn--sm admin-btn--danger"
                                   onclick="return confirm('Delete this piece?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
