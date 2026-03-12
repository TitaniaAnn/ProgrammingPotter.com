<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

$id = (int)($_GET['id'] ?? 0);
$product = Database::fetchOne("SELECT * FROM products WHERE id = ?", [$id]);
if ($product) {
    if ($product['image_path']) ImageUpload::delete($product['image_path']);
    Database::delete('products', 'id = ?', [$id]);
    flash('success', 'Product deleted.');
} else {
    flash('error', 'Product not found.');
}
redirect(SITE_URL . '/admin/shop/index.php');
