-- DSP Monitoring Table
-- Tracks monthly follow-up measurements for DSP-enrolled children.
-- Separate from `assessments` (OPT/annual baseline).

CREATE TABLE IF NOT EXISTS dsp_monitoring (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    local_id         VARCHAR(36)    DEFAULT NULL,
    enrollment_id    INT            NOT NULL,
    beneficiary_id   INT            NOT NULL,
    monitoring_date  DATE           NOT NULL,
    age_in_months    INT            DEFAULT NULL,
    weight_kg        DECIMAL(5,2)   NOT NULL,
    height_cm        DECIMAL(5,2)   DEFAULT NULL,
    muac_cm          DECIMAL(4,2)   NOT NULL,
    weight_for_age_zscore DECIMAL(6,3) DEFAULT NULL,
    wflh_zscore      DECIMAL(6,3)   DEFAULT NULL,
    nutritional_status VARCHAR(20)  DEFAULT NULL,
    wflh_status      VARCHAR(10)    DEFAULT NULL,
    monitored_by     VARCHAR(100)   DEFAULT NULL,
    remarks          TEXT           DEFAULT NULL,
    validation_status ENUM('pending','validated','rejected') NOT NULL DEFAULT 'pending',
    validated_by     INT            DEFAULT NULL,
    validated_at     DATETIME       DEFAULT NULL,
    created_by       INT            DEFAULT NULL,
    created_at       TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_dm_enrollment  (enrollment_id),
    INDEX idx_dm_beneficiary (beneficiary_id),
    INDEX idx_dm_date        (monitoring_date),
    CONSTRAINT fk_dm_enrollment  FOREIGN KEY (enrollment_id)  REFERENCES program_enrollments(id) ON DELETE CASCADE,
    CONSTRAINT fk_dm_beneficiary FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id)
);
