-- Create activity_logs table for tracking WhatsApp bot actions
-- Run this SQL to add logging functionality

CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `source` varchar(50) DEFAULT 'system',
  PRIMARY KEY (`id`),
  KEY `idx_timestamp` (`timestamp`),
  KEY `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample data
INSERT INTO `activity_logs` (`action`, `details`, `source`) VALUES
('SYSTEM_START', 'WhatsApp Bot system started', 'whatsapp_bot'),
('OT_APPROVED', 'OT #123 approved via WhatsApp Bot - Employee: John Doe, Date: 2025-08-07', 'whatsapp_bot'),
('OT_REJECTED', 'OT #124 rejected via WhatsApp Bot - Employee: Jane Smith, Date: 2025-08-07', 'whatsapp_bot');

-- Add notified column to overtime_requests if it doesn't exist
ALTER TABLE `overtime_requests` 
ADD COLUMN IF NOT EXISTS `notified` tinyint(1) DEFAULT 0 COMMENT 'Whether manager has been notified via WhatsApp';

-- Create index for better performance
ALTER TABLE `overtime_requests` 
ADD INDEX IF NOT EXISTS `idx_status_notified` (`status`, `notified`);

-- Update existing records to set notified = 0 for pending requests
UPDATE `overtime_requests` 
SET `notified` = 0 
WHERE `status` = 'Pending' AND `notified` IS NULL;
