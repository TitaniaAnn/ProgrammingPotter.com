<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$events = Database::fetchAll(
    "SELECT *
     FROM events
     WHERE publish_date IS NOT NULL
       AND publish_date <= CURDATE()
     ORDER BY
       CASE WHEN end_date IS NOT NULL AND end_date < CURDATE() THEN 1 ELSE 0 END,
       CASE WHEN start_date IS NULL THEN 1 ELSE 0 END,
       start_date ASC,
       sort_order ASC,
       created_at DESC"
);

function eventTypeLabel(string $type): string {
    $labels = [
        'pottery_show' => 'Pottery Show',
        'pottery_sale' => 'Pottery Sale',
        'storefront_sale' => 'Storefront Sale',
        'class' => 'Class',
    ];

    return $labels[$type] ?? 'Event';
}

function eventTypeClass(string $type): string {
    $classes = [
        'pottery_show' => 'pottery-show',
        'pottery_sale' => 'pottery-sale',
        'storefront_sale' => 'storefront-sale',
        'class' => 'class',
    ];

    return $classes[$type] ?? 'event';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events — <?= e(setting('site_name')) ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/favicon-512.png">
    <link rel="apple-touch-icon" href="/favicon-512.png">
</head>
<body>
<?php include __DIR__ . '/../templates/nav.php'; ?>

<div class="page-header">
    <div class="container">
        <h1 class="page-header__title">Events</h1>
        <p class="page-header__sub">Shows, sales, storefronts, and classes</p>
    </div>
</div>

<section class="section">
    <div class="container">
        <?php if (empty($events)): ?>
        <p class="empty-state">More events coming soon</p>
        <?php else: ?>
        <div class="grid grid--3">
            <?php foreach ($events as $event): ?>
            <article class="event-card">
                <div class="event-card__type event-card__type--<?= e(eventTypeClass($event['event_type'])) ?>"><?= e(eventTypeLabel($event['event_type'])) ?></div>
                <h2 class="event-card__title"><?= e($event['name']) ?></h2>

                <?php if (!empty($event['location'])): ?>
                <p class="event-card__meta"><?= e($event['location']) ?></p>
                <?php endif; ?>

                <p class="event-card__date">
                    <?php if (!empty($event['start_date']) && !empty($event['end_date'])): ?>
                        <?= e(date('M j', strtotime($event['start_date']))) ?> - <?= e(date('M j, Y', strtotime($event['end_date']))) ?>
                    <?php elseif (!empty($event['start_date'])): ?>
                        <?= e(date('M j, Y', strtotime($event['start_date']))) ?>
                    <?php else: ?>
                        Date to be announced
                    <?php endif; ?>
                </p>

                <?php if (!empty($event['daily_open_times'])): ?>
                <p class="event-card__meta"><?= e($event['daily_open_times']) ?></p>
                <?php endif; ?>

                <?php if (!empty($event['description'])): ?>
                <p class="event-card__desc"><?= e($event['description']) ?></p>
                <?php endif; ?>

                <?php if (!empty($event['url'])): ?>
                <a href="<?= e($event['url']) ?>" target="_blank" rel="noopener" class="btn btn--small btn--outline--dark">Learn More</a>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../templates/footer.php'; ?>
<script src="/js/main.js"></script>
</body>
</html>