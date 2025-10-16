-- Employee Daily Schedule Table
-- Comprehensive table to track complete employee schedule and status information per date
-- This table will serve as a unified source for both admin and employee calendar views

-- Drop existing table if it exists (for fresh creation)
DROP TABLE IF EXISTS employee_daily_schedules;

-- Main table for employee daily schedule details
CREATE TABLE employee_daily_schedules (
    id INT(11) NOT NULL AUTO_INCREMENT,
    employee_id INT(11) NOT NULL,
    schedule_date DATE NOT NULL,
    
    -- Base Schedule Information
    base_schedule_id INT(11),                    -- Reference to work_schedules table
    base_time_in TIME,                          -- Base scheduled time in
    base_time_out TIME,                         -- Base scheduled time out
    
    -- Actual/Override Schedule Information  
    actual_schedule_id INT(11),                 -- Actual schedule (after overrides)
    actual_time_in TIME,                        -- Actual scheduled time in
    actual_time_out TIME,                       -- Actual scheduled time out
    
    -- Schedule Status & Types
    is_rest_day BOOLEAN DEFAULT 0,             -- Is this a rest day?
    is_holiday BOOLEAN DEFAULT 0,              -- Is this a holiday?
    is_company_holiday BOOLEAN DEFAULT 0,      -- Company-specific holiday
    holiday_name VARCHAR(255),                  -- Name of holiday if applicable
    holiday_type ENUM('regular', 'special', 'company', 'local') DEFAULT NULL,
    
    -- Schedule Override Information
    has_override BOOLEAN DEFAULT 0,            -- Has schedule override for this date
    override_id INT(11),                       -- Reference to calendar_schedule_overrides
    override_type ENUM('schedule_change', 'rest_day', 'overtime', 'holiday', 'leave') DEFAULT NULL,
    override_status ENUM('pending', 'approved', 'rejected') DEFAULT NULL,
    override_reason TEXT,                       -- Reason for override
    override_requested_by INT(11),             -- Employee who requested
    override_approved_by INT(11),              -- Admin who approved
    override_approved_at DATETIME,             -- When approved
    
    -- Attendance/Time Log Information
    has_time_log BOOLEAN DEFAULT 0,           -- Employee has time log for this date
    logged_time_in TIME,                      -- Actual time in from time_logs
    logged_time_out TIME,                     -- Actual time out from time_logs
    time_log_status ENUM('complete', 'incomplete', 'no_log') DEFAULT 'no_log',
    
    -- Attendance Status Calculation
    attendance_status ENUM(
        'present',          -- On time attendance
        'late',            -- Late arrival
        'undertime',       -- Early departure
        'late_undertime',  -- Both late and early departure
        'absent',          -- No attendance record
        'rest_day_work',   -- Working on rest day (overtime)
        'holiday_work',    -- Working on holiday
        'on_leave',        -- On approved leave
        'incomplete'       -- Incomplete time log
    ) DEFAULT 'absent',
    
    -- Leave Information
    is_on_leave BOOLEAN DEFAULT 0,            -- Is employee on leave this date
    leave_type ENUM('sick', 'vacation', 'paternity', 'maternity', 'solo_parent', 'bereavement', 'emergency') DEFAULT NULL,
    leave_request_id INT(11),                 -- Reference to leave_requests table
    leave_status ENUM('approved', 'pending', 'rejected') DEFAULT NULL,
    
    -- Work Hours Calculation
    scheduled_hours DECIMAL(4,2) DEFAULT 0,   -- Scheduled work hours for the day
    actual_hours DECIMAL(4,2) DEFAULT 0,      -- Actual hours worked
    overtime_hours DECIMAL(4,2) DEFAULT 0,    -- Overtime hours
    undertime_hours DECIMAL(4,2) DEFAULT 0,   -- Undertime hours
    late_minutes INT(11) DEFAULT 0,           -- Minutes late
    
    -- Pattern & Assignment Information
    pattern_assignment_id INT(11),            -- Reference to employee_schedule_assignments
    pattern_day_of_cycle INT(11),             -- Day in pattern cycle (1-7 for weekly)
    pattern_name VARCHAR(255),                -- Name of schedule pattern being used
    
    -- Visual & Display Information
    status_color VARCHAR(7) DEFAULT '#9ca3af', -- Hex color for calendar display
    display_priority INT(11) DEFAULT 0,       -- Priority for display (higher = more important)
    notes TEXT,                               -- Additional notes for the day
    
    -- Administrative Information
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT(11),                       -- Who created this record
    updated_by INT(11),                       -- Who last updated this record
    
    -- Indexes
    PRIMARY KEY (id),
    UNIQUE KEY unique_employee_date (employee_id, schedule_date),
    INDEX idx_employee_date (employee_id, schedule_date),
    INDEX idx_schedule_date (schedule_date),
    INDEX idx_attendance_status (attendance_status),
    INDEX idx_holiday_status (is_holiday, is_rest_day),
    INDEX idx_override_status (has_override, override_status),
    INDEX idx_leave_status (is_on_leave, leave_type),
    
    -- Foreign Key Constraints
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (base_schedule_id) REFERENCES work_schedules(id) ON DELETE SET NULL,
    FOREIGN KEY (actual_schedule_id) REFERENCES work_schedules(id) ON DELETE SET NULL,
    FOREIGN KEY (override_id) REFERENCES calendar_schedule_overrides(id) ON DELETE SET NULL,
    FOREIGN KEY (leave_request_id) REFERENCES leave_requests(id) ON DELETE SET NULL,
    FOREIGN KEY (pattern_assignment_id) REFERENCES employee_schedule_assignments(id) ON DELETE SET NULL
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create indexes for performance optimization
CREATE INDEX idx_employee_daily_schedules_composite ON employee_daily_schedules(employee_id, schedule_date, attendance_status);
CREATE INDEX idx_employee_daily_schedules_status ON employee_daily_schedules(attendance_status, is_rest_day, is_holiday);
CREATE INDEX idx_employee_daily_schedules_override ON employee_daily_schedules(has_override, override_status, override_type);

-- Helper view for easy querying with employee information
CREATE OR REPLACE VIEW v_employee_daily_schedule_details AS
SELECT 
    eds.*,
    e.fname,
    e.lname,
    e.email,
    e.position,
    e.company,
    e.official_sched,
    
    -- Base schedule details
    ws_base.schedule_name as base_schedule_name,
    ws_base.start_time as base_start_time,
    ws_base.end_time as base_end_time,
    
    -- Actual schedule details  
    ws_actual.schedule_name as actual_schedule_name,
    ws_actual.start_time as actual_start_time,
    ws_actual.end_time as actual_end_time,
    
    -- Time calculations
    CASE 
        WHEN eds.logged_time_in IS NOT NULL AND eds.actual_time_in IS NOT NULL THEN
            TIMESTAMPDIFF(MINUTE, eds.actual_time_in, eds.logged_time_in)
        ELSE 0 
    END as minutes_late_calculated,
    
    CASE 
        WHEN eds.logged_time_out IS NOT NULL AND eds.actual_time_out IS NOT NULL THEN
            TIMESTAMPDIFF(MINUTE, eds.logged_time_out, eds.actual_time_out)
        ELSE 0 
    END as minutes_undertime_calculated,
    
    -- Status descriptions
    CASE eds.attendance_status
        WHEN 'present' THEN 'Present - On Time'
        WHEN 'late' THEN 'Late Arrival'
        WHEN 'undertime' THEN 'Early Departure'
        WHEN 'late_undertime' THEN 'Late & Early Departure'
        WHEN 'absent' THEN 'Absent'
        WHEN 'rest_day_work' THEN 'Rest Day Work (OT)'
        WHEN 'holiday_work' THEN 'Holiday Work (OT)'
        WHEN 'on_leave' THEN 'On Leave'
        WHEN 'incomplete' THEN 'Incomplete Time Log'
        ELSE 'Unknown'
    END as attendance_status_description

FROM employee_daily_schedules eds
LEFT JOIN employees e ON eds.employee_id = e.id
LEFT JOIN work_schedules ws_base ON eds.base_schedule_id = ws_base.id
LEFT JOIN work_schedules ws_actual ON eds.actual_schedule_id = ws_actual.id
ORDER BY eds.employee_id, eds.schedule_date;

-- Stored procedure to populate/update daily schedule records
DELIMITER $$

CREATE OR REPLACE PROCEDURE PopulateEmployeeDailySchedule(
    IN p_employee_id INT,
    IN p_start_date DATE,
    IN p_end_date DATE
)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE current_date DATE;
    DECLARE date_cursor CURSOR FOR 
        SELECT DATE(p_start_date + INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY) as date_val
        FROM (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS a
        CROSS JOIN (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS b
        CROSS JOIN (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS c
        WHERE DATE(p_start_date + INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY) <= p_end_date;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN date_cursor;
    
    date_loop: LOOP
        FETCH date_cursor INTO current_date;
        IF done THEN
            LEAVE date_loop;
        END IF;
        
        -- Insert or update record for this employee and date
        INSERT INTO employee_daily_schedules (
            employee_id, 
            schedule_date,
            created_by,
            updated_by
        ) VALUES (
            p_employee_id,
            current_date,
            0, -- System generated
            0  -- System generated
        ) ON DUPLICATE KEY UPDATE
            updated_at = CURRENT_TIMESTAMP,
            updated_by = 0;
            
    END LOOP;
    
    CLOSE date_cursor;
    
END$$

DELIMITER ;

-- Function to calculate attendance status
DELIMITER $$

CREATE OR REPLACE FUNCTION CalculateAttendanceStatus(
    p_is_rest_day BOOLEAN,
    p_is_holiday BOOLEAN,
    p_is_on_leave BOOLEAN,
    p_has_time_log BOOLEAN,
    p_logged_time_in TIME,
    p_logged_time_out TIME,
    p_actual_time_in TIME,
    p_actual_time_out TIME,
    p_grace_period_minutes INT
) RETURNS VARCHAR(20)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE result VARCHAR(20);
    DECLARE late_minutes INT;
    DECLARE undertime_minutes INT;
    
    -- Default grace period
    IF p_grace_period_minutes IS NULL THEN
        SET p_grace_period_minutes = 15;
    END IF;
    
    -- Check leave status first
    IF p_is_on_leave THEN
        RETURN 'on_leave';
    END IF;
    
    -- Check if no time log
    IF NOT p_has_time_log THEN
        IF p_is_rest_day THEN
            RETURN 'rest_day';
        ELSEIF p_is_holiday THEN
            RETURN 'holiday';
        ELSE
            RETURN 'absent';
        END IF;
    END IF;
    
    -- Has time log - check if working on rest day/holiday
    IF p_is_rest_day THEN
        RETURN 'rest_day_work';
    ELSEIF p_is_holiday THEN
        RETURN 'holiday_work';
    END IF;
    
    -- Check for incomplete time log
    IF p_logged_time_in IS NULL OR p_logged_time_out IS NULL OR p_logged_time_out = '00:00:00' THEN
        RETURN 'incomplete';
    END IF;
    
    -- Calculate late and undertime minutes
    SET late_minutes = TIMESTAMPDIFF(MINUTE, p_actual_time_in, p_logged_time_in);
    SET undertime_minutes = TIMESTAMPDIFF(MINUTE, p_logged_time_out, p_actual_time_out);
    
    -- Apply grace period
    IF late_minutes <= p_grace_period_minutes THEN
        SET late_minutes = 0;
    END IF;
    
    -- Determine status based on attendance
    IF late_minutes > 0 AND undertime_minutes > 0 THEN
        RETURN 'late_undertime';
    ELSEIF late_minutes > 0 THEN
        RETURN 'late';
    ELSEIF undertime_minutes > 0 THEN
        RETURN 'undertime';
    ELSE
        RETURN 'present';
    END IF;
    
END$$

DELIMITER ;

-- Sample data population for current month
-- Run this to populate sample data for testing
/*
CALL PopulateEmployeeDailySchedule(1, '2025-10-01', '2025-10-31');
CALL PopulateEmployeeDailySchedule(2, '2025-10-01', '2025-10-31');
*/

-- Useful queries for calendar views:

-- Get all employee schedules for a specific date
/*
SELECT * FROM v_employee_daily_schedule_details 
WHERE schedule_date = '2025-10-16' 
ORDER BY fname, lname;
*/

-- Get schedule for specific employee for a month
/*
SELECT * FROM v_employee_daily_schedule_details 
WHERE employee_id = 1 
  AND schedule_date BETWEEN '2025-10-01' AND '2025-10-31'
ORDER BY schedule_date;
*/

-- Get attendance summary by status
/*
SELECT 
    attendance_status,
    COUNT(*) as count,
    GROUP_CONCAT(DISTINCT CONCAT(fname, ' ', lname) SEPARATOR ', ') as employees
FROM v_employee_daily_schedule_details 
WHERE schedule_date = '2025-10-16'
GROUP BY attendance_status;
*/