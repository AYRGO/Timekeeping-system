-- Add status column to track 'active' / 'completed' / 'incomplete'
ALTER TABLE time_logs ADD COLUMN status ENUM('active','completed','incomplete') DEFAULT 'active';

-- Optional: if manual completion history is needed (previously suggested)
CREATE TABLE IF NOT EXISTS shift_completions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    time_log_id INT NOT NULL,
    log_date DATE NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (time_log_id) REFERENCES time_logs(id) ON DELETE CASCADE,
    UNIQUE KEY unique_completion (employee_id, log_date)
);
