-- Create dispensing_records table
CREATE TABLE IF NOT EXISTS `dispensing_records` (
  `id`              INT(11)        NOT NULL AUTO_INCREMENT,
  `beneficiary_id`  INT(11)        NOT NULL,
  `enrollment_id`   INT(11)        DEFAULT NULL,
  `program`         VARCHAR(50)    NOT NULL,
  `supplement_type` VARCHAR(100)   NOT NULL,
  `quantity`        DECIMAL(10,2)  NOT NULL DEFAULT 1.00,
  `unit`            VARCHAR(50)    NOT NULL DEFAULT 'piece(s)',
  `date_dispensed`  DATE           NOT NULL,
  `dispensed_by`    INT(11)        DEFAULT NULL,
  `notes`           TEXT           DEFAULT NULL,
  `created_at`      TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `enrollment_id`   (`enrollment_id`),
  KEY `dispensed_by`    (`dispensed_by`),
  KEY `idx_disp_bene`   (`beneficiary_id`),
  KEY `idx_disp_prog`   (`program`),
  KEY `idx_disp_date`   (`date_dispensed`),
  KEY `idx_disp_type`   (`supplement_type`),
  CONSTRAINT `dispensing_records_ibfk_1` FOREIGN KEY (`beneficiary_id`) REFERENCES `beneficiaries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dispensing_records_ibfk_2` FOREIGN KEY (`enrollment_id`)  REFERENCES `program_enrollments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dispensing_records_ibfk_3` FOREIGN KEY (`dispensed_by`)   REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
