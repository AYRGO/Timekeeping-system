<?php
// Debug script to check monthly schedule approval
require_once __DIR__ . '/Public/config/connection.php';

echo "<h2>Monthly Schedule Requests</h2>";
$requests = $pdo->query("SELECT * FROM month_weekly_schedule ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($requests);
echo "</pre>";

echo "<h2>Employee Default Schedules (Resty Nazareno - Latest)</h2>";
$defaults = $pdo->query("
    SELECT eds.*, ws.name as schedule_name, ws.time_in, ws.time_out,
           CASE eds.day_of_week
               WHEN 0 THEN 'Sunday'
               WHEN 1 THEN 'Monday'
               WHEN 2 THEN 'Tuesday'
               WHEN 3 THEN 'Wednesday'
               WHEN 4 THEN 'Thursday'
               WHEN 5 THEN 'Friday'
               WHEN 6 THEN 'Saturday'
           END as day_name
    FROM employee_default_schedules eds
    LEFT JOIN work_schedules ws ON eds.work_schedule_id = ws.id
    WHERE eds.employee_id = 1006
    ORDER BY eds.effective_from DESC, eds.day_of_week ASC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($defaults);
echo "</pre>";

echo "<h2>Employee Daily Schedule Cache (December 2025 - First 10 days)</h2>";
$cache = $pdo->query("
    SELECT * FROM employee_daily_schedule_cache
    WHERE employee_id = 1006
      AND schedule_date BETWEEN '2025-12-01' AND '2025-12-10'
    ORDER BY schedule_date ASC
")->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($cache);
echo "</pre>";
