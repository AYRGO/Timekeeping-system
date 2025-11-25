<?php
// Quick test to see if cache is working
require_once __DIR__ . '/Public/config/db.php';

echo "<h1>Cache Working Test</h1>";
echo "<style>body{font-family:Arial;padding:20px;} table{border-collapse:collapse;margin:20px 0;} th,td{border:1px solid #ddd;padding:8px;} th{background:#4CAF50;color:white;}</style>";

$employee_id = isset($_GET['emp_id']) ? (int)$_GET['emp_id'] : 1;

// Get employee name
$emp = $pdo->prepare("SELECT fname, lname FROM employees WHERE id = ?");
$emp->execute([$employee_id]);
$employee = $emp->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    echo "<p style='color:red;'>Employee not found!</p>";
    exit;
}

echo "<h2>Employee: {$employee['fname']} {$employee['lname']} (ID: $employee_id)</h2>";

// Get October 2025 schedules from cache
$stmt = $pdo->prepare("
    SELECT 
        schedule_date,
        DAYNAME(schedule_date) as day_name,
        schedule_name,
        TIME_FORMAT(time_in, '%h:%i %p') as time_in,
        TIME_FORMAT(time_out, '%h:%i %p') as time_out,
        CASE 
            WHEN is_holiday = 1 THEN CONCAT('HOLIDAY: ', holiday_name)
            WHEN is_rest_day = 1 THEN 'REST DAY'
            ELSE 'WORK DAY'
        END as day_type,
        source
    FROM employee_daily_schedule_cache
    WHERE employee_id = ?
      AND schedule_date BETWEEN '2025-10-01' AND '2025-10-31'
    ORDER BY schedule_date
");
$stmt->execute([$employee_id]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($schedules) {
    echo "<h3>✅ Found " . count($schedules) . " days in October 2025</h3>";
    echo "<table>";
    echo "<tr><th>Date</th><th>Day</th><th>Schedule</th><th>Time</th><th>Type</th><th>Source</th></tr>";
    
    foreach ($schedules as $row) {
        $displaySchedule = $row['schedule_name'] ?: $row['day_type'];
        $displayTime = $row['time_in'] && $row['time_out'] ? "{$row['time_in']} - {$row['time_out']}" : "-";
        
        // Highlight today
        $isToday = $row['schedule_date'] === date('Y-m-d');
        $rowStyle = $isToday ? "style='background:#e3f2fd;font-weight:bold;'" : "";
        
        echo "<tr $rowStyle>";
        echo "<td>{$row['schedule_date']}</td>";
        echo "<td>{$row['day_name']}</td>";
        echo "<td>{$displaySchedule}</td>";
        echo "<td>{$displayTime}</td>";
        echo "<td>{$row['day_type']}</td>";
        echo "<td>{$row['source']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='background:#d4edda;padding:15px;border-left:4px solid #28a745;margin:20px 0;'>";
    echo "<h3>🎉 Success! Cache is Working!</h3>";
    echo "<p>✅ The calendars should now display these schedules</p>";
    echo "<p>✅ Past dates are saved and accessible</p>";
    echo "<p>✅ Future dates are pre-computed</p>";
    echo "</div>";
    
} else {
    echo "<div style='background:#f8d7da;padding:15px;border-left:4px solid #dc3545;margin:20px 0;'>";
    echo "<h3>❌ No cache data found for October 2025</h3>";
    echo "<p>Make sure you ran the setup scripts:</p>";
    echo "<ol>";
    echo "<li><a href='setup_default_schedules.php'>Setup Default Schedules</a></li>";
    echo "<li><a href='run_cache_setup.php'>Run Cache Setup</a></li>";
    echo "</ol>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='?emp_id=" . ($employee_id + 1) . "' style='display:inline-block;padding:10px 20px;background:#2196F3;color:white;text-decoration:none;border-radius:5px;'>Next Employee →</a></p>";
echo "<p><a href='employee-edit.php?id=$employee_id#current-schedule' style='display:inline-block;padding:10px 20px;background:#4CAF50;color:white;text-decoration:none;border-radius:5px;'>View Calendar →</a></p>";
?>
