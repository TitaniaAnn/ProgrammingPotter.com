-- Create per-file table
CREATE TABLE IF NOT EXISTS pottery_template_files (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    file_path   VARCHAR(500) NOT NULL,
    file_name   VARCHAR(255) NOT NULL,
    file_size   INT          DEFAULT 0,
    file_ext    VARCHAR(10)  DEFAULT '',
    label       VARCHAR(255) DEFAULT '',
    sort_order  INT          DEFAULT 0,
    FOREIGN KEY (template_id) REFERENCES pottery_templates(id) ON DELETE CASCADE
);

-- Migrate any existing single files into the new table
INSERT INTO pottery_template_files (template_id, file_path, file_name, file_size, file_ext, sort_order)
SELECT id, file_path, file_name, file_size, file_ext, 0
FROM pottery_templates
WHERE file_path IS NOT NULL AND file_path != '';

-- Drop file columns from pottery_templates
ALTER TABLE pottery_templates
    DROP COLUMN file_path,
    DROP COLUMN file_name,
    DROP COLUMN file_size,
    DROP COLUMN file_ext;
