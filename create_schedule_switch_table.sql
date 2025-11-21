-- Create table for schedule switch requests
CREATE TABLE IF NOT EXISTS schedule_switch_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    source_date DATE NOT NULL COMMENT 'Date A - The date employee wants to switch from',
    target_date DATE NOT NULL COMMENT 'Date B - The date employee wants to switch to',
    reason TEXT NOT NULL,
    attachment_path VARCHAR(500) DEFAULT NULL,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL DEFAULT NULL,
    processed_by INT DEFAULT NULL,
    admin_notes TEXT DEFAULT NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_employee (employee_id),
    INDEX idx_dates (source_date, target_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Schedule switch requests - swap schedules between two dates';
