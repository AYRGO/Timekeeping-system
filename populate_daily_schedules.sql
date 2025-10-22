-- Populate employee_daily_schedules_simple for October - December 2025
-- Run this query to fill the table

INSERT INTO employee_daily_schedules_simple (
    employee_id, schedule_date, work_schedule_id, time_in, time_out,
    is_rest_day, is_override, schedule_type, override_request_id, 
    reason, day_of_week
)
SELECT 
    e.id AS employee_id,
    dates.date_val AS schedule_date,
    CASE 
        WHEN pscr.is_rest_day = 1 THEN NULL
        WHEN pscr.work_schedule_id IS NOT NULL THEN pscr.work_schedule_id
        ELSE e.official_sched 
    END AS work_schedule_id,
    CASE 
        WHEN pscr.is_rest_day = 1 THEN NULL
        WHEN pscr.work_schedule_id IS NOT NULL THEN ws_override.time_in
        ELSE ws_default.time_in 
    END AS time_in,
    CASE 
        WHEN pscr.is_rest_day = 1 THEN NULL
        WHEN pscr.work_schedule_id IS NOT NULL THEN ws_override.time_out
        ELSE ws_default.time_out 
    END AS time_out,
    CASE WHEN pscr.is_rest_day = 1 THEN 1 ELSE 0 END AS is_rest_day,
    CASE WHEN pscr.id IS NOT NULL AND pscr.is_rest_day = 0 THEN 1 ELSE 0 END AS is_override,
    CASE 
        WHEN pscr.is_rest_day = 1 THEN 'rest_day'
        WHEN pscr.id IS NOT NULL THEN 'override'
        ELSE 'default' 
    END AS schedule_type,
    pscr.id AS override_request_id,
    pscr.reason,
    DAYNAME(dates.date_val) AS day_of_week
FROM employees e
CROSS JOIN (
    SELECT DATE('2025-10-01') + INTERVAL (a.N + b.N * 10 + c.N * 100) DAY AS date_val
    FROM 
        (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
        (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
        (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2) c
    WHERE DATE('2025-10-01') + INTERVAL (a.N + b.N * 10 + c.N * 100) DAY <= '2026-01-31'
) AS dates
LEFT JOIN work_schedules ws_default ON e.official_sched = ws_default.id
LEFT JOIN post_schedule_change_requests pscr 
    ON e.id = pscr.employee_id 
    AND dates.date_val BETWEEN pscr.start_date AND pscr.end_date
    AND pscr.status = 'Approved'
LEFT JOIN work_schedules ws_override ON pscr.work_schedule_id = ws_override.id
WHERE e.status = 'active'
ON DUPLICATE KEY UPDATE
    work_schedule_id = VALUES(work_schedule_id),
    time_in = VALUES(time_in),
    time_out = VALUES(time_out),
    is_rest_day = VALUES(is_rest_day),
    is_override = VALUES(is_override),
    schedule_type = VALUES(schedule_type),
    override_request_id = VALUES(override_request_id),
    reason = VALUES(reason),
    day_of_week = VALUES(day_of_week),
    updated_at = CURRENT_TIMESTAMP;
