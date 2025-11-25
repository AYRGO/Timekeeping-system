<?php
require_once 'Public/config/db.php';

// Get current user from session or use test user
session_start();
$current_user_id = $_SESSION['user_id'] ?? 1009;

echo "<h2>Testing Monthly Schedule Notifications</h2>";
echo "<p>Employee ID: $current_user_id</p>";

// Test the exact query from notification_modal.php
$monthly_stmt = $pdo->prepare("
    SELECT id, employee_id, year, month, status, created_at, processed_at, processed_by, admin_notes, attachment_path, reason,
           sunday_schedule_id, sunday_is_rest_day, monday_schedule_id, monday_is_rest_day,
           tuesday_schedule_id, tuesday_is_rest_day, wednesday_schedule_id, wednesday_is_rest_day,
           thursday_schedule_id, thursday_is_rest_day, friday_schedule_id, friday_is_rest_day,
           saturday_schedule_id, saturday_is_rest_day
    FROM month_weekly_schedule
    WHERE employee_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");

try {
    $monthly_stmt->execute([$current_user_id]);
    $monthly_results = $monthly_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Found " . count($monthly_results) . " monthly schedule requests</h3>";
    
    if (count($monthly_results) > 0) {
        echo "<pre>";
        foreach ($monthly_results as $monthly) {
            echo "Request ID: " . $monthly['id'] . "\n";
            echo "Year: " . $monthly['year'] . "\n";
            echo "Month: " . $monthly['month'] . "\n";
            echo "Status: " . $monthly['status'] . "\n";
            echo "Created: " . $monthly['created_at'] . "\n";
            echo "---\n";
            
            // Test the notification building logic
            $raw_status = strtolower($monthly['status']);
            if ($raw_status === 'approved') {
                $status = 'Approved';
            } elseif ($raw_status === 'declined' || $raw_status === 'rejected') {
                $status = 'Declined';
            } else {
                $status = 'Pending';
            }
            
            $scheduleMonth = date('F Y', strtotime($monthly['year'] . '-' . str_pad($monthly['month'], 2, '0', STR_PAD_LEFT) . '-01'));
            $message = "Monthly schedule request for <strong>$scheduleMonth</strong> was <strong>$status</strong>.";
            
            echo "Formatted message: " . strip_tags($message) . "\n";
            echo "Schedule month formatted: $scheduleMonth\n";
            echo "\n";
        }
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>No monthly schedule requests found for this employee.</p>";
        
        // Check if there are ANY records in the table
        $allStmt = $pdo->query("SELECT COUNT(*) as total FROM month_weekly_schedule");
        $total = $allStmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Total records in month_weekly_schedule table: " . $total['total'] . "</p>";
        
        if ($total['total'] > 0) {
            echo "<p>Checking all employee IDs in table:</p>";
            $empStmt = $pdo->query("SELECT DISTINCT employee_id FROM month_weekly_schedule");
            $empIds = $empStmt->fetchAll(PDO::FETCH_COLUMN);
            echo "<p>Employee IDs with requests: " . implode(', ', $empIds) . "</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
