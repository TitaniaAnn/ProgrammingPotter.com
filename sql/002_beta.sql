-- 002_beta.sql — Beta Testing Portal
-- Run this against your database to add the beta testing tables.

CREATE TABLE IF NOT EXISTS beta_users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name          VARCHAR(255) NOT NULL,
    approved      TINYINT(1) DEFAULT 1,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login    TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS beta_feedback (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    user_id             INT NOT NULL,
    type                ENUM('bug', 'feature') NOT NULL,
    title               VARCHAR(255) NOT NULL,
    body                TEXT NOT NULL,
    github_issue_number INT NULL,
    github_issue_url    TEXT NULL,
    status              ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES beta_users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
