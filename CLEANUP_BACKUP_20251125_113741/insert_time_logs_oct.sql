-- Insert time logs for employees 1003-1007 for October 1-15, 2025
-- Employee 1006 gets 8:52 AM - 6:00 PM
-- Others get random times from the provided image

-- Employee 1003 - Random times from image
INSERT INTO time_logs (employee_id, log_date, time_in, time_out, log_out_date, is_late_in, is_early_out, updated_at, status) VALUES
(1003, '2025-10-01', '06:52:36', '16:12:59', NULL, 0, 0, NOW(), 'active'),
(1003, '2025-10-02', '06:50:01', '16:00:01', NULL, 0, 0, NOW(), 'active'),
(1003, '2025-10-03', '06:57:36', '16:02:12', NULL, 0, 0, NOW(), 'active'),
(1003, '2025-10-06', '07:00:45', '16:02:12', NULL, 0, 0, NOW(), 'active'),
(1003, '2025-10-07', '06:57:20', '16:02:12', NULL, 0, 0, NOW(), 'active'),
(1003, '2025-10-08', '07:04:33', '16:02:12', NULL, 0, 0, NOW(), 'active'),
(1003, '2025-10-09', '07:00:33', '16:00:00', NULL, 0, 0, NOW(), 'active'),
(1003, '2025-10-10', '06:50:54', '16:02:33', NULL, 0, 0, NOW(), 'active'),
(1003, '2025-10-13', '06:52:59', '16:35:51', NULL, 0, 0, NOW(), 'active'),
(1003, '2025-10-14', '07:04:03', '16:05:33', NULL, 0, 0, NOW(), 'active'),
(1003, '2025-10-15', '06:51:38', '16:01:03', NULL, 0, 0, NOW(), 'active');

-- Employee 1004 - Random times from image
INSERT INTO time_logs (employee_id, log_date, time_in, time_out, log_out_date, is_late_in, is_early_out, updated_at, status) VALUES
(1004, '2025-10-01', '06:52:24', '16:02:00', NULL, 0, 0, NOW(), 'active'),
(1004, '2025-10-02', '07:00:10', '16:26:40', NULL, 0, 0, NOW(), 'active'),
(1004, '2025-10-03', '07:01:59', '16:25:48', NULL, 0, 0, NOW(), 'active'),
(1004, '2025-10-06', '06:59:40', '16:06:34', NULL, 0, 0, NOW(), 'active'),
(1004, '2025-10-07', '07:09:56', '16:02:58', NULL, 0, 0, NOW(), 'active'),
(1004, '2025-10-08', '07:02:19', '21:51:30', NULL, 0, 0, NOW(), 'active'),
(1004, '2025-10-09', '07:11:51', '16:01:47', NULL, 0, 0, NOW(), 'active'),
(1004, '2025-10-10', '07:07:03', '16:01:33', NULL, 0, 0, NOW(), 'active'),
(1004, '2025-10-13', '06:56:24', '16:04:44', NULL, 0, 0, NOW(), 'active'),
(1004, '2025-10-14', '06:58:16', '16:00:02', NULL, 0, 0, NOW(), 'active'),
(1004, '2025-10-15', '06:52:52', '16:01:29', NULL, 0, 0, NOW(), 'active');

-- Employee 1005 - Random times from image
INSERT INTO time_logs (employee_id, log_date, time_in, time_out, log_out_date, is_late_in, is_early_out, updated_at, status) VALUES
(1005, '2025-10-01', '06:55:25', '16:02:01', NULL, 0, 0, NOW(), 'active'),
(1005, '2025-10-02', '06:52:36', '16:12:59', NULL, 0, 0, NOW(), 'active'),
(1005, '2025-10-03', '06:53:19', '16:04:31', NULL, 0, 0, NOW(), 'active'),
(1005, '2025-10-06', '06:34:09', '16:01:00', NULL, 0, 0, NOW(), 'active'),
(1005, '2025-10-07', '06:50:54', '16:02:33', NULL, 0, 0, NOW(), 'active'),
(1005, '2025-10-08', '07:04:03', '16:05:33', NULL, 0, 0, NOW(), 'active'),
(1005, '2025-10-09', '06:51:38', '16:01:03', NULL, 0, 0, NOW(), 'active'),
(1005, '2025-10-10', '06:57:36', '16:02:12', NULL, 0, 0, NOW(), 'active'),
(1005, '2025-10-13', '07:00:45', '16:02:12', NULL, 0, 0, NOW(), 'active'),
(1005, '2025-10-14', '06:57:20', '16:02:12', NULL, 0, 0, NOW(), 'active'),
(1005, '2025-10-15', '07:04:33', '16:02:12', NULL, 0, 0, NOW(), 'active');

-- Employee 1006 - FIXED TIME: 8:52 AM - 6:00 PM (08:52:00 - 18:00:00)
INSERT INTO time_logs (employee_id, log_date, time_in, time_out, log_out_date, is_late_in, is_early_out, updated_at, status) VALUES
(1006, '2025-10-01', '08:52:00', '18:00:00', NULL, 0, 0, NOW(), 'active'),
(1006, '2025-10-02', '08:52:00', '18:00:00', NULL, 0, 0, NOW(), 'active'),
(1006, '2025-10-03', '08:52:00', '18:00:00', NULL, 0, 0, NOW(), 'active'),
(1006, '2025-10-06', '08:52:00', '18:00:00', NULL, 0, 0, NOW(), 'active'),
(1006, '2025-10-07', '08:52:00', '18:00:00', NULL, 0, 0, NOW(), 'active'),
(1006, '2025-10-08', '08:52:00', '18:00:00', NULL, 0, 0, NOW(), 'active'),
(1006, '2025-10-09', '08:52:00', '18:00:00', NULL, 0, 0, NOW(), 'active'),
(1006, '2025-10-10', '08:52:00', '18:00:00', NULL, 0, 0, NOW(), 'active'),
(1006, '2025-10-13', '08:52:00', '18:00:00', NULL, 0, 0, NOW(), 'active'),
(1006, '2025-10-14', '08:52:00', '18:00:00', NULL, 0, 0, NOW(), 'active'),
(1006, '2025-10-15', '08:52:00', '18:00:00', NULL, 0, 0, NOW(), 'active');

-- Employee 1007 - Random times from image
INSERT INTO time_logs (employee_id, log_date, time_in, time_out, log_out_date, is_late_in, is_early_out, updated_at, status) VALUES
(1007, '2025-10-01', '07:00:33', '16:00:00', NULL, 0, 0, NOW(), 'active'),
(1007, '2025-10-02', '06:52:59', '16:35:51', NULL, 0, 0, NOW(), 'active'),
(1007, '2025-10-03', '07:11:51', '16:01:47', NULL, 0, 0, NOW(), 'active'),
(1007, '2025-10-06', '07:07:03', '16:01:33', NULL, 0, 0, NOW(), 'active'),
(1007, '2025-10-07', '06:56:24', '16:04:44', NULL, 0, 0, NOW(), 'active'),
(1007, '2025-10-08', '06:58:16', '16:00:02', NULL, 0, 0, NOW(), 'active'),
(1007, '2025-10-09', '06:52:52', '16:01:29', NULL, 0, 0, NOW(), 'active'),
(1007, '2025-10-10', '06:55:25', '16:02:01', NULL, 0, 0, NOW(), 'active'),
(1007, '2025-10-13', '06:53:19', '16:04:31', NULL, 0, 0, NOW(), 'active'),
(1007, '2025-10-14', '06:34:09', '16:01:00', NULL, 0, 0, NOW(), 'active'),
(1007, '2025-10-15', '07:09:56', '16:02:58', NULL, 0, 0, NOW(), 'active');

-- Summary:
-- Total records: 65 time logs (13 days x 5 employees)
-- Oct 1-3: Wed-Fri
-- Oct 6-10: Mon-Fri
-- Oct 13-15: Mon-Wed
-- Includes all working days from Oct 1-15, 2025
