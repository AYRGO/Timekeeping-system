<?php
require 'Public/config/db.php';

echo "Checking schedules for Pangilinan and Pasion:\n\n";

$stmt = $pdo->query("
    SELECT e.id, e.fname, e.lname, edd.day_of_week, ws.name as schedule_name, ws.time_in, ws.time_out 
    FROM employees e 
    JOIN employee_default_schedules edd ON e.id = edd.employee_id 
    LEFT JOIN work_schedules ws ON edd.work_schedule_id = ws.id 
    WHERE e.lname IN ('Pangilinan', 'Pasion') 
    ORDER BY e.lname, edd.day_of_week
");

$days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $dayName = $days[$row['day_of_week']];
    echo $row['lname'] . ', ' . $row['fname'] . ' (ID:' . $row['id'] . ')' . "\n";
    echo "  " . $dayName . ' (Day ' . $row['day_of_week'] . '): ';
    echo $row['schedule_name'] . ' - ' . $row['time_in'] . ' to ' . $row['time_out'] . "\n\n";
}
