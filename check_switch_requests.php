<?php
include('Public/config/db.php');

echo "<h2>Switch Requests in Database:</h2>";
$stmt = $pdo->query("SELECT * FROM schedule_switch_requests ORDER BY id DESC LIMIT 5");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<pre>";
print_r($requests);
echo "</pre>";

echo "<h2>Schedule Cache for Employee 1009 (Nov 14-15):</h2>";
$stmt = $pdo->query("SELECT * FROM employee_daily_schedule_cache WHERE employee_id = 1009 AND schedule_date IN ('2025-11-14', '2025-11-15') ORDER BY schedule_date");
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<pre>";
print_r($schedules);
echo "</pre>";
?>
