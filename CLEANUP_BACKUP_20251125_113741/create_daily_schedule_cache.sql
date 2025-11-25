-- ============================================================================
-- COMPREHENSIVE DAILY SCHEDULE SYSTEM
-- ============================================================================
-- This creates a pre-computed table that stores each employee's schedule
-- for each day, making it easy to query and display in calendars.
-- ============================================================================

-- Step 1: Create the comprehensive daily schedule table
DROP TABLE IF EXISTS employee_daily_schedule_cache;

CREATE TABLE employee_daily_schedule_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    schedule_date DATE NOT NULL,
    
    -- Schedule information
    work_schedule_id INT NULL,
    is_rest_day TINYINT(1) DEFAULT 0,
    is_holiday TINYINT(1) DEFAULT 0,
    
    -- Source tracking (for debugging/audit)
    source VARCHAR(50) NOT NULL, -- 'approved_request', 'admin_override', 'weekly_default', 'holiday', 'weekend'
    source_id INT NULL, -- Reference to the source record
    
    -- Schedule details (denormalized for performance)
    schedule_name VARCHAR(100) NULL,
    time_in TIME NULL,
    time_out TIME NULL,
    
    -- Holiday information
    holiday_name VARCHAR(200) NULL,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for fast lookup
    UNIQUE KEY unique_employee_date (employee_id, schedule_date),
    INDEX idx_employee (employee_id),
    INDEX idx_date (schedule_date),
    INDEX idx_source (source),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (work_schedule_id) REFERENCES work_schedules(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- Step 2: Create stored procedure to populate the cache
-- ============================================================================

DELIMITER //

DROP PROCEDURE IF EXISTS populate_schedule_cache//

CREATE PROCEDURE populate_schedule_cache(
    IN p_employee_id INT,
    IN p_start_date DATE,
    IN p_end_date DATE
)
BEGIN
    DECLARE v_current_date DATE;
    DECLARE v_day_of_week INT;
    DECLARE v_work_schedule_id INT;
    DECLARE v_is_rest_day TINYINT(1);
    DECLARE v_schedule_name VARCHAR(100);
    DECLARE v_time_in TIME;
    DECLARE v_time_out TIME;
    DECLARE v_source VARCHAR(50);
    DECLARE v_source_id INT;
    DECLARE v_is_holiday TINYINT(1);
    DECLARE v_holiday_name VARCHAR(200);
    
    SET v_current_date = p_start_date;
    
    -- Loop through each date
    WHILE v_current_date <= p_end_date DO
        SET v_day_of_week = DAYOFWEEK(v_current_date) - 1; -- 0=Sunday, 6=Saturday
        SET v_work_schedule_id = NULL;
        SET v_is_rest_day = 0;
        SET v_is_holiday = 0;
        SET v_schedule_name = NULL;
        SET v_time_in = NULL;
        SET v_time_out = NULL;
        SET v_source = 'none';
        SET v_source_id = NULL;
        SET v_holiday_name = NULL;
        
        -- PRIORITY 1: Check for approved schedule change requests
        SELECT pscr.id, pscr.work_schedule_id, ws.name, ws.time_in, ws.time_out
        INTO v_source_id, v_work_schedule_id, v_schedule_name, v_time_in, v_time_out
        FROM post_schedule_change_requests pscr
        LEFT JOIN work_schedules ws ON pscr.work_schedule_id = ws.id
        WHERE pscr.employee_id = p_employee_id
          AND pscr.status = 'Approved'
          AND v_current_date BETWEEN pscr.start_date AND pscr.end_date
        ORDER BY pscr.created_at DESC
        LIMIT 1;
        
        IF v_source_id IS NOT NULL THEN
            IF v_work_schedule_id IS NULL THEN
                SET v_is_rest_day = 1;
            END IF;
            SET v_source = 'approved_request';
        ELSE
            -- PRIORITY 2: Check for admin override
            SELECT eds.id, eds.actual_schedule_id, eds.is_rest_day, ws.name, ws.time_in, ws.time_out
            INTO v_source_id, v_work_schedule_id, v_is_rest_day, v_schedule_name, v_time_in, v_time_out
            FROM employee_daily_schedules eds
            LEFT JOIN work_schedules ws ON eds.actual_schedule_id = ws.id
            WHERE eds.employee_id = p_employee_id
              AND eds.schedule_date = v_current_date
            LIMIT 1;
            
            IF v_source_id IS NOT NULL THEN
                SET v_source = 'admin_override';
            ELSE
                -- PRIORITY 3: Check for weekly default
                SELECT edd.id, edd.work_schedule_id, edd.is_rest_day, ws.name, ws.time_in, ws.time_out
                INTO v_source_id, v_work_schedule_id, v_is_rest_day, v_schedule_name, v_time_in, v_time_out
                FROM employee_default_schedules edd
                LEFT JOIN work_schedules ws ON edd.work_schedule_id = ws.id
                WHERE edd.employee_id = p_employee_id
                  AND edd.day_of_week = v_day_of_week
                  AND edd.effective_from <= v_current_date
                  AND (edd.effective_until IS NULL OR edd.effective_until >= v_current_date)
                LIMIT 1;
                
                IF v_source_id IS NOT NULL THEN
                    SET v_source = 'weekly_default';
                ELSE
                    -- PRIORITY 4: Check for holiday
                    SELECT id, holiday_name
                    INTO v_source_id, v_holiday_name
                    FROM company_holidays
                    WHERE DATE(holiday_date) = v_current_date
                       OR (is_recurring = 1 AND DATE_FORMAT(holiday_date, '%m-%d') = DATE_FORMAT(v_current_date, '%m-%d'))
                    LIMIT 1;
                    
                    IF v_source_id IS NOT NULL THEN
                        SET v_source = 'holiday';
                        SET v_is_holiday = 1;
                        SET v_is_rest_day = 1;
                    ELSE
                        -- PRIORITY 5: Weekend fallback
                        IF v_day_of_week = 0 OR v_day_of_week = 6 THEN
                            SET v_source = 'weekend';
                            SET v_is_rest_day = 1;
                        END IF;
                    END IF;
                END IF;
            END IF;
        END IF;
        
        -- Insert or update the cache
        INSERT INTO employee_daily_schedule_cache (
            employee_id, schedule_date, work_schedule_id, is_rest_day, is_holiday,
            source, source_id, schedule_name, time_in, time_out, holiday_name
        ) VALUES (
            p_employee_id, v_current_date, v_work_schedule_id, v_is_rest_day, v_is_holiday,
            v_source, v_source_id, v_schedule_name, v_time_in, v_time_out, v_holiday_name
        )
        ON DUPLICATE KEY UPDATE
            work_schedule_id = v_work_schedule_id,
            is_rest_day = v_is_rest_day,
            is_holiday = v_is_holiday,
            source = v_source,
            source_id = v_source_id,
            schedule_name = v_schedule_name,
            time_in = v_time_in,
            time_out = v_time_out,
            holiday_name = v_holiday_name;
        
        SET v_current_date = DATE_ADD(v_current_date, INTERVAL 1 DAY);
    END WHILE;
END//

DELIMITER ;

-- ============================================================================
-- Step 3: Create stored procedure to populate for all employees
-- ============================================================================

DELIMITER //

DROP PROCEDURE IF EXISTS populate_all_employees_schedule_cache//

CREATE PROCEDURE populate_all_employees_schedule_cache(
    IN p_start_date DATE,
    IN p_end_date DATE
)
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_employee_id INT;
    DECLARE cur CURSOR FOR SELECT id FROM employees WHERE status = 'Active';
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO v_employee_id;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        CALL populate_schedule_cache(v_employee_id, p_start_date, p_end_date);
    END LOOP;
    
    CLOSE cur;
END//

DELIMITER ;

-- ============================================================================
-- Step 4: Create triggers to auto-update cache when source data changes
-- ============================================================================

-- Trigger: When approved schedule change request is inserted/updated
DELIMITER //

DROP TRIGGER IF EXISTS after_schedule_request_change//

CREATE TRIGGER after_schedule_request_change
AFTER INSERT ON post_schedule_change_requests
FOR EACH ROW
BEGIN
    IF NEW.status = 'Approved' THEN
        CALL populate_schedule_cache(NEW.employee_id, NEW.start_date, NEW.end_date);
    END IF;
END//

DROP TRIGGER IF EXISTS after_schedule_request_update//

CREATE TRIGGER after_schedule_request_update
AFTER UPDATE ON post_schedule_change_requests
FOR EACH ROW
BEGIN
    IF NEW.status = 'Approved' THEN
        CALL populate_schedule_cache(NEW.employee_id, NEW.start_date, NEW.end_date);
    END IF;
END//

-- Trigger: When admin override is inserted/updated/deleted
DROP TRIGGER IF EXISTS after_daily_schedule_insert//

CREATE TRIGGER after_daily_schedule_insert
AFTER INSERT ON employee_daily_schedules
FOR EACH ROW
BEGIN
    CALL populate_schedule_cache(NEW.employee_id, NEW.schedule_date, NEW.schedule_date);
END//

DROP TRIGGER IF EXISTS after_daily_schedule_update//

CREATE TRIGGER after_daily_schedule_update
AFTER UPDATE ON employee_daily_schedules
FOR EACH ROW
BEGIN
    CALL populate_schedule_cache(NEW.employee_id, NEW.schedule_date, NEW.schedule_date);
END//

DROP TRIGGER IF EXISTS after_daily_schedule_delete//

CREATE TRIGGER after_daily_schedule_delete
AFTER DELETE ON employee_daily_schedules
FOR EACH ROW
BEGIN
    CALL populate_schedule_cache(OLD.employee_id, OLD.schedule_date, OLD.schedule_date);
END//

-- Trigger: When weekly default is inserted/updated/deleted
DROP TRIGGER IF EXISTS after_default_schedule_insert//

CREATE TRIGGER after_default_schedule_insert
AFTER INSERT ON employee_default_schedules
FOR EACH ROW
BEGIN
    -- Rebuild cache for next 6 months for this employee
    CALL populate_schedule_cache(NEW.employee_id, NEW.effective_from, DATE_ADD(NEW.effective_from, INTERVAL 6 MONTH));
END//

DROP TRIGGER IF EXISTS after_default_schedule_update//

CREATE TRIGGER after_default_schedule_update
AFTER UPDATE ON employee_default_schedules
FOR EACH ROW
BEGIN
    -- Rebuild cache for next 6 months for this employee
    CALL populate_schedule_cache(NEW.employee_id, NEW.effective_from, DATE_ADD(NEW.effective_from, INTERVAL 6 MONTH));
END//

DELIMITER ;

-- ============================================================================
-- Step 5: Initial population with past, present, and future dates
-- ============================================================================

-- Populate for all active employees from October 2025 onwards to 12 months in the future
CALL populate_all_employees_schedule_cache(
    '2025-10-01',
    DATE_ADD(CURDATE(), INTERVAL 12 MONTH)
);

-- ============================================================================
-- Step 6: Create maintenance event (auto-populate future dates)
-- ============================================================================

-- Enable event scheduler if not already enabled
SET GLOBAL event_scheduler = ON;

-- Create event to populate future dates every day at midnight
DROP EVENT IF EXISTS maintain_schedule_cache;

CREATE EVENT maintain_schedule_cache
ON SCHEDULE EVERY 1 DAY
STARTS (CURRENT_DATE + INTERVAL 1 DAY)
DO
    CALL populate_all_employees_schedule_cache(
        CURDATE(),
        DATE_ADD(CURDATE(), INTERVAL 12 MONTH)
    );

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================

-- Check cache statistics
SELECT 
    source,
    COUNT(*) as total_days,
    COUNT(DISTINCT employee_id) as employees,
    MIN(schedule_date) as earliest_date,
    MAX(schedule_date) as latest_date
FROM employee_daily_schedule_cache
GROUP BY source;

-- Check a specific employee's schedule
SELECT 
    schedule_date,
    DAYNAME(schedule_date) as day_name,
    schedule_name,
    CONCAT(TIME_FORMAT(time_in, '%h:%i %p'), ' - ', TIME_FORMAT(time_out, '%h:%i %p')) as schedule_time,
    CASE 
        WHEN is_holiday = 1 THEN CONCAT('HOLIDAY: ', holiday_name)
        WHEN is_rest_day = 1 THEN 'REST DAY'
        ELSE 'WORK DAY'
    END as day_type,
    source
FROM employee_daily_schedule_cache
WHERE employee_id = 1
  AND schedule_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)
ORDER BY schedule_date;

-- ============================================================================
-- HELPER: Manual refresh for specific employee and date range
-- ============================================================================
-- CALL populate_schedule_cache(1, '2025-10-01', '2025-12-31');

-- ============================================================================
-- HELPER: Full rebuild of cache
-- ============================================================================
-- TRUNCATE employee_daily_schedule_cache;
-- CALL populate_all_employees_schedule_cache(DATE_SUB(CURDATE(), INTERVAL 6 MONTH), DATE_ADD(CURDATE(), INTERVAL 12 MONTH));
