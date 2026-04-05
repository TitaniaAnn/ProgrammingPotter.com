-- 008_announcements.sql: Event announcements system with linked entities
-- Supports scheduling announcements by publish_date
-- Links announcements to events and pottery pieces
-- Tracks social media posts

-- Announcements table (base entity)
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    publish_date DATETIME NOT NULL,
    image_path TEXT,
    image_thumb TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for queries
    KEY idx_publish_date (publish_date),
    KEY idx_created_at (created_at),
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Junction table: announcements <-> entities (events, pottery)
-- Allows many-to-many linking and future expansion to other entity types
CREATE TABLE IF NOT EXISTS announcement_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    entity_type ENUM('event', 'pottery') NOT NULL,
    entity_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
    KEY idx_entity_lookup (entity_type, entity_id),
    KEY idx_announcement_id (announcement_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit trail: track which announcements were posted to social media
CREATE TABLE IF NOT EXISTS announcement_social_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    platform ENUM('instagram', 'tiktok') NOT NULL,
    posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    platform_post_id VARCHAR(255),
    status ENUM('success', 'pending', 'failed') DEFAULT 'pending',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
    KEY idx_platform (platform),
    KEY idx_posted_at (posted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
