<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    flash('error', 'Invalid announcement ID.');
    redirect(SITE_URL . '/admin/announcements/index.php');
}

$announcement = Database::fetchOne("SELECT * FROM announcements WHERE id = ?", [$id]);
if (!$announcement) {
    flash('error', 'Announcement not found.');
    redirect(SITE_URL . '/admin/announcements/index.php');
}

try {
    // Delete image files if they exist
    if (!empty($announcement['image_path'])) {
        ImageUpload::delete($announcement['image_path']);
    }
    if (!empty($announcement['image_thumb']) && $announcement['image_thumb'] !== $announcement['image_path']) {
        ImageUpload::delete($announcement['image_thumb']);
    }

    // Delete announcement (cascade will delete links and social posts)
    Database::query("DELETE FROM announcements WHERE id = ?", [$id]);

    flash('success', 'Announcement deleted successfully.');
} catch (Exception $e) {
    flash('error', 'Failed to delete announcement: ' . $e->getMessage());
}

redirect(SITE_URL . '/admin/announcements/index.php');
