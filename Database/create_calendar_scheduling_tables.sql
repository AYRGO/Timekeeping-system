-- ===================================================================
-- CALENDAR-TYPE SCHEDULING SYSTEM - DATABASE SCHEMA
-- ===================================================================
-- Created: 2025-10-16
-- Purpose: Store employee schedules per date without affecting existing
--          schedule_change_requests table
-- ===================================================================

-- 1. Main table: Daily schedule assignments for each employee
-- This is the primary table that stores what schedule an employee has on any given date
CREATE TABLE IF NOT EXISTS employee_daily_schedules (
    id INT(11) NOT NULL AUTO_INCREMENT,
    employee_id INT(11) NOT NULL,
    schedule_date DATE NOT NULL,
    work_schedule_id INT(11) NULL COMMENT 'References work_schedules table for time_in/time_out',
    schedule_type ENUM('regular', 'override', 'rest_day', 'holiday', 'leave', 'overtime') NOT NULL DEFAULT 'regular',
    is_rest_day TINYINT(1) DEFAULT 0,
    is_holiday TINYINT(1) DEFAULT 0,
    notes TEXT NULL COMMENT 'Additional notes about this schedule entry',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT(11) NULL COMMENT 'Admin user who created/modified this entry',
    PRIMARY KEY (id),
    UNIQUE KEY unique_employee_date (employee_id, schedule_date),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (work_schedule_id) REFERENCES work_schedules(id) ON DELETE SET NULL,
    INDEX idx_employee_date (employee_id, schedule_date),
    INDEX idx_schedule_date (schedule_date),
    INDEX idx_schedule_type (schedule_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Stores daily schedule for each employee';

-- 2. Table: Default weekly schedule patterns for employees
-- Defines what an employee's "normal" schedule is for each day of the week
CREATE TABLE IF NOT EXISTS employee_default_schedules (
    id INT(11) NOT NULL AUTO_INCREMENT,
    employee_id INT(11) NOT NULL,
    day_of_week TINYINT(1) NOT NULL COMMENT '0=Sunday, 1=Monday, 2=Tuesday, 3=Wednesday, 4=Thursday, 5=Friday, 6=Saturday',
    work_schedule_id INT(11) NULL,
    is_rest_day TINYINT(1) DEFAULT 0,
    effective_from DATE NOT NULL,
    effective_until DATE NULL COMMENT 'NULL means indefinite',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (work_schedule_id) REFERENCES work_schedules(id) ON DELETE SET NULL,
    INDEX idx_employee_effective (employee_id, effective_from, effective_until),
    INDEX idx_day_of_week (day_of_week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Default weekly schedule pattern for employees';

-- 3. Table: Holiday calendar (company-wide or location-specific)
-- Centralized holiday management
CREATE TABLE IF NOT EXISTS company_holidays (
    id INT(11) NOT NULL AUTO_INCREMENT,
    holiday_date DATE NOT NULL,
    holiday_name VARCHAR(255) NOT NULL,
    holiday_type ENUM('regular', 'special_non_working', 'special_working') DEFAULT 'regular',
    is_recurring TINYINT(1) DEFAULT 0 COMMENT 'If 1, applies every year (e.g., New Year)',
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_holiday_date (holiday_date),
    INDEX idx_holiday_date (holiday_date),
    INDEX idx_holiday_type (holiday_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Company holiday calendar';

-- 4. Table: Schedule override history (links to schedule_change_requests when approved)
-- Maintains audit trail of all schedule changes
CREATE TABLE IF NOT EXISTS schedule_override_history (
    id INT(11) NOT NULL AUTO_INCREMENT,
    employee_id INT(11) NOT NULL,
    schedule_date DATE NOT NULL,
    original_schedule_id INT(11) NULL COMMENT 'What the schedule was before',
    new_schedule_id INT(11) NULL COMMENT 'What the schedule changed to',
    override_reason VARCHAR(255) NULL,
    schedule_change_request_id INT(11) NULL COMMENT 'Link to approved schedule_change_requests',
    applied_by INT(11) NULL COMMENT 'Admin who applied the change',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (original_schedule_id) REFERENCES work_schedules(id) ON DELETE SET NULL,
    FOREIGN KEY (new_schedule_id) REFERENCES work_schedules(id) ON DELETE SET NULL,
    FOREIGN KEY (schedule_change_request_id) REFERENCES schedule_change_requests(id) ON DELETE SET NULL,
    INDEX idx_employee_date (employee_id, schedule_date),
    INDEX idx_request_link (schedule_change_request_id),
    INDEX idx_applied_at (applied_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='History of all schedule overrides and changes';

-- 5. Table: Rotating schedule patterns (for employees with rotating shifts)
-- Defines rotation patterns like "4 days on, 2 days off" cycles
CREATE TABLE IF NOT EXISTS rotating_schedule_patterns (
    id INT(11) NOT NULL AUTO_INCREMENT,
    pattern_name VARCHAR(255) NOT NULL,
    pattern_description TEXT NULL,
    cycle_length INT(11) NOT NULL COMMENT 'Number of days in one complete rotation cycle',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Rotating schedule pattern definitions';

-- 6. Table: Rotating schedule pattern details (defines each day in the cycle)
-- Specifies what schedule to use for each day in the rotation
CREATE TABLE IF NOT EXISTS rotating_schedule_pattern_days (
    id INT(11) NOT NULL AUTO_INCREMENT,
    pattern_id INT(11) NOT NULL,
    day_number INT(11) NOT NULL COMMENT 'Day 1, 2, 3... in the rotation cycle',
    work_schedule_id INT(11) NULL,
    is_rest_day TINYINT(1) DEFAULT 0,
    day_label VARCHAR(100) NULL COMMENT 'Optional label like "Morning Shift", "Night Shift"',
    PRIMARY KEY (id),
    FOREIGN KEY (pattern_id) REFERENCES rotating_schedule_patterns(id) ON DELETE CASCADE,
    FOREIGN KEY (work_schedule_id) REFERENCES work_schedules(id) ON DELETE SET NULL,
    UNIQUE KEY unique_pattern_day (pattern_id, day_number),
    INDEX idx_pattern (pattern_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Details of each day in a rotating schedule pattern';

-- 7. Table: Employee assignment to rotating patterns
-- Assigns employees to specific rotating schedule patterns
CREATE TABLE IF NOT EXISTS employee_rotating_schedules (
    id INT(11) NOT NULL AUTO_INCREMENT,
    employee_id INT(11) NOT NULL,
    pattern_id INT(11) NOT NULL,
    start_date DATE NOT NULL,
    cycle_start_day INT(11) DEFAULT 1 COMMENT 'Which day of the pattern cycle to start with',
    end_date DATE NULL COMMENT 'NULL means indefinite',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (pattern_id) REFERENCES rotating_schedule_patterns(id) ON DELETE CASCADE,
    INDEX idx_employee_dates (employee_id, start_date, end_date),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Employee assignments to rotating schedule patterns';

-- ===================================================================
-- SAMPLE DATA - Philippine Holidays 2025
-- ===================================================================

INSERT INTO company_holidays (holiday_date, holiday_name, holiday_type, is_recurring, description) VALUES
('2025-01-01', 'New Year\'s Day', 'regular', 1, 'Start of the new year'),
('2025-04-09', 'Araw ng Kagitingan (Day of Valor)', 'regular', 1, 'Commemoration of the fall of Bataan'),
('2025-04-17', 'Maundy Thursday', 'regular', 0, 'Holy Week'),
('2025-04-18', 'Good Friday', 'regular', 0, 'Holy Week'),
('2025-05-01', 'Labor Day', 'regular', 1, 'International Workers Day'),
('2025-06-12', 'Independence Day', 'regular', 1, 'Philippine Independence Day'),
('2025-08-25', 'National Heroes Day', 'regular', 1, 'Last Monday of August'),
('2025-11-01', 'All Saints\' Day', 'special_non_working', 1, 'Day to honor all saints'),
('2025-11-30', 'Bonifacio Day', 'regular', 1, 'Birth anniversary of Andres Bonifacio'),
('2025-12-25', 'Christmas Day', 'regular', 1, 'Celebration of Christmas'),
('2025-12-30', 'Rizal Day', 'regular', 1, 'Death anniversary of Jose Rizal'),
('2025-12-31', 'New Year\'s Eve', 'special_working', 1, 'Last day of the year')
ON DUPLICATE KEY UPDATE 
    holiday_name = VALUES(holiday_name),
    holiday_type = VALUES(holiday_type);

-- ===================================================================
-- SAMPLE ROTATING SCHEDULE PATTERN
-- ===================================================================

-- Example: 4-day cycle (2 morning shifts, 1 evening shift, 1 rest day)
INSERT INTO rotating_schedule_patterns (pattern_name, pattern_description, cycle_length) VALUES
('4-Day Rotation', 'Two morning shifts, one evening shift, one rest day', 4)
ON DUPLICATE KEY UPDATE pattern_description = VALUES(pattern_description);

-- Define the pattern days (assuming work_schedule_id 1=morning, 2=evening)
SET @pattern_id = LAST_INSERT_ID();
INSERT INTO rotating_schedule_pattern_days (pattern_id, day_number, work_schedule_id, is_rest_day, day_label) VALUES
(@pattern_id, 1, 1, 0, 'Morning Shift'),
(@pattern_id, 2, 1, 0, 'Morning Shift'),
(@pattern_id, 3, 2, 0, 'Evening Shift'),
(@pattern_id, 4, NULL, 1, 'Rest Day')
ON DUPLICATE KEY UPDATE day_label = VALUES(day_label);

-- ===================================================================
-- INDEXES FOR PERFORMANCE
-- ===================================================================

-- Additional composite indexes for common queries
ALTER TABLE employee_daily_schedules 
ADD INDEX idx_date_type (schedule_date, schedule_type);

ALTER TABLE employee_default_schedules
ADD INDEX idx_employee_day (employee_id, day_of_week);

-- ===================================================================
-- VIEWS FOR EASIER QUERYING (Optional)
-- ===================================================================

-- View: Current active schedules for all employees
CREATE OR replace VIEW v_current_employee_schedules AS
SELECT 
    e.id AS employee_id,
    e.fname,
    e.lname,
    e.position,
    e.company,
    eds.schedule_date,
    eds.work_schedule_id,
    ws.time_in,
    ws.time_out,
    eds.schedule_type,
    eds.is_rest_day,
    eds.is_holiday
FROM employees e
LEFT JOIN employee_daily_schedules eds ON e.id = eds.employee_id
LEFT JOIN work_schedules ws ON eds.work_schedule_id = ws.id
WHERE eds.schedule_date >= CURDATE();

-- ===================================================================
-- END OF SCHEMA
-- ===================================================================
