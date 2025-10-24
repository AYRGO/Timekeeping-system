<?php
require 'Public/config/db.php';

echo "=== ALL employee_default_schedules entries for 1006 ===\n\n";

$stmt = $pdo->prepare("
    SELECT day_of_week, work_schedule_id, is_rest_day, effective_from, effective_until, id
    FROM employee_default_schedules 
    WHERE employee_id = 1006
    ORDER BY day_of_week, effective_from DESC
");
$stmt->execute();
$all = $stmt->fetchAll(PDO::FETCH_ASSOC);

$days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

$grouped = [];
foreach ($all as $row) {
    $grouped[$row['day_of_week']][] = $row;
}

foreach ($grouped as $dow => $entries) {
    echo "\n{$days[$dow]} (day_of_week={$dow}):\n";
    foreach ($entries as $entry) {
        echo "  ID {$entry['id']}: ";
        if ($entry['is_rest_day']) {
            echo "REST DAY";
        } else {
            echo "schedule_id={$entry['work_schedule_id']}";
        }
        echo ", from={$entry['effective_from']}, until=" . ($entry['effective_until'] ?? 'NULL') . "\n";
    }
}

echo "\n\n=== RECOMMENDATION ===\n";
echo "If there are old entries with different schedule_ids, they need to be:\n";
echo "1. Deleted, OR\n";
echo "2. Set effective_until to before October 1, 2025\n";
