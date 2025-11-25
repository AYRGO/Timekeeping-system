-- SQL Script to Add Schedules 19-22 to work_schedules table
-- This uses INSERT IGNORE to avoid duplicate entry errors
-- If the schedule already exists, it will be skipped

-- Method 1: Using INSERT IGNORE (Recommended)
INSERT IGNORE INTO `work_schedules` (`id`, `name`, `time_in`, `time_out`, `employee_id`, `day_of_week`, `full_text`) VALUES
(19, 'Night Shift 1', '19:00:00', '03:00:00', NULL, NULL, NULL),
(20, 'Night Shift 2', '19:00:00', '04:30:00', NULL, NULL, NULL),
(21, 'Evening Shift 1', '17:00:00', '02:00:00', NULL, NULL, NULL),
(22, 'Evening Shift 2', '17:30:00', '02:00:00', NULL, NULL, NULL);

-- OR Method 2: Update if exists, insert if not (Alternative)
-- Uncomment below if you prefer this method instead

/*
INSERT INTO `work_schedules` (`id`, `name`, `time_in`, `time_out`, `employee_id`, `day_of_week`, `full_text`) VALUES
(19, 'Night Shift 1', '19:00:00', '03:00:00', NULL, NULL, NULL),
(20, 'Night Shift 2', '19:00:00', '04:30:00', NULL, NULL, NULL),
(21, 'Evening Shift 1', '17:00:00', '02:00:00', NULL, NULL, NULL),
(22, 'Evening Shift 2', '17:30:00', '02:00:00', NULL, NULL, NULL)
ON DUPLICATE KEY UPDATE 
    name = VALUES(name),
    time_in = VALUES(time_in),
    time_out = VALUES(time_out);
*/

-- Verify the schedules were added
SELECT * FROM `work_schedules` WHERE id IN (19, 20, 21, 22);
