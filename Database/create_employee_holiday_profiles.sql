-- ===================================================================
-- EMPLOYEE HOLIDAY PROFILE SCHEMA
-- ===================================================================
-- Purpose: Support employee-specific replacement holiday calendars
--          so attendance history and payroll can resolve holidays per
--          employee profile instead of only using company_holidays.
-- ===================================================================

CREATE TABLE IF NOT EXISTS employee_holiday_profiles (
    id INT(11) NOT NULL AUTO_INCREMENT,
    profile_code VARCHAR(50) NOT NULL,
    profile_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_profile_code (profile_code),
    INDEX idx_profile_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Holiday profile definitions for employee-specific replacement calendars';

CREATE TABLE IF NOT EXISTS employee_holiday_profile_holidays (
    id INT(11) NOT NULL AUTO_INCREMENT,
    profile_id INT(11) NOT NULL,
    holiday_date DATE NOT NULL,
    holiday_name VARCHAR(255) NOT NULL,
    holiday_type ENUM('regular', 'special_non_working', 'special_working') DEFAULT 'regular',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_profile_holiday (profile_id, holiday_date),
    FOREIGN KEY (profile_id) REFERENCES employee_holiday_profiles(id) ON DELETE CASCADE,
    INDEX idx_profile_holiday_date (profile_id, holiday_date),
    INDEX idx_profile_holiday_type (holiday_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Holiday dates belonging to employee holiday profiles';

CREATE TABLE IF NOT EXISTS employee_holiday_profile_assignments (
    id INT(11) NOT NULL AUTO_INCREMENT,
    employee_id INT(11) NOT NULL,
    profile_id INT(11) NOT NULL,
    effective_from DATE NOT NULL,
    effective_until DATE NULL,
    is_active TINYINT(1) DEFAULT 1,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (profile_id) REFERENCES employee_holiday_profiles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_employee_profile_start (employee_id, profile_id, effective_from),
    INDEX idx_employee_profile_dates (employee_id, effective_from, effective_until),
    INDEX idx_profile_active (profile_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Employee to holiday profile assignments';