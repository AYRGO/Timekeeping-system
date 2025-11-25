<?php
// Check if schedule_switch_requests table exists and show structure

include('Public/config/db.php');

try {
    echo "<h2>Checking schedule_switch_requests table...</h2>";
    
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'schedule_switch_requests'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "<p style='color: green;'>✅ Table 'schedule_switch_requests' EXISTS</p>";
        
        // Show table structure
        echo "<h3>Table Structure:</h3>";
        $stmt = $pdo->query("DESCRIBE schedule_switch_requests");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "<td>{$col['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show record count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM schedule_switch_requests");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p><strong>Total Records:</strong> {$count['count']}</p>";
        
        // Show sample records if any
        if ($count['count'] > 0) {
            echo "<h3>Sample Records (Latest 5):</h3>";
            $stmt = $pdo->query("SELECT * FROM schedule_switch_requests ORDER BY created_at DESC LIMIT 5");
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr>";
            foreach (array_keys($records[0]) as $key) {
                echo "<th>{$key}</th>";
            }
            echo "</tr>";
            foreach ($records as $record) {
                echo "<tr>";
                foreach ($record as $value) {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Table 'schedule_switch_requests' DOES NOT EXIST</p>";
        echo "<p>You need to run the SQL file: <strong>create_schedule_switch_table.sql</strong></p>";
        echo "<pre>";
        echo "Command: mysql -u root -p rss < create_schedule_switch_table.sql\n";
        echo "Or execute the SQL in phpMyAdmin/MySQL Workbench";
        echo "</pre>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
