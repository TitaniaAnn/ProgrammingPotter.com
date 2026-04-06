-- 1. DATABASE INITIALIZATION
CREATE DATABASE IF NOT EXISTS pottery_portfolio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pottery_portfolio;

-- 2. CORE STUDIO TABLES
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    google_id VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255),
    avatar_url TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Portfolio pieces (Base data)
CREATE TABLE IF NOT EXISTS pottery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    technique VARCHAR(255),
    dimensions VARCHAR(255),
    year INT,
    image_path TEXT NOT NULL, -- Kept for legacy compatibility during migration
    image_thumb TEXT,
    featured TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. MULTI-IMAGE SUPPORT (Patch 001 Integrated)
CREATE TABLE IF NOT EXISTS pottery_images (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    pottery_id  INT NOT NULL,
    image_path  TEXT NOT NULL,
    image_thumb TEXT,
    sort_order  INT DEFAULT 0,
    is_primary  TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pottery_id) REFERENCES pottery(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3.5. EVENTS SYSTEM (Patch 007 Integrated)
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

-- 4. E-COMMERCE & SHOP TABLES
CREATE TABLE IF NOT EXISTS shop_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    type ENUM('pot', 'merch') NOT NULL,
    description TEXT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2),
    type ENUM('pot', 'merch') NOT NULL DEFAULT 'pot',
    status ENUM('available', 'sold', 'coming_soon') DEFAULT 'available',
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    image_path TEXT,
    dimensions VARCHAR(255),
    technique VARCHAR(255),
    quantity INT DEFAULT 1,
    pod_provider ENUM('printful', 'printify', 'redbubble', 'other') NULL,
    pod_product_url TEXT NULL,
    pod_product_id VARCHAR(255) NULL,
    external_url TEXT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES shop_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path TEXT NOT NULL,
    image_thumb TEXT NULL,
    sort_order INT DEFAULT 0,
    is_primary TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_product_sort (product_id, sort_order),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. SOCIAL & SITE SETTINGS
CREATE TABLE IF NOT EXISTS social_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    platform VARCHAR(50) NOT NULL,
    url TEXT NOT NULL,
    handle VARCHAR(255),
    active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS social_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    platform VARCHAR(50) NOT NULL,
    post_id VARCHAR(255),
    embed_code TEXT,
    post_url TEXT,
    caption TEXT,
    thumbnail_url TEXT,
    post_date TIMESTAMP NULL,
    featured TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 6. ORDERS (Stripe-backed)
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stripe_session_id VARCHAR(255) UNIQUE,
    stripe_payment_intent VARCHAR(255),
    product_id INT,
    product_name VARCHAR(255),
    product_price DECIMAL(10,2),
    quantity INT DEFAULT 1,
    status ENUM('pending','paid','shipped','cancelled','refunded') DEFAULT 'pending',
    customer_name VARCHAR(255),
    customer_email VARCHAR(255),
    shipping_line1 VARCHAR(255),
    shipping_line2 VARCHAR(255),
    shipping_city VARCHAR(255),
    shipping_state VARCHAR(255),
    shipping_postal_code VARCHAR(255),
    shipping_country VARCHAR(10),
    tracking_number VARCHAR(255),
    tracking_carrier VARCHAR(100),
    shipped_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 7. DEFAULT SEED DATA
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'ProgrammingPotter'),
('tagline', 'Handcrafted ceramics and custom code'),
('hero_title', 'Shaped by Hand and Fire'),
('hero_subtitle', 'Functional ceramics from my Gladstone studio'),
('shop_currency', 'CAD');

INSERT INTO shop_categories (name, slug, type) VALUES
('Original Pots', 'original-pots', 'pot'),
('Studio Merch', 'studio-merch', 'merch');