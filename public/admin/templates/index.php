<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

$flash = getFlash();
$templates = Database::fetchAll(
    "SELECT t.*, COUNT(f.id) AS file_count
     FROM pottery_templates t
     LEFT JOIN pottery_template_files f ON f.template_id = t.id
     GROUP BY t.id
     ORDER BY t.sort_order ASC, t.created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Templates — Admin</title>
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<main class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h1>Templates</h1>
            <a href="/admin/templates/add.php" class="admin-btn admin-btn--primary">+ Add Template</a>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert--<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
        <?php endif; ?>

        <?php if (empty($templates)): ?>
        <p style="color:var(--ash)">No templates yet. <a href="/admin/templates/add.php">Add one</a>.</p>
        <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Preview</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Files</th>
                        <th>Downloads</th>
                        <th>Sort</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($templates as $t): ?>
                    <tr>
                        <td>
                            <?php if (!empty($t['preview_thumb'])): ?>
                            <img src="/uploads/<?= e($t['preview_thumb']) ?>" alt="<?= e($t['title']) ?>" class="admin-table__thumb">
                            <?php else: ?>
                            <span style="font-size:.75rem;color:var(--ash)">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($t['title']) ?></td>
                        <td><?= e($t['category'] ?: '—') ?></td>
                        <td><?= (int)$t['file_count'] ?> file<?= $t['file_count'] != 1 ? 's' : '' ?></td>
                        <td><?= number_format($t['download_count']) ?></td>
                        <td><?= (int)$t['sort_order'] ?></td>
                        <td>
                            <a href="/admin/templates/edit.php?id=<?= $t['id'] ?>" class="admin-btn admin-btn--sm">Edit</a>
                            <a href="/admin/templates/delete.php?id=<?= $t['id'] ?>&csrf=<?= e(csrf_token()) ?>" class="admin-btn admin-btn--sm admin-btn--danger"
                               onclick="return confirm('Delete this template?')">Delete</a>
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
