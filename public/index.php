<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../includes/bootstrap.php';

// Fetch featured pottery with best matching event per piece
// "Best matching" = nearest upcoming event by start_date, or currently active event if no upcoming
$featured = Database::fetchAll(
    "SELECT 
        p.*,
        e.id as event_id,
        e.name as event_name,
        e.url as event_url,
        e.event_type as event_type
    FROM pottery p
    LEFT JOIN events e ON e.id = (
        SELECT ep.event_id
        FROM event_pottery ep
        LEFT JOIN events e2 ON e2.id = ep.event_id
        WHERE ep.pottery_id = p.id
            AND e2.publish_date IS NOT NULL 
            AND e2.publish_date <= CURDATE()
        ORDER BY 
            CASE WHEN e2.start_date > CURDATE() THEN 0 ELSE 1 END,
            CASE WHEN e2.start_date > CURDATE() THEN e2.start_date ELSE e2.end_date END DESC
        LIMIT 1
    )
    WHERE p.featured = 1
    ORDER BY p.sort_order ASC, p.created_at DESC 
    LIMIT 6"
);
$socialLinks = Database::fetchAll(
    "SELECT * FROM social_links WHERE active = 1 ORDER BY sort_order ASC"
);
$socialPosts = Database::fetchAll(
    "SELECT * FROM social_posts WHERE featured = 1 ORDER BY sort_order ASC LIMIT 6"
);

$events = Database::fetchAll(
        "SELECT *
         FROM events
         WHERE publish_date IS NOT NULL
             AND publish_date <= CURDATE()
             AND (end_date IS NULL OR end_date >= CURDATE())
         ORDER BY
             CASE WHEN start_date IS NULL THEN 1 ELSE 0 END,
             CASE WHEN start_date >= CURDATE() THEN 0 ELSE 1 END,
             start_date ASC,
             sort_order ASC,
             created_at DESC
         LIMIT 3"
);

// Fetch published announcements (ordered by publish_date DESC, limit 5)
$announcements = Database::fetchAll(
    "SELECT *
     FROM announcements
     WHERE publish_date <= NOW()
     ORDER BY publish_date DESC, created_at DESC
     LIMIT 5"
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
    <title><?= e(setting('site_name', 'My Pottery')) ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/favicon-512.png">
    <link rel="apple-touch-icon" href="/favicon-512.png">
</head>
<body>

<?php include __DIR__ . '/../templates/nav.php'; ?>

<!-- HERO -->
<?php $heroImage = setting('hero_image'); ?>
<section class="hero" <?= $heroImage ? 'style="background-image: url(\'/uploads/' . e($heroImage) . '\');"' : '' ?>>
    <div class="hero__bg-overlay"></div>

    <!-- Folk art corner flourishes -->
    <div class="hero__corner hero__corner--tl">
        <svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M10 10 Q10 60 60 60 Q10 60 10 110" stroke="#C9B48A" stroke-width="1.5" fill="none" opacity=".5"/>
            <circle cx="10" cy="10" r="4" fill="#B85C38" opacity=".6"/>
            <circle cx="60" cy="60" r="3" fill="#B85C38" opacity=".4"/>
            <path d="M10 35 Q35 35 35 10" stroke="#C9B48A" stroke-width="1" fill="none" opacity=".3"/>
            <path d="M10 85 Q60 85 60 35" stroke="#C9B48A" stroke-width="1" fill="none" opacity=".3"/>
            <circle cx="35" cy="10" r="2" fill="#C9B48A" opacity=".4"/>
            <circle cx="10" cy="85" r="2" fill="#C9B48A" opacity=".4"/>
        </svg>
    </div>
    <div class="hero__corner hero__corner--tr">
        <svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M10 10 Q10 60 60 60 Q10 60 10 110" stroke="#C9B48A" stroke-width="1.5" fill="none" opacity=".5"/>
            <circle cx="10" cy="10" r="4" fill="#B85C38" opacity=".6"/>
            <circle cx="60" cy="60" r="3" fill="#B85C38" opacity=".4"/>
            <path d="M10 35 Q35 35 35 10" stroke="#C9B48A" stroke-width="1" fill="none" opacity=".3"/>
            <path d="M10 85 Q60 85 60 35" stroke="#C9B48A" stroke-width="1" fill="none" opacity=".3"/>
        </svg>
    </div>
    <div class="hero__corner hero__corner--bl">
        <svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M10 10 Q10 60 60 60 Q10 60 10 110" stroke="#C9B48A" stroke-width="1.5" fill="none" opacity=".5"/>
            <circle cx="10" cy="10" r="4" fill="#B85C38" opacity=".6"/>
            <circle cx="60" cy="60" r="3" fill="#B85C38" opacity=".4"/>
        </svg>
    </div>
    <div class="hero__corner hero__corner--br">
        <svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M10 10 Q10 60 60 60 Q10 60 10 110" stroke="#C9B48A" stroke-width="1.5" fill="none" opacity=".5"/>
            <circle cx="10" cy="10" r="4" fill="#B85C38" opacity=".6"/>
            <circle cx="60" cy="60" r="3" fill="#B85C38" opacity=".4"/>
        </svg>
    </div>

    <div class="hero__card">
    <div class="hero__content">
        <span class="hero__eyebrow"><?= e(setting('tagline', 'made by hand, fired with love')) ?></span>
        <h1 class="hero__title"><?= e(setting('hero_title', 'Earth & Fire')) ?></h1>
        <div class="hero__title-rule">
            <span></span>
            <em>✦</em>
            <span></span>
        </div>
        <p class="hero__sub"><?= e(setting('hero_subtitle', 'Each piece shaped by hand, fired with intention')) ?></p>
        <div class="hero__actions">
            <a href="/portfolio.php" class="btn btn--primary">View Portfolio</a>
            <a href="/shop.php" class="btn btn--outline">Visit the Shop</a>
        </div>
    </div>
    </div><!-- /.hero__card -->
    <div class="hero__scroll-hint">
        <span>scroll on down</span>
        <div class="hero__scroll-line"></div>
    </div>
</section>

<!-- FEATURED WORK -->
<?php if (!empty($featured)): ?>
<section class="section featured">
    <div class="container">
        <div class="section__header">
            <h2 class="section__title">Featured Work</h2>
            <a href="/portfolio.php" class="section__link">View all pieces →</a>
        </div>
        <div class="grid grid--3">
            <?php foreach ($featured as $piece): ?>
            <a href="/portfolio.php#piece-<?= $piece['id'] ?>" class="pottery-card">
                <div class="pottery-card__img-wrap">
                    <img
                        src="/uploads/<?= e($piece['image_thumb'] ?? $piece['image_path']) ?>"
                        alt="<?= e($piece['title']) ?>"
                        loading="lazy"
                    >
                    <div class="pottery-card__overlay">
                        <span class="pottery-card__view">View Piece</span>
                    </div>
                    <?php if (!empty($piece['event_name'])): ?>
                    <div class="pottery-card__event-ribbon pottery-card__event-ribbon--<?= e($piece['event_type'] ?? 'event') ?>">
                        <?= e($piece['event_name']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="pottery-card__info">
                    <h3 class="pottery-card__title"><?= e($piece['title']) ?></h3>
                    <?php if ($piece['technique']): ?>
                    <span class="pottery-card__tag"><?= e($piece['technique']) ?></span>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ANNOUNCEMENTS -->
<?php if (!empty($announcements)): ?>
<section class="section announcements">
    <div class="container">
        <div class="section__header">
            <h2 class="section__title">Announcements</h2>
        </div>
        <div class="grid grid--3">
            <?php foreach ($announcements as $ann): ?>
            <article class="announcement-card">
                <?php if (!empty($ann['image_thumb']) || !empty($ann['image_path'])): ?>
                <div class="announcement-card__img-wrap">
                    <img
                        src="<?= e(UPLOAD_URL . ($ann['image_thumb'] ?? $ann['image_path'])) ?>"
                        alt="<?= e($ann['title']) ?>"
                        loading="lazy"
                    >
                </div>
                <?php endif; ?>
                
                <div class="announcement-card__content">
                    <h3 class="announcement-card__title"><?= e($ann['title']) ?></h3>
                    
                    <?php if (!empty($ann['description'])): ?>
                    <p class="announcement-card__desc">
                        <?php 
                            $desc = $ann['description'];
                            if (strlen($desc) > 150) {
                                $desc = substr($desc, 0, 150) . '...';
                            }
                            echo e($desc); 
                        ?>
                    </p>
                    <?php endif; ?>
                    
                    <?php 
                        // Get linked entities
                        $links = Database::fetchAll(
                            "SELECT entity_type, entity_id FROM announcement_links WHERE announcement_id = ? ORDER BY sort_order ASC LIMIT 3",
                            [$ann['id']]
                        );
                    ?>
                    <?php if (!empty($links)): ?>
                    <div class="announcement-card__links">
                        <?php foreach ($links as $link): ?>
                            <?php if ($link['entity_type'] === 'event'): ?>
                                <?php $event = Database::fetchOne("SELECT id, name, url FROM events WHERE id = ?", [$link['entity_id']]); ?>
                                <?php if ($event): ?>
                                <a href="<?= e($event['url'] ?? '/events.php#event-' . $event['id']) ?>" class="announcement-card__link-tag">📅 <?= e($event['name']) ?></a>
                                <?php endif; ?>
                            <?php elseif ($link['entity_type'] === 'pottery'): ?>
                                <?php $pottery = Database::fetchOne("SELECT id, title FROM pottery WHERE id = ?", [$link['entity_id']]); ?>
                                <?php if ($pottery): ?>
                                <a href="/portfolio.php#piece-<?= $pottery['id'] ?>" class="announcement-card__link-tag">🏺 <?= e($pottery['title']) ?></a>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- EVENTS PREVIEW -->
<section class="section events-preview">
    <div class="container">
        <div class="section__header">
            <h2 class="section__title">Events</h2>
            <a href="/events.php" class="section__link">View all events →</a>
        </div>

        <?php if (empty($events)): ?>
        <p class="empty-state events-preview__empty">More events coming soon</p>
        <?php else: ?>
        <div class="grid grid--3">
            <?php foreach ($events as $event): ?>
            <article class="event-card">
                <div class="event-card__type event-card__type--<?= e(eventTypeClass($event['event_type'])) ?>"><?= e(eventTypeLabel($event['event_type'])) ?></div>
                <h3 class="event-card__title"><?= e($event['name']) ?></h3>

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

<!-- ABOUT STRIP -->
<section class="about-strip">
    <div class="container about-strip__inner">
        <div class="about-strip__text">
            <span class="eyebrow">About the Studio</span>
            <h2><?= e(setting('site_name', 'My Pottery')) ?></h2>
            <p><?= e(setting('about_text', '')) ?></p>
            <a href="/about.php" class="btn btn--dark">My Story</a>
        </div>
        <div class="about-strip__decoration">
            <div class="about-strip__sunburst"></div>
            <div class="about-strip__sunburst-inner"></div>
        </div>
    </div>
</section>

<!-- SOCIAL FEED PREVIEW -->
<?php if (!empty($socialPosts)): ?>
<section class="section social-preview">
    <div class="container">
        <div class="section__header">
            <h2 class="section__title">From the Studio</h2>
            <div class="social-icons">
                <?php foreach ($socialLinks as $link): ?>
                <a href="<?= e($link['url']) ?>" target="_blank" rel="noopener" class="social-icon social-icon--<?= strtolower(e($link['platform'])) ?>" title="Follow on <?= e($link['platform']) ?>">
                    <?php echo getSocialIcon($link['platform']); ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="social-grid">
            <?php foreach ($socialPosts as $post): ?>
            <a href="<?= e($post['post_url']) ?>" target="_blank" rel="noopener" class="social-post">
                <?php if ($post['thumbnail_url']): ?>
                <img src="<?= e($post['thumbnail_url']) ?>" alt="Social post" loading="lazy">
                <?php elseif ($post['embed_code']): ?>
                <div class="social-post__embed"><?= $post['embed_code'] ?></div>
                <?php endif; ?>
                <div class="social-post__platform"><?= e($post['platform']) ?></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- SHOP TEASER -->
<section class="section shop-teaser">
    <div class="container">
        <div class="shop-teaser__inner">
            <div class="shop-teaser__text">
                <span class="eyebrow">The Shop</span>
                <h2>Own a Piece of the Studio</h2>
                <p><?= e(setting('shop_intro', '')) ?></p>
                <div class="shop-teaser__btns">
                    <a href="/shop.php?type=pot" class="btn btn--primary">Original Pots</a>
                    <a href="/shop.php?type=merch" class="btn btn--outline">Merch</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../templates/footer.php'; ?>
<script src="/js/main.js"></script>
</body>
</html>

<?php
?>
