-- Medicine & Supplement Dispensing Tracker
-- Run this against the nms_db database

USE nms_db;

CREATE TABLE IF NOT EXISTS dispensing_records (
    id              INT           PRIMARY KEY AUTO_INCREMENT,
    beneficiary_id  INT           NOT NULL,
    enrollment_id   INT           NULL,
    program         VARCHAR(20)   NOT NULL COMMENT 'OPT, DSP, MNS, General',
    supplement_type VARCHAR(80)   NOT NULL COMMENT 'RUSF, RUTF, Iron, Deworming, Zinc, Vit A, MNP, LNS-SQ, Other',
    quantity        DECIMAL(8,2)  NOT NULL DEFAULT 1,
    unit            VARCHAR(30)   NOT NULL DEFAULT 'piece(s)' COMMENT 'sachet, capsule, pack, tablet, ml, piece(s)',
    date_dispensed  DATE          NOT NULL,
    dispensed_by    INT           NULL,
    notes           TEXT,
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE,
    FOREIGN KEY (enrollment_id)  REFERENCES program_enrollments(id) ON DELETE SET NULL,
    FOREIGN KEY (dispensed_by)   REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_disp_bene   (beneficiary_id),
    INDEX idx_disp_prog   (program),
    INDEX idx_disp_date   (date_dispensed),
    INDEX idx_disp_type   (supplement_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
