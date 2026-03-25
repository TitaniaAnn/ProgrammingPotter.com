<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

$id = (int)($_GET['id'] ?? 0);
$template = Database::fetchOne("SELECT * FROM pottery_templates WHERE id = ?", [$id]);

if ($template) {
    // Delete all associated template files from disk
    $files = Database::fetchAll("SELECT * FROM pottery_template_files WHERE template_id = ?", [$id]);
    foreach ($files as $f) {
        $filePath = ROOT_PATH . '/public/uploads/' . $f['file_path'];
        if (file_exists($filePath)) unlink($filePath);
    }

    // Delete preview images
    if (!empty($template['preview_path'])) {
        ImageUpload::delete($template['preview_path']);
    }

    // DB rows cascade via FK, but delete explicitly if FK not enforced
    Database::delete('pottery_template_files', 'template_id = ?', [$id]);
    Database::delete('pottery_templates', 'id = ?', [$id]);

    flash('success', 'Template deleted.');
} else {
    flash('error', 'Template not found.');
}

redirect(SITE_URL . '/admin/templates/index.php');
