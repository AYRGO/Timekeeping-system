-- Ninoy Aquino Day is Aug 21, not Aug 25. Fixes 2026 only.
-- To also fix 2025 (retroactive - see caveat), change the two 2026 literals below.

START TRANSACTION;

-- 1. Source tables
UPDATE company_holidays
SET holiday_date = '2026-08-21'
WHERE holiday_name = 'Ninoy Aquino Day' AND holiday_date = '2026-08-25';

UPDATE employee_holiday_profile_holidays
SET holiday_date = '2026-08-21'
WHERE holiday_name = 'Ninoy Aquino Day' AND holiday_date = '2026-08-25';

-- 2. Derived cache: move the holiday flags from the 25th onto the 21st,
--    keeping each employee's own schedule on the 21st intact.
UPDATE employee_daily_schedule_cache t
JOIN employee_daily_schedule_cache s
  ON s.employee_id = t.employee_id
 AND s.schedule_date = '2026-08-25'
 AND s.holiday_name = 'Ninoy Aquino Day'
SET t.is_holiday   = 1,
    t.holiday_name = s.holiday_name,
    t.holiday_type = s.holiday_type,
    t.source       = CONCAT(SUBSTRING_INDEX(s.source, '|', 1), '|', SUBSTRING_INDEX(t.source, '|', -1))
WHERE t.schedule_date = '2026-08-21';

UPDATE employee_daily_schedule_cache
SET is_holiday   = 0,
    holiday_name = NULL,
    holiday_type = NULL,
    source       = SUBSTRING_INDEX(source, '|', -1)
WHERE schedule_date = '2026-08-25' AND holiday_name = 'Ninoy Aquino Day';

COMMIT;

-- 3. Verify: expect Ninoy rows on 08-21 and none on 08-25.
SELECT 'company_holidays' AS src, holiday_date, holiday_name FROM company_holidays
WHERE holiday_date IN ('2026-08-21','2026-08-25')
UNION ALL
SELECT 'profile_holidays', holiday_date, holiday_name FROM employee_holiday_profile_holidays
WHERE holiday_date IN ('2026-08-21','2026-08-25')
UNION ALL
SELECT CONCAT('cache x', COUNT(*)), schedule_date, holiday_name FROM employee_daily_schedule_cache
WHERE schedule_date IN ('2026-08-21','2026-08-25') AND holiday_name IS NOT NULL
GROUP BY schedule_date, holiday_name;
