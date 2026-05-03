<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();
csrf_verify();

$id = (int)($_GET['id'] ?? 0);
$product = Database::fetchOne("SELECT * FROM products WHERE id = ?", [$id]);
if ($product) {
    $images = Database::fetchAll("SELECT image_path FROM product_images WHERE product_id = ?", [$id]);
    foreach ($images as $image) {
        if (!empty($image['image_path'])) {
            ImageUpload::delete($image['image_path']);
        }
    }

    if ($product['image_path']) {
        ImageUpload::delete($product['image_path']);
    }

    Database::delete('products', 'id = ?', [$id]);
    flash('success', 'Product deleted.');
} else {
    flash('error', 'Product not found.');
}
redirect(SITE_URL . '/admin/shop/index.php');
