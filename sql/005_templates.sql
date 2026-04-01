CREATE TABLE IF NOT EXISTS pottery_templates (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(255) NOT NULL,
    description  TEXT,
    category     VARCHAR(100) DEFAULT '',
    file_path    VARCHAR(500) NOT NULL,
    file_name    VARCHAR(255) NOT NULL,
    file_size    INT          DEFAULT 0,
    file_ext     VARCHAR(10)  DEFAULT '',
    preview_path VARCHAR(500) DEFAULT '',
    preview_thumb VARCHAR(500) DEFAULT '',
    download_count INT        DEFAULT 0,
    sort_order   INT          DEFAULT 0,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);
