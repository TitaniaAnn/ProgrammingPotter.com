<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

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
            // Keep request safe if schema auto-repair fails.
        }
    }
} catch (Exception $e) {
    // Keep request safe if schema lookup fails.
}

if (!$hasVisibilityColumn) {
    flash('error', 'Product visibility is not available on this database yet.');
    redirect(SITE_URL . '/admin/shop/index.php');
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    flash('error', 'Invalid product.');
    redirect(SITE_URL . '/admin/shop/index.php');
}

$product = Database::fetchOne("SELECT id, name, is_visible FROM products WHERE id = ?", [$id]);
if (!$product) {
    flash('error', 'Product not found.');
    redirect(SITE_URL . '/admin/shop/index.php');
}

$newVisibility = !empty($product['is_visible']) ? 0 : 1;
Database::update('products', ['is_visible' => $newVisibility], 'id = :id', ['id' => $id]);

$action = $newVisibility ? 'shown' : 'hidden';
flash('success', 'Product "' . $product['name'] . '" is now ' . $action . ' in the shop.');
redirect(SITE_URL . '/admin/shop/index.php');
