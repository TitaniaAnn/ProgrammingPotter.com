<?php
$footerLinks = Database::fetchAll("SELECT * FROM social_links WHERE active = 1 ORDER BY sort_order ASC");
?>
<footer class="footer">
    <div class="container footer__inner">
        <div class="footer__brand">
            <span class="footer__logo"><?= e(setting('site_name', 'My Pottery')) ?></span>
            <p class="footer__tagline"><?= e(setting('tagline', 'Handcrafted with intention')) ?></p>
        </div>
        <div class="footer__nav">
            <a href="/portfolio.php">Portfolio</a>
            <a href="/shop.php">Shop</a>
            <a href="/about.php">About</a>
        </div>
        <div class="footer__social">
            <?php foreach ($footerLinks as $link): ?>
            <a href="<?= e($link['url']) ?>" target="_blank" rel="noopener" title="<?= e($link['platform']) ?>">
                <?= getSocialIcon($link['platform']); ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="footer__bottom">
        <span>&copy; <?= date('Y') ?> <?= e(setting('site_name', 'My Pottery')) ?>. All rights reserved.</span>
    </div>
</footer>
