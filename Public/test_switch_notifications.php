<?php
session_start();
include('config/db.php');

// Set a test employee ID
$current_user_id = $_SESSION['employee']['id'] ?? 1009;

echo "<h2>Testing Schedule Swap Notifications</h2>";
echo "<p>Current User ID: $current_user_id</p>";

// Check schedule_switch_requests table
echo "<h3>Schedule Swap Requests in Database:</h3>";
$stmt = $pdo->prepare("SELECT * FROM schedule_switch_requests WHERE employee_id = ? ORDER BY created_at DESC");
$stmt->execute([$current_user_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($requests)) {
    echo "<p style='color: red;'>No schedule swap requests found for employee $current_user_id</p>";
} else {
    echo "<p style='color: green;'>Found " . count($requests) . " request(s)</p>";
    echo "<pre>";
    print_r($requests);
    echo "</pre>";
}

// Now test the notification building logic
echo "<h3>Testing Notification Building:</h3>";

// Helper function (copied from notification_modal.php)
function getActualCurrentScheduleFromCalendar($pdo, $employee_id, $date = null) {
    if (!$date) $date = date('Y-m-d');
    
    // PRIORITY 1: Check employee_daily_schedule_cache
    try {
        $stmt = $pdo->prepare("
            SELECT work_schedule_id, is_rest_day, schedule_name, time_in, time_out
            FROM employee_daily_schedule_cache 
            WHERE employee_id = ? AND schedule_date = ?
            LIMIT 1
        ");
        $stmt->execute([$employee_id, $date]);
        $cache = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cache) {
            if ($cache['is_rest_day'] == 1) {
                return ['time_in' => '—', 'time_out' => '—', 'is_rest_day' => true];
            }
            if ($cache['time_in'] && $cache['time_out']) {
                return [
                    'time_in' => date('g:i A', strtotime($cache['time_in'])),
                    'time_out' => date('g:i A', strtotime($cache['time_out'])),
                    'is_rest_day' => false
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Cache lookup failed: " . $e->getMessage());
    }
    
    return ['time_in' => '—', 'time_out' => '—', 'is_rest_day' => false];
}

$notifications = [];

foreach ($requests as $switch) {
    $status = ucfirst(strtolower($switch['status']));
    $source_date = date('F j, Y', strtotime($switch['source_date']));
    $target_date = date('F j, Y', strtotime($switch['target_date']));
    
    // Get schedule details for both dates
    $source_schedule = getActualCurrentScheduleFromCalendar($pdo, $current_user_id, $switch['source_date']);
    $target_schedule = getActualCurrentScheduleFromCalendar($pdo, $current_user_id, $switch['target_date']);
    
    $notification = [
        'message' => "Schedule swap request from <strong>{$source_date}</strong> to <strong>{$target_date}</strong> was <strong>{$status}</strong>.",
        'created_at' => $switch['created_at'],
        'type' => 'Schedule Swap Request',
        'status' => $switch['status'],
        'source_date' => $switch['source_date'],
        'target_date' => $switch['target_date'],
        'source_schedule_in' => $source_schedule['time_in'],
        'source_schedule_out' => $source_schedule['time_out'],
        'target_schedule_in' => $target_schedule['time_in'],
        'target_schedule_out' => $target_schedule['time_out'],
        'reason' => $switch['reason'],
        'attachment_scr' => $switch['attachment_path'] ?? '',
        'explanation' => $switch['admin_notes'] ?? '',
        'processed_at' => $switch['processed_at'],
        'request_id' => $switch['id'],
        'table_name' => 'schedule_switch_requests',
        'source_table' => 'schedule_switch_requests'
    ];
    
    $notifications[] = $notification;
}

echo "<h4>Built Notifications:</h4>";
echo "<pre>";
print_r($notifications);
echo "</pre>";

// Test JSON encoding (as used in the button)
if (!empty($notifications)) {
    echo "<h4>JSON Encoded (as passed to modal):</h4>";
    $testNotif = $notifications[0];
    $jsonData = [
        'type' => 'Schedule Swap Request',
        'status' => ucfirst(strtolower($testNotif['status'])),
        'date' => date('M j, Y', strtotime($testNotif['created_at'])),
        'source_date' => !empty($testNotif['source_date']) ? date('M j, Y', strtotime($testNotif['source_date'])) : '',
        'target_date' => !empty($testNotif['target_date']) ? date('M j, Y', strtotime($testNotif['target_date'])) : '',
        'source_schedule_in' => $testNotif['source_schedule_in'] ?? '',
        'source_schedule_out' => $testNotif['source_schedule_out'] ?? '',
        'target_schedule_in' => $testNotif['target_schedule_in'] ?? '',
        'target_schedule_out' => $testNotif['target_schedule_out'] ?? '',
        'reason' => $testNotif['reason'] ?? '',
        'explanation' => $testNotif['explanation'] ?? '',
        'attachment_scr' => $testNotif['attachment_scr'] ?? '',
        'request_id' => $testNotif['request_id'] ?? '',
        'table_name' => $testNotif['table_name'] ?? '',
        'source_table' => $testNotif['source_table'] ?? '',
        'processed_at' => !empty($testNotif['processed_at']) ? date('M j, Y g:i A', strtotime($testNotif['processed_at'])) : ''
    ];
    echo "<pre>";
    echo htmlspecialchars(json_encode($jsonData, JSON_PRETTY_PRINT));
    echo "</pre>";
}
?>
