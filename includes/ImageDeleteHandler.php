<?php
// includes/ImageDeleteHandler.php
//
// Shared logic for the pottery and shop "delete one image from a multi-image
// gallery" JSON endpoints. Deletes the file + row, then promotes the next
// image as primary and syncs the parent row's image columns.

class ImageDeleteHandler {

    /**
     * @param array $cfg {
     *   imageId:            int     The id of the row in $imagesTable to delete.
     *   parentId:           int     The id of the parent (pottery / product).
     *   imagesTable:        string  e.g. 'pottery_images', 'product_images'.
     *   parentIdColumn:     string  e.g. 'pottery_id', 'product_id'.
     *   parentTable:        string  e.g. 'pottery', 'products'.
     *   parentThumbColumn:  ?string 'image_thumb' if the parent stores a thumb path; null otherwise.
     *   blockLastImage:     bool    If true, refuse to delete when this is the last remaining image.
     * }
     * @return array ['success' => bool, 'error' => ?string]
     */
    public static function delete(array $cfg): array {
        $imageId          = (int) $cfg['imageId'];
        $parentId         = (int) $cfg['parentId'];
        $imagesTable      = $cfg['imagesTable'];
        $parentIdColumn   = $cfg['parentIdColumn'];
        $parentTable      = $cfg['parentTable'];
        $parentThumbCol   = $cfg['parentThumbColumn'] ?? null;
        $blockLastImage   = !empty($cfg['blockLastImage']);

        if (!$imageId || !$parentId) {
            return ['success' => false, 'error' => 'Invalid parameters'];
        }

        $img = Database::fetchOne(
            "SELECT * FROM {$imagesTable} WHERE id = ? AND {$parentIdColumn} = ?",
            [$imageId, $parentId]
        );
        if (!$img) {
            return ['success' => false, 'error' => 'Image not found'];
        }

        if ($blockLastImage) {
            $count = Database::fetchOne(
                "SELECT COUNT(*) AS cnt FROM {$imagesTable} WHERE {$parentIdColumn} = ?",
                [$parentId]
            );
            if ((int) ($count['cnt'] ?? 0) <= 1) {
                return ['success' => false, 'error' => 'Cannot delete the only image. Add another first.'];
            }
        }

        if (!empty($img['image_path'])) {
            ImageUpload::delete($img['image_path']);
        }
        Database::query("DELETE FROM {$imagesTable} WHERE id = ?", [$imageId]);

        $next = Database::fetchOne(
            "SELECT * FROM {$imagesTable}
              WHERE {$parentIdColumn} = ?
              ORDER BY sort_order ASC, id ASC
              LIMIT 1",
            [$parentId]
        );

        Database::query(
            "UPDATE {$imagesTable} SET is_primary = 0 WHERE {$parentIdColumn} = ?",
            [$parentId]
        );

        if ($next) {
            Database::query(
                "UPDATE {$imagesTable} SET is_primary = 1 WHERE id = ?",
                [$next['id']]
            );
            if ($parentThumbCol) {
                Database::query(
                    "UPDATE {$parentTable} SET image_path = ?, {$parentThumbCol} = ? WHERE id = ?",
                    [$next['image_path'], $next['image_thumb'] ?? null, $parentId]
                );
            } else {
                Database::query(
                    "UPDATE {$parentTable} SET image_path = ? WHERE id = ?",
                    [$next['image_path'], $parentId]
                );
            }
        } elseif (!$blockLastImage) {
            // No images left and the endpoint allowed it; clear parent image_path.
            Database::query(
                "UPDATE {$parentTable} SET image_path = NULL WHERE id = ?",
                [$parentId]
            );
        }

        return ['success' => true, 'error' => null];
    }
}
