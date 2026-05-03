<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();
csrf_verify();

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
