-- ============================================================
-- Migration 03 — LNS OPT link + defaulter ENUM rename
-- Safe to run multiple times.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Fix 1: Add assessment_id to lns_sq_records (links each LNS record
--         to the OPT assessment that proved the child was wasted/stunted)
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lns_sq_records'
            AND COLUMN_NAME = 'assessment_id');
SET @sql = IF(@col = 0,
    'ALTER TABLE lns_sq_records ADD COLUMN assessment_id INT NULL AFTER beneficiary_id',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lns_sq_records'
           AND CONSTRAINT_NAME = 'fk_lns_assessment');
SET @sql = IF(@fk = 0,
    'ALTER TABLE lns_sq_records ADD CONSTRAINT fk_lns_assessment FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx = (SELECT COUNT(*) FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lns_sq_records'
            AND INDEX_NAME = 'idx_lns_assessment');
SET @sql = IF(@idx = 0,
    'CREATE INDEX idx_lns_assessment ON lns_sq_records(assessment_id)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Fix 2: Rename mns_defaulter_reasons.supplement ENUM values
--         vita_feb → vita_april, vita_aug → vita_oct
--         (rounds happen in April and October, not Feb and Aug)

-- Step 1: expand ENUM to include both old and new values
ALTER TABLE mns_defaulter_reasons
    MODIFY COLUMN supplement ENUM('vita_feb','vita_aug','vita_april','vita_oct','mnp','lns') NOT NULL;

-- Step 2: migrate existing rows
UPDATE mns_defaulter_reasons SET supplement = 'vita_april' WHERE supplement = 'vita_feb';
UPDATE mns_defaulter_reasons SET supplement = 'vita_oct'   WHERE supplement = 'vita_aug';

-- Step 3: remove old values from ENUM
ALTER TABLE mns_defaulter_reasons
    MODIFY COLUMN supplement ENUM('vita_april','vita_oct','mnp','lns') NOT NULL;

SET FOREIGN_KEY_CHECKS = 1;
