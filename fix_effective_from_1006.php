<?php
require 'Public/config/db.php';

echo "=== UPDATING effective_from date to October 1, 2025 ===\n\n";

$stmt = $pdo->prepare("
    UPDATE employee_default_schedules 
    SET effective_from = '2025-10-01'
    WHERE employee_id = 1006
");
$stmt->execute();

echo "Updated " . $stmt->rowCount() . " rows\n\n";

// Verify
$verify = $pdo->prepare("
    SELECT day_of_week, work_schedule_id, is_rest_day, effective_from, effective_until
    FROM employee_default_schedules 
    WHERE employee_id = 1006
    ORDER BY day_of_week
");
$verify->execute();
$results = $verify->fetchAll(PDO::FETCH_ASSOC);

$days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
echo "AFTER UPDATE:\n";
foreach ($results as $row) {
    echo "{$days[$row['day_of_week']]}: ";
    if ($row['is_rest_day']) {
        echo "REST DAY";
    } else {
        echo "schedule_id={$row['work_schedule_id']}";
    }
    echo ", effective_from={$row['effective_from']}\n";
}

echo "\n✅ Now regenerate the report!";
