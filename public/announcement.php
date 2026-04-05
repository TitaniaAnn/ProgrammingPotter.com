<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
}

$announcement = null;
if ($id > 0) {
    $announcement = Database::fetchOne(
        "SELECT *
         FROM announcements
         WHERE id = ?
           AND publish_date <= NOW()",
        [$id]
    );
}

if (!$announcement) {
    http_response_code(404);
}

$entityLinks = [];
if ($announcement) {
    $entityLinks = Database::fetchAll(
        "SELECT entity_type, entity_id
         FROM announcement_links
         WHERE announcement_id = ?
         ORDER BY sort_order ASC",
        [$announcement['id']]
    );
}

$linkedEvents = [];
$linkedPottery = [];

foreach ($entityLinks as $link) {
    if ($link['entity_type'] === 'event') {
        $event = Database::fetchOne(
            "SELECT id, name, url FROM events WHERE id = ?",
            [(int)$link['entity_id']]
        );
        if ($event) {
            $linkedEvents[] = $event;
        }
    }

    if ($link['entity_type'] === 'pottery') {
        $piece = Database::fetchOne(
            "SELECT id, title FROM pottery WHERE id = ?",
            [(int)$link['entity_id']]
        );
        if ($piece) {
            $linkedPottery[] = $piece;
        }
    }
}

$announcementText = '';
if ($announcement) {
    $announcementText = trim((string)($announcement['description'] ?? $announcement['content'] ?? ''));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $announcement ? e($announcement['title']) : 'Announcement Not Found' ?> — <?= e(setting('site_name', 'My Pottery')) ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .announcement-detail { padding: 6rem 0 4rem; }
        .announcement-card-full {
            max-width: 900px;
            margin: 0 auto;
            background: var(--warm-white);
            border: 1px solid var(--linen);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .announcement-card-full__image { width: 100%; max-height: 420px; object-fit: cover; }
        .announcement-card-full__body { padding: 2rem; }
        .announcement-card-full__meta { color: var(--stone); font-size: .9rem; margin-bottom: .6rem; }
        .announcement-card-full__title { margin-bottom: 1rem; }
        .announcement-card-full__text { color: var(--ink-lt); white-space: pre-line; }
        .announcement-linked { margin-top: 1.5rem; display: grid; gap: 1rem; }
        .announcement-linked h3 { font-size: 1rem; margin-bottom: .35rem; }
        .announcement-linked a { color: var(--sage); text-decoration: underline; text-underline-offset: 3px; }
        .announcement-not-found {
            max-width: 760px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid var(--linen);
            border-radius: var(--radius-lg);
            padding: 2rem;
            text-align: center;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../templates/nav.php'; ?>

<section class="announcement-detail">
    <div class="container">
        <?php if (!$announcement): ?>
            <div class="announcement-not-found">
                <h1>Announcement Not Found</h1>
                <p>This announcement is unavailable or not published yet.</p>
                <p style="margin-top: 1rem;"><a href="/" class="btn btn--primary">Back Home</a></p>
            </div>
        <?php else: ?>
            <article class="announcement-card-full">
                <?php if (!empty($announcement['image_path']) || !empty($announcement['image_thumb'])): ?>
                    <img class="announcement-card-full__image" src="<?= e(UPLOAD_URL . ($announcement['image_path'] ?? $announcement['image_thumb'])) ?>" alt="<?= e($announcement['title']) ?>">
                <?php endif; ?>
                <div class="announcement-card-full__body">
                    <div class="announcement-card-full__meta">Published <?= e(date('M j, Y g:i A', strtotime($announcement['publish_date']))) ?></div>
                    <h1 class="announcement-card-full__title"><?= e($announcement['title']) ?></h1>

                    <?php if ($announcementText !== ''): ?>
                        <p class="announcement-card-full__text"><?= e($announcementText) ?></p>
                    <?php endif; ?>

                    <div class="announcement-linked">
                        <?php if (!empty($linkedEvents)): ?>
                            <div>
                                <h3>Related Events</h3>
                                <?php foreach ($linkedEvents as $event): ?>
                                    <div>
                                        <?php if (!empty($event['url'])): ?>
                                            <a href="<?= e($event['url']) ?>" target="_blank" rel="noopener"><?= e($event['name']) ?></a>
                                        <?php else: ?>
                                            <a href="/events.php"><?= e($event['name']) ?></a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($linkedPottery)): ?>
                            <div>
                                <h3>Related Pieces</h3>
                                <?php foreach ($linkedPottery as $piece): ?>
                                    <div><a href="/portfolio.php#piece-<?= (int)$piece['id'] ?>"><?= e($piece['title']) ?></a></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <p style="margin-top:1.5rem;"><a class="btn btn--outline--dark" href="/">Back Home</a></p>
                </div>
            </article>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../templates/footer.php'; ?>
<script src="/js/main.js"></script>
</body>
</html>
