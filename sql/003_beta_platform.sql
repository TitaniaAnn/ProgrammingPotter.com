-- 003_beta_platform.sql — Add platform tracking to beta_users
ALTER TABLE beta_users
    ADD COLUMN platform ENUM('android','ios','both','other') NOT NULL DEFAULT 'other'
    AFTER approved;
