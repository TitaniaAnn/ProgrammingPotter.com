<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$featured = Database::fetchAll(
    "SELECT * FROM pottery WHERE featured = 1 ORDER BY sort_order ASC, created_at DESC LIMIT 6"
);
$socialLinks = Database::fetchAll(
    "SELECT * FROM social_links WHERE active = 1 ORDER BY sort_order ASC"
);
$socialPosts = Database::fetchAll(
    "SELECT * FROM social_posts WHERE featured = 1 ORDER BY sort_order ASC LIMIT 6"
);
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
