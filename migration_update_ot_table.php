<?php
/**
 * Migration script to update new_ot_request table with missing columns
 * Run this file once to add log_date and total_hours columns
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include('Public/config/db.php');

try {
    echo "Starting migration to update new_ot_request table...\n";
    
    // Check if log_date column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM new_ot_request LIKE 'log_date'");
    if ($stmt->rowCount() == 0) {
        echo "Adding log_date column...\n";
        $pdo->exec("ALTER TABLE new_ot_request ADD COLUMN log_date DATE NOT NULL AFTER time_log_id");
        echo "✓ log_date column added successfully\n";
    } else {
        echo "✓ log_date column already exists\n";
    }
    
    // Check if total_hours column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM new_ot_request LIKE 'total_hours'");
    if ($stmt->rowCount() == 0) {
        echo "Adding total_hours column...\n";
        $pdo->exec("ALTER TABLE new_ot_request ADD COLUMN total_hours DECIMAL(4,2) NOT NULL AFTER time_out");
        echo "✓ total_hours column added successfully\n";
    } else {
        echo "✓ total_hours column already exists\n";
    }
    
    echo "\nMigration completed successfully!\n";
    echo "The new_ot_request table now includes:\n";
    echo "- log_date: Date of the overtime work\n";
    echo "- total_hours: Total hours worked in the shift\n";
    
} catch (PDOException $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
    exit(1);
}
?>
