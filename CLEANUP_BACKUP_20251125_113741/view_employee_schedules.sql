-- ============================================================================
-- SQL QUERIES TO VIEW EMPLOYEE SCHEDULES PER DATE
-- ============================================================================

-- 1. VIEW ALL EMPLOYEES WITH THEIR DEFAULT/OFFICIAL SCHEDULES
-- Shows the base schedule for each employee
SELECT 
    e.id AS employee_id,
    CONCAT(e.fname, ' ', e.lname) AS employee_name,
    e.official_sched AS schedule_id,
    ws.time_in AS default_time_in,
    ws.time_out AS default_time_out,
    CONCAT(ws.time_in, ' - ', ws.time_out) AS schedule_display
FROM employees e
LEFT JOIN work_schedules ws ON e.official_sched = ws.id
WHERE e.status = 'active'
ORDER BY e.fname, e.lname;

-- ============================================================================

-- 2. VIEW APPROVED SCHEDULE CHANGE REQUESTS (SCHEDULE OVERRIDES)
-- Shows all approved schedule changes that override the default schedule
SELECT 
    pscr.id AS request_id,
    pscr.employee_id,
    CONCAT(e.fname, ' ', e.lname) AS employee_name,
    pscr.start_date,
    pscr.end_date,
    pscr.is_rest_day,
    CASE 
        WHEN pscr.is_rest_day = 1 THEN 'REST DAY / DAY OFF'
        ELSE CONCAT('Schedule ', pscr.work_schedule_id)
    END AS requested_schedule,
    ws.time_in AS override_time_in,
    ws.time_out AS override_time_out,
    CASE 
        WHEN pscr.is_rest_day = 1 THEN 'Day Off'
        ELSE CONCAT(TIME_FORMAT(ws.time_in, '%h:%i %p'), ' - ', TIME_FORMAT(ws.time_out, '%h:%i %p'))
    END AS schedule_display,
    pscr.reason,
    pscr.created_at AS approved_date
FROM post_schedule_change_requests pscr
JOIN employees e ON pscr.employee_id = e.id
LEFT JOIN work_schedules ws ON pscr.work_schedule_id = ws.id
WHERE pscr.status = 'Approved'
ORDER BY pscr.employee_id, pscr.start_date;

-- ============================================================================

-- 3. VIEW EMPLOYEE SCHEDULE FOR A SPECIFIC DATE
-- Replace '2025-10-21' with your desired date
-- This query shows what schedule each employee has on a specific date
SELECT 
    e.id AS employee_id,
    CONCAT(e.fname, ' ', e.lname) AS employee_name,
    '2025-10-21' AS schedule_date,
    CASE 
        WHEN pscr.is_rest_day = 1 THEN 'REST DAY'
        WHEN pscr.work_schedule_id IS NOT NULL THEN CONCAT('Schedule ', pscr.work_schedule_id, ' (Override)')
        ELSE CONCAT('Schedule ', e.official_sched, ' (Default)')
    END AS schedule_type,
    CASE 
        WHEN pscr.is_rest_day = 1 THEN 'Day Off'
        WHEN pscr.work_schedule_id IS NOT NULL THEN CONCAT(TIME_FORMAT(ws_override.time_in, '%h:%i %p'), ' - ', TIME_FORMAT(ws_override.time_out, '%h:%i %p'))
        ELSE CONCAT(TIME_FORMAT(ws_default.time_in, '%h:%i %p'), ' - ', TIME_FORMAT(ws_default.time_out, '%h:%i %p'))
    END AS working_hours,
    pscr.reason AS override_reason
FROM employees e
LEFT JOIN work_schedules ws_default ON e.official_sched = ws_default.id
LEFT JOIN post_schedule_change_requests pscr 
    ON e.id = pscr.employee_id 
    AND '2025-10-21' BETWEEN pscr.start_date AND pscr.end_date
    AND pscr.status = 'Approved'
LEFT JOIN work_schedules ws_override ON pscr.work_schedule_id = ws_override.id
WHERE e.employment_status = 'Active'
ORDER BY e.fname, e.lname;

-- ============================================================================

-- 4. VIEW EMPLOYEE SCHEDULES FOR A DATE RANGE
-- Shows all employees' schedules across multiple dates (Oct 21-27, 2025)
SELECT 
    e.id AS employee_id,
    CONCAT(e.fname, ' ', e.lname) AS employee_name,
    dates.schedule_date,
    DAYNAME(dates.schedule_date) AS day_of_week,
    CASE 
        WHEN pscr.is_rest_day = 1 THEN 'REST DAY'
        WHEN pscr.work_schedule_id IS NOT NULL THEN CONCAT('Schedule ', pscr.work_schedule_id, ' (Override)')
        ELSE CONCAT('Schedule ', e.official_sched, ' (Default)')
    END AS schedule_type,
    CASE 
        WHEN pscr.is_rest_day = 1 THEN '🛏️ Day Off'
        WHEN pscr.work_schedule_id IS NOT NULL THEN CONCAT(TIME_FORMAT(ws_override.time_in, '%h:%i %p'), ' - ', TIME_FORMAT(ws_override.time_out, '%h:%i %p'))
        ELSE CONCAT(TIME_FORMAT(ws_default.time_in, '%h:%i %p'), ' - ', TIME_FORMAT(ws_default.time_out, '%h:%i %p'))
    END AS working_hours
FROM employees e
CROSS JOIN (
    SELECT '2025-10-21' AS schedule_date
    UNION SELECT '2025-10-22'
    UNION SELECT '2025-10-23'
    UNION SELECT '2025-10-24'
    UNION SELECT '2025-10-25'
    UNION SELECT '2025-10-26'
    UNION SELECT '2025-10-27'
) AS dates
LEFT JOIN work_schedules ws_default ON e.official_sched = ws_default.id
LEFT JOIN post_schedule_change_requests pscr 
    ON e.id = pscr.employee_id 
    AND dates.schedule_date BETWEEN pscr.start_date AND pscr.end_date
    AND pscr.status = 'Approved'
LEFT JOIN work_schedules ws_override ON pscr.work_schedule_id = ws_override.id
WHERE e.employment_status = 'Active'
ORDER BY e.fname, e.lname, dates.schedule_date;

-- ============================================================================

-- 5. VIEW SCHEDULE SUMMARY - Count employees by schedule per day
SELECT 
    dates.schedule_date,
    DAYNAME(dates.schedule_date) AS day_of_week,
    CASE 
        WHEN pscr.is_rest_day = 1 THEN 'REST DAY'
        WHEN pscr.work_schedule_id IS NOT NULL THEN CONCAT('Schedule ', pscr.work_schedule_id)
        ELSE CONCAT('Schedule ', e.official_sched)
    END AS schedule_type,
    COUNT(*) AS employee_count
FROM employees e
CROSS JOIN (
    SELECT '2025-10-21' AS schedule_date
    UNION SELECT '2025-10-22'
    UNION SELECT '2025-10-23'
    UNION SELECT '2025-10-24'
    UNION SELECT '2025-10-25'
    UNION SELECT '2025-10-26'
    UNION SELECT '2025-10-27'
) AS dates
LEFT JOIN post_schedule_change_requests pscr 
    ON e.id = pscr.employee_id 
    AND dates.schedule_date BETWEEN pscr.start_date AND pscr.end_date
    AND pscr.status = 'Approved'
WHERE e.employment_status = 'Active'
GROUP BY dates.schedule_date, schedule_type
ORDER BY dates.schedule_date, schedule_type;

-- ============================================================================

-- 6. VIEW PENDING SCHEDULE CHANGE REQUESTS
-- Shows upcoming/pending schedule changes that haven't been approved yet
SELECT 
    scr.id AS request_id,
    scr.employee_id,
    CONCAT(e.fname, ' ', e.lname) AS employee_name,
    scr.start_date,
    scr.end_date,
    scr.is_rest_day,
    CASE 
        WHEN scr.is_rest_day = 1 THEN 'REST DAY REQUEST'
        ELSE CONCAT('Schedule ', scr.work_schedule_id)
    END AS requested_schedule,
    scr.status,
    scr.reason,
    scr.created_at AS request_date
FROM schedule_change_requests scr
JOIN employees e ON scr.employee_id = e.id
WHERE scr.status = 'Pending'
ORDER BY scr.created_at DESC;

-- ============================================================================

-- 7. VIEW SPECIFIC EMPLOYEE'S SCHEDULE HISTORY
-- Replace employee_id = 1 with the employee you want to check
SELECT 
    e.id AS employee_id,
    CONCAT(e.fname, ' ', e.lname) AS employee_name,
    pscr.start_date,
    pscr.end_date,
    pscr.is_rest_day,
    CASE 
        WHEN pscr.is_rest_day = 1 THEN 'REST DAY / DAY OFF'
        ELSE CONCAT('Schedule ', pscr.work_schedule_id)
    END AS schedule_type,
    CASE 
        WHEN pscr.is_rest_day = 1 THEN 'Day Off'
        ELSE CONCAT(TIME_FORMAT(ws.time_in, '%h:%i %p'), ' - ', TIME_FORMAT(ws.time_out, '%h:%i %p'))
    END AS working_hours,
    pscr.reason,
    pscr.status,
    pscr.created_at
FROM employees e
LEFT JOIN post_schedule_change_requests pscr ON e.id = pscr.employee_id
LEFT JOIN work_schedules ws ON pscr.work_schedule_id = ws.id
WHERE e.id = 1
ORDER BY pscr.start_date DESC;

-- ============================================================================

-- 8. VIEW TODAY'S SCHEDULE FOR ALL EMPLOYEES
-- Automatically uses today's date
SELECT 
    e.id AS employee_id,
    CONCAT(e.fname, ' ', e.lname) AS employee_name,
    CURDATE() AS today,
    CASE 
        WHEN pscr.is_rest_day = 1 THEN '🛏️ REST DAY'
        WHEN pscr.work_schedule_id IS NOT NULL THEN '⚡ OVERRIDE'
        ELSE '📅 DEFAULT'
    END AS status,
    CASE 
        WHEN pscr.is_rest_day = 1 THEN 'Day Off'
        WHEN pscr.work_schedule_id IS NOT NULL THEN CONCAT(TIME_FORMAT(ws_override.time_in, '%h:%i %p'), ' - ', TIME_FORMAT(ws_override.time_out, '%h:%i %p'))
        ELSE CONCAT(TIME_FORMAT(ws_default.time_in, '%h:%i %p'), ' - ', TIME_FORMAT(ws_default.time_out, '%h:%i %p'))
    END AS working_hours,
    pscr.reason
FROM employees e
LEFT JOIN work_schedules ws_default ON e.official_sched = ws_default.id
LEFT JOIN post_schedule_change_requests pscr 
    ON e.id = pscr.employee_id 
    AND CURDATE() BETWEEN pscr.start_date AND pscr.end_date
    AND pscr.status = 'Approved'
LEFT JOIN work_schedules ws_override ON pscr.work_schedule_id = ws_override.id
WHERE e.employment_status = 'Active'
ORDER BY e.fname, e.lname;

-- ============================================================================
-- USAGE INSTRUCTIONS:
-- 1. Copy any query above
-- 2. Replace date values ('2025-10-21') with your desired dates
-- 3. Replace employee_id values with specific employee IDs you want to check
-- 4. Run in MySQL command line or phpMyAdmin
-- ============================================================================
