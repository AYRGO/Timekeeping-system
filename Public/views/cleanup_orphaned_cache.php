<?php
/**
 * Cleanup Orphaned Cache Entries
 * 
 * This script removes cache entries from employee_daily_schedule_cache 
 * where source='approved_request' but the source_id doesn't exist in 
 * post_schedule_change_requests anymore (request was deleted).
 * 
 * Then rebuilds those dates using the employee's default weekly schedule.
 */

require_once('../config/db.php');

// Find orphaned cache entries (approved_request with invalid source_id)
$orphanedQuery = "
    SELECT DISTINCT 
        c.employee_id,
        c.schedule_date,
        c.source_id
    FROM employee_daily_schedule_cache c
    WHERE c.source = 'approved_request'
      AND (c.source_id IS NULL 
           OR c.source_id = 0
           OR NOT EXISTS (
               SELECT 1 FROM post_schedule_change_requests p 
               WHERE p.id = c.source_id
           ))
    ORDER BY c.employee_id, c.schedule_date
";

$stmt = $pdo->query($orphanedQuery);
$orphanedEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cleaned = 0;
$rebuilt = 0;

foreach ($orphanedEntries as $entry) {
    $employee_id = $entry['employee_id'];
    $schedule_date = $entry['schedule_date'];
    
    // Delete orphaned entry
    $deleteStmt = $pdo->prepare("
        DELETE FROM employee_daily_schedule_cache 
        WHERE employee_id = ? AND schedule_date = ? AND source = 'approved_request'
    ");
    $deleteStmt->execute([$employee_id, $schedule_date]);
    $cleaned++;
    
    // Rebuild from default schedule
    $dayOfWeek = date('w', strtotime($schedule_date)); // 0 (Sun) to 6 (Sat)
    
    // Get default schedule for this day
    $defaultStmt = $pdo->prepare("
        SELECT eds.work_schedule_id, eds.is_rest_day, ws.name, ws.time_in, ws.time_out
        FROM employee_default_schedules eds
        LEFT JOIN work_schedules ws ON eds.work_schedule_id = ws.id
        WHERE eds.employee_id = ? AND eds.day_of_week = ?
    ");
    $defaultStmt->execute([$employee_id, $dayOfWeek]);
    $defaultSched = $defaultStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($defaultSched) {
        // Insert new cache entry with default schedule
        $insertStmt = $pdo->prepare("
            INSERT INTO employee_daily_schedule_cache 
            (employee_id, schedule_date, work_schedule_id, is_rest_day, is_holiday, 
             schedule_name, time_in, time_out, holiday_name, source, source_id, created_at)
            VALUES (?, ?, ?, ?, 0, ?, ?, ?, NULL, 'weekly_default', NULL, NOW())
        ");
        $insertStmt->execute([
            $employee_id,
            $schedule_date,
            $defaultSched['work_schedule_id'],
            $defaultSched['is_rest_day'],
            $defaultSched['name'],
            $defaultSched['time_in'],
            $defaultSched['time_out']
        ]);
        $rebuilt++;
    }
}

echo json_encode([
    'success' => true,
    'cleaned' => $cleaned,
    'rebuilt' => $rebuilt,
    'message' => "Cleaned $cleaned orphaned entries and rebuilt $rebuilt with default schedules."
]);
?>
