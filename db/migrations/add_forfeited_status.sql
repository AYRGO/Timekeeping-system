-- Add 'Forfeited' status to all request tables
-- Run this SQL script to update the database schema

-- 1. Update schedule_change_requests table
ALTER TABLE `schedule_change_requests` 
MODIFY COLUMN `status` ENUM('Pending','Approved','Declined','Rejected','Forfeited') DEFAULT 'Pending';

-- 2. Update schedule_switch_requests table
ALTER TABLE `schedule_switch_requests` 
MODIFY COLUMN `status` ENUM('pending','approved','rejected','cancelled','forfeited') DEFAULT 'pending';

-- 3. Update month_weekly_schedule table
ALTER TABLE `month_weekly_schedule` 
MODIFY COLUMN `status` ENUM('pending','approved','rejected','cancelled','forfeited') DEFAULT 'pending';

-- 4. Create index on date columns for better performance
CREATE INDEX IF NOT EXISTS idx_end_date ON schedule_change_requests(end_date, status);
CREATE INDEX IF NOT EXISTS idx_target_date ON schedule_switch_requests(target_date, status);
CREATE INDEX IF NOT EXISTS idx_year_month ON month_weekly_schedule(year, month, status);
