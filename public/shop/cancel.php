<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$productId = (int)($_GET['product_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Cancelled — <?= e(setting('site_name')) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Crimson+Pro:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .cancel-page {
            min-height: 80vh;
            display: flex; align-items: center; justify-content: center;
            padding: 8rem 2rem 4rem;
        }
        .cancel-card {
            max-width: 480px; width: 100%; text-align: center;
            background: var(--parchment);
            border: 2px solid var(--wheat);
            box-shadow: 4px 4px 0 var(--wheat);
            padding: 3rem 2.5rem;
        }
        .cancel-card__icon { font-size: 3rem; margin-bottom: 1rem; }
        .cancel-card__title { font-family: var(--font-display); font-size: 1.8rem; margin-bottom: .75rem; }
        .cancel-card__sub { font-family: var(--font-script); font-size: 1.2rem; color: var(--ember); display: block; margin-bottom: 1.5rem; }
        .cancel-card__actions { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-top: 2rem; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../../templates/nav.php'; ?>

<div class="cancel-page">
    <div class="cancel-card">
        <div class="cancel-card__icon">👐</div>
        <h1 class="cancel-card__title">No worries!</h1>
        <span class="cancel-card__sub">Your checkout was cancelled</span>
        <p>Nothing was charged. The item is still available if you change your mind.</p>
        <div class="cancel-card__actions">
            <?php if ($productId): ?>
            <a href="/shop.php" class="btn btn--primary">Back to Shop</a>
            <?php endif; ?>
            <?php if (setting('contact_email')): ?>
            <a href="mailto:<?= e(setting('contact_email')) ?>" class="btn btn--outline">Ask me a question</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
<script src="/js/main.js"></script>
</body>
</html>
