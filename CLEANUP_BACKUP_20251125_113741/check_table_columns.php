<?php
require_once 'Public/config/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Check Table Columns</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #333; color: white; }
    </style>
</head>
<body>
    <h1>Table Column Check</h1>
    
    <?php
    try {
        // Check employee_daily_schedules
        echo "<div class='box'>";
        echo "<h2>employee_daily_schedules columns:</h2>";
        $stmt = $pdo->query("DESCRIBE employee_daily_schedules");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
        
        // Check if table exists at all
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'employee_daily_schedules'")->fetch();
        if (!$tableCheck) {
            echo "<div class='box' style='background: #f8d7da; color: #721c24;'>";
            echo "<h2>❌ Table 'employee_daily_schedules' does NOT exist!</h2>";
            echo "<p>The automatic schedule processing system needs this table to work.</p>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='box' style='background: #f8d7da; color: #721c24;'>";
        echo "<h2>Error:</h2>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
    ?>
</body>
</html>
