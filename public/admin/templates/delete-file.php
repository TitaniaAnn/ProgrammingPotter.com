<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();
csrf_verify();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

$fileId     = (int)($_GET['file_id'] ?? 0);
$templateId = (int)($_GET['template_id'] ?? 0);

$file = Database::fetchOne(
    "SELECT * FROM pottery_template_files WHERE id = ? AND template_id = ?",
    [$fileId, $templateId]
);

if (!$file) {
    echo json_encode(['success' => false, 'error' => 'File not found.']);
    exit;
}

$filePath = ROOT_PATH . '/public/uploads/' . $file['file_path'];
if (file_exists($filePath)) {
    unlink($filePath);
}

Database::delete('pottery_template_files', 'id = ?', [$fileId]);

echo json_encode(['success' => true]);
