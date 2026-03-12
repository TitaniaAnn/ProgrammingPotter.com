<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$type = $_GET['type'] ?? '';
$categorySlug = $_GET['category'] ?? '';

$params = [];
$whereClauses = [];

if ($type === 'pot' || $type === 'merch') {
    $whereClauses[] = 'p.type = ?';
    $params[] = $type;
}

if ($categorySlug) {
    $whereClauses[] = 'c.slug = ?';
    $params[] = $categorySlug;
}

$where = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

$products = Database::fetchAll(
    "SELECT p.*, c.name as category_name, c.slug as category_slug
     FROM products p
     LEFT JOIN shop_categories c ON p.category_id = c.id
     $where
     ORDER BY p.sort_order ASC, p.created_at DESC",
    $params
);

$categories = Database::fetchAll("SELECT * FROM shop_categories ORDER BY type, name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop — <?= e(setting('site_name')) ?></title>
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
        <h1 class="page-header__title">Shop</h1>
        <p class="page-header__sub"><?= e(setting('shop_intro', 'Own a piece of handcrafted art. Each pot is one-of-a-kind.')) ?></p>
        <div class="portfolio-filters">
            <a href="/shop.php" class="filter-btn <?= !$type && !$categorySlug ? 'active' : '' ?>">All</a>
            <a href="/shop.php?type=pot" class="filter-btn <?= $type === 'pot' ? 'active' : '' ?>">Original Pots</a>
            <a href="/shop.php?type=merch" class="filter-btn <?= $type === 'merch' ? 'active' : '' ?>">Merch</a>
            <?php foreach ($categories as $cat): ?>
            <a href="/shop.php?category=<?= e($cat['slug']) ?>"
               class="filter-btn <?= $categorySlug === $cat['slug'] ? 'active' : '' ?>">
                <?= e($cat['name']) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<section class="section">
    <div class="container">
        <?php if (empty($products)): ?>
        <p class="empty-state">No products available yet. Check back soon!</p>
        <?php else: ?>
        <div class="grid grid--4">
            <?php foreach ($products as $product): ?>
            <div class="product-card <?= $product['status'] === 'sold' ? 'product-card--sold' : '' ?>">
                <div class="product-card__img-wrap">
                    <?php if ($product['image_path']): ?>
                    <img src="/uploads/<?= e($product['image_path']) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
                    <?php else: ?>
                    <div class="product-card__no-img">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                    </div>
                    <?php endif; ?>
                    <?php if ($product['status'] === 'sold'): ?>
                    <div class="product-card__sold-badge">Sold</div>
                    <?php elseif ($product['status'] === 'coming_soon'): ?>
                    <div class="product-card__soon-badge">Coming Soon</div>
                    <?php endif; ?>
                    <?php if ($product['category_name']): ?>
                    <span class="product-card__cat"><?= e($product['category_name']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="product-card__body">
                    <h3 class="product-card__name"><?= e($product['name']) ?></h3>
                    <?php if ($product['description']): ?>
                    <p class="product-card__desc"><?= e(substr($product['description'], 0, 80)) . (strlen($product['description']) > 80 ? '…' : '') ?></p>
                    <?php endif; ?>
                    <div class="product-card__footer">
                        <?php if ($product['price']): ?>
                        <span class="product-card__price">$<?= number_format($product['price'], 2) ?></span>
                        <?php endif; ?>

                        <?php if ($product['status'] === 'available'): ?>
                            <?php if ($product['type'] === 'merch' && $product['pod_provider'] === 'printful' && $product['pod_product_url']): ?>
                            <a href="<?= e($product['pod_product_url']) ?>" target="_blank" rel="noopener" class="btn btn--small btn--primary">Order on Printful ↗</a>
                            <?php elseif ($product['type'] === 'merch' && $product['pod_product_url']): ?>
                            <a href="<?= e($product['pod_product_url']) ?>" target="_blank" rel="noopener" class="btn btn--small btn--primary">Buy Now ↗</a>
                            <?php elseif ($product['external_url']): ?>
                            <a href="<?= e($product['external_url']) ?>" target="_blank" rel="noopener" class="btn btn--small btn--primary">Buy Now ↗</a>
                            <?php elseif ($product['type'] === 'pot' && $product['price'] > 0): ?>
                            <form method="POST" action="/shop/checkout.php">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="btn btn--small btn--primary">Buy Now</button>
                            </form>
                            <?php else: ?>
                            <?php if (setting('contact_email')): ?>
                            <a href="mailto:<?= e(setting('contact_email')) ?>?subject=Enquiry: <?= urlencode($product['name']) ?>" class="btn btn--small btn--primary">Enquire</a>
                            <?php else: ?>
                            <span class="product-card__enquire">Contact to purchase</span>
                            <?php endif; ?>
                            <?php endif; ?>
                        <?php elseif ($product['status'] === 'coming_soon'): ?>
                        <span class="product-card__soon-text">Coming soon</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($product['pod_provider'] === 'printful'): ?>
                    <div class="printful-badge">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                        Printed &amp; shipped by Printful
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../templates/footer.php'; ?>
<script src="/js/main.js"></script>
</body>
</html>
