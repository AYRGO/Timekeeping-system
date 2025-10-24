-- Add cache entries for Sept 29-30 so the report can find the correct schedule
INSERT INTO employee_daily_schedule_cache 
(employee_id, schedule_date, work_schedule_id, is_rest_day, is_holiday, schedule_name, time_in, time_out, holiday_name, source, source_id, created_at, updated_at) 
VALUES
-- Sept 29 (Mon) = 9am-6pm
(1006, '2025-09-29', 6, 0, 0, 'Day shift', '09:00:00', '18:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
-- Sept 30 (Tue) = 9am-6pm
(1006, '2025-09-30', 6, 0, 0, 'Day shift', '09:00:00', '18:00:00', NULL, 'weekly_default', NULL, NOW(), NOW());
