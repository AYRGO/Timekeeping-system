<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../config/db.php');

// Test with the specific request
$requestId = 37; // Change this to the problematic request ID
$employeeId = 1009; // Change this to the employee ID (from the request)

echo "<h2>🔍 Debug: Former Schedule Fetch</h2>";
echo "<hr>";

// Check the schedule_change_requests table (pending)
echo "<h3>1. Pending Requests (schedule_change_requests)</h3>";
$stmt = $pdo->prepare("
    SELECT 
        scr.id,
        scr.employee_id,
        scr.work_schedule_id as 'Requested Schedule ID',
        scr.current_work_schedule_id as 'Former Schedule ID',
        scr.status,
        current_ws.time_in as 'Former Time In',
        current_ws.time_out as 'Former Time Out',
        new_ws.time_in as 'Requested Time In',
        new_ws.time_out as 'Requested Time Out'
    FROM schedule_change_requests scr
    LEFT JOIN work_schedules current_ws ON scr.current_work_schedule_id = current_ws.id
    LEFT JOIN work_schedules new_ws ON scr.work_schedule_id = new_ws.id
    WHERE scr.id = ?
");
$stmt->execute([$requestId]);
$pendingResult = $stmt->fetch(PDO::FETCH_ASSOC);

if ($pendingResult) {
    echo "<pre>";
    print_r($pendingResult);
    echo "</pre>";
} else {
    echo "<p>❌ No pending request found with ID: $requestId</p>";
}

echo "<hr>";

// Check the post_schedule_change_requests table (approved/declined)
echo "<h3>2. Approved Requests (post_schedule_change_requests)</h3>";
$stmt2 = $pdo->prepare("
    SELECT 
        pscr.id,
        pscr.employee_id,
        pscr.work_schedule_id as 'Requested Schedule ID',
        pscr.current_work_schedule_id as 'Former Schedule ID',
        pscr.status,
        current_ws.time_in as 'Former Time In',
        current_ws.time_out as 'Former Time Out',
        new_ws.time_in as 'Requested Time In',
        new_ws.time_out as 'Requested Time Out'
    FROM post_schedule_change_requests pscr
    LEFT JOIN work_schedules current_ws ON pscr.current_work_schedule_id = current_ws.id
    LEFT JOIN work_schedules new_ws ON pscr.work_schedule_id = new_ws.id
    WHERE pscr.id = ?
");
$stmt2->execute([$requestId]);
$postResult = $stmt2->fetch(PDO::FETCH_ASSOC);

if ($postResult) {
    echo "<pre>";
    print_r($postResult);
    echo "</pre>";
} else {
    echo "<p>❌ No approved request found with ID: $requestId</p>";
}

echo "<hr>";

// Check what notification_modal.php would return
echo "<h3>3. What notification_modal.php Returns</h3>";
$schedule_stmt = $pdo->prepare("
    SELECT scr.id, scr.work_schedule_id, scr.status, scr.start_date, scr.end_date, scr.created_at,
           scr.current_work_schedule_id, 'pending' as source_table, scr.employee_id,
           current_ws.time_in as stored_current_time_in, current_ws.time_out as stored_current_time_out,
           new_ws.time_in as requested_time_in, new_ws.time_out as requested_time_out
    FROM schedule_change_requests scr
    LEFT JOIN work_schedules current_ws ON scr.current_work_schedule_id = current_ws.id
    LEFT JOIN work_schedules new_ws ON scr.work_schedule_id = new_ws.id
    WHERE scr.employee_id = ? AND scr.id = ?
    UNION ALL
    SELECT pscr.id, pscr.work_schedule_id, pscr.status, pscr.start_date, pscr.end_date, pscr.created_at,
           pscr.current_work_schedule_id, 'approved' as source_table, pscr.employee_id,
           current_ws2.time_in as stored_current_time_in, current_ws2.time_out as stored_current_time_out,
           new_ws2.time_in as requested_time_in, new_ws2.time_out as requested_time_out
    FROM post_schedule_change_requests pscr
    LEFT JOIN work_schedules current_ws2 ON pscr.current_work_schedule_id = current_ws2.id
    LEFT JOIN work_schedules new_ws2 ON pscr.work_schedule_id = new_ws2.id
    WHERE pscr.employee_id = ? AND pscr.id = ?
");
$schedule_stmt->execute([$employeeId, $requestId, $employeeId, $requestId]);
$notificationResult = $schedule_stmt->fetch(PDO::FETCH_ASSOC);

if ($notificationResult) {
    echo "<p><strong>Source Table:</strong> " . $notificationResult['source_table'] . "</p>";
    echo "<p><strong>Current Work Schedule ID (Former):</strong> " . $notificationResult['current_work_schedule_id'] . "</p>";
    echo "<p><strong>Former Schedule:</strong> " . 
         ($notificationResult['stored_current_time_in'] ? date('g:i A', strtotime($notificationResult['stored_current_time_in'])) : 'NULL') . 
         " - " . 
         ($notificationResult['stored_current_time_out'] ? date('g:i A', strtotime($notificationResult['stored_current_time_out'])) : 'NULL') . 
         "</p>";
    echo "<p><strong>Requested Schedule:</strong> " . 
         ($notificationResult['requested_time_in'] ? date('g:i A', strtotime($notificationResult['requested_time_in'])) : 'NULL') . 
         " - " . 
         ($notificationResult['requested_time_out'] ? date('g:i A', strtotime($notificationResult['requested_time_out'])) : 'NULL') . 
         "</p>";
    echo "<hr>";
    echo "<pre>";
    print_r($notificationResult);
    echo "</pre>";
} else {
    echo "<p>❌ No result from notification query</p>";
}

echo "<hr>";

// Check employee's current actual schedule
echo "<h3>4. Employee's ACTUAL Current Schedule (Live Calendar Lookup)</h3>";
$today = date('Y-m-d');

// Check cache
$cacheStmt = $pdo->prepare("
    SELECT work_schedule_id, time_in, time_out, is_rest_day
    FROM employee_daily_schedule_cache
    WHERE employee_id = ? AND schedule_date = ?
");
$cacheStmt->execute([$employeeId, $today]);
$cacheResult = $cacheStmt->fetch(PDO::FETCH_ASSOC);

if ($cacheResult) {
    echo "<p><strong>From Cache (today: $today):</strong></p>";
    echo "<p>Schedule ID: " . $cacheResult['work_schedule_id'] . "</p>";
    echo "<p>Time: " . 
         ($cacheResult['time_in'] ? date('g:i A', strtotime($cacheResult['time_in'])) : 'NULL') . 
         " - " . 
         ($cacheResult['time_out'] ? date('g:i A', strtotime($cacheResult['time_out'])) : 'NULL') . 
         "</p>";
    echo "<p>Is Rest Day: " . ($cacheResult['is_rest_day'] ? 'Yes' : 'No') . "</p>";
}

// Check default schedule
$dayOfWeek = date('w');
$defaultStmt = $pdo->prepare("
    SELECT eds.work_schedule_id, ws.time_in, ws.time_out, eds.is_rest_day
    FROM employee_default_schedules eds
    LEFT JOIN work_schedules ws ON eds.work_schedule_id = ws.id
    WHERE eds.employee_id = ? AND eds.day_of_week = ?
");
$defaultStmt->execute([$employeeId, $dayOfWeek]);
$defaultResult = $defaultStmt->fetch(PDO::FETCH_ASSOC);

if ($defaultResult) {
    echo "<p><strong>From Default Schedules (day " . $dayOfWeek . "):</strong></p>";
    echo "<p>Schedule ID: " . $defaultResult['work_schedule_id'] . "</p>";
    echo "<p>Time: " . 
         ($defaultResult['time_in'] ? date('g:i A', strtotime($defaultResult['time_in'])) : 'NULL') . 
         " - " . 
         ($defaultResult['time_out'] ? date('g:i A', strtotime($defaultResult['time_out'])) : 'NULL') . 
         "</p>";
    echo "<p>Is Rest Day: " . ($defaultResult['is_rest_day'] ? 'Yes' : 'No') . "</p>";
}

// Check official schedule
$empStmt = $pdo->prepare("SELECT official_sched FROM employees WHERE id = ?");
$empStmt->execute([$employeeId]);
$emp = $empStmt->fetch(PDO::FETCH_ASSOC);

if ($emp && $emp['official_sched']) {
    $officialSchedId = $emp['official_sched'];
    $schedStmt = $pdo->prepare("SELECT time_in, time_out FROM work_schedules WHERE id = ?");
    $schedStmt->execute([$officialSchedId]);
    $officialSched = $schedStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($officialSched) {
        echo "<p><strong>From Official Schedule (ID: $officialSchedId):</strong></p>";
        echo "<p>Time: " . 
             date('g:i A', strtotime($officialSched['time_in'])) . 
             " - " . 
             date('g:i A', strtotime($officialSched['time_out'])) . 
             "</p>";
    }
}
?>
