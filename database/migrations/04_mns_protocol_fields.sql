-- ============================================================
-- Migration 04 — MNS protocol compliance fields
-- Safe to run multiple times.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. LNS monthly weight tracking
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lns_sq_records' AND COLUMN_NAME = 'weight_kg');
SET @sql = IF(@col = 0,
    'ALTER TABLE lns_sq_records ADD COLUMN weight_kg DECIMAL(5,2) NULL AFTER sachets_distributed',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. MNP counseling confirmation
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mnp_records' AND COLUMN_NAME = 'counseling_given');
SET @sql = IF(@col = 0,
    'ALTER TABLE mnp_records ADD COLUMN counseling_given TINYINT(1) NOT NULL DEFAULT 0 AFTER completed_routine',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. Vitamin A delivery mode
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vitamin_a_records' AND COLUMN_NAME = 'delivery_mode');
SET @sql = IF(@col = 0,
    "ALTER TABLE vitamin_a_records ADD COLUMN delivery_mode VARCHAR(30) NOT NULL DEFAULT 'Health Post' AFTER administered_by",
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. LNS recoveries table
CREATE TABLE IF NOT EXISTS lns_recoveries (
    id             INT      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    beneficiary_id INT      NOT NULL,
    year           INT      NOT NULL,
    recovery_date  DATE     NOT NULL,
    recorded_by    INT      NULL,
    notes          TEXT     NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by)    REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uq_lns_recovery (beneficiary_id, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
