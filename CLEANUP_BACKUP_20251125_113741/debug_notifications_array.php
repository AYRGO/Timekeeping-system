<?php
session_start();
require_once 'Public/config/db.php';

// Simulate logged-in user
$_SESSION['user_id'] = 1009;
$current_user_id = 1009;

// Initialize notifications array
$notifications = [];

// Include notification_modal.php which should populate $notifications
ob_start();
include 'Public/module/notification_modal.php';
ob_end_clean();

echo "<h2>Notifications Debug for Employee $current_user_id</h2>";
echo "<p>Total notifications: " . count($notifications) . "</p>";

if (count($notifications) > 0) {
    echo "<h3>Notification Types:</h3>";
    $types = array_count_values(array_column($notifications, 'type'));
    echo "<pre>";
    print_r($types);
    echo "</pre>";
    
    echo "<h3>All Notifications:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>#</th><th>Type</th><th>Message</th><th>Status</th><th>Created</th></tr>";
    
    foreach ($notifications as $index => $notif) {
        echo "<tr>";
        echo "<td>" . ($index + 1) . "</td>";
        echo "<td>" . ($notif['type'] ?? 'N/A') . "</td>";
        echo "<td>" . strip_tags($notif['message']) . "</td>";
        echo "<td>" . ($notif['status'] ?? 'N/A') . "</td>";
        echo "<td>" . ($notif['created_at'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Show Monthly Schedule details if any
    $monthlyNotifs = array_filter($notifications, function($n) {
        return isset($n['type']) && $n['type'] === 'Monthly Schedule';
    });
    
    if (count($monthlyNotifs) > 0) {
        echo "<h3>Monthly Schedule Notifications Details:</h3>";
        echo "<pre>";
        foreach ($monthlyNotifs as $mn) {
            print_r($mn);
            echo "\n---\n";
        }
        echo "</pre>";
    }
} else {
    echo "<p style='color: red;'>No notifications found!</p>";
    
    // Debug: Check if queries are running
    echo "<h3>Checking database directly:</h3>";
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM month_weekly_schedule WHERE employee_id = ?");
    $stmt->execute([$current_user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Monthly schedule records for employee $current_user_id: " . $result['total'] . "</p>";
}
?>
