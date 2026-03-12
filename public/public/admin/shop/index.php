<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

$products = Database::fetchAll(
    "SELECT p.*, c.name as category_name
     FROM products p
     LEFT JOIN shop_categories c ON p.category_id = c.id
     ORDER BY p.type, p.sort_order ASC, p.created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Products — Admin</title>
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<main class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h1>Shop Products <span class="badge"><?= count($products) ?></span></h1>
            <a href="/admin/shop/add-product.php" class="admin-btn admin-btn--primary">+ Add Product</a>
        </div>

        <?php if (empty($products)): ?>
        <div class="empty-admin">
            <p>No products yet.</p>
            <a href="/admin/shop/add-product.php" class="admin-btn admin-btn--primary">Add first product</a>
        </div>
        <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td>
                            <?php if ($p['image_path']): ?>
                            <img src="/uploads/<?= e($p['image_path']) ?>" alt="" class="admin-table__thumb">
                            <?php else: ?>
                            <span class="no-img">—</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= e($p['name']) ?></strong></td>
                        <td><span class="badge badge--<?= $p['type'] === 'merch' ? 'blue' : 'clay' ?>"><?= e($p['type']) ?></span></td>
                        <td><?= e($p['category_name'] ?? '—') ?></td>
                        <td><?= $p['price'] ? '$' . number_format($p['price'], 2) : '—' ?></td>
                        <td>
                            <span class="status-badge status-badge--<?= e($p['status']) ?>">
                                <?= e($p['status']) ?>
                            </span>
                        </td>
                        <td class="actions-cell">
                            <a href="/admin/shop/edit-product.php?id=<?= $p['id'] ?>" class="admin-btn admin-btn--sm">Edit</a>
                            <a href="/admin/shop/delete-product.php?id=<?= $p['id'] ?>"
                               class="admin-btn admin-btn--sm admin-btn--danger"
                               onclick="return confirm('Delete this product?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
