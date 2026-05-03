-- 010_consolidation.sql
-- Brings an existing prod database up to the canonical schema in init.sql.
-- Run BEFORE deploying the matching code (Auth.php expects provider_user_id).
--
--     mysql -u <user> -p <db> < sql/010_consolidation.sql
--
-- Idempotency note: ALTER ... ADD INDEX has no IF NOT EXISTS in older MySQL
-- versions. If a re-run errors with "Duplicate key name", the index already
-- exists — safe to ignore that specific error.

-- C7: rename admin_users.google_id → admin_users.provider_user_id
ALTER TABLE admin_users
    CHANGE COLUMN google_id provider_user_id VARCHAR(255) NOT NULL;

-- H2: indexes used by hot lookups
ALTER TABLE orders        ADD INDEX idx_status (status);
ALTER TABLE orders        ADD INDEX idx_payment_intent (stripe_payment_intent);
ALTER TABLE orders        ADD INDEX idx_customer_email (customer_email);
ALTER TABLE pottery       ADD INDEX idx_featured (featured);
ALTER TABLE pottery       ADD INDEX idx_sort_order (sort_order);
ALTER TABLE pottery_images ADD INDEX idx_pottery_sort (pottery_id, sort_order);
ALTER TABLE social_posts  ADD INDEX idx_featured (featured);
