<?php
// Check database structure for leave_credits table
include('Public/config/db.php');

try {
    echo "LEAVE_CREDITS TABLE STRUCTURE:\n";
    echo str_repeat("=", 40) . "\n";
    
    $stmt = $pdo->query('DESCRIBE leave_credits');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "{$column['Field']} - {$column['Type']}\n";
    }
    
    echo "\nSAMPLE RECORDS:\n";
    echo str_repeat("=", 40) . "\n";
    
    $stmt = $pdo->query('SELECT * FROM leave_credits LIMIT 3');
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($records)) {
        // Show column names
        echo implode(' | ', array_keys($records[0])) . "\n";
        echo str_repeat("-", 80) . "\n";
        
        foreach ($records as $record) {
            echo implode(' | ', array_values($record)) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>