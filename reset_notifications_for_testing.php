<?php
include('Public/config/db.php');

echo "<h2>Reset Notification Flags (FOR TESTING ONLY)</h2>";
echo "<p><strong>WARNING:</strong> This will reset notification flags and may cause duplicate emails!</p>";

// Uncomment the sections below to reset specific notification flags

/*
// Reset leave request notifications (last 5)
echo "<h3>Resetting Leave Request Notifications...</h3>";
$stmt = $pdo->prepare("
    UPDATE leave_requests 
    SET notified = 0 
    WHERE status IN ('approved', 'declined', 'rejected') 
    ORDER BY created_at DESC 
    LIMIT 5
");
$result = $stmt->execute();
echo "Leave requests reset: " . ($result ? "Success" : "Failed") . "<br>";
*/

/*
// Reset schedule change notifications (last 5)  
echo "<h3>Resetting Schedule Change Notifications...</h3>";
$stmt = $pdo->prepare("
    UPDATE schedule_change_requests 
    SET notified = 0 
    WHERE status IN ('approved', 'declined') 
    ORDER BY created_at DESC 
    LIMIT 5
");
$result = $stmt->execute();
echo "Schedule change requests reset: " . ($result ? "Success" : "Failed") . "<br>";
*/

/*
// Reset time adjustment notifications (last 5)
echo "<h3>Resetting Time Adjustment Notifications...</h3>";
$stmt = $pdo->prepare("
    UPDATE time_adjustment_requests 
    SET notified = 0 
    WHERE status IN ('approved', 'declined', 'rejected') 
    ORDER BY created_at DESC 
    LIMIT 5
");
$result = $stmt->execute();
echo "Time adjustment requests reset: " . ($result ? "Success" : "Failed") . "<br>";
*/

echo "<p><strong>To use this script:</strong></p>";
echo "<ol>";
echo "<li>Uncomment the sections above for the request types you want to test</li>";
echo "<li>Run this script</li>";
echo "<li>Access the notification system (notification_modal.php) to trigger emails</li>";
echo "<li>Check that emails are sent to the respective employees</li>";
echo "</ol>";

echo "<p><strong>Note:</strong> Only use this for testing. In production, the system correctly prevents duplicate notifications.</p>";
?>