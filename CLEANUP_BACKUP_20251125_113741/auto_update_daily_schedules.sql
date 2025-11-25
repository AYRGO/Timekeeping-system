-- ============================================================================
-- AUTO-UPDATE TRIGGERS FOR employee_daily_schedules_simple
-- These triggers automatically update the schedule table when changes occur
-- ============================================================================

-- 1. TRIGGER: When a schedule change request is APPROVED
-- Updates all affected dates in employee_daily_schedules_simple

DELIMITER $$

DROP TRIGGER IF EXISTS after_schedule_approved$$

CREATE TRIGGER after_schedule_approved
AFTER INSERT ON post_schedule_change_requests
FOR EACH ROW
BEGIN
    DECLARE current_date DATE;
    
    -- Only process if status is Approved
    IF NEW.status = 'Approved' THEN
        SET current_date = NEW.start_date;
        
        -- Loop through each date in the range
        WHILE current_date <= NEW.end_date DO
            
            -- Insert or update the daily schedule
            INSERT INTO employee_daily_schedules_simple (
                employee_id, 
                schedule_date, 
                work_schedule_id, 
                time_in, 
                time_out,
                is_rest_day, 
                is_override, 
                schedule_type, 
                override_request_id, 
                reason, 
                day_of_week
            )
            SELECT 
                NEW.employee_id,
                current_date,
                CASE WHEN NEW.is_rest_day = 1 THEN NULL ELSE NEW.work_schedule_id END,
                CASE WHEN NEW.is_rest_day = 1 THEN NULL ELSE ws.time_in END,
                CASE WHEN NEW.is_rest_day = 1 THEN NULL ELSE ws.time_out END,
                NEW.is_rest_day,
                CASE WHEN NEW.is_rest_day = 1 THEN 0 ELSE 1 END,
                CASE WHEN NEW.is_rest_day = 1 THEN 'rest_day' ELSE 'override' END,
                NEW.id,
                NEW.reason,
                DAYNAME(current_date)
            FROM work_schedules ws
            WHERE ws.id = NEW.work_schedule_id
            LIMIT 1
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
    END IF;
END$$

DELIMITER ;

-- ============================================================================
-- 2. STORED PROCEDURE: Refresh specific date range for an employee
-- ============================================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS refresh_employee_schedule$$

CREATE PROCEDURE refresh_employee_schedule(
    IN p_employee_id INT,
    IN p_start_date DATE,
    IN p_end_date DATE
)
BEGIN
    DECLARE current_date DATE;
    SET current_date = p_start_date;
    
    WHILE current_date <= p_end_date DO
        
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
        WHERE e.id = p_employee_id
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
-- 3. STORED PROCEDURE: Refresh all employees for a specific date
-- ============================================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS refresh_all_schedules_for_date$$

CREATE PROCEDURE refresh_all_schedules_for_date(
    IN p_date DATE
)
BEGIN
    INSERT INTO employee_daily_schedules_simple (
        employee_id, schedule_date, work_schedule_id, time_in, time_out,
        is_rest_day, is_override, schedule_type, override_request_id, 
        reason, day_of_week
    )
    SELECT 
        e.id,
        p_date,
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
        DAYNAME(p_date)
    FROM employees e
    LEFT JOIN work_schedules ws_d ON e.official_sched = ws_d.id
    LEFT JOIN post_schedule_change_requests pscr 
        ON e.id = pscr.employee_id 
        AND p_date BETWEEN pscr.start_date AND pscr.end_date
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
END$$

DELIMITER ;

-- ============================================================================
-- USAGE EXAMPLES:
-- ============================================================================

-- After approving a schedule change manually, refresh that employee's affected dates:
-- CALL refresh_employee_schedule(77, '2025-10-27', '2025-10-31');

-- Refresh all employees for a specific date:
-- CALL refresh_all_schedules_for_date('2025-10-25');

-- ============================================================================
-- TEST THE TRIGGER:
-- ============================================================================

-- Insert a test approved schedule change and see if it auto-updates:
-- INSERT INTO post_schedule_change_requests (employee_id, start_date, end_date, work_schedule_id, status, reason)
-- VALUES (1, '2025-10-30', '2025-10-30', 5, 'Approved', 'Test auto-update');

-- Then check if employee_daily_schedules_simple was updated:
-- SELECT * FROM employee_daily_schedules_simple WHERE employee_id = 1 AND schedule_date = '2025-10-30';

-- ============================================================================
