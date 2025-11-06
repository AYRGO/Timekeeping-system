<?php
/**
 * Reset November Accrual - For Testing
 * This will reset the updated_at timestamp so the reminder banner shows again
 */

require_once 'Public/config/db.php';

try {
    // Simple approach: Set all updated_at timestamps back to October 31, 2025
    $stmt = $pdo->exec("
        UPDATE leave_credits 
        SET updated_at = '2025-10-31 23:59:59' 
        WHERE YEAR(updated_at) = 2025 
        AND MONTH(updated_at) = 11
    ");
    
    // Also reset the system tracking (if table exists)
    try {
        $pdo->exec("UPDATE system_settings SET setting_value = '202410' WHERE setting_key = 'last_auto_accrual_month'");
        echo "✓ Reset system tracking to October 2024<br>";
    } catch (PDOException $e) {
        echo "⚠ System settings table not found - skipping<br>";
    }
    
    echo "<br><strong>Done!</strong> Reset {$stmt} record(s).<br>";
    echo "<br>The 'Last processed' date is now showing as October 31, 2025.<br>";
    echo "Go back to your admin dashboard and you'll see the November reminder banner.<br>";
    echo "<br><a href='Public/views/admin_homepage.php' style='color: blue; text-decoration: underline;'>Go to Admin Dashboard</a>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset November Accrual</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔄 Reset November 2025 Accrual Tracking</h2>
        <hr>
        <?php // Results shown above ?>
    </div>
</body>
</html>
