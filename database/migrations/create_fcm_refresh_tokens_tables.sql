use vconn;

-- Create user_fcm_tokens table
CREATE TABLE IF NOT EXISTS `user_fcm_tokens` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `fcm_token` VARCHAR(255) NOT NULL,
  `device_type` VARCHAR(50) DEFAULT NULL COMMENT 'android, ios, web',
  `device_id` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_user_device` (`user_id`, `fcm_token`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_active` (`is_active`),
  INDEX `idx_fcm_token` (`fcm_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create refresh_tokens table
CREATE TABLE IF NOT EXISTS `refresh_tokens` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `token` VARCHAR(255) NOT NULL UNIQUE,
  `expires_at` TIMESTAMP NOT NULL,
  `is_revoked` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `revoked_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_token` (`token`),
  INDEX `idx_expires_at` (`expires_at`),
  INDEX `idx_is_revoked` (`is_revoked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create notification_logs table
CREATE TABLE IF NOT EXISTS `notification_logs` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `ticket_id` INT DEFAULT NULL,
  `notification_type` VARCHAR(50) DEFAULT NULL,
  `title` VARCHAR(255) DEFAULT NULL,
  `message` TEXT DEFAULT NULL,
  `fcm_token` VARCHAR(255) DEFAULT NULL,
  `status` VARCHAR(20) DEFAULT NULL COMMENT 'sent, failed, token_invalid',
  `error_message` TEXT DEFAULT NULL,
  `fcm_message_id` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_ticket_id` (`ticket_id`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
