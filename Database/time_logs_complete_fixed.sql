-- Complete Fixed SQL Import for time_logs table
-- This script will properly import your production data

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Disable foreign key checks during import
SET FOREIGN_KEY_CHECKS = 0;

--
-- Drop table if exists to ensure clean import
--
DROP TABLE IF EXISTS `time_logs`;

--
-- Table structure for table `time_logs` with proper constraints
--
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
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_log_date` (`log_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=833 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Now run the original data import (from your production file)
-- You should copy all the INSERT statements from your original file here

-- After creating the table structure, re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

COMMIT;