<?php
// public/shop/checkout.php
// POST handler: creates a Stripe Checkout session and redirects the user

require_once __DIR__ . '/../../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/shop.php');
}

$productId = (int)($_POST['product_id'] ?? 0);
$quantity  = max(1, (int)($_POST['quantity'] ?? 1));

$hasVisibilityColumn = false;
try {
    $visibilityColumn = Database::fetchOne(
        "SELECT COLUMN_NAME
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'products'
           AND COLUMN_NAME = 'is_visible'
         LIMIT 1"
    );
    $hasVisibilityColumn = !empty($visibilityColumn);

    if (!$hasVisibilityColumn) {
        try {
            Database::query("ALTER TABLE products ADD COLUMN is_visible TINYINT(1) NOT NULL DEFAULT 1 AFTER status");
            $hasVisibilityColumn = true;
        } catch (Exception $e) {
            // Keep checkout usable if schema auto-repair fails.
        }
    }
} catch (Exception $e) {
    // Keep checkout usable without visibility filter.
}

$visibilitySql = $hasVisibilityColumn ? " AND is_visible = 1" : "";

$product = Database::fetchOne(
    "SELECT * FROM products WHERE id = ? AND type = 'pot' AND status = 'available'" . $visibilitySql,
    [$productId]
);

if (!$product) {
    flash('error', 'Sorry, that item is no longer available.');
    redirect(SITE_URL . '/shop.php');
}

if ($product['quantity'] < $quantity) {
    flash('error', 'Not enough stock — only ' . $product['quantity'] . ' left.');
    redirect(SITE_URL . '/shop.php');
}

if (!$product['price'] || $product['price'] <= 0) {
    flash('error', 'This item does not have a price set. Please contact me to purchase.');
    redirect(SITE_URL . '/shop.php');
}

try {
    $checkoutUrl = StripeHelper::createCheckoutSession($product, $quantity);
    header('Location: ' . $checkoutUrl);
    exit;
} catch (RuntimeException $e) {
    // SDK not installed yet — friendly message
    if (str_contains($e->getMessage(), 'SDK not found')) {
        flash('error', 'Online checkout is being set up. Please contact me directly to purchase.');
    } else {
        flash('error', 'Checkout error: ' . $e->getMessage());
    }
    redirect(SITE_URL . '/shop.php');
} catch (Exception $e) {
    error_log('Stripe checkout error: ' . $e->getMessage());
    flash('error', 'Something went wrong with checkout. Please try again or contact me directly.');
    redirect(SITE_URL . '/shop.php');
}
