<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

$announcements = Database::fetchAll(
    "SELECT * FROM announcements ORDER BY publish_date DESC, created_at DESC"
);

// Helper to get announcement status
function getAnnouncementStatus($announcement) {
    $now = date('Y-m-d H:i:s');
    if ($announcement['publish_date'] > $now) {
        return 'Scheduled';
    }
    return 'Published';
}

// Helper to get linked entities count and summary
function getLinkedEntities($announcementId) {
    $links = Database::fetchAll(
        "SELECT entity_type, entity_id FROM announcement_links WHERE announcement_id = ? ORDER BY sort_order ASC",
        [$announcementId]
    );
    
    $summary = [];
    foreach ($links as $link) {
        if ($link['entity_type'] === 'event') {
            $event = Database::fetchOne("SELECT name FROM events WHERE id = ?", [$link['entity_id']]);
            if ($event) {
                $summary[] = "Event: " . $event['name'];
            }
        } elseif ($link['entity_type'] === 'pottery') {
            $pottery = Database::fetchOne("SELECT title FROM pottery WHERE id = ?", [$link['entity_id']]);
            if ($pottery) {
                $summary[] = "Piece: " . $pottery['title'];
            }
        }
    }
    return $summary;
}

// Helper to get social media post status
function getLastPostStatus($announcementId) {
    $post = Database::fetchOne(
        "SELECT platform, status, posted_at FROM announcement_social_posts 
         WHERE announcement_id = ? 
         ORDER BY posted_at DESC 
         LIMIT 1",
        [$announcementId]
    );
    return $post;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements — Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/admin/css/admin.css">
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<main class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h1>Announcements <span class="badge"><?= count($announcements) ?></span></h1>
            <a href="/admin/announcements/add.php" class="admin-btn admin-btn--primary">+ Add Announcement</a>
        </div>

        <?php if (!empty($message = getFlash('success'))): ?>
        <div class="alert alert--success"><?= e($message) ?></div>
        <?php endif; ?>

        <?php if (!empty($message = getFlash('error'))): ?>
        <div class="alert alert--error"><?= e($message) ?></div>
        <?php endif; ?>

        <?php if (empty($announcements)): ?>
        <div class="empty-admin">
            <p>No announcements yet.</p>
            <a href="/admin/announcements/add.php" class="admin-btn admin-btn--primary">Create your first announcement</a>
        </div>
        <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Publish Date</th>
                        <th>Status</th>
                        <th>Linked Entities</th>
                        <th>Image</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($announcements as $ann): ?>
                    <tr>
                        <td><strong><?= e($ann['title']) ?></strong></td>
                        <td><?= date('M d, Y h:i A', strtotime($ann['publish_date'])) ?></td>
                        <td>
                            <span class="badge <?= getAnnouncementStatus($ann) === 'Published' ? 'badge--active' : 'badge--secondary' ?>">
                                <?= e(getAnnouncementStatus($ann)) ?>
                            </span>
                        </td>
                        <td>
                            <?php $links = getLinkedEntities($ann['id']); ?>
                            <?php if (empty($links)): ?>
                                <span style="opacity: 0.5;">—</span>
                            <?php else: ?>
                                <?php foreach (array_slice($links, 0, 2) as $link): ?>
                                    <div style="font-size: 0.85rem; line-height: 1.4;"><?= e($link) ?></div>
                                <?php endforeach; ?>
                                <?php if (count($links) > 2): ?>
                                    <div style="font-size: 0.85rem; opacity: 0.7;">+<?= count($links) - 2 ?> more</div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $ann['image_path'] ? '📷' : '—' ?>
                        </td>
                        <td class="actions-cell">
                            <a href="/admin/announcements/edit.php?id=<?= $ann['id'] ?>" class="admin-btn admin-btn--sm">Edit</a>
                            <?php if (getAnnouncementStatus($ann) === 'Published'): ?>
                                <a href="/admin/announcements/post.php?id=<?= $ann['id'] ?>" class="admin-btn admin-btn--sm admin-btn--secondary">Post Social</a>
                            <?php endif; ?>
                            <a href="javascript:void(0)" onclick="confirmDelete(<?= $ann['id'] ?>)" class="admin-btn admin-btn--sm admin-btn--danger">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this announcement?')) {
        window.location.href = '/admin/announcements/delete.php?id=' + id;
    }
}
</script>
</body>
</html>
