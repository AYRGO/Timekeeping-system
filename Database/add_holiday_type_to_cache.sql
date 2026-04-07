-- ===================================================================
-- Add holiday_type column to employee_daily_schedule_cache
-- ===================================================================
-- Purpose: Store holiday type in cache for payroll report display
-- Date: 2026-04-07
-- ===================================================================

-- Add holiday_type column to cache table
ALTER TABLE `employee_daily_schedule_cache` 
ADD COLUMN `holiday_type` ENUM('regular', 'special_non_working', 'special_working') DEFAULT NULL 
AFTER `holiday_name`;

-- Update existing cache entries with holiday types
UPDATE employee_daily_schedule_cache edc
INNER JOIN company_holidays ch ON edc.holiday_name = ch.holiday_name
SET edc.holiday_type = ch.holiday_type
WHERE edc.is_holiday = 1 AND edc.holiday_name IS NOT NULL;
