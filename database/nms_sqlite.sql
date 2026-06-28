-- ============================================================
-- NMS — Nutrition Monitoring System
-- SQLite Schema
-- ============================================================

PRAGMA foreign_keys = OFF;

CREATE TABLE IF NOT EXISTS users (
    id            INTEGER      PRIMARY KEY AUTOINCREMENT,
    username      TEXT         UNIQUE NOT NULL,
    password_hash TEXT         NOT NULL,
    full_name     TEXT,
    role          TEXT         NOT NULL DEFAULT 'encoder' CHECK(role IN ('admin','nutritionist','bhw','encoder')),
    barangay      TEXT,
    is_active     INTEGER      NOT NULL DEFAULT 1,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_users_role     ON users(role);
CREATE INDEX IF NOT EXISTS idx_users_barangay ON users(barangay);

CREATE TABLE IF NOT EXISTS beneficiaries (
    id                       INTEGER       PRIMARY KEY AUTOINCREMENT,
    last_name                TEXT          NOT NULL,
    first_name               TEXT          NOT NULL,
    middle_name              TEXT,
    suffix                   TEXT,
    date_of_birth            DATE          NOT NULL,
    sex                      TEXT          NOT NULL CHECK(sex IN ('Male','Female')),
    place_of_birth           TEXT,
    region                   TEXT,
    province                 TEXT,
    city_municipality        TEXT,
    barangay                 TEXT          NOT NULL,
    purok_zone               TEXT,
    household_no             TEXT,
    incode                   TEXT,
    mother_name              TEXT,
    father_name              TEXT,
    guardian_name            TEXT,
    guardian_relationship    TEXT,
    contact_number           TEXT,
    income_classification    TEXT,
    household_monthly_income NUMERIC,
    income_source            TEXT,
    philhealth_status        TEXT,
    is_4ps_member            INTEGER       NOT NULL DEFAULT 0,
    nhts_pr_status           TEXT,
    is_pwd_household         INTEGER       NOT NULL DEFAULT 0,
    is_indigenous_people     INTEGER       NOT NULL DEFAULT 0,
    ip_group                 TEXT,
    photo                    TEXT,
    source                   TEXT,
    created_by               INTEGER,
    created_at               TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at               TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at               DATETIME,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_bene_barangay   ON beneficiaries(barangay);
CREATE INDEX IF NOT EXISTS idx_bene_name       ON beneficiaries(last_name, first_name);
CREATE INDEX IF NOT EXISTS idx_bene_dob        ON beneficiaries(date_of_birth);
CREATE INDEX IF NOT EXISTS idx_bene_deleted_at ON beneficiaries(deleted_at);
CREATE INDEX IF NOT EXISTS idx_bene_source     ON beneficiaries(source);

CREATE TABLE IF NOT EXISTS assessments (
    id                     INTEGER      PRIMARY KEY AUTOINCREMENT,
    beneficiary_id         INTEGER      NOT NULL,
    assessment_date        DATE         NOT NULL,
    age_in_months          INTEGER      NOT NULL,
    weight_kg              NUMERIC      NOT NULL,
    height_cm              NUMERIC,
    muac_cm                NUMERIC,
    weight_for_age_zscore  NUMERIC,
    height_for_age_zscore  NUMERIC,
    hfa_status             TEXT         CHECK(hfa_status IN ('SSt','St','Normal')),
    wflh_zscore            NUMERIC,
    wflh_status            TEXT         CHECK(wflh_status IN ('SW','MW','Normal','OW','OB')),
    nutritional_status     TEXT         NOT NULL DEFAULT 'Normal' CHECK(nutritional_status IN ('SUW','UW','Normal','OW','OB')),
    period                 TEXT         NOT NULL CHECK(period IN ('January','July')),
    assessment_year        INTEGER      NOT NULL,
    assessed_by            TEXT,
    remarks                TEXT,
    created_by             INTEGER,
    created_at             TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by)     REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_assess_bene   ON assessments(beneficiary_id);
CREATE INDEX IF NOT EXISTS idx_assess_year   ON assessments(assessment_year, period);
CREATE INDEX IF NOT EXISTS idx_assess_status ON assessments(nutritional_status);

CREATE TABLE IF NOT EXISTS program_enrollments (
    id                INTEGER      PRIMARY KEY AUTOINCREMENT,
    beneficiary_id    INTEGER      NOT NULL,
    program           TEXT         NOT NULL CHECK(program IN ('OPT','DSP','MNS')),
    enrollment_date   DATE         NOT NULL,
    end_date          DATE,
    status            TEXT         NOT NULL DEFAULT 'Active' CHECK(status IN ('Active','Completed','Dropped')),
    cycle_year        INTEGER,
    notes             TEXT,
    intervention_type TEXT         CHECK(intervention_type IN ('RUSF','RUTF','Health Education','Supplementary Feeding')),
    pre_weight_kg     NUMERIC,
    post_weight_kg    NUMERIC,
    enrolled_by       INTEGER,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE,
    FOREIGN KEY (enrolled_by)    REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_enroll_bene    ON program_enrollments(beneficiary_id);
CREATE INDEX IF NOT EXISTS idx_enroll_program ON program_enrollments(program, status);
CREATE INDEX IF NOT EXISTS idx_enroll_year    ON program_enrollments(cycle_year);

CREATE TABLE IF NOT EXISTS vitamin_a_records (
    id                INTEGER      PRIMARY KEY AUTOINCREMENT,
    beneficiary_id    INTEGER      NOT NULL,
    distribution_date DATE         NOT NULL,
    round             TEXT         NOT NULL CHECK(round IN ('February','August')),
    year              INTEGER      NOT NULL,
    dosage_iu         INTEGER      NOT NULL,
    capsule_color     TEXT         NOT NULL CHECK(capsule_color IN ('Blue','Red')),
    administered_by   TEXT,
    created_by        INTEGER,
    created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by)     REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_vita_bene  ON vitamin_a_records(beneficiary_id);
CREATE INDEX IF NOT EXISTS idx_vita_round ON vitamin_a_records(round, year);

CREATE TABLE IF NOT EXISTS mnp_records (
    id                INTEGER      PRIMARY KEY AUTOINCREMENT,
    beneficiary_id    INTEGER      NOT NULL,
    given_by          INTEGER,
    date_given        DATE         NOT NULL,
    year              INTEGER      NOT NULL,
    age_group         TEXT         NOT NULL CHECK(age_group IN ('6-11 months','12-23 months','24-59 months')),
    completed_routine INTEGER      NOT NULL DEFAULT 0,
    notes             TEXT,
    created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE,
    FOREIGN KEY (given_by)       REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_mnp_bene ON mnp_records(beneficiary_id);
CREATE INDEX IF NOT EXISTS idx_mnp_year ON mnp_records(year);

CREATE TABLE IF NOT EXISTS lns_sq_records (
    id                INTEGER      PRIMARY KEY AUTOINCREMENT,
    beneficiary_id    INTEGER      NOT NULL,
    given_by          INTEGER,
    date_given        DATE         NOT NULL,
    year              INTEGER      NOT NULL,
    age_group         TEXT         NOT NULL CHECK(age_group IN ('6-11 months','12-23 months')),
    completed_routine INTEGER      NOT NULL DEFAULT 0,
    notes             TEXT,
    created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE,
    FOREIGN KEY (given_by)       REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_lns_bene ON lns_sq_records(beneficiary_id);
CREATE INDEX IF NOT EXISTS idx_lns_year ON lns_sq_records(year);

CREATE TABLE IF NOT EXISTS import_logs (
    id               INTEGER      PRIMARY KEY AUTOINCREMENT,
    filename         TEXT,
    saved_filename   TEXT,
    imported_by      INTEGER,
    import_date      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_rows       INTEGER      NOT NULL DEFAULT 0,
    success_count    INTEGER      NOT NULL DEFAULT 0,
    error_count      INTEGER      NOT NULL DEFAULT 0,
    error_details    TEXT,
    folder           TEXT,
    FOREIGN KEY (imported_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS activity_logs (
    id          INTEGER   PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER,
    user_name   TEXT,
    action      TEXT      NOT NULL,
    description TEXT,
    ip_address  TEXT,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS stored_files (
    id                INTEGER      PRIMARY KEY AUTOINCREMENT,
    original_filename TEXT         NOT NULL,
    saved_filename    TEXT         NOT NULL,
    folder            TEXT,
    file_size         INTEGER      NOT NULL DEFAULT 0,
    mime_type         TEXT,
    uploaded_by       INTEGER,
    uploaded_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_sf_folder ON stored_files(folder);

CREATE TABLE IF NOT EXISTS who_growth_standards (
    id               INTEGER      PRIMARY KEY AUTOINCREMENT,
    sex              TEXT         NOT NULL CHECK(sex IN ('Male','Female')),
    age_months       INTEGER      NOT NULL,
    measurement_type TEXT         NOT NULL CHECK(measurement_type IN ('WFA','HFA')),
    l_value          NUMERIC      NOT NULL,
    m_value          NUMERIC      NOT NULL,
    s_value          NUMERIC      NOT NULL,
    UNIQUE(sex, age_months, measurement_type)
);

CREATE INDEX IF NOT EXISTS idx_who_lookup ON who_growth_standards(sex, measurement_type, age_months);

CREATE TABLE IF NOT EXISTS dispensing_records (
    id              INTEGER       PRIMARY KEY AUTOINCREMENT,
    beneficiary_id  INTEGER       NOT NULL,
    enrollment_id   INTEGER,
    program         TEXT          NOT NULL,
    supplement_type TEXT          NOT NULL,
    quantity        NUMERIC       NOT NULL DEFAULT 1,
    unit            TEXT          NOT NULL DEFAULT 'piece(s)',
    date_dispensed  DATE          NOT NULL,
    dispensed_by    INTEGER,
    notes           TEXT,
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE,
    FOREIGN KEY (enrollment_id)  REFERENCES program_enrollments(id) ON DELETE SET NULL,
    FOREIGN KEY (dispensed_by)   REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_disp_bene ON dispensing_records(beneficiary_id);
CREATE INDEX IF NOT EXISTS idx_disp_prog ON dispensing_records(program);
CREATE INDEX IF NOT EXISTS idx_disp_date ON dispensing_records(date_dispensed);
CREATE INDEX IF NOT EXISTS idx_disp_type ON dispensing_records(supplement_type);

CREATE TABLE IF NOT EXISTS programs (
    id          INTEGER      PRIMARY KEY AUTOINCREMENT,
    code        TEXT         UNIQUE NOT NULL,
    name        TEXT         NOT NULL,
    description TEXT,
    icon        TEXT         NOT NULL DEFAULT 'bi-clipboard-check',
    color       TEXT         NOT NULL DEFAULT 'primary',
    type        TEXT         NOT NULL DEFAULT 'generic' CHECK(type IN ('assessment','supplementation','micronutrient','generic')),
    is_active   INTEGER      NOT NULL DEFAULT 1,
    sort_order  INTEGER      NOT NULL DEFAULT 0,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_prog_active ON programs(is_active);
CREATE INDEX IF NOT EXISTS idx_prog_sort   ON programs(sort_order);

PRAGMA foreign_keys = ON;

-- Seed programs
INSERT OR IGNORE INTO programs (code, name, description, icon, color, type, sort_order) VALUES
('OPT', 'Operation Timbang',             'Nutritional screening and assessment of children 0-59 months',          'bi-scale',     'success', 'assessment',     1),
('DSP', 'Dietary Supplementation Program','Supplementary feeding and nutrition intervention for malnourished children','bi-egg-fried','warning','supplementation',2),
('MNS', 'Micronutrient Supplementation', 'Vitamin A, MNP, and LNS-SQ distribution for children',                'bi-capsule',   'primary', 'micronutrient',  3);

-- Default admin: admin / Admin@1234
INSERT OR IGNORE INTO users (username, password_hash, full_name, role, is_active)
VALUES (
    'admin',
    '$2y$10$lS/ypt1lraAqatclBLxup.zZZ86EsanIYLhRqtFqlyt0MGFRSzzrS',
    'System Administrator',
    'admin',
    1
);
