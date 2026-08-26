-- ============================================================
-- Migration: Add mobile validation & submission columns
-- Run this on existing NMS installations
-- ============================================================

-- 1. Add bns and midwife roles to users
ALTER TABLE users
    MODIFY COLUMN role ENUM('admin','nutritionist','bhw','bns','midwife','encoder') NOT NULL DEFAULT 'encoder';

-- 2. Add Mobile to beneficiaries.source
ALTER TABLE beneficiaries
    MODIFY COLUMN source ENUM('Walk-in','Excel Import','Mobile') NULL;

-- 3. Add validation/submission columns to beneficiaries
ALTER TABLE beneficiaries
    ADD COLUMN IF NOT EXISTS validation_status ENUM('pending','validated','rejected') NOT NULL DEFAULT 'validated' AFTER source,
    ADD COLUMN IF NOT EXISTS validated_by      INT         NULL AFTER validation_status,
    ADD COLUMN IF NOT EXISTS validated_at      DATETIME    NULL AFTER validated_by,
    ADD COLUMN IF NOT EXISTS rejection_note    TEXT        NULL AFTER validated_at,
    ADD COLUMN IF NOT EXISTS submitted_at      DATETIME    NULL AFTER rejection_note,
    ADD COLUMN IF NOT EXISTS submitted_by      INT         NULL AFTER submitted_at;

ALTER TABLE beneficiaries
    ADD CONSTRAINT fk_bene_validated_by FOREIGN KEY (validated_by) REFERENCES users(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_bene_submitted_by FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE beneficiaries
    ADD INDEX idx_bene_validation (validation_status);

-- 4. Add validation columns to assessments
ALTER TABLE assessments
    ADD COLUMN IF NOT EXISTS validation_status ENUM('pending','validated','rejected') NOT NULL DEFAULT 'validated' AFTER remarks,
    ADD COLUMN IF NOT EXISTS validated_by      INT         NULL AFTER validation_status,
    ADD COLUMN IF NOT EXISTS validated_at      DATETIME    NULL AFTER validated_by,
    ADD COLUMN IF NOT EXISTS rejection_note    TEXT        NULL AFTER validated_at;

ALTER TABLE assessments
    ADD CONSTRAINT fk_assess_validated_by FOREIGN KEY (validated_by) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE assessments
    ADD INDEX idx_assess_validation (validation_status);
