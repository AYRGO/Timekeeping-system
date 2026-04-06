-- ===================================================================
-- CREATE LEAVE CREDIT AUDIT/HISTORY TABLE
-- Purpose: Track all changes to leave credits for auditing purposes
-- Run this on production ONCE to enable leave credit tracking
-- ===================================================================

-- Create the audit table
CREATE TABLE IF NOT EXISTS `leave_credits_history` (
  `history_id` INT(11) NOT NULL AUTO_INCREMENT,
  `leave_credit_id` INT(11) DEFAULT NULL,
  `employee_id` INT(11) NOT NULL,
  `leave_type` VARCHAR(50) NOT NULL,
  `old_balance` DECIMAL(5,2) DEFAULT NULL,
  `new_balance` DECIMAL(5,2) DEFAULT NULL,
  `balance_change` DECIMAL(5,2) DEFAULT NULL,
  `old_monthly_increment` DECIMAL(5,2) DEFAULT NULL,
  `new_monthly_increment` DECIMAL(5,2) DEFAULT NULL,
  `old_last_processed_month` INT(11) DEFAULT NULL,
  `new_last_processed_month` INT(11) DEFAULT NULL,
  `change_type` VARCHAR(50) DEFAULT NULL COMMENT 'ACCRUAL, DEDUCTION, ADMIN_INCREASE, ADMIN_DECREASE, LEAVE_USED, CARRY_OVER, FORFEITURE, ADJUSTMENT',
  `change_reason` TEXT DEFAULT NULL,
  `changed_by` VARCHAR(100) DEFAULT NULL COMMENT 'System or admin username',
  `changed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `year` INT(11) NOT NULL,
  PRIMARY KEY (`history_id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_leave_type` (`leave_type`),
  KEY `idx_changed_at` (`changed_at`),
  KEY `idx_change_type` (`change_type`),
  KEY `idx_leave_credit_id` (`leave_credit_id`),
  KEY `idx_year` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===================================================================
-- NOTE: The PHP code in employee-edit.php handles audit logging directly
-- This gives better control over change types (ADMIN_INCREASE, ADMIN_DECREASE, etc.)
-- No database trigger needed - the PHP code inserts into leave_credits_history
-- ===================================================================

-- ===================================================================
-- QUERIES TO VIEW AUDIT HISTORY
-- ===================================================================

-- 1. View all changes from yesterday
SELECT 
    h.history_id,
    h.employee_id,
    e.EmployeeName,
    h.leave_type,
    h.old_balance,
    h.new_balance,
    h.balance_change,
    h.change_type,
    h.change_reason,
    h.changed_by,
    h.changed_at
FROM leave_credits_history h
LEFT JOIN employee e ON h.employee_id = e.id
WHERE DATE(h.changed_at) = DATE(NOW() - INTERVAL 1 DAY)
ORDER BY h.changed_at DESC;

-- 2. View accruals from yesterday
SELECT 
    h.employee_id,
    e.EmployeeName,
    h.leave_type,
    h.old_balance,
    h.new_balance,
    h.balance_change,
    h.new_last_processed_month,
    h.changed_at
FROM leave_credits_history h
LEFT JOIN employee e ON h.employee_id = e.id
WHERE DATE(h.changed_at) = DATE(NOW() - INTERVAL 1 DAY)
  AND h.change_type = 'ACCRUAL'
ORDER BY h.changed_at DESC;

-- 3. View history for specific employee
SELECT 
    h.history_id,
    h.leave_type,
    h.old_balance,
    h.new_balance,
    h.balance_change,
    h.change_type,
    h.change_reason,
    h.changed_by,
    h.changed_at
FROM leave_credits_history h
WHERE h.employee_id = ? -- Replace ? with employee_id
  AND h.year = YEAR(NOW())
ORDER BY h.changed_at DESC;

-- 4. Summary of changes by type (last 30 days)
SELECT 
    h.change_type,
    h.leave_type,
    COUNT(*) as total_changes,
    SUM(h.balance_change) as total_balance_change,
    MIN(h.changed_at) as first_change,
    MAX(h.changed_at) as last_change
FROM leave_credits_history h
WHERE h.changed_at >= DATE(NOW() - INTERVAL 30 DAY)
GROUP BY h.change_type, h.leave_type
ORDER BY h.change_type, h.leave_type;

-- 5. Find all accrual operations in a specific month
SELECT 
    h.employee_id,
    e.EmployeeName,
    h.leave_type,
    h.balance_change,
    h.new_last_processed_month,
    h.changed_at
FROM leave_credits_history h
LEFT JOIN employee e ON h.employee_id = e.id
WHERE h.change_type = 'ACCRUAL'
  AND h.new_last_processed_month = MONTH(NOW())
  AND h.year = YEAR(NOW())
ORDER BY h.changed_at DESC;

-- ===================================================================
-- BACKFILL SCRIPT (Optional)
-- Create initial history records for current state
-- Only run this once if you want to capture the current state
-- ===================================================================
/*
INSERT INTO leave_credits_history (
    leave_credit_id,
    employee_id,
    leave_type,
    old_balance,
    new_balance,
    balance_change,
    old_monthly_increment,
    new_monthly_increment,
    old_last_processed_month,
    new_last_processed_month,
    change_type,
    change_reason,
    changed_by,
    changed_at,
    year
)
SELECT 
    lc.id,
    lc.employee_id,
    lc.leave_type,
    0.00,
    lc.balance,
    lc.balance,
    0.00,
    lc.monthly_increment,
    NULL,
    lc.last_processed_month,
    'INITIAL',
    'Initial state captured for audit tracking',
    'SYSTEM',
    COALESCE(lc.updated_at, NOW()),
    lc.year
FROM leave_credits lc
WHERE NOT EXISTS (
    SELECT 1 FROM leave_credits_history h 
    WHERE h.leave_credit_id = lc.id
);
*/

-- ===================================================================
-- INSTRUCTIONS:
-- 1. Run this entire script to create the history table and trigger
-- 2. From now on, all updates to leave_credits will be automatically logged
-- 3. Use the provided queries to view historical changes
-- 4. Optionally run the backfill script (uncomment it) to capture current state
-- ===================================================================
