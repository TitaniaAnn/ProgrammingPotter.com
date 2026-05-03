<?php
// includes/TemplateFileUploader.php
//
// Validates + saves uploaded files for the pottery_templates feature.
// Used by both public/admin/templates/add.php and edit.php.

class TemplateFileUploader {

    public const ALLOWED_EXTS  = ['pdf', 'svg', 'png', 'jpg', 'jpeg', 'webp', 'zip'];
    public const ALLOWED_MIMES = [
        'application/pdf', 'image/svg+xml', 'image/png',
        'image/jpeg', 'image/webp', 'application/zip',
        'application/x-zip-compressed',
    ];
    public const MAX_SIZE = 52428800; // 50 * 1024 * 1024

    /**
     * Move a single uploaded file from $_FILES into the templates upload dir.
     * Returns ['file_path', 'file_name', 'file_size', 'file_ext'] for DB insert.
     */
    public static function upload(array $file): array {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('File upload error: ' . $file['name']);
        }
        if ($file['size'] > self::MAX_SIZE) {
            throw new RuntimeException($file['name'] . ' exceeds 50MB limit.');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTS, true)) {
            throw new RuntimeException(
                'File type not allowed: ' . $ext . '. Accepted: ' . implode(', ', self::ALLOWED_EXTS)
            );
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new RuntimeException('File MIME type not allowed: ' . $file['name']);
        }

        $dir = ROOT_PATH . '/public/uploads/templates/files/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $safeName = 'template_' . uniqid() . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . $safeName)) {
            throw new RuntimeException('Failed to save file: ' . $file['name']);
        }

        return [
            'file_path' => 'templates/files/' . $safeName,
            'file_name' => $file['name'],
            'file_size' => $file['size'],
            'file_ext'  => $ext,
        ];
    }
}
