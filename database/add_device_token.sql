-- Migration: Add device_token to users table for trusted-device admin restriction
-- Run once: mysql -u nmsuser -pNmsAdmin@2026 nms < database/add_device_token.sql

ALTER TABLE users ADD COLUMN IF NOT EXISTS device_token VARCHAR(64) NULL DEFAULT NULL;
