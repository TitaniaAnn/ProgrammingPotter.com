-- ============================================================
-- Patch 001: Multiple images per pottery piece
-- Run this ONCE on your live database, then delete this file.
-- ============================================================

-- 1. New images table
CREATE TABLE IF NOT EXISTS pottery_images (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    pottery_id  INT NOT NULL,
    image_path  TEXT NOT NULL,
    image_thumb TEXT,
    sort_order  INT DEFAULT 0,
    is_primary  TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pottery_id) REFERENCES pottery(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Migrate all existing primary images into the new table
INSERT INTO pottery_images (pottery_id, image_path, image_thumb, sort_order, is_primary)
SELECT id, image_path, image_thumb, 0, 1
FROM pottery
WHERE image_path IS NOT NULL AND image_path != '';
