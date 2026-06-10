-- Keep each employee's latest weekly schedule active until a newer schedule starts.
-- Safe to run more than once.

UPDATE employee_default_schedules eds
INNER JOIN (
    SELECT employee_id, MAX(effective_from) AS latest_effective_from
    FROM employee_default_schedules
    GROUP BY employee_id
) latest
    ON latest.employee_id = eds.employee_id
   AND latest.latest_effective_from = eds.effective_from
SET eds.effective_until = NULL,
    eds.updated_at = CURRENT_TIMESTAMP
WHERE eds.effective_until IS NOT NULL;
