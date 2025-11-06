<?php
include('Public/config/db.php');

// Check what data is being fetched for request ID 37
$stmt = $pdo->prepare("
    SELECT pscr.id, pscr.work_schedule_id, pscr.current_work_schedule_id, pscr.employee_id,
           current_ws.name as current_name, current_ws.time_in as current_in, current_ws.time_out as current_out,
           new_ws.name as requested_name, new_ws.time_in as requested_in, new_ws.time_out as requested_out
    FROM post_schedule_change_requests pscr
    LEFT JOIN work_schedules current_ws ON pscr.current_work_schedule_id = current_ws.id
    LEFT JOIN work_schedules new_ws ON pscr.work_schedule_id = new_ws.id
    WHERE pscr.id = 37
");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h2>Schedule Change Request ID 37</h2>";
echo "<pre>";
print_r($result);
echo "</pre>";

// Convert to 12-hour format
if ($result) {
    echo "<h3>Formatted Times:</h3>";
    echo "Current Schedule: " . date('g:i A', strtotime($result['current_in'])) . " - " . date('g:i A', strtotime($result['current_out'])) . "<br>";
    echo "Requested Schedule: " . date('g:i A', strtotime($result['requested_in'])) . " - " . date('g:i A', strtotime($result['requested_out'])) . "<br>";
}
?>
