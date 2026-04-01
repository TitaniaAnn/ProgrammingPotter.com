-- Events table for shows, sales, and classes
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('show', 'sale', 'class') NOT NULL,
    status ENUM('draft', 'published', 'cancelled') DEFAULT 'draft',
    start_date DATE NOT NULL,
    end_date DATE NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    location VARCHAR(255),
    address TEXT,
    max_attendees INT NULL,
    registration_required TINYINT(1) DEFAULT 0,
    registration_url TEXT NULL,
    website_url TEXT NULL,
    featured_image TEXT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Event pieces (for linking pottery to events)
CREATE TABLE IF NOT EXISTS event_pieces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    pottery_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (pottery_id) REFERENCES pottery(id) ON DELETE CASCADE,
    UNIQUE KEY unique_event_piece (event_id, pottery_id)
);