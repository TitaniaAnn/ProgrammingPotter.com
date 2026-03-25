<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$fileId = (int)($_GET['file_id'] ?? 0);
if ($fileId <= 0) {
    http_response_code(404);
    exit('File not found.');
}

$file = Database::fetchOne(
    "SELECT f.*, t.id AS template_id FROM pottery_template_files f
     JOIN pottery_templates t ON t.id = f.template_id
     WHERE f.id = ?",
    [$fileId]
);
if (!$file) {
    http_response_code(404);
    exit('File not found.');
}

$filePath = ROOT_PATH . '/public/uploads/' . $file['file_path'];
if (!file_exists($filePath)) {
    http_response_code(404);
    exit('File not found on server.');
}

// Increment template download counter
Database::query("UPDATE pottery_templates SET download_count = download_count + 1 WHERE id = ?", [$file['template_id']]);

$ext     = strtolower($file['file_ext']);
$mimeMap = [
    'pdf'  => 'application/pdf',
    'svg'  => 'image/svg+xml',
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'webp' => 'image/webp',
    'zip'  => 'application/zip',
];
$mime = $mimeMap[$ext] ?? 'application/octet-stream';

$safeFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['file_name']);

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache');
readfile($filePath);
exit;
