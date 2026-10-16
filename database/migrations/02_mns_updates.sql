-- ============================================================
-- Migration 02 — MNS feature additions
-- Run this on any existing database that was initialized
-- before these tables/columns were added.
-- Safe to run multiple times (IF NOT EXISTS / IF NOT EXISTS).
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Defaulter reasons table
CREATE TABLE IF NOT EXISTS mns_defaulter_reasons (
    id             INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
    beneficiary_id INT           NOT NULL,
    year           INT           NOT NULL,
    supplement     ENUM('vita_feb','vita_aug','mnp','lns') NOT NULL,
    reason         VARCHAR(200)  NOT NULL,
    noted_by       INT,
    noted_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE,
    FOREIGN KEY (noted_by)       REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uq_defaulter_reason (beneficiary_id, year, supplement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Distribution completions table
CREATE TABLE IF NOT EXISTS mns_distribution_completions (
    id           INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id      INT           NOT NULL,
    barangay     VARCHAR(100)  NOT NULL,
    year         INT           NOT NULL,
    supplement   ENUM('mnp','lnssq') NOT NULL,
    note         TEXT,
    completed_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_dist_completion (user_id, year, supplement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. dose_number column on vitamin_a_records
ALTER TABLE vitamin_a_records
    ADD COLUMN IF NOT EXISTS dose_number TINYINT DEFAULT NULL;

-- 4. batch_number column on mnp_records
ALTER TABLE mnp_records
    ADD COLUMN IF NOT EXISTS batch_number TINYINT NOT NULL DEFAULT 1;

SET FOREIGN_KEY_CHECKS = 1;
