<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        Database::insert('social_links', [
            'platform'   => trim($_POST['platform'] ?? ''),
            'url'        => trim($_POST['url'] ?? ''),
            'handle'     => trim($_POST['handle'] ?? ''),
            'active'     => 1,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ]);
        flash('success', 'Social link added!');
    } elseif ($action === 'delete') {
        Database::delete('social_links', 'id = ?', [(int)$_POST['id']]);
        flash('success', 'Link removed.');
    } elseif ($action === 'toggle') {
        $link = Database::fetchOne("SELECT active FROM social_links WHERE id = ?", [(int)$_POST['id']]);
        if ($link) {
            Database::update('social_links', ['active' => $link['active'] ? 0 : 1], 'id = :id', ['id' => (int)$_POST['id']]);
        }
    }
    redirect(SITE_URL . '/admin/social/links.php');
}

$links = Database::fetchAll("SELECT * FROM social_links ORDER BY sort_order ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Links — Admin</title>
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<main class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h1>Social Links</h1>
        </div>

        <div class="two-col-layout">
            <div class="admin-card">
                <h2>Add Social Link</h2>
                <form method="POST" class="admin-form">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label>Platform</label>
                        <select name="platform" required>
                            <option value="instagram">Instagram</option>
                            <option value="tiktok">TikTok</option>
                            
                            <option value="youtube">YouTube</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Profile URL *</label>
                        <input type="url" name="url" required placeholder="https://instagram.com/yourhandle">
                    </div>
                    <div class="form-group">
                        <label>Handle (without @)</label>
                        <input type="text" name="handle" placeholder="yourhandle">
                    </div>
                    <div class="form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" value="<?= count($links) ?>">
                    </div>
                    <button type="submit" class="admin-btn admin-btn--primary">Add Link</button>
                </form>
            </div>

            <div>
                <h2>Current Links</h2>
                <?php if (empty($links)): ?>
                <p class="muted">No social links yet.</p>
                <?php else: ?>
                <table class="admin-table">
                    <thead><tr><th>Platform</th><th>Handle</th><th>Active</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($links as $link): ?>
                        <tr class="<?= !$link['active'] ? 'row--muted' : '' ?>">
                            <td><?= e($link['platform']) ?></td>
                            <td><a href="<?= e($link['url']) ?>" target="_blank">@<?= e($link['handle'] ?: '—') ?></a></td>
                            <td><?= $link['active'] ? '✅' : '❌' ?></td>
                            <td class="actions-cell">
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= $link['id'] ?>">
                                    <button class="admin-btn admin-btn--sm"><?= $link['active'] ? 'Hide' : 'Show' ?></button>
                                </form>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $link['id'] ?>">
                                    <button class="admin-btn admin-btn--sm admin-btn--danger"
                                            onclick="return confirm('Delete this link?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
</body>
</html>
