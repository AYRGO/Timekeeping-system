-- Create table for monthly/weekly schedule change requests
-- Make sure to use your database first
USE `rss`;

CREATE TABLE IF NOT EXISTS month_weekly_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    year INT NOT NULL,
    month INT NOT NULL,
    
    -- Weekly schedule configuration (7 days: Sunday to Saturday)
    sunday_schedule_id INT NULL,
    sunday_is_rest_day TINYINT(1) DEFAULT 0,
    monday_schedule_id INT NULL,
    monday_is_rest_day TINYINT(1) DEFAULT 0,
    tuesday_schedule_id INT NULL,
    tuesday_is_rest_day TINYINT(1) DEFAULT 0,
    wednesday_schedule_id INT NULL,
    wednesday_is_rest_day TINYINT(1) DEFAULT 0,
    thursday_schedule_id INT NULL,
    thursday_is_rest_day TINYINT(1) DEFAULT 0,
    friday_schedule_id INT NULL,
    friday_is_rest_day TINYINT(1) DEFAULT 0,
    saturday_schedule_id INT NULL,
    saturday_is_rest_day TINYINT(1) DEFAULT 0,
    
    reason TEXT NOT NULL,
    attachment_path VARCHAR(255) NOT NULL,
    
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    processed_by INT NULL,
    admin_notes TEXT NULL,
    
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES employees(id) ON DELETE SET NULL,
    
    INDEX idx_employee_month (employee_id, year, month),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
