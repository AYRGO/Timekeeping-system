<?php
// Debug script to check database structure
require_once '../config/db.php';

try {
    // Check what tables exist
    echo "<h3>Existing Tables:</h3>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "- $table<br>";
    }
    
    echo "<h3>Request-related Tables:</h3>";
    $requestTables = array_filter($tables, function($table) {
        return strpos($table, 'request') !== false || strpos($table, '_ot_') !== false;
    });
    
    foreach ($requestTables as $table) {
        echo "<h4>Table: $table</h4>";
        $columns = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            echo "  - {$column['Field']} ({$column['Type']}) {$column['Null']} {$column['Key']}<br>";
        }
        echo "<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>