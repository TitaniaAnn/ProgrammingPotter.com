<?php
// includes/ImageUpload.php

class ImageUpload {

    public static function upload(array $file, string $subdir = ''): array {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload error: ' . $file['error']);
        }
        if ($file['size'] > MAX_IMAGE_SIZE) {
            throw new RuntimeException('File too large (max 10MB)');
        }

        $mime = mime_content_type($file['tmp_name']);
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($mime, $allowed)) {
            throw new RuntimeException('Invalid file type. Use JPG, PNG, WebP, or GIF.');
        }

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('pottery_', true) . '.' . strtolower($ext);
        $dir      = UPLOAD_PATH . ($subdir ? rtrim($subdir, '/') . '/' : '');

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $destination = $dir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('Failed to move uploaded file');
        }

        // Generate thumbnail
        $thumbFilename = 'thumb_' . $filename;
        $thumbPath     = $dir . $thumbFilename;
        self::createThumbnail($destination, $thumbPath);

        $urlBase = UPLOAD_URL . ($subdir ? rtrim($subdir, '/') . '/' : '');
        return [
            'path'  => ($subdir ? rtrim($subdir, '/') . '/' : '') . $filename,
            'thumb' => ($subdir ? rtrim($subdir, '/') . '/' : '') . $thumbFilename,
            'url'   => $urlBase . $filename,
            'thumb_url' => $urlBase . $thumbFilename,
        ];
    }

    private static function createThumbnail(string $src, string $dest): void {
        [$origW, $origH, $type] = getimagesize($src);

        $ratio  = min(THUMB_WIDTH / $origW, THUMB_HEIGHT / $origH);
        $newW   = (int) ($origW * $ratio);
        $newH   = (int) ($origH * $ratio);

        $thumb = imagecreatetruecolor($newW, $newH);

        switch ($type) {
            case IMAGETYPE_JPEG:
                $src_img = imagecreatefromjpeg($src);
                break;
            case IMAGETYPE_PNG:
                $src_img = imagecreatefrompng($src);
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
                break;
            case IMAGETYPE_WEBP:
                $src_img = imagecreatefromwebp($src);
                break;
            default:
                $src_img = imagecreatefromjpeg($src);
        }

        imagecopyresampled($thumb, $src_img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        switch ($type) {
            case IMAGETYPE_PNG:
                imagepng($thumb, $dest, 8);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($thumb, $dest, 85);
                break;
            default:
                imagejpeg($thumb, $dest, 85);
        }

        imagedestroy($thumb);
        imagedestroy($src_img);
    }

    public static function delete(string $path): void {
        $full = UPLOAD_PATH . $path;
        if (file_exists($full)) unlink($full);

        // Also delete thumb
        $dir      = dirname($full);
        $filename = basename($full);
        $thumb    = $dir . '/thumb_' . $filename;
        if (file_exists($thumb)) unlink($thumb);
    }
}
