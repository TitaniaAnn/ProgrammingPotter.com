-- 012_schema_migrations.sql
-- Adds the migration ledger that the admin migration runner reads/writes.
-- The runner also calls CREATE TABLE IF NOT EXISTS on first use, so this
-- file is only here for parity with init.sql and to give the runner a
-- well-known migration to "mark applied" when reconciling the ledger
-- with a database that already has every prior migration baked in.

CREATE TABLE IF NOT EXISTS schema_migrations (
    version     VARCHAR(255) PRIMARY KEY,
    applied_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    applied_by  INT NULL,
    source      ENUM('run','mark') NOT NULL DEFAULT 'run',
    notes       TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
