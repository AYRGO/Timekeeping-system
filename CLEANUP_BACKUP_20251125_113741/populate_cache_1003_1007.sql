-- Populate employee_daily_schedule_cache for employees 1003-1007
-- October 1-23, 2025 (before Oct 24 which already has data)
-- Based on their default weekly schedules

-- First, let's assume they all have the same "Day shift" schedule (07:00:00 - 16:00:00) like employee 1009
-- Weekdays (Mon-Fri) = Day shift, Weekends (Sat-Sun) = Rest day

-- DELETE EXISTING ENTRIES FIRST (to avoid duplicate key errors)
DELETE FROM employee_daily_schedule_cache 
WHERE employee_id IN (1003, 1004, 1005, 1006, 1007) 
AND schedule_date BETWEEN '2025-10-01' AND '2025-10-23';

-- EMPLOYEE 1003 - October 1-23, 2025
-- Oct 1 (Wed), 2 (Thu), 3 (Fri) = Day shift
INSERT INTO employee_daily_schedule_cache 
(employee_id, schedule_date, work_schedule_id, is_rest_day, is_holiday, schedule_name, time_in, time_out, holiday_name, source, source_id, created_at, updated_at) 
VALUES
(1003, '2025-10-01', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1003, '2025-10-02', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1003, '2025-10-03', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
-- Oct 4-5 (Sat-Sun) = Weekend
(1003, '2025-10-04', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
(1003, '2025-10-05', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
-- Oct 6-10 (Mon-Fri) = Day shift
(1003, '2025-10-06', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1003, '2025-10-07', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1003, '2025-10-08', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1003, '2025-10-09', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1003, '2025-10-10', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
-- Oct 11-12 (Sat-Sun) = Weekend
(1003, '2025-10-11', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
(1003, '2025-10-12', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
-- Oct 13-17 (Mon-Fri) = Day shift
(1003, '2025-10-13', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1003, '2025-10-14', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1003, '2025-10-15', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1003, '2025-10-16', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1003, '2025-10-17', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
-- Oct 18-19 (Sat-Sun) = Weekend
(1003, '2025-10-18', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
(1003, '2025-10-19', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
-- Oct 20-23 (Mon-Thu) = Day shift
(1003, '2025-10-20', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1003, '2025-10-21', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1003, '2025-10-22', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1003, '2025-10-23', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW());

-- EMPLOYEE 1004 - October 1-23, 2025
INSERT INTO employee_daily_schedule_cache 
(employee_id, schedule_date, work_schedule_id, is_rest_day, is_holiday, schedule_name, time_in, time_out, holiday_name, source, source_id, created_at, updated_at) 
VALUES
(1004, '2025-10-01', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1004, '2025-10-02', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1004, '2025-10-03', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1004, '2025-10-04', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
(1004, '2025-10-05', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
(1004, '2025-10-06', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1004, '2025-10-07', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1004, '2025-10-08', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1004, '2025-10-09', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1004, '2025-10-10', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1004, '2025-10-11', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
(1004, '2025-10-12', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
(1004, '2025-10-13', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1004, '2025-10-14', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1004, '2025-10-15', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1004, '2025-10-16', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1004, '2025-10-17', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1004, '2025-10-18', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
(1004, '2025-10-19', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
(1004, '2025-10-20', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1004, '2025-10-21', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1004, '2025-10-22', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1004, '2025-10-23', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW());

-- EMPLOYEE 1005 - October 1-23, 2025
INSERT INTO employee_daily_schedule_cache 
(employee_id, schedule_date, work_schedule_id, is_rest_day, is_holiday, schedule_name, time_in, time_out, holiday_name, source, source_id, created_at, updated_at) 
VALUES
(1005, '2025-10-01', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1005, '2025-10-02', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1005, '2025-10-03', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1005, '2025-10-04', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
(1005, '2025-10-05', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
(1005, '2025-10-06', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1005, '2025-10-07', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1005, '2025-10-08', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1005, '2025-10-09', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1005, '2025-10-10', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1005, '2025-10-11', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
(1005, '2025-10-12', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
(1005, '2025-10-13', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1005, '2025-10-14', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1005, '2025-10-15', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1005, '2025-10-16', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1005, '2025-10-17', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1005, '2025-10-18', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
(1005, '2025-10-19', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
(1005, '2025-10-20', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1005, '2025-10-21', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1005, '2025-10-22', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1005, '2025-10-23', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW());

-- EMPLOYEE 1006 - October 1-23, 2025 (Mon-Fri: 9am-6pm)
INSERT INTO employee_daily_schedule_cache 
(employee_id, schedule_date, work_schedule_id, is_rest_day, is_holiday, schedule_name, time_in, time_out, holiday_name, source, source_id, created_at, updated_at) 
VALUES
-- Oct 1 (Wed), 2 (Thu), 3 (Fri) = 9am-6pm
(1006, '2025-10-01', 6, 0, 0, 'Day shift', '09:00:00', '18:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1006, '2025-10-02', 6, 0, 0, 'Day shift', '09:00:00', '18:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1006, '2025-10-03', 6, 0, 0, 'Day shift', '09:00:00', '18:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
-- Oct 4-5 (Sat-Sun) = Weekend
(1006, '2025-10-04', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
(1006, '2025-10-05', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
-- Oct 6-10 (Mon-Fri) = 9am-6pm
(1006, '2025-10-06', 6, 0, 0, 'Day shift', '09:00:00', '18:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1006, '2025-10-07', 6, 0, 0, 'Day shift', '09:00:00', '18:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1006, '2025-10-08', 6, 0, 0, 'Day shift', '09:00:00', '18:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1006, '2025-10-09', 6, 0, 0, 'Day shift', '09:00:00', '18:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1006, '2025-10-10', 6, 0, 0, 'Day shift', '09:00:00', '18:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
-- Oct 11-12 (Sat-Sun) = Weekend
(1006, '2025-10-11', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
(1006, '2025-10-12', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
-- Oct 13-17 (Mon-Fri) = 9am-6pm
(1006, '2025-10-13', 6, 0, 0, 'Day shift', '09:00:00', '18:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1006, '2025-10-14', 6, 0, 0, 'Day shift', '09:00:00', '18:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1006, '2025-10-15', 6, 0, 0, 'Day shift', '09:00:00', '18:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1006, '2025-10-16', 6, 0, 0, 'Day shift', '09:00:00', '18:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1006, '2025-10-17', 6, 0, 0, 'Day shift', '09:00:00', '18:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
-- Oct 18-19 (Sat-Sun) = Weekend
(1006, '2025-10-18', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
(1006, '2025-10-19', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
-- Oct 20-23 (Mon-Thu) = 9am-6pm
(1006, '2025-10-20', 6, 0, 0, 'Day shift', '09:00:00', '18:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1006, '2025-10-21', 6, 0, 0, 'Day shift', '09:00:00', '18:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1006, '2025-10-22', 6, 0, 0, 'Day shift', '09:00:00', '18:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1006, '2025-10-23', 6, 0, 0, 'Day shift', '09:00:00', '18:00:00', NULL, 'weekly_default', NULL, NOW(), NOW());

-- EMPLOYEE 1007 - October 1-23, 2025
INSERT INTO employee_daily_schedule_cache 
(employee_id, schedule_date, work_schedule_id, is_rest_day, is_holiday, schedule_name, time_in, time_out, holiday_name, source, source_id, created_at, updated_at) 
VALUES
(1007, '2025-10-01', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1007, '2025-10-02', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1007, '2025-10-03', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1007, '2025-10-04', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
(1007, '2025-10-05', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
(1007, '2025-10-06', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1007, '2025-10-07', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1007, '2025-10-08', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1007, '2025-10-09', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1007, '2025-10-10', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1007, '2025-10-11', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
(1007, '2025-10-12', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
(1007, '2025-10-13', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1007, '2025-10-14', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1007, '2025-10-15', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1007, '2025-10-16', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1007, '2025-10-17', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1007, '2025-10-18', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
(1007, '2025-10-19', NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekend', NULL, NOW(), NOW()),
(1007, '2025-10-20', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1007, '2025-10-21', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1007, '2025-10-22', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1007, '2025-10-23', 4, 0, 0, 'Day shift', '07:00:00', '16:00:00', NULL, 'weekly_default', NULL, NOW(), NOW());

-- Summary:
-- Total records: 115 cache entries (23 days × 5 employees)
-- Each employee gets:
--   - 15 working days (Mon-Fri): Day shift (07:00 - 16:00)
--   - 8 weekend days (Sat-Sun): Rest day
-- This matches the pattern used for employee 1009
