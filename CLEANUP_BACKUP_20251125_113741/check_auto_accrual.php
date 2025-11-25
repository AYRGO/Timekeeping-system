<?php
require_once 'Public/config/db.php';

echo "<h2>Auto-Accrual System Diagnostic</h2>";
echo "<hr>";

// Check if system_settings table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'system_settings'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "✓ <strong>system_settings table EXISTS</strong><br><br>";
        
        // Show table contents
        $stmt = $pdo->query("SELECT * FROM system_settings");
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Current Settings:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Setting Key</th><th>Setting Value</th><th>Updated At</th></tr>";
        foreach ($settings as $setting) {
            echo "<tr>";
            echo "<td>{$setting['id']}</td>";
            echo "<td>{$setting['setting_key']}</td>";
            echo "<td>{$setting['setting_value']}</td>";
            echo "<td>{$setting['updated_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "✗ <strong>system_settings table DOES NOT EXIST</strong><br>";
        echo "<br>You need to run the SQL migration first!<br>";
        echo "<br><a href='create_system_settings_table.sql' style='color: blue;'>View SQL File</a>";
        echo "<br><br>Copy and paste this SQL into phpMyAdmin:<br><br>";
        echo "<textarea style='width: 100%; height: 200px; font-family: monospace;'>";
        echo file_get_contents('create_system_settings_table.sql');
        echo "</textarea>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

echo "<br><br><a href='Public/views/admin_homepage.php' style='color: blue;'>← Back to Admin Dashboard</a>";
?>
