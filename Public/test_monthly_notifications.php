<?php
session_start();
include('config/db.php');

$current_user_id = $_SESSION['employee']['id'] ?? 1009;

echo "<h2>Testing Monthly Schedule Notifications</h2>";
echo "<p>Current User ID: $current_user_id</p>";

// Check month_weekly_schedule table
echo "<h3>Monthly Schedule Requests in Database:</h3>";
$stmt = $pdo->prepare("SELECT * FROM month_weekly_schedule WHERE employee_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$current_user_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($requests)) {
    echo "<p style='color: red;'>No monthly schedule requests found for employee $current_user_id</p>";
} else {
    echo "<p style='color: green;'>Found " . count($requests) . " request(s)</p>";
    foreach ($requests as $req) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
        echo "<strong>ID:</strong> {$req['id']}<br>";
        echo "<strong>Month:</strong> " . date('F Y', strtotime("{$req['year']}-{$req['month']}-01")) . "<br>";
        echo "<strong>Status:</strong> {$req['status']}<br>";
        echo "<strong>Created:</strong> {$req['created_at']}<br>";
        
        echo "<strong>Weekly Schedule:</strong><br>";
        $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        foreach ($days as $day) {
            $schedule_id = $req["{$day}_schedule_id"];
            $is_rest = $req["{$day}_is_rest_day"];
            
            if ($is_rest == 1 || empty($schedule_id)) {
                echo "- " . ucfirst($day) . ": <span style='color: red;'>REST DAY</span><br>";
            } else {
                // Get schedule
                $sched_stmt = $pdo->prepare("SELECT time_in, time_out FROM work_schedules WHERE id = ?");
                $sched_stmt->execute([$schedule_id]);
                $sched = $sched_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($sched) {
                    $time_in = date('g:i A', strtotime($sched['time_in']));
                    $time_out = date('g:i A', strtotime($sched['time_out']));
                    echo "- " . ucfirst($day) . ": <span style='color: green;'>{$time_in} - {$time_out}</span><br>";
                } else {
                    echo "- " . ucfirst($day) . ": <span style='color: orange;'>Schedule ID {$schedule_id} not found</span><br>";
                }
            }
        }
        echo "</div>";
    }
}
?>
