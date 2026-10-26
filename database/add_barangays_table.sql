-- Migration: Create barangays master table
-- Run this once on the server: mysql -u nmsuser -p nms < database/add_barangays_table.sql

CREATE TABLE IF NOT EXISTS barangays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_barangay_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed from config/barangays.php
INSERT IGNORE INTO barangays (name) VALUES
('Barangay 1'),
('Barangay 2'),
('Barangay 3'),
('Barangay 4'),
('Barangay 5'),
('Barangay 6'),
('Barangay 7'),
('Barangay 8'),
('Barangay 9'),
('Bantayan'),
('Binicuil'),
('Camansi'),
('Camingawan'),
('Camugao'),
('Carol-an'),
('Daan Banua'),
('Hilamonan'),
('Inapoy'),
('Linao'),
('Locotan'),
('Magballo'),
('Oringao'),
('Orong'),
('Pinaguinpinan'),
('Salong'),
('Tabugon'),
('Tagoc'),
('Tagukon'),
('Talubangig'),
('Tampalon'),
('Tan-Awan'),
('Tapi');
