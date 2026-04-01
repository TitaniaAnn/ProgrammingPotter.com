-- 007_events.sql: Events system with pottery assignments
-- Supports pottery shows, sales, storefront sales, and classes
-- Includes publish_date for visibility control

-- Events table (base entity)
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type ENUM('pottery_show', 'pottery_sale', 'storefront_sale', 'class') NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    location VARCHAR(255),
    url TEXT,
    
    -- Dates
    start_date DATE,
    end_date DATE,
    publish_date DATE,
    
    -- Type-specific: Sales
    daily_open_times TEXT,
    
    -- Type-specific: Classes
    class_type ENUM('handbuilding', 'wheelthrowing', 'month_long', 'workshop'),
    class_age_range VARCHAR(255),
    class_date_start DATE,
    class_date_end DATE,
    class_time_start TIME,
    class_time_end TIME,
    
    -- Management
    featured TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for queries
    KEY idx_event_type (event_type),
    KEY idx_start_date (start_date),
    KEY idx_end_date (end_date),
    KEY idx_publish_date (publish_date),
    KEY idx_featured (featured),
    KEY idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Junction table: events <-> pottery (many-to-many)
CREATE TABLE IF NOT EXISTS event_pottery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    pottery_id INT NOT NULL,
    label VARCHAR(255),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (pottery_id) REFERENCES pottery(id) ON DELETE CASCADE,
    UNIQUE KEY unique_event_pottery (event_id, pottery_id),
    KEY idx_pottery_id (pottery_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
