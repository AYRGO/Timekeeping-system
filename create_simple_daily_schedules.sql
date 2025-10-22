-- ============================================================================
-- SIMPLIFIED EMPLOYEE DAILY SCHEDULE TABLE - PRACTICAL VERSION
-- This stores the actual working schedule for each employee each day
-- ============================================================================

DROP TABLE IF EXISTS employee_daily_schedules_simple;

CREATE TABLE employee_daily_schedules_simple (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    employee_id INT(11) NOT NULL,
    schedule_date DATE NOT NULL,
    
    -- Schedule Information
    work_schedule_id INT(11) NULL,
    time_in TIME NULL,
    time_out TIME NULL,
    
    -- Status Flags
    is_rest_day TINYINT(1) DEFAULT 0 COMMENT '1 if day off',
    is_override TINYINT(1) DEFAULT 0 COMMENT '1 if overridden from default',
    schedule_type ENUM('default', 'override', 'rest_day') DEFAULT 'default',
    
    -- Reference & Details
    override_request_id INT(11) NULL,
    reason TEXT NULL,
    day_of_week VARCHAR(10) NULL,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    UNIQUE KEY unique_employee_date (employee_id, schedule_date),
    INDEX idx_employee (employee_id),
    INDEX idx_date (schedule_date),
    INDEX idx_type (schedule_type),
    
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (work_schedule_id) REFERENCES work_schedules(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- POPULATE THE TABLE
-- ============================================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS populate_daily_schedules$$

CREATE PROCEDURE populate_daily_schedules(
    IN start_date DATE,
    IN end_date DATE
)
BEGIN
    DECLARE current_date DATE;
    SET current_date = start_date;
    
    WHILE current_date <= end_date DO
        INSERT INTO employee_daily_schedules_simple (
            employee_id, schedule_date, work_schedule_id, time_in, time_out,
            is_rest_day, is_override, schedule_type, override_request_id, 
            reason, day_of_week
        )
        SELECT 
            e.id,
            current_date,
            CASE WHEN pscr.is_rest_day = 1 THEN NULL
                 WHEN pscr.work_schedule_id IS NOT NULL THEN pscr.work_schedule_id
                 ELSE e.official_sched END,
            CASE WHEN pscr.is_rest_day = 1 THEN NULL
                 WHEN pscr.work_schedule_id IS NOT NULL THEN ws_o.time_in
                 ELSE ws_d.time_in END,
            CASE WHEN pscr.is_rest_day = 1 THEN NULL
                 WHEN pscr.work_schedule_id IS NOT NULL THEN ws_o.time_out
                 ELSE ws_d.time_out END,
            CASE WHEN pscr.is_rest_day = 1 THEN 1 ELSE 0 END,
            CASE WHEN pscr.id IS NOT NULL AND pscr.is_rest_day = 0 THEN 1 ELSE 0 END,
            CASE WHEN pscr.is_rest_day = 1 THEN 'rest_day'
                 WHEN pscr.id IS NOT NULL THEN 'override'
                 ELSE 'default' END,
            pscr.id,
            pscr.reason,
            DAYNAME(current_date)
        FROM employees e
        LEFT JOIN work_schedules ws_d ON e.official_sched = ws_d.id
        LEFT JOIN post_schedule_change_requests pscr 
            ON e.id = pscr.employee_id 
            AND current_date BETWEEN pscr.start_date AND pscr.end_date
            AND pscr.status = 'Approved'
        LEFT JOIN work_schedules ws_o ON pscr.work_schedule_id = ws_o.id
        WHERE e.status = 'active'
        ON DUPLICATE KEY UPDATE
            work_schedule_id = VALUES(work_schedule_id),
            time_in = VALUES(time_in),
            time_out = VALUES(time_out),
            is_rest_day = VALUES(is_rest_day),
            is_override = VALUES(is_override),
            schedule_type = VALUES(schedule_type),
            override_request_id = VALUES(override_request_id),
            reason = VALUES(reason),
            day_of_week = VALUES(day_of_week),
            updated_at = CURRENT_TIMESTAMP;
        
        SET current_date = DATE_ADD(current_date, INTERVAL 1 DAY);
    END WHILE;
END$$

DELIMITER ;

-- ============================================================================
-- POPULATE FOR OCTOBER 2025 TO JANUARY 2026
-- ============================================================================

CALL populate_daily_schedules('2025-10-01', '2026-01-31');

-- ============================================================================
-- USEFUL QUERIES
-- ============================================================================

-- 1. Today's schedule for all employees
SELECT 
    e.id,
    CONCAT(e.fname, ' ', e.lname) AS name,
    eds.day_of_week,
    eds.schedule_type,
    CASE WHEN eds.is_rest_day = 1 THEN 'Day Off' 
         ELSE CONCAT(eds.time_in, ' - ', eds.time_out) END AS schedule
FROM employee_daily_schedules_simple eds
JOIN employees e ON eds.employee_id = e.id
WHERE eds.schedule_date = CURDATE();

-- 2. Specific employee's month schedule
SELECT 
    schedule_date,
    day_of_week,
    schedule_type,
    CASE WHEN is_rest_day = 1 THEN 'Day Off' 
         ELSE CONCAT(time_in, ' - ', time_out) END AS schedule,
    reason
FROM employee_daily_schedules_simple
WHERE employee_id = 77 
  AND schedule_date BETWEEN '2025-10-01' AND '2025-10-31';

-- 3. All rest days in October
SELECT 
    CONCAT(e.fname, ' ', e.lname) AS employee,
    eds.schedule_date,
    eds.day_of_week,
    eds.reason
FROM employee_daily_schedules_simple eds
JOIN employees e ON eds.employee_id = e.id
WHERE eds.is_rest_day = 1
  AND eds.schedule_date BETWEEN '2025-10-01' AND '2025-10-31';

-- 4. All overrides in October
SELECT 
    CONCAT(e.fname, ' ', e.lname) AS employee,
    eds.schedule_date,
    CONCAT(eds.time_in, ' - ', eds.time_out) AS override_schedule,
    eds.reason
FROM employee_daily_schedules_simple eds
JOIN employees e ON eds.employee_id = e.id
WHERE eds.is_override = 1
  AND eds.schedule_date BETWEEN '2025-10-01' AND '2025-10-31';

-- 5. Schedule summary by type
SELECT 
    schedule_date,
    schedule_type,
    COUNT(*) as count
FROM employee_daily_schedules_simple
WHERE schedule_date BETWEEN '2025-10-01' AND '2025-10-31'
GROUP BY schedule_date, schedule_type;

