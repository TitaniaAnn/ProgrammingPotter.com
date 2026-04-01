<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/Auth.php';

Auth::requireLogin();

$events = Database::fetchAll(
    "SELECT e.*,
            COUNT(ep.pottery_id) as piece_count
     FROM events e
     LEFT JOIN event_pieces ep ON e.id = ep.event_id
     GROUP BY e.id
     ORDER BY e.start_date DESC, e.created_at DESC"
);

// Group events by status
$upcomingEvents = array_filter($events, function($event) {
    return $event['status'] === 'published' && strtotime($event['start_date']) >= strtotime('today');
});

$pastEvents = array_filter($events, function($event) {
    return $event['status'] === 'published' && strtotime($event['start_date']) < strtotime('today');
});

$draftEvents = array_filter($events, function($event) {
    return $event['status'] === 'draft';
});

$cancelledEvents = array_filter($events, function($event) {
    return $event['status'] === 'cancelled';
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events — Admin</title>
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include __DIR__ . '/../partials/topbar.php'; ?>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>Events</h1>
            <a href="add.php" class="btn btn--primary">
                <i class="fas fa-plus"></i> Add Event
            </a>
        </div>

        <?php if (empty($events)): ?>
        <div class="empty-state">
            <i class="fas fa-calendar-alt empty-state__icon"></i>
            <h3>No events yet</h3>
            <p>Create your first event to get started.</p>
            <a href="add.php" class="btn btn--primary">Add Your First Event</a>
        </div>
        <?php else: ?>

        <!-- Upcoming Events -->
        <?php if (!empty($upcomingEvents)): ?>
        <section class="admin-section">
            <h2>Upcoming Events</h2>
            <div class="admin-grid">
                <?php foreach ($upcomingEvents as $event): ?>
                <div class="admin-card">
                    <div class="admin-card__header">
                        <h3><?= e($event['title']) ?></h3>
                        <div class="admin-card__actions">
                            <a href="edit.php?id=<?= $event['id'] ?>" class="btn btn--sm btn--outline">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="delete.php?id=<?= $event['id'] ?>" class="btn btn--sm btn--danger" onclick="return confirm('Are you sure you want to delete this event?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                    <div class="admin-card__content">
                        <div class="event-meta">
                            <span class="badge badge--<?= $event['type'] === 'show' ? 'info' : ($event['type'] === 'sale' ? 'success' : 'warning') ?>">
                                <?= ucfirst($event['type']) ?>
                            </span>
                            <span class="event-date">
                                <i class="fas fa-calendar"></i>
                                <?= date('M j, Y', strtotime($event['start_date'])) ?>
                                <?php if ($event['end_date'] && $event['end_date'] !== $event['start_date']): ?>
                                    - <?= date('M j, Y', strtotime($event['end_date'])) ?>
                                <?php endif; ?>
                            </span>
                            <?php if ($event['location']): ?>
                            <span class="event-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?= e($event['location']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php if ($event['piece_count'] > 0): ?>
                        <p class="event-pieces">
                            <i class="fas fa-image"></i>
                            <?= $event['piece_count'] ?> piece<?= $event['piece_count'] > 1 ? 's' : '' ?> featured
                        </p>
                        <?php endif; ?>
                        <div class="event-description">
                            <?= nl2br(e(substr($event['description'], 0, 150))) ?>
                            <?php if (strlen($event['description']) > 150): ?>...<?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Draft Events -->
        <?php if (!empty($draftEvents)): ?>
        <section class="admin-section">
            <h2>Draft Events</h2>
            <div class="admin-grid">
                <?php foreach ($draftEvents as $event): ?>
                <div class="admin-card admin-card--draft">
                    <div class="admin-card__header">
                        <h3><?= e($event['title']) ?> <span class="badge badge--secondary">Draft</span></h3>
                        <div class="admin-card__actions">
                            <a href="edit.php?id=<?= $event['id'] ?>" class="btn btn--sm btn--outline">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="delete.php?id=<?= $event['id'] ?>" class="btn btn--sm btn--danger" onclick="return confirm('Are you sure you want to delete this event?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                    <div class="admin-card__content">
                        <div class="event-meta">
                            <span class="badge badge--<?= $event['type'] === 'show' ? 'info' : ($event['type'] === 'sale' ? 'success' : 'warning') ?>">
                                <?= ucfirst($event['type']) ?>
                            </span>
                            <span class="event-date">
                                <i class="fas fa-calendar"></i>
                                <?= date('M j, Y', strtotime($event['start_date'])) ?>
                            </span>
                        </div>
                        <div class="event-description">
                            <?= nl2br(e(substr($event['description'], 0, 150))) ?>
                            <?php if (strlen($event['description']) > 150): ?>...<?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Past Events -->
        <?php if (!empty($pastEvents)): ?>
        <section class="admin-section">
            <h2>Past Events</h2>
            <div class="admin-grid admin-grid--past">
                <?php foreach (array_slice($pastEvents, 0, 6) as $event): ?>
                <div class="admin-card admin-card--past">
                    <div class="admin-card__header">
                        <h3><?= e($event['title']) ?> <span class="badge badge--secondary">Past</span></h3>
                        <div class="admin-card__actions">
                            <a href="edit.php?id=<?= $event['id'] ?>" class="btn btn--sm btn--outline">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="delete.php?id=<?= $event['id'] ?>" class="btn btn--sm btn--danger" onclick="return confirm('Are you sure you want to delete this event?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                    <div class="admin-card__content">
                        <div class="event-meta">
                            <span class="badge badge--<?= $event['type'] === 'show' ? 'info' : ($event['type'] === 'sale' ? 'success' : 'warning') ?>">
                                <?= ucfirst($event['type']) ?>
                            </span>
                            <span class="event-date">
                                <i class="fas fa-calendar"></i>
                                <?= date('M j, Y', strtotime($event['start_date'])) ?>
                            </span>
                        </div>
                        <div class="event-description">
                            <?= nl2br(e(substr($event['description'], 0, 150))) ?>
                            <?php if (strlen($event['description']) > 150): ?>...<?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</main>

<script src="/admin/js/admin.js"></script>
</body>
</html>