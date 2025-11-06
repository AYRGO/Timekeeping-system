-- Create system_settings table for auto-accrual configuration
-- Make sure you're in the 'rss' database or change 'rss' to your database name
USE `rss`;

CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default auto-accrual setting (disabled by default)
INSERT INTO `system_settings` (`setting_key`, `setting_value`) 
VALUES ('auto_accrual_enabled', '0')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- Add last_auto_accrual tracking
INSERT INTO `system_settings` (`setting_key`, `setting_value`) 
VALUES ('last_auto_accrual_month', '0')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
