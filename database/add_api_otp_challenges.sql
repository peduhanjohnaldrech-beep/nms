-- OTP challenges for mobile admin login
CREATE TABLE IF NOT EXISTS `api_otp_challenges` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `user_id`    INT(11)      NOT NULL,
    `otp_token`  VARCHAR(64)  NOT NULL,
    `otp_code`   VARCHAR(6)   NOT NULL,
    `expires_at` DATETIME     NOT NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `otp_token` (`otp_token`),
    CONSTRAINT `api_otp_challenges_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
