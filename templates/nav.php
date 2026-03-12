<nav class="nav" id="nav">
    <div class="nav__inner">
        <a href="/" class="nav__logo">
            <span class="nav__logo-text"><?= e(setting('site_name', 'My Pottery')) ?></span>
        </a>
        <ul class="nav__links">
            <li><a href="/" class="nav__link">Home</a></li>
            <li><a href="/portfolio.php" class="nav__link">Portfolio</a></li>
            <li><a href="/shop.php" class="nav__link">Shop</a></li>
            <li><a href="/about.php" class="nav__link">About</a></li>
        </ul>
        <button class="nav__burger" aria-label="Menu" id="burger">
            <span></span><span></span><span></span>
        </button>
    </div>
    <!-- Mobile menu -->
    <div class="nav__mobile" id="mobileMenu">
        <a href="/" class="nav__mobile-link">Home</a>
        <a href="/portfolio.php" class="nav__mobile-link">Portfolio</a>
        <a href="/shop.php" class="nav__mobile-link">Shop</a>
        <a href="/about.php" class="nav__mobile-link">About</a>
    </div>
</nav>
