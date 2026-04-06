<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

Database::query(
    "CREATE TABLE IF NOT EXISTS product_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        image_path TEXT NOT NULL,
        image_thumb TEXT NULL,
        sort_order INT DEFAULT 0,
        is_primary TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_product_sort (product_id, sort_order),
        CONSTRAINT fk_product_images_product
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

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
