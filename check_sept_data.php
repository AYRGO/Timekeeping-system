<?php
require 'Public/config/db.php';

echo "=== CHECKING EMPLOYEE 1006 DATA AROUND OCTOBER 1 ===\n\n";

// Check September 29-30 (the Monday-Tuesday before Oct 1)
echo "SEPTEMBER 29-30 (Mon-Tue before Oct 1):\n";
$stmt = $pdo->prepare("
    SELECT schedule_date, work_schedule_id, time_in, time_out, is_rest_day, source 
    FROM employee_daily_schedule_cache 
    WHERE employee_id = 1006 
    AND schedule_date BETWEEN '2025-09-29' AND '2025-09-30'
    ORDER BY schedule_date
");
$stmt->execute();
$sepData = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($sepData) {
    foreach ($sepData as $row) {
        $dayOfWeek = date('D', strtotime($row['schedule_date']));
        echo "  {$row['schedule_date']} ({$dayOfWeek}): ";
        if ($row['is_rest_day']) {
            echo "REST DAY\n";
        } else {
            echo "work_schedule_id={$row['work_schedule_id']}, {$row['time_in']}-{$row['time_out']}\n";
        }
    }
} else {
    echo "  NO DATA - Will fall back to employee_default_schedules\n";
}

echo "\n";
echo "OCTOBER 24-31 (Current week):\n";
$stmt2 = $pdo->prepare("
    SELECT schedule_date, work_schedule_id, time_in, time_out, is_rest_day, source 
    FROM employee_daily_schedule_cache 
    WHERE employee_id = 1006 
    AND schedule_date BETWEEN '2025-10-24' AND '2025-10-31'
    ORDER BY schedule_date
");
$stmt2->execute();
$octData = $stmt2->fetchAll(PDO::FETCH_ASSOC);

if ($octData) {
    foreach ($octData as $row) {
        $dayOfWeek = date('D', strtotime($row['schedule_date']));
        echo "  {$row['schedule_date']} ({$dayOfWeek}): ";
        if ($row['is_rest_day']) {
            echo "REST DAY\n";
        } else {
            echo "work_schedule_id={$row['work_schedule_id']}, {$row['time_in']}-{$row['time_out']}\n";
        }
    }
} else {
    echo "  NO DATA FOUND\n";
}
