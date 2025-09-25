-- Clean import script for time_logs table
-- Run this first to ensure clean import

-- Drop table if exists (safer approach)
DROP TABLE IF EXISTS `time_logs`;

-- Create the table structure
CREATE TABLE `time_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) DEFAULT NULL,
  `log_date` date DEFAULT NULL,
  `log_out_date` date DEFAULT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `is_late_in` tinyint(1) DEFAULT 0,
  `is_early_out` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','incomplete','completed') DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Reset AUTO_INCREMENT to start from 1 or desired value
ALTER TABLE `time_logs` AUTO_INCREMENT = 1;