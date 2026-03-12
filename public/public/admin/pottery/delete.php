<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

$id = (int)($_GET['id'] ?? 0);
$piece = Database::fetchOne("SELECT * FROM pottery WHERE id = ?", [$id]);
if ($piece) {
    ImageUpload::delete($piece['image_path']);
    Database::delete('pottery', 'id = ?', [$id]);
    flash('success', 'Piece deleted.');
} else {
    flash('error', 'Piece not found.');
}
redirect(SITE_URL . '/admin/pottery/index.php');
