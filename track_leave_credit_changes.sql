-- ===================================================================
-- LEAVE CREDITS CHANGE TRACKING QUERIES
-- Purpose: Track and audit leave credit changes, especially accruals
-- Database: u816220874_calendartype
-- ===================================================================

-- 1. CHECK CURRENT LEAVE CREDITS STATUS (2026)
-- Shows current balance and last update time for all employees
SELECT 
    lc.employee_id,
    CONCAT(e.fname, ' ', e.lname) as employee_name,
    lc.leave_type,
    lc.balance,
    lc.monthly_increment,
    lc.carry_over,
    lc.updated_at,
    lc.year,
    DATEDIFF(NOW(), lc.updated_at) as days_since_update
FROM leave_credits lc
LEFT JOIN employees e ON lc.employee_id = e.id
WHERE lc.year = YEAR(NOW())
ORDER BY lc.updated_at DESC, lc.employee_id, lc.leave_type;

-- ===================================================================
-- 2. CHECK YESTERDAY'S UPDATES
-- Shows all leave credits that were updated yesterday
-- ===================================================================
SELECT 
    lc.employee_id,
    CONCAT(e.fname, ' ', e.lname) as employee_name,
    lc.leave_type,
    lc.balance,
    lc.monthly_increment,
    lc.updated_at,
    DATE(lc.updated_at) as update_date
FROM leave_credits lc
LEFT JOIN employees e ON lc.employee_id = e.id
WHERE DATE(lc.updated_at) = DATE(NOW() - INTERVAL 1 DAY)
  AND lc.year = YEAR(NOW())
ORDER BY lc.updated_at DESC, lc.employee_id, lc.leave_type;

-- ===================================================================
-- 3. CHECK SPECIFIC DATE UPDATES (Change the date as needed)
-- ===================================================================
SELECT 
    lc.employee_id,
    CONCAT(e.fname, ' ', e.lname) as employee_name,
    lc.leave_type,
    lc.balance,
    lc.monthly_increment,
    lc.updated_at
FROM leave_credits lc
LEFT JOIN employees e ON lc.employee_id = e.id
WHERE DATE(lc.updated_at) = '2026-03-31'  -- Change this date
  AND lc.year = YEAR(NOW())
ORDER BY lc.employee_id, lc.leave_type;

-- ===================================================================
-- 4. VERIFY MONTHLY ACCRUALS WERE APPLIED
-- Shows employees with monthly_increment and when last updated
-- ===================================================================
SELECT 
    e.id as employee_id,
    CONCAT(e.fname, ' ', e.lname) as employee_name,
    lc.leave_type,
    lc.balance,
    lc.monthly_increment,
    lc.updated_at,
    MONTH(lc.updated_at) as last_update_month,
    MONTH(NOW()) as current_month,
    CASE 
        WHEN lc.updated_at IS NULL THEN 'Never Processed'
        WHEN MONTH(lc.updated_at) < MONTH(NOW()) AND YEAR(lc.updated_at) <= YEAR(NOW()) THEN 'Needs Processing'
        WHEN MONTH(lc.updated_at) = MONTH(NOW()) AND YEAR(lc.updated_at) = YEAR(NOW()) THEN 'Already Processed This Month'
        ELSE 'Unknown'
    END as accrual_status
FROM employees e
JOIN leave_credits lc ON e.id = lc.employee_id
WHERE lc.monthly_increment > 0
  AND lc.year = YEAR(NOW())
  AND e.status = 'active'
ORDER BY accrual_status, employee_name, lc.leave_type;

-- ===================================================================
-- 5. EMPLOYEES WITH RECENT BALANCE CHANGES (Last 7 days)
-- ===================================================================
SELECT 
    lc.employee_id,
    CONCAT(e.fname, ' ', e.lname) as employee_name,
    lc.leave_type,
    lc.balance,
    lc.updated_at,
    DATE(lc.updated_at) as update_date
FROM leave_credits lc
LEFT JOIN employees e ON lc.employee_id = e.id
WHERE lc.updated_at >= DATE(NOW() - INTERVAL 7 DAY)
  AND lc.year = YEAR(NOW())
ORDER BY lc.updated_at DESC;

-- ===================================================================
-- 6. COMPARE BALANCES: Current vs Expected (if accrual was applied)
-- Helps identify if yesterday's accrual was missed
-- ===================================================================
SELECT 
    lc.employee_id,
    CONCAT(e.fname, ' ', e.lname) as employee_name,
    lc.leave_type,
    lc.balance as current_balance,
    lc.monthly_increment,
    (lc.balance - lc.monthly_increment) as balance_before_accrual,
    lc.updated_at,
    CASE 
        WHEN MONTH(lc.updated_at) = MONTH(NOW()) AND YEAR(lc.updated_at) = YEAR(NOW())
        THEN 'Accrual Applied This Month'
        ELSE 'Accrual NOT Applied Yet'
    END as accrual_status
FROM leave_credits lc
LEFT JOIN employees e ON lc.employee_id = e.id
WHERE lc.monthly_increment > 0
  AND lc.year = YEAR(NOW())
  AND e.status = 'active'
ORDER BY accrual_status DESC, employee_name;

-- ===================================================================
-- 7. COUNT EMPLOYEES BY ACCRUAL STATUS
-- Quick summary to see if accruals ran
-- ===================================================================
SELECT 
    lc.leave_type,
    COUNT(DISTINCT lc.employee_id) as total_employees,
    SUM(CASE WHEN MONTH(lc.updated_at) = MONTH(NOW()) AND YEAR(lc.updated_at) = YEAR(NOW()) THEN 1 ELSE 0 END) as processed_this_month,
    SUM(CASE WHEN MONTH(lc.updated_at) < MONTH(NOW()) OR lc.updated_at IS NULL THEN 1 ELSE 0 END) as not_processed_yet,
    MAX(lc.updated_at) as latest_update
FROM leave_credits lc
JOIN employees e ON lc.employee_id = e.id
WHERE lc.monthly_increment > 0
  AND lc.year = YEAR(NOW())
  AND e.status = 'active'
GROUP BY lc.leave_type
ORDER BY lc.leave_type;

-- ===================================================================
-- 8. EMPLOYEES WHO RECEIVED ACCRUAL IN THE LAST 24 HOURS
-- ===================================================================
SELECT 
    lc.employee_id,
    CONCAT(e.fname, ' ', e.lname) as employee_name,
    lc.leave_type,
    lc.balance,
    lc.monthly_increment,
    lc.updated_at,
    TIMESTAMPDIFF(HOUR, lc.updated_at, NOW()) as hours_ago
FROM leave_credits lc
LEFT JOIN employees e ON lc.employee_id = e.id
WHERE lc.updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
  AND lc.monthly_increment > 0
  AND lc.year = YEAR(NOW())
ORDER BY lc.updated_at DESC;

-- ===================================================================
-- 9. AUDIT: Check for suspicious changes
-- (Large balance changes or multiple updates on same day)
-- ===================================================================
SELECT 
    lc.employee_id,
    CONCAT(e.fname, ' ', e.lname) as employee_name,
    lc.leave_type,
    lc.balance,
    lc.monthly_increment,
    lc.updated_at,
    CASE 
        WHEN lc.balance < 0 THEN 'NEGATIVE BALANCE'
        WHEN lc.balance > 15 AND lc.leave_type IN ('sick', 'vacation') THEN 'HIGH BALANCE'
        ELSE 'NORMAL'
    END as alert
FROM leave_credits lc
LEFT JOIN employees e ON lc.employee_id = e.id
WHERE lc.year = YEAR(NOW())
  AND (lc.balance < 0 OR lc.balance > 15)
  AND lc.leave_type IN ('sick', 'vacation')
ORDER BY alert DESC, lc.balance DESC;

-- ===================================================================
-- 10. DETAILED ACCRUAL STATUS BY EMPLOYEE
-- Shows which employees need accrual processing
-- ===================================================================
SELECT 
    e.id,
    CONCAT(e.fname, ' ', e.lname) as employee_name,
    e.position,
    e.status,
    GROUP_CONCAT(
        CONCAT(lc.leave_type, ': ', lc.balance, ' (inc: ', COALESCE(lc.monthly_increment, 0), ')')
        SEPARATOR ' | '
    ) as leave_credits_summary
FROM employees e
LEFT JOIN leave_credits lc ON e.id = lc.employee_id AND lc.year = YEAR(NOW())
WHERE e.status = 'active'
GROUP BY e.id, employee_name, e.position, e.status
ORDER BY employee_name;

-- ===================================================================
-- INSTRUCTIONS:
-- 1. Run query #2 to check if any records were updated yesterday
-- 2. Run query #7 to see summary counts of processed vs not processed
-- 3. Run query #8 to see records updated in last 24 hours
-- 4. Run query #4 to identify who still needs processing
-- 
-- If you don't see yesterday's updates, accruals may not have run!
-- ===================================================================
