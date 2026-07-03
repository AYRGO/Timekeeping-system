-- Deployment pre-check/migration for the leave credit edit history feature.
-- Run this on production before deploying code if you are not sure the table exists.
-- It is intentionally schema-only: it does not insert or overwrite employee data.

CREATE TABLE IF NOT EXISTS `leave_credits_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `leave_credit_id` int(11) DEFAULT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type` varchar(50) NOT NULL,
  `old_balance` decimal(5,2) DEFAULT NULL,
  `new_balance` decimal(5,2) DEFAULT NULL,
  `balance_change` decimal(5,2) DEFAULT NULL,
  `old_monthly_increment` decimal(5,2) DEFAULT NULL,
  `new_monthly_increment` decimal(5,2) DEFAULT NULL,
  `old_last_processed_month` int(11) DEFAULT NULL,
  `new_last_processed_month` int(11) DEFAULT NULL,
  `change_type` varchar(50) DEFAULT NULL COMMENT 'ACCRUAL, DEDUCTION, ADMIN_INCREASE, ADMIN_DECREASE, LEAVE_USED, CARRY_OVER, FORFEITURE, ADJUSTMENT',
  `change_reason` text DEFAULT NULL,
  `changed_by` varchar(100) DEFAULT NULL COMMENT 'System or admin username',
  `changed_at` datetime DEFAULT current_timestamp(),
  `year` int(11) NOT NULL,
  PRIMARY KEY (`history_id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_leave_type` (`leave_type`),
  KEY `idx_changed_at` (`changed_at`),
  KEY `idx_change_type` (`change_type`),
  KEY `idx_leave_credit_id` (`leave_credit_id`),
  KEY `idx_year` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `leave_credits_history`
  ADD COLUMN IF NOT EXISTS `change_reason` text DEFAULT NULL AFTER `change_type`;
