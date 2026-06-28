USE nms_db;

CREATE TABLE IF NOT EXISTS programs (
    id          INT          PRIMARY KEY AUTO_INCREMENT,
    code        VARCHAR(20)  UNIQUE NOT NULL,
    name        VARCHAR(100) NOT NULL,
    description TEXT,
    icon        VARCHAR(50)  NOT NULL DEFAULT 'bi-clipboard-check',
    color       VARCHAR(20)  NOT NULL DEFAULT 'primary',
    type        ENUM('assessment','supplementation','micronutrient','generic') NOT NULL DEFAULT 'generic',
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    sort_order  INT          NOT NULL DEFAULT 0,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_prog_active (is_active),
    INDEX idx_prog_sort   (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO programs (code, name, description, icon, color, type, sort_order) VALUES
('OPT', 'Operation Timbang',           'Nutritional screening and assessment of children 0–59 months',          'bi-scale',     'success', 'assessment',    1),
('DSP', 'Dietary Supplementation Program', 'Supplementary feeding and nutrition intervention for malnourished children', 'bi-egg-fried', 'warning', 'supplementation', 2),
('MNS', 'Micronutrient Supplementation', 'Vitamin A, MNP, and LNS-SQ distribution for children',              'bi-capsule',   'primary', 'micronutrient', 3);
