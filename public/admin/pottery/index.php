<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

$pieces = Database::fetchAll(
    "SELECT * FROM pottery ORDER BY featured DESC, sort_order ASC, created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Pieces — Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/admin/css/admin.css">
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<main class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h1>Portfolio Pieces <span class="badge"><?= count($pieces) ?></span></h1>
            <a href="/admin/pottery/add.php" class="admin-btn admin-btn--primary">+ Add Piece</a>
        </div>

        <?php if (empty($pieces)): ?>
        <div class="empty-admin">
            <p>No pottery pieces yet.</p>
            <a href="/admin/pottery/add.php" class="admin-btn admin-btn--primary">Add your first piece</a>
        </div>
        <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Technique</th>
                        <th>Year</th>
                        <th>Featured</th>
                        <th>Order</th>
                        <th>Added</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pieces as $p): ?>
                    <tr>
                        <td>
                            <img src="/uploads/<?= e($p['image_thumb'] ?? $p['image_path']) ?>"
                                 alt="<?= e($p['title']) ?>" class="admin-table__thumb">
                        </td>
                        <td><strong><?= e($p['title']) ?></strong></td>
                        <td><?= e($p['technique'] ?? '—') ?></td>
                        <td><?= e($p['year'] ?? '—') ?></td>
                        <td><?= $p['featured'] ? '<span class="badge badge--gold">⭐ Featured</span>' : '—' ?></td>
                        <td><?= e($p['sort_order']) ?></td>
                        <td><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                        <td class="actions-cell">
                            <a href="/admin/pottery/edit.php?id=<?= $p['id'] ?>" class="admin-btn admin-btn--sm">Edit</a>
                            <a href="/admin/pottery/delete.php?id=<?= $p['id'] ?>"
                               class="admin-btn admin-btn--sm admin-btn--danger"
                               onclick="return confirm('Delete \'<?= e(addslashes($p['title'])) ?>\'? This cannot be undone.')">
                                Delete
                            </a>
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
