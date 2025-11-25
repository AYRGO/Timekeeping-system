<?php
require 'Public/config/db.php';

echo "=== CURRENT employee_default_schedules for 1006 ===\n\n";

$stmt = $pdo->prepare("
    SELECT day_of_week, work_schedule_id, is_rest_day, effective_from, effective_until
    FROM employee_default_schedules 
    WHERE employee_id = 1006
    ORDER BY day_of_week
");
$stmt->execute();
$defaults = $stmt->fetchAll(PDO::FETCH_ASSOC);

$days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
foreach ($defaults as $row) {
    echo "{$days[$row['day_of_week']]} (day={$row['day_of_week']}): ";
    if ($row['is_rest_day']) {
        echo "REST DAY";
    } else {
        echo "schedule_id={$row['work_schedule_id']}";
    }
    echo ", effective_from={$row['effective_from']}, effective_until=" . ($row['effective_until'] ?? 'NULL') . "\n";
}

echo "\n=== FIXING: Setting all weekdays to schedule_id=6 ===\n";

// Update Monday through Friday to use schedule_id 6
$updateStmt = $pdo->prepare("
    UPDATE employee_default_schedules 
    SET work_schedule_id = 6
    WHERE employee_id = 1006 
    AND day_of_week IN (1, 2, 3, 4, 5)
    AND is_rest_day = 0
");
$updateStmt->execute();

echo "Updated " . $updateStmt->rowCount() . " rows\n";

echo "\n=== AFTER UPDATE ===\n";
$stmt->execute();
$defaults = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($defaults as $row) {
    echo "{$days[$row['day_of_week']]} (day={$row['day_of_week']}): ";
    if ($row['is_rest_day']) {
        echo "REST DAY";
    } else {
        echo "schedule_id={$row['work_schedule_id']}";
    }
    echo "\n";
}
