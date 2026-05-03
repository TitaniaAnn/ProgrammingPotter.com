<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

$events = Database::fetchAll(
    "SELECT * FROM events ORDER BY featured DESC, sort_order ASC, created_at DESC"
);

// Helper to get human-readable event type + status
function getEventTypeLabel($type) {
    $labels = [
        'pottery_show' => 'Pottery Show',
        'pottery_sale' => 'Pottery Sale',
        'storefront_sale' => 'Storefront Sale',
        'class' => 'Class',
    ];
    return $labels[$type] ?? $type;
}

function getEventStatus($event) {
    $today = date('Y-m-d');
    if (!$event['publish_date']) return 'Unpublished';
    if ($event['publish_date'] > $today) return 'Scheduled';
    if ($event['start_date'] && $event['start_date'] > $today) return 'Upcoming';
    if ($event['end_date'] && $event['end_date'] < $today) return 'Past';
    return 'Active';
}

function getAssignedPieceCount($eventId) {
    $result = Database::fetchOne(
        "SELECT COUNT(*) as cnt FROM event_pottery WHERE event_id = ?",
        [$eventId]
    );
    return $result['cnt'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Events — Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/admin/css/admin.css">
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<main class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h1>Events <span class="badge"><?= count($events) ?></span></h1>
            <a href="/admin/events/add.php" class="admin-btn admin-btn--primary">+ Add Event</a>
        </div>

        <?php if (empty($events)): ?>
        <div class="empty-admin">
            <p>No events yet.</p>
            <a href="/admin/events/add.php" class="admin-btn admin-btn--primary">Create your first event</a>
        </div>
        <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Dates</th>
                        <th>Status</th>
                        <th>Pieces</th>
                        <th>Featured</th>
                        <th>Order</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event): ?>
                    <tr>
                        <td><strong><?= e($event['name']) ?></strong></td>
                        <td><?= e(getEventTypeLabel($event['event_type'])) ?></td>
                        <td>
                            <?php if ($event['start_date']): ?>
                                <?= date('M d', strtotime($event['start_date'])) ?>
                                <?php if ($event['end_date']): ?>
                                    – <?= date('M d, Y', strtotime($event['end_date'])) ?>
                                <?php endif; ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= getEventStatus($event) === 'Active' ? 'badge--active' : (getEventStatus($event) === 'Unpublished' ? 'badge--secondary' : '') ?>">
                                <?= e(getEventStatus($event)) ?>
                            </span>
                        </td>
                        <td><?= getAssignedPieceCount($event['id']) ?></td>
                        <td><?= $event['featured'] ? '<span class="badge badge--gold">⭐ Featured</span>' : '—' ?></td>
                        <td><?= e($event['sort_order']) ?></td>
                        <td class="actions-cell">
                            <a href="/admin/events/edit.php?id=<?= $event['id'] ?>" class="admin-btn admin-btn--sm">Edit</a>
                            <a href="/admin/events/delete.php?id=<?= $event['id'] ?>&csrf=<?= e(csrf_token()) ?>"
                               class="admin-btn admin-btn--sm admin-btn--danger"
                               onclick="return confirm('Delete \'<?= e(addslashes($event['name'])) ?>\'? This cannot be undone.')">
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
