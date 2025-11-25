<?php
echo "<h2>📊 Leave Credit Monitor</h2>";
echo "<p><strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

try {
    include('Public/config/db.php');
    
    // Check recent leave credit updates
    $stmt = $pdo->query("
        SELECT 
            lc.updated_at,
            e.fname,
            e.lname,
            lc.leave_type,
            lc.balance,
            lc.last_processed_month
        FROM leave_credits lc
        JOIN employees e ON lc.employee_id = e.id
        WHERE lc.updated_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ORDER BY lc.updated_at DESC
        LIMIT 10
    ");
    
    $recentUpdates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($recentUpdates)) {
        echo "<h3 style='color: green;'>✅ Recent Updates (Last 5 minutes):</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Time</th><th>Employee</th><th>Leave Type</th><th>Balance</th><th>Month</th></tr>";
        
        foreach ($recentUpdates as $update) {
            echo "<tr>";
            echo "<td>" . $update['updated_at'] . "</td>";
            echo "<td>" . $update['fname'] . " " . $update['lname'] . "</td>";
            echo "<td>" . $update['leave_type'] . "</td>";
            echo "<td>" . $update['balance'] . "</td>";
            echo "<td>" . ($update['last_processed_month'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<h3 style='color: orange;'>⏳ No updates in the last 5 minutes</h3>";
        echo "<p>The cron job may not be running yet, or there's an issue.</p>";
    }
    
    // Check if the last_processed_month column exists
    $columnCheck = $pdo->query("SHOW COLUMNS FROM leave_credits LIKE 'last_processed_month'");
    if ($columnCheck->rowCount() == 0) {
        echo "<h3 style='color: red;'>❌ Missing Database Column</h3>";
        echo "<p>You need to add the 'last_processed_month' column to your database:</p>";
        echo "<code>ALTER TABLE leave_credits ADD COLUMN last_processed_month INT DEFAULT NULL;</code>";
    } else {
        echo "<h3 style='color: green;'>✅ Database column 'last_processed_month' exists</h3>";
    }
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Database Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><small>Refresh this page every minute to monitor updates. Delete after testing!</small></p>";
echo "<script>setTimeout(function(){ location.reload(); }, 60000);</script>"; // Auto-refresh every minute
?>

<style>
table { width: 100%; margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>