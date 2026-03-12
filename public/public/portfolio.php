<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$technique = $_GET['technique'] ?? '';
$params = [];
$where = '';
if ($technique) {
    $where = 'WHERE technique = ?';
    $params[] = $technique;
}

$pieces = Database::fetchAll(
    "SELECT * FROM pottery $where ORDER BY featured DESC, sort_order ASC, created_at DESC",
    $params
);

$techniques = Database::fetchAll(
    "SELECT DISTINCT technique FROM pottery WHERE technique IS NOT NULL AND technique != '' ORDER BY technique"
);

// Load all images indexed by pottery_id (graceful fallback if table not yet migrated)
$allImages = [];
try {
    $imageRows = Database::fetchAll(
        "SELECT * FROM pottery_images ORDER BY pottery_id, sort_order ASC, id ASC"
    );
    foreach ($imageRows as $row) {
        $allImages[$row['pottery_id']][] = $row;
    }
} catch (Exception $e) {
    // pottery_images table not yet created — will fall back to pottery.image_path below
    $allImages = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio — <?= e(setting('site_name')) ?></title>
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
        <h1 class="page-header__title">Portfolio</h1>
        <p class="page-header__sub">A collection of handcrafted ceramics</p>
        <?php if (!empty($techniques)): ?>
        <div class="portfolio-filters">
            <a href="/portfolio.php" class="filter-btn <?= !$technique ? 'active' : '' ?>">All</a>
            <?php foreach ($techniques as $t): ?>
            <a href="/portfolio.php?technique=<?= urlencode($t['technique']) ?>"
               class="filter-btn <?= $technique === $t['technique'] ? 'active' : '' ?>">
                <?= e($t['technique']) ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Gallery -->
<section class="section">
    <div class="container">
        <?php if (empty($pieces)): ?>
        <p class="empty-state">No pieces to display yet. Check back soon!</p>
        <?php else: ?>
        <div class="masonry-grid" id="gallery">
            <?php foreach ($pieces as $piece): ?>
            <?php
                $pieceImgs = $allImages[$piece['id']] ?? [];
                if (empty($pieceImgs)) {
                    $pieceImgs = [['image_path' => $piece['image_path'], 'image_thumb' => $piece['image_thumb'] ?? $piece['image_path']]];
                }
                // Use JSON in single-quoted attributes to avoid " collision
                $imagesJson = htmlspecialchars(json_encode(array_values(array_map(fn($i) => '/uploads/' . $i['image_path'], $pieceImgs))), ENT_QUOTES, 'UTF-8');
                $thumbsJson = htmlspecialchars(json_encode(array_values(array_map(fn($i) => '/uploads/' . ($i['image_thumb'] ?? $i['image_path']), $pieceImgs))), ENT_QUOTES, 'UTF-8');
            ?>
            <div class="masonry-item" id="piece-<?= $piece['id'] ?>">
                <div role="button" tabindex="0" class="lightbox-trigger"
                   data-id='<?= $piece['id'] ?>'
                   data-images='<?= $imagesJson ?>'
                   data-thumbs='<?= $thumbsJson ?>'
                   data-title='<?= e($piece['title']) ?>'
                   data-desc='<?= e($piece['description'] ?? '') ?>'
                   data-technique='<?= e($piece['technique'] ?? '') ?>'
                   data-dimensions='<?= e($piece['dimensions'] ?? '') ?>'
                   data-year='<?= e($piece['year'] ?? '') ?>'>
                    <img src="/uploads/<?= e($pieceImgs[0]['image_thumb'] ?? $pieceImgs[0]['image_path']) ?>"
                         alt="<?= e($piece['title']) ?>" loading="lazy">
                    <div class="masonry-item__overlay">
                        <h3><?= e($piece['title']) ?></h3>
                        <?php if ($piece['technique']): ?><span><?= e($piece['technique']) ?></span><?php endif; ?>
                    </div>
                    <?php if (count($pieceImgs) > 1): ?>
                    <div class="masonry-item__count">⬡ <?= count($pieceImgs) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Lightbox -->
<div class="lightbox" id="lightbox" aria-modal="true" role="dialog">
    <button class="lightbox__close" id="lightboxClose" aria-label="Close">&times;</button>
    <button class="lightbox__nav lightbox__nav--prev" id="lbPrev" aria-label="Previous">&#8249;</button>
    <button class="lightbox__nav lightbox__nav--next" id="lbNext" aria-label="Next">&#8250;</button>
    <div class="lightbox__inner">
        <div class="lightbox__img-wrap">
            <img src="" alt="" id="lightboxImg">
            <div class="lightbox__counter" id="lbCounter"></div>
        </div>
        <div class="lightbox__info">
            <h2 id="lightboxTitle"></h2>
            <p id="lightboxDesc"></p>
            <dl class="lightbox__meta" id="lightboxMeta"></dl>
            <!-- Thumbnail strip -->
            <div class="lightbox__thumbs" id="lbThumbs"></div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
<script src="/js/main.js"></script>
<script src="/js/portfolio.js?v=4"></script>
</body>
</html>
