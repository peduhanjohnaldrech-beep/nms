-- 2FA (TOTP) support for admin accounts
ALTER TABLE users
    ADD COLUMN totp_secret  VARCHAR(64)  NULL DEFAULT NULL AFTER is_active,
    ADD COLUMN totp_enabled TINYINT(1)   NOT NULL DEFAULT 0 AFTER totp_secret;
