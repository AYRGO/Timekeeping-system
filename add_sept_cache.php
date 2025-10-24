<?php
require 'Public/config/db.php';

echo "Adding September 29-30 cache entries for employee 1006...\n";

$sql = "INSERT INTO employee_daily_schedule_cache 
(employee_id, schedule_date, work_schedule_id, is_rest_day, is_holiday, schedule_name, time_in, time_out, holiday_name, source, source_id, created_at, updated_at) 
VALUES
(1006, '2025-09-29', 6, 0, 0, 'Day shift', '09:00:00', '18:00:00', NULL, 'weekly_default', NULL, NOW(), NOW()),
(1006, '2025-09-30', 6, 0, 0, 'Day shift', '09:00:00', '18:00:00', NULL, 'weekly_default', NULL, NOW(), NOW())";

try {
    $pdo->exec($sql);
    echo "✅ Successfully added Sept 29-30 entries!\n";
    
    // Verify
    $stmt = $pdo->query("SELECT schedule_date, time_in, time_out FROM employee_daily_schedule_cache WHERE employee_id = 1006 AND schedule_date IN ('2025-09-29', '2025-09-30')");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nVerification:\n";
    foreach ($results as $row) {
        echo "  {$row['schedule_date']}: {$row['time_in']}-{$row['time_out']}\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
