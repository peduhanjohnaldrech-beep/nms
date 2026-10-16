-- Performance indexes migration
-- Run once on production: mysql -u root nms < database/add_performance_indexes.sql

-- Beneficiary validation_status — used in nearly every query
CREATE INDEX IF NOT EXISTS idx_bene_validation
    ON beneficiaries(validation_status);

-- Assessment correlated subquery index (beneficiary_id, validation_status, assessment_date DESC)
-- Speeds up the follow-up query and any "latest assessment per beneficiary" lookup
CREATE INDEX IF NOT EXISTS idx_assess_bene_valid_date
    ON assessments(beneficiary_id, validation_status, assessment_date);

-- Assessment date standalone — used in date-range report queries
CREATE INDEX IF NOT EXISTS idx_assess_date
    ON assessments(assessment_date);

-- Program enrollments — composite for DSP follow-up query (program + status + enrollment_date)
CREATE INDEX IF NOT EXISTS idx_enroll_dsp_active
    ON program_enrollments(program, status, enrollment_date);

-- Dispensing records — date + program composite for monthly log queries
CREATE INDEX IF NOT EXISTS idx_disp_date_prog
    ON dispensing_records(date_dispensed, program);

-- API tokens — expiry check optimization
CREATE INDEX IF NOT EXISTS idx_api_token_expiry
    ON api_tokens(token, expires_at);
