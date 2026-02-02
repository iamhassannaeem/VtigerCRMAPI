use vconn;

-- Add is_read and updated_at fields to notification_logs table
ALTER TABLE `notification_logs` 
ADD COLUMN `is_read` TINYINT(1) DEFAULT 0 AFTER `status`,
ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
ADD INDEX `idx_is_read` (`is_read`);
