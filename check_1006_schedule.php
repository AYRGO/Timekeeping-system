<?php
require 'Public/config/db.php';

echo "=== CHECKING EMPLOYEE 1006 SCHEDULE DATA ===\n\n";

// Check employee_daily_schedule_cache
echo "1. EMPLOYEE_DAILY_SCHEDULE_CACHE (Oct 1-10):\n";
$stmt = $pdo->prepare("
    SELECT schedule_date, work_schedule_id, time_in, time_out, is_rest_day, source 
    FROM employee_daily_schedule_cache 
    WHERE employee_id = 1006 
    AND schedule_date BETWEEN '2025-10-01' AND '2025-10-10'
    ORDER BY schedule_date
");
$stmt->execute();
$cacheData = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($cacheData) {
    foreach ($cacheData as $row) {
        $dayOfWeek = date('D', strtotime($row['schedule_date']));
        echo "  {$row['schedule_date']} ({$dayOfWeek}): ";
        if ($row['is_rest_day']) {
            echo "REST DAY\n";
        } else {
            echo "work_schedule_id={$row['work_schedule_id']}, {$row['time_in']}-{$row['time_out']}, source={$row['source']}\n";
        }
    }
} else {
    echo "  NO DATA FOUND IN CACHE!\n";
}

echo "\n2. EMPLOYEE_DEFAULT_SCHEDULES:\n";
$stmt2 = $pdo->prepare("
    SELECT eds.day_of_week, eds.work_schedule_id, eds.is_rest_day, ws.name, ws.time_in, ws.time_out
    FROM employee_default_schedules eds
    LEFT JOIN work_schedules ws ON eds.work_schedule_id = ws.id
    WHERE eds.employee_id = 1006
    ORDER BY eds.day_of_week
");
$stmt2->execute();
$defaultData = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
if ($defaultData) {
    foreach ($defaultData as $row) {
        $dayName = $days[$row['day_of_week']];
        echo "  {$dayName} (day_of_week={$row['day_of_week']}): ";
        if ($row['is_rest_day']) {
            echo "REST DAY\n";
        } else {
            echo "work_schedule_id={$row['work_schedule_id']}, {$row['name']}, {$row['time_in']}-{$row['time_out']}\n";
        }
    }
} else {
    echo "  NO DEFAULT SCHEDULES FOUND!\n";
}

echo "\n3. WORK_SCHEDULES TABLE (ID 4 and 6):\n";
$stmt3 = $pdo->query("SELECT id, name, time_in, time_out FROM work_schedules WHERE id IN (4, 6)");
$schedules = $stmt3->fetchAll(PDO::FETCH_ASSOC);
foreach ($schedules as $sched) {
    echo "  ID {$sched['id']}: {$sched['name']}, {$sched['time_in']}-{$sched['time_out']}\n";
}

echo "\n=== END ===\n";
