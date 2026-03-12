<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

header('Content-Type: application/json');

$imgId  = (int)($_GET['img_id']  ?? 0);
$pieceId = (int)($_GET['piece_id'] ?? 0);

if (!$imgId || !$pieceId) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

$img = Database::fetchOne(
    "SELECT * FROM pottery_images WHERE id = ? AND pottery_id = ?", [$imgId, $pieceId]
);

if (!$img) {
    echo json_encode(['success' => false, 'error' => 'Image not found']);
    exit;
}

// Count remaining images — don't allow deleting the last one
$count = Database::fetchOne(
    "SELECT COUNT(*) as cnt FROM pottery_images WHERE pottery_id = ?", [$pieceId]
);
if ((int)$count['cnt'] <= 1) {
    echo json_encode(['success' => false, 'error' => 'Cannot delete the only image. Add another first.']);
    exit;
}

// Delete file
ImageUpload::delete($img['image_path']);

// Delete DB row
Database::query("DELETE FROM pottery_images WHERE id = ?", [$imgId]);

// If this was the primary, promote the next image
if ($img['is_primary']) {
    $next = Database::fetchOne(
        "SELECT * FROM pottery_images WHERE pottery_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1",
        [$pieceId]
    );
    if ($next) {
        Database::query("UPDATE pottery_images SET is_primary = 1 WHERE id = ?", [$next['id']]);
        Database::query(
            "UPDATE pottery SET image_path = ?, image_thumb = ? WHERE id = ?",
            [$next['image_path'], $next['image_thumb'], $pieceId]
        );
    }
}

echo json_encode(['success' => true]);
