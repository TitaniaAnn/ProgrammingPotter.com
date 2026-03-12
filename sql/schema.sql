-- Pottery Portfolio Database Schema
CREATE DATABASE IF NOT EXISTS pottery_portfolio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pottery_portfolio;

-- Admin users (Google OAuth)
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    google_id VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255),
    avatar_url TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Portfolio pieces
CREATE TABLE IF NOT EXISTS pottery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    technique VARCHAR(255),
    dimensions VARCHAR(255),
    year INT,
    image_path TEXT NOT NULL,
    image_thumb TEXT,
    featured TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Shop categories
CREATE TABLE IF NOT EXISTS shop_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    type ENUM('pot', 'merch') NOT NULL,
    description TEXT
);

-- Shop products
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2),
    type ENUM('pot', 'merch') NOT NULL DEFAULT 'pot',
    status ENUM('available', 'sold', 'coming_soon') DEFAULT 'available',
    image_path TEXT,
    -- For pots
    dimensions VARCHAR(255),
    technique VARCHAR(255),
    quantity INT DEFAULT 1,
    -- For print-on-demand merch
    pod_provider ENUM('printful', 'printify', 'redbubble', 'other') NULL,
    pod_product_url TEXT NULL,
    pod_product_id VARCHAR(255) NULL,
    -- For external links (Redbubble etc)
    external_url TEXT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES shop_categories(id) ON DELETE SET NULL
);

-- Social media links
CREATE TABLE IF NOT EXISTS social_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    platform VARCHAR(50) NOT NULL,
    url TEXT NOT NULL,
    handle VARCHAR(255),
    active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0
);

-- Social media posts (embedded/cached)
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
);

-- Site settings
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Orders table (Stripe-backed)
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stripe_session_id VARCHAR(255) UNIQUE,
    stripe_payment_intent VARCHAR(255),
    product_id INT,
    product_name VARCHAR(255),
    product_price DECIMAL(10,2),
    quantity INT DEFAULT 1,
    status ENUM('pending','paid','shipped','cancelled','refunded') DEFAULT 'pending',
    -- Customer details (filled by Stripe on success)
    customer_name VARCHAR(255),
    customer_email VARCHAR(255),
    -- Shipping address
    shipping_line1 VARCHAR(255),
    shipping_line2 VARCHAR(255),
    shipping_city VARCHAR(255),
    shipping_state VARCHAR(255),
    shipping_postal_code VARCHAR(255),
    shipping_country VARCHAR(10),
    -- Tracking
    tracking_number VARCHAR(255),
    tracking_carrier VARCHAR(100),
    shipped_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'My Pottery'),
('tagline', 'Handcrafted ceramics made with love'),
('bio', 'I am a ceramic artist based in [your city]. I create functional and sculptural pottery using traditional techniques.'),
('hero_title', 'Handcrafted Ceramics'),
('hero_subtitle', 'Each piece tells a story shaped by hand and fire'),
('about_text', 'Welcome to my pottery studio. I create functional and decorative ceramics using traditional wheel-throwing and hand-building techniques.'),
('contact_email', ''),
('shop_intro', 'Own a piece of handcrafted art. Each pot is one-of-a-kind, and my merch line lets you carry the studio with you.'),
('stripe_publishable_key', 'pk_test_YOUR_KEY'),
('stripe_secret_key', 'sk_test_YOUR_KEY'),
('stripe_webhook_secret', 'whsec_YOUR_SECRET'),
('stripe_shipping_enabled', '1'),
('shop_currency', 'USD'),
('github_client_id', 'YOUR_GITHUB_CLIENT_ID'),
('github_client_secret', 'YOUR_GITHUB_CLIENT_SECRET'),
('allowed_github_users', 'your-github-username'),
('printful_api_key', ''),
('printify_shop_id', '');

-- Default shop categories
INSERT INTO shop_categories (name, slug, type) VALUES
('Original Pots', 'original-pots', 'pot'),
('Mugs', 'mugs', 'pot'),
('Merch', 'merch', 'merch');
