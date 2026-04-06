<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

header('Content-Type: application/json');

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

$imageId = (int)($_GET['img_id'] ?? 0);
$productId = (int)($_GET['product_id'] ?? 0);

if (!$imageId || !$productId) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

$image = Database::fetchOne(
    "SELECT * FROM product_images WHERE id = ? AND product_id = ?",
    [$imageId, $productId]
);

if (!$image) {
    echo json_encode(['success' => false, 'error' => 'Image not found']);
    exit;
}

ImageUpload::delete($image['image_path']);
Database::query("DELETE FROM product_images WHERE id = ?", [$imageId]);

$nextPrimary = Database::fetchOne(
    "SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1",
    [$productId]
);

Database::query("UPDATE product_images SET is_primary = 0 WHERE product_id = ?", [$productId]);

if ($nextPrimary) {
    Database::query("UPDATE product_images SET is_primary = 1 WHERE id = ?", [$nextPrimary['id']]);
    Database::query("UPDATE products SET image_path = ? WHERE id = ?", [$nextPrimary['image_path'], $productId]);
} else {
    Database::query("UPDATE products SET image_path = NULL WHERE id = ?", [$productId]);
}

echo json_encode(['success' => true]);