<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Get upcoming events (published, start_date >= today)
$upcomingEvents = Database::fetchAll(
    "SELECT e.*,
            COUNT(ep.pottery_id) as piece_count
     FROM events e
     LEFT JOIN event_pieces ep ON e.id = ep.event_id
     WHERE e.status = 'published'
     AND e.start_date >= CURDATE()
     GROUP BY e.id
     ORDER BY e.start_date ASC, e.start_time ASC"
);

// Get past events (published, start_date < today)
$pastEvents = Database::fetchAll(
    "SELECT e.*,
            COUNT(ep.pottery_id) as piece_count
     FROM events e
     LEFT JOIN event_pieces ep ON e.id = ep.event_id
     WHERE e.status = 'published'
     AND e.start_date < CURDATE()
     GROUP BY e.id
     ORDER BY e.start_date DESC
     LIMIT 10"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events — <?= e(setting('site_name', 'My Pottery')) ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/templates/nav.php'; ?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>Events</h1>
            <p>Shows, sales, and classes — stay connected with my latest happenings.</p>
        </div>

        <?php if (empty($upcomingEvents) && empty($pastEvents)): ?>
        <div class="empty-state">
            <i class="fas fa-calendar-alt empty-state__icon"></i>
            <h3>No events at this time</h3>
            <p>Check back soon for upcoming shows, sales, and classes!</p>
        </div>
        <?php else: ?>

        <!-- Upcoming Events -->
        <?php if (!empty($upcomingEvents)): ?>
        <section class="events-section">
            <h2>Upcoming Events</h2>
            <div class="events-grid">
                <?php foreach ($upcomingEvents as $event): ?>
                <div class="event-card">
                    <div class="event-card__header">
                        <div class="event-type-badge event-type-badge--<?= $event['type'] ?>">
                            <?= ucfirst($event['type']) ?>
                        </div>
                        <?php if ($event['featured_image']): ?>
                        <div class="event-card__image">
                            <img src="<?= e($event['featured_image']) ?>" alt="<?= e($event['title']) ?>">
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="event-card__content">
                        <h3 class="event-title"><?= e($event['title']) ?></h3>

                        <div class="event-meta">
                            <div class="event-date">
                                <i class="fas fa-calendar"></i>
                                <span>
                                    <?= date('l, F j, Y', strtotime($event['start_date'])) ?>
                                    <?php if ($event['start_time']): ?>
                                        at <?= date('g:i A', strtotime($event['start_time'])) ?>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <?php if ($event['end_date'] && $event['end_date'] !== $event['start_date']): ?>
                            <div class="event-date event-date--end">
                                <i class="fas fa-calendar-check"></i>
                                <span>Ends <?= date('l, F j, Y', strtotime($event['end_date'])) ?>
                                <?php if ($event['end_time']): ?>
                                    at <?= date('g:i A', strtotime($event['end_time'])) ?>
                                <?php endif; ?>
                                </span>
                            </div>
                            <?php endif; ?>

                            <?php if ($event['location']): ?>
                            <div class="event-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?= e($event['location']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($event['description']): ?>
                        <div class="event-description">
                            <?= nl2br(e($event['description'])) ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($event['website_url']): ?>
                        <div class="event-website">
                            <a href="<?= e($event['website_url']) ?>" target="_blank" class="btn btn--outline">
                                <i class="fas fa-external-link-alt"></i>
                                Visit Event Website
                            </a>
                        </div>
                        <?php endif; ?>

                        <?php if ($event['registration_required'] && $event['registration_url']): ?>
                        <div class="event-registration">
                            <a href="<?= e($event['registration_url']) ?>" target="_blank" class="btn btn--primary">
                                <i class="fas fa-external-link-alt"></i>
                                Register Now
                            </a>
                        </div>
                        <?php endif; ?>

                        <?php if ($event['piece_count'] > 0): ?>
                        <div class="event-pieces">
                            <i class="fas fa-image"></i>
                            Featuring <?= $event['piece_count'] ?> piece<?= $event['piece_count'] > 1 ? 's' : '' ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Past Events -->
        <?php if (!empty($pastEvents)): ?>
        <section class="events-section events-section--past">
            <h2>Recent Events</h2>
            <div class="events-grid events-grid--past">
                <?php foreach ($pastEvents as $event): ?>
                <div class="event-card event-card--past">
                    <div class="event-card__header">
                        <div class="event-type-badge event-type-badge--<?= $event['type'] ?> event-type-badge--past">
                            Past <?= ucfirst($event['type']) ?>
                        </div>
                    </div>
                    <div class="event-card__content">
                        <h3 class="event-title"><?= e($event['title']) ?></h3>

                        <div class="event-meta">
                            <div class="event-date">
                                <i class="fas fa-calendar"></i>
                                <span>
                                    <?= date('F j, Y', strtotime($event['start_date'])) ?>
                                    <?php if ($event['end_date'] && $event['end_date'] !== $event['start_date']): ?>
                                        - <?= date('F j, Y', strtotime($event['end_date'])) ?>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <?php if ($event['location']): ?>
                            <div class="event-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?= e($event['location']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($event['description']): ?>
                        <div class="event-description">
                            <?= nl2br(e(substr($event['description'], 0, 200))) ?>
                            <?php if (strlen($event['description']) > 200): ?>...<?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/templates/footer.php'; ?>
</body>
</html>