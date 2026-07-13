-- ============================================================
-- NMS — Nutrition Monitoring System
-- MySQL Schema
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id            INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(100)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    full_name     VARCHAR(200),
    role          VARCHAR(20)   NOT NULL DEFAULT 'encoder',
    barangay      VARCHAR(200),
    permissions   TEXT,
    is_active     TINYINT(1)    NOT NULL DEFAULT 1,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_users_role     ON users(role);
CREATE INDEX idx_users_barangay ON users(barangay);

CREATE TABLE IF NOT EXISTS beneficiaries (
    id                       INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
    last_name                VARCHAR(100)  NOT NULL,
    first_name               VARCHAR(100)  NOT NULL,
    middle_name              VARCHAR(100),
    suffix                   VARCHAR(20),
    date_of_birth            DATE          NOT NULL,
    sex                      VARCHAR(10)   NOT NULL,
    place_of_birth           VARCHAR(200),
    region                   VARCHAR(100),
    province                 VARCHAR(100),
    city_municipality        VARCHAR(100),
    barangay                 VARCHAR(100)  NOT NULL,
    purok_zone               VARCHAR(100),
    household_no             VARCHAR(50),
    incode                   VARCHAR(50),
    mother_name              VARCHAR(200),
    father_name              VARCHAR(200),
    guardian_name            VARCHAR(200),
    guardian_relationship    VARCHAR(100),
    contact_number           VARCHAR(50),
    income_classification    VARCHAR(100),
    household_monthly_income DECIMAL(12,2),
    income_source            VARCHAR(200),
    philhealth_status        VARCHAR(100),
    is_4ps_member            TINYINT(1)    NOT NULL DEFAULT 0,
    nhts_pr_status           VARCHAR(100),
    is_pwd_household         TINYINT(1)    NOT NULL DEFAULT 0,
    is_indigenous_people     TINYINT(1)    NOT NULL DEFAULT 0,
    ip_group                 VARCHAR(100),
    photo                    VARCHAR(255),
    source                   VARCHAR(100),
    validation_status        VARCHAR(20)   NOT NULL DEFAULT 'validated',
    validated_by             INT,
    validated_at             DATETIME,
    rejection_note           TEXT,
    created_by               INT,
    created_at               TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at               TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at               DATETIME,
    FOREIGN KEY (created_by)  REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (validated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_bene_barangay   ON beneficiaries(barangay);
CREATE INDEX idx_bene_name       ON beneficiaries(last_name, first_name);
CREATE INDEX idx_bene_dob        ON beneficiaries(date_of_birth);
CREATE INDEX idx_bene_deleted_at ON beneficiaries(deleted_at);
CREATE INDEX idx_bene_source     ON beneficiaries(source);

CREATE TABLE IF NOT EXISTS assessments (
    id                     INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
    beneficiary_id         INT           NOT NULL,
    assessment_date        DATE          NOT NULL,
    age_in_months          INT           NOT NULL,
    weight_kg              DECIMAL(5,2)  NOT NULL,
    height_cm              DECIMAL(5,2),
    muac_cm                DECIMAL(5,2),
    weight_for_age_zscore  DECIMAL(6,3),
    height_for_age_zscore  DECIMAL(6,3),
    hfa_status             VARCHAR(20),
    wflh_zscore            DECIMAL(6,3),
    wflh_status            VARCHAR(20),
    nutritional_status     VARCHAR(20)   NOT NULL DEFAULT 'Normal',
    period                 VARCHAR(20)   NOT NULL,
    assessment_year        INT           NOT NULL,
    assessed_by            VARCHAR(200),
    remarks                TEXT,
    validation_status      VARCHAR(20)   NOT NULL DEFAULT 'validated',
    validated_by           INT           NULL,
    validated_at           DATETIME      NULL,
    rejection_note         TEXT          NULL,
    created_by             INT,
    created_at             TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by)     REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (validated_by)   REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_assess_bene   ON assessments(beneficiary_id);
CREATE INDEX idx_assess_year   ON assessments(assessment_year, period);
CREATE INDEX idx_assess_status ON assessments(nutritional_status);

CREATE TABLE IF NOT EXISTS program_enrollments (
    id                INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
    beneficiary_id    INT           NOT NULL,
    program           VARCHAR(20)   NOT NULL,
    enrollment_date   DATE          NOT NULL,
    end_date          DATE,
    status            VARCHAR(20)   NOT NULL DEFAULT 'Active',
    cycle_year        INT,
    notes             TEXT,
    intervention_type VARCHAR(100),
    pre_weight_kg     DECIMAL(5,2),
    post_weight_kg    DECIMAL(5,2),
    enrolled_by       INT,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE,
    FOREIGN KEY (enrolled_by)    REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_enroll_bene    ON program_enrollments(beneficiary_id);
CREATE INDEX idx_enroll_program ON program_enrollments(program, status);
CREATE INDEX idx_enroll_year    ON program_enrollments(cycle_year);

CREATE TABLE IF NOT EXISTS vitamin_a_records (
    id                INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
    beneficiary_id    INT           NOT NULL,
    distribution_date DATE          NOT NULL,
    round             VARCHAR(20)   NOT NULL,
    year              INT           NOT NULL,
    dosage_iu         INT           NOT NULL,
    capsule_color     VARCHAR(20)   NOT NULL,
    administered_by   VARCHAR(200),
    created_by        INT,
    created_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by)     REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_vita_bene  ON vitamin_a_records(beneficiary_id);
CREATE INDEX idx_vita_round ON vitamin_a_records(round, year);

CREATE TABLE IF NOT EXISTS mnp_records (
    id                INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
    beneficiary_id    INT           NOT NULL,
    given_by          INT,
    date_given        DATE          NOT NULL,
    year              INT           NOT NULL,
    age_group         VARCHAR(50)   NOT NULL,
    completed_routine TINYINT(1)    NOT NULL DEFAULT 0,
    notes             TEXT,
    created_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE,
    FOREIGN KEY (given_by)       REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_mnp_bene ON mnp_records(beneficiary_id);
CREATE INDEX idx_mnp_year ON mnp_records(year);

CREATE TABLE IF NOT EXISTS lns_sq_records (
    id                INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
    beneficiary_id    INT           NOT NULL,
    given_by          INT,
    date_given        DATE          NOT NULL,
    year              INT           NOT NULL,
    age_group         VARCHAR(50)   NOT NULL,
    completed_routine TINYINT(1)    NOT NULL DEFAULT 0,
    notes             TEXT,
    created_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE,
    FOREIGN KEY (given_by)       REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_lns_bene ON lns_sq_records(beneficiary_id);
CREATE INDEX idx_lns_year ON lns_sq_records(year);

CREATE TABLE IF NOT EXISTS dispensing_records (
    id              INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
    beneficiary_id  INT           NOT NULL,
    enrollment_id   INT,
    program         VARCHAR(50)   NOT NULL,
    supplement_type VARCHAR(100)  NOT NULL,
    quantity        DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit            VARCHAR(50)   NOT NULL DEFAULT 'piece(s)',
    date_dispensed  DATE          NOT NULL,
    dispensed_by    INT,
    notes           TEXT,
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE,
    FOREIGN KEY (enrollment_id)  REFERENCES program_enrollments(id) ON DELETE SET NULL,
    FOREIGN KEY (dispensed_by)   REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_disp_bene ON dispensing_records(beneficiary_id);
CREATE INDEX idx_disp_prog ON dispensing_records(program);
CREATE INDEX idx_disp_date ON dispensing_records(date_dispensed);
CREATE INDEX idx_disp_type ON dispensing_records(supplement_type);

CREATE TABLE IF NOT EXISTS import_logs (
    id               INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
    filename         VARCHAR(255),
    saved_filename   VARCHAR(255),
    imported_by      INT,
    import_date      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_rows       INT           NOT NULL DEFAULT 0,
    success_count    INT           NOT NULL DEFAULT 0,
    error_count      INT           NOT NULL DEFAULT 0,
    error_details    TEXT,
    folder           VARCHAR(100),
    FOREIGN KEY (imported_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS activity_logs (
    id          INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     INT,
    user_name   VARCHAR(200),
    action      VARCHAR(200)  NOT NULL,
    description TEXT,
    ip_address  VARCHAR(45),
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stored_files (
    id                INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
    original_filename VARCHAR(255)  NOT NULL,
    saved_filename    VARCHAR(255)  NOT NULL,
    folder            VARCHAR(100),
    file_size         INT           NOT NULL DEFAULT 0,
    mime_type         VARCHAR(100),
    uploaded_by       INT,
    uploaded_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_sf_folder ON stored_files(folder);

CREATE TABLE IF NOT EXISTS who_growth_standards (
    id               INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
    sex              VARCHAR(10)   NOT NULL,
    age_months       INT           NOT NULL,
    measurement_type VARCHAR(10)   NOT NULL,
    l_value          DECIMAL(10,6) NOT NULL,
    m_value          DECIMAL(10,6) NOT NULL,
    s_value          DECIMAL(10,6) NOT NULL,
    UNIQUE KEY uq_who (sex, age_months, measurement_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_who_lookup ON who_growth_standards(sex, measurement_type, age_months);

CREATE TABLE IF NOT EXISTS programs (
    id          INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(20)   NOT NULL UNIQUE,
    name        VARCHAR(200)  NOT NULL,
    description TEXT,
    icon        VARCHAR(100)  NOT NULL DEFAULT 'bi-clipboard-check',
    color       VARCHAR(50)   NOT NULL DEFAULT 'primary',
    type        VARCHAR(50)   NOT NULL DEFAULT 'generic',
    is_active   TINYINT(1)    NOT NULL DEFAULT 1,
    sort_order  INT           NOT NULL DEFAULT 0,
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_prog_active ON programs(is_active);
CREATE INDEX idx_prog_sort   ON programs(sort_order);

CREATE TABLE IF NOT EXISTS api_tokens (
    id          INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     INT           NOT NULL,
    token       VARCHAR(255)  NOT NULL UNIQUE,
    device_name VARCHAR(200),
    expires_at  DATETIME      NOT NULL,
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_api_token   ON api_tokens(token);
CREATE INDEX idx_api_user    ON api_tokens(user_id);
CREATE INDEX idx_api_expires ON api_tokens(expires_at);

SET FOREIGN_KEY_CHECKS = 1;

-- Seed programs
INSERT IGNORE INTO programs (code, name, description, icon, color, type, sort_order) VALUES
('OPT', 'Operation Timbang',             'Nutritional screening and assessment of children 0-59 months',           'bi-scale',     'success', 'assessment',     1),
('DSP', 'Dietary Supplementation Program','Supplementary feeding and nutrition intervention for malnourished children','bi-egg-fried','warning','supplementation',2),
('MNS', 'Micronutrient Supplementation', 'Vitamin A, MNP, and LNS-SQ distribution for children',                 'bi-capsule',   'primary', 'micronutrient',  3);

-- Default admin: admin / Admin@1234
INSERT IGNORE INTO users (username, password_hash, full_name, role, is_active)
VALUES (
    'admin',
    '$2y$10$lS/ypt1lraAqatclBLxup.zZZ86EsanIYLhRqtFqlyt0MGFRSzzrS',
    'System Administrator',
    'admin',
    1
);
