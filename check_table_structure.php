<?php
require_once('Public/config/db.php');

try {
    // Check schedule_override_history table
    $tables = $pdo->query("SHOW TABLES LIKE 'schedule_override_history'")->fetchAll();
    
    if (count($tables) > 0) {
        echo "✓ Table 'schedule_override_history' EXISTS\n\n";
        
        // Show table structure
        $cols = $pdo->query("DESCRIBE schedule_override_history")->fetchAll(PDO::FETCH_ASSOC);
        echo "Columns:\n";
        foreach ($cols as $col) {
            echo sprintf("  %-30s %-25s %s\n", 
                $col['Field'], 
                $col['Type'], 
                $col['Key'] ? "[$col[Key]]" : ""
            );
        }
    } else {
        echo "✗ Table 'schedule_override_history' NOT FOUND!\n";
    }
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
