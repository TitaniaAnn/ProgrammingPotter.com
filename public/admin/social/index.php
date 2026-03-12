<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        Database::insert('social_posts', [
            'platform'      => trim($_POST['platform'] ?? ''),
            'post_url'      => trim($_POST['post_url'] ?? ''),
            'embed_code'    => trim($_POST['embed_code'] ?? ''),
            'caption'       => trim($_POST['caption'] ?? ''),
            'thumbnail_url' => trim($_POST['thumbnail_url'] ?? ''),
            'featured'      => isset($_POST['featured']) ? 1 : 0,
            'sort_order'    => (int)($_POST['sort_order'] ?? 0),
        ]);
        flash('success', 'Post added!');
    } elseif ($action === 'delete') {
        Database::delete('social_posts', 'id = ?', [(int)$_POST['id']]);
        flash('success', 'Post removed.');
    }
    redirect(SITE_URL . '/admin/social/index.php');
}

$posts = Database::fetchAll("SELECT * FROM social_posts ORDER BY sort_order ASC, created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Posts — Admin</title>
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<main class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h1>Social Posts</h1>
        </div>

        <div class="two-col-layout">
            <!-- Add form -->
            <div class="admin-card">
                <h2>Add Social Post</h2>
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
                        <label>Post URL *</label>
                        <input type="url" name="post_url" required placeholder="https://instagram.com/p/...">
                    </div>
                    <div class="form-group">
                        <label>Thumbnail Image URL</label>
                        <input type="url" name="thumbnail_url" placeholder="https://... (direct image URL)">
                        <small>Use a direct image link so it shows as a preview grid square.</small>
                    </div>
                    <div class="form-group">
                        <label>Caption (optional)</label>
                        <textarea name="caption" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Embed Code (optional — for iframes)</label>
                        <textarea name="embed_code" rows="3" placeholder="Paste Instagram/TikTok embed code here"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" value="0">
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="featured" value="1" checked>
                            <span>Show on homepage</span>
                        </label>
                    </div>
                    <button type="submit" class="admin-btn admin-btn--primary">Add Post</button>
                </form>
            </div>

            <!-- Existing posts -->
            <div>
                <h2>Current Posts (<?= count($posts) ?>)</h2>
                <?php if (empty($posts)): ?>
                <p class="muted">No posts added yet.</p>
                <?php else: ?>
                <div class="social-admin-grid">
                    <?php foreach ($posts as $post): ?>
                    <div class="social-admin-item">
                        <?php if ($post['thumbnail_url']): ?>
                        <img src="<?= e($post['thumbnail_url']) ?>" alt="" onerror="this.style.display='none'">
                        <?php endif; ?>
                        <div class="social-admin-item__info">
                            <span class="badge"><?= e($post['platform']) ?></span>
                            <?php if ($post['caption']): ?>
                            <p><?= e(substr($post['caption'], 0, 60)) ?>…</p>
                            <?php else: ?>
                            <p><a href="<?= e($post['post_url']) ?>" target="_blank">View post ↗</a></p>
                            <?php endif; ?>
                            <span><?= $post['featured'] ? '⭐ Featured' : 'Hidden' ?></span>
                        </div>
                        <form method="POST" style="margin-top:.5rem;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $post['id'] ?>">
                            <button type="submit" class="admin-btn admin-btn--sm admin-btn--danger"
                                    onclick="return confirm('Remove this post?')">Remove</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
</body>
</html>
