-- ============================================================
-- NMS — Nutrition Monitoring System
-- Complete Database Setup
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS nms_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE nms_db;

CREATE TABLE IF NOT EXISTS users (
    id            INT          PRIMARY KEY AUTO_INCREMENT,
    username      VARCHAR(50)  UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(100),
    role          ENUM('admin','nutritionist','bhw','encoder') NOT NULL DEFAULT 'encoder',
    barangay      VARCHAR(100),
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_role     (role),
    INDEX idx_users_barangay (barangay)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS beneficiaries (
    id                       INT           PRIMARY KEY AUTO_INCREMENT,
    last_name                VARCHAR(50)   NOT NULL,
    first_name               VARCHAR(50)   NOT NULL,
    middle_name              VARCHAR(50),
    suffix                   VARCHAR(10),
    date_of_birth            DATE          NOT NULL,
    sex                      ENUM('Male','Female') NOT NULL,
    place_of_birth           VARCHAR(100),
    region                   VARCHAR(100),
    province                 VARCHAR(100),
    city_municipality        VARCHAR(100),
    barangay                 VARCHAR(100)  NOT NULL,
    purok_zone               VARCHAR(50),
    household_no             VARCHAR(30),
    incode                   VARCHAR(20),
    mother_name              VARCHAR(100),
    father_name              VARCHAR(100),
    guardian_name            VARCHAR(100),
    guardian_relationship    VARCHAR(50),
    contact_number           VARCHAR(20),
    income_classification    ENUM('Poor','Near Poor','Non-Poor'),
    household_monthly_income DECIMAL(10,2),
    income_source            VARCHAR(100),
    philhealth_status        ENUM('Member','Indigent','Non-member'),
    is_4ps_member            TINYINT(1)    NOT NULL DEFAULT 0,
    nhts_pr_status           ENUM('Poor','Not Poor'),
    is_pwd_household         TINYINT(1)    NOT NULL DEFAULT 0,
    is_indigenous_people     TINYINT(1)    NOT NULL DEFAULT 0,
    ip_group                 VARCHAR(100),
    source                   ENUM('Walk-in','Excel Import') NULL,
    created_by               INT,
    created_at               TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at               TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at               DATETIME,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_bene_barangay   (barangay),
    INDEX idx_bene_name       (last_name, first_name),
    INDEX idx_bene_dob        (date_of_birth),
    INDEX idx_bene_deleted_at (deleted_at),
    INDEX idx_bene_source     (source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assessments (
    id                     INT          PRIMARY KEY AUTO_INCREMENT,
    beneficiary_id         INT          NOT NULL,
    assessment_date        DATE         NOT NULL,
    age_in_months          INT          NOT NULL,
    weight_kg              DECIMAL(5,2) NOT NULL,
    height_cm              DECIMAL(5,2),
    muac_cm                DECIMAL(4,2),
    weight_for_age_zscore  DECIMAL(6,3),
    height_for_age_zscore  DECIMAL(6,3),
    hfa_status             ENUM('SSt','St','Normal') NULL,
    wflh_zscore            DECIMAL(6,3) NULL,
    wflh_status            ENUM('SW','MW','Normal','OW','OB') NULL,
    nutritional_status     ENUM('SUW','UW','Normal','OW','OB') NOT NULL DEFAULT 'Normal',
    period                 ENUM('January','July') NOT NULL,
    assessment_year        YEAR         NOT NULL,
    assessed_by            VARCHAR(100),
    remarks                TEXT,
    created_by             INT,
    created_at             TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by)     REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_assess_bene   (beneficiary_id),
    INDEX idx_assess_year   (assessment_year, period),
    INDEX idx_assess_status (nutritional_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS program_enrollments (
    id                INT       PRIMARY KEY AUTO_INCREMENT,
    beneficiary_id    INT       NOT NULL,
    program           ENUM('OPT','DSP','MNS') NOT NULL,
    enrollment_date   DATE      NOT NULL,
    end_date          DATE,
    status            ENUM('Active','Completed','Dropped') NOT NULL DEFAULT 'Active',
    cycle_year        YEAR,
    notes             TEXT,
    intervention_type ENUM('RUSF','RUTF','Health Education','Supplementary Feeding') NULL,
    pre_weight_kg     DECIMAL(5,2) NULL,
    post_weight_kg    DECIMAL(5,2) NULL,
    enrolled_by       INT,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE,
    FOREIGN KEY (enrolled_by)    REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_enroll_bene    (beneficiary_id),
    INDEX idx_enroll_program (program, status),
    INDEX idx_enroll_year    (cycle_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vitamin_a_records (
    id                INT       PRIMARY KEY AUTO_INCREMENT,
    beneficiary_id    INT       NOT NULL,
    distribution_date DATE      NOT NULL,
    round             ENUM('February','August') NOT NULL,
    year              YEAR      NOT NULL,
    dosage_iu         INT       NOT NULL,
    capsule_color     ENUM('Blue','Red') NOT NULL,
    administered_by   VARCHAR(100),
    created_by        INT,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by)     REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_vita_bene  (beneficiary_id),
    INDEX idx_vita_round (round, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mnp_records (
    id                INT          PRIMARY KEY AUTO_INCREMENT,
    beneficiary_id    INT          NOT NULL,
    given_by          INT,
    date_given        DATE         NOT NULL,
    year              YEAR         NOT NULL,
    age_group         ENUM('6-11 months','12-23 months','24-59 months') NOT NULL,
    completed_routine TINYINT(1)   NOT NULL DEFAULT 0,
    notes             TEXT,
    created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE,
    FOREIGN KEY (given_by)       REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_mnp_bene (beneficiary_id),
    INDEX idx_mnp_year (year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lns_sq_records (
    id                INT          PRIMARY KEY AUTO_INCREMENT,
    beneficiary_id    INT          NOT NULL,
    given_by          INT,
    date_given        DATE         NOT NULL,
    year              YEAR         NOT NULL,
    age_group         ENUM('6-11 months','12-23 months') NOT NULL,
    completed_routine TINYINT(1)   NOT NULL DEFAULT 0,
    notes             TEXT,
    created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE,
    FOREIGN KEY (given_by)       REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_lns_bene (beneficiary_id),
    INDEX idx_lns_year (year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS import_logs (
    id               INT          PRIMARY KEY AUTO_INCREMENT,
    filename         VARCHAR(255),
    saved_filename   VARCHAR(255),
    imported_by      INT,
    import_date      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_rows       INT          NOT NULL DEFAULT 0,
    success_count    INT          NOT NULL DEFAULT 0,
    error_count      INT          NOT NULL DEFAULT 0,
    error_details    JSON,
    FOREIGN KEY (imported_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stored_files (
    id                INT          PRIMARY KEY AUTO_INCREMENT,
    original_filename VARCHAR(255) NOT NULL,
    saved_filename    VARCHAR(255) NOT NULL,
    folder            VARCHAR(100) NULL,
    file_size         INT          NOT NULL DEFAULT 0,
    mime_type         VARCHAR(100),
    uploaded_by       INT,
    uploaded_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_sf_folder (folder)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS who_growth_standards (
    id               INT          PRIMARY KEY AUTO_INCREMENT,
    sex              ENUM('Male','Female') NOT NULL,
    age_months       TINYINT UNSIGNED NOT NULL,
    measurement_type ENUM('WFA','HFA') NOT NULL,
    l_value          DECIMAL(10,6) NOT NULL,
    m_value          DECIMAL(10,6) NOT NULL,
    s_value          DECIMAL(10,6) NOT NULL,
    UNIQUE KEY uk_who_lms (sex, age_months, measurement_type),
    INDEX idx_who_lookup (sex, measurement_type, age_months)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Default admin: admin / Admin@1234
INSERT INTO users (username, password_hash, full_name, role, is_active)
VALUES (
    'admin',
    '$2y$10$Ej8R.pXNJUjJfLZs7FUoHOjzWxPdXVxLAi6gJQIwXjXLQ2j3R.Nqy',
    'System Administrator',
    'admin',
    1
)
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), role = VALUES(role), is_active = VALUES(is_active);
