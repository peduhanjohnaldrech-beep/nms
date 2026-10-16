-- Add sachets_distributed tracking for MNP and LNS-SQ
ALTER TABLE mnp_records
    ADD COLUMN sachets_distributed INT DEFAULT NULL AFTER completed_routine;

ALTER TABLE lns_sq_records
    ADD COLUMN sachets_distributed INT DEFAULT NULL AFTER completed_routine;
