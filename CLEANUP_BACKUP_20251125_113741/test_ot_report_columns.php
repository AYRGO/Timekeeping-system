<?php
require 'Public/config/db.php';

echo "Testing post_ot_requests table columns...\n\n";

try {
    // Check table structure
    $stmt = $pdo->query("DESCRIBE post_ot_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\n";
    
    // Test query with correct column names
    $testQuery = "
        SELECT pot.employee_id, pot.ot_duration, pot.ot_type, tl.log_date, e.fname, e.lname
        FROM post_ot_requests pot
        LEFT JOIN time_logs tl ON pot.time_log_id = tl.id
        LEFT JOIN employees e ON pot.employee_id = e.id
        WHERE pot.status = 'approved'
        LIMIT 3
    ";
    
    $stmt = $pdo->prepare($testQuery);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Sample query results:\n";
    if (empty($results)) {
        echo "No approved overtime records found.\n";
    } else {
        foreach ($results as $row) {
            echo "Employee: {$row['fname']} {$row['lname']}, Date: {$row['log_date']}, Duration: {$row['ot_duration']}, Type: {$row['ot_type']}\n";
        }
    }
    
    echo "\nColumn names are correct! The report should work now.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}