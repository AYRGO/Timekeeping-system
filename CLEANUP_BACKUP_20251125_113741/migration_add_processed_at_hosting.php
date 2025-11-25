<?php
// Migration script to add processed_at column to post_leave_requests table
// Run this on your hosting server

// Include your database configuration
include('Public/config/db.php');

try {
    echo "Starting migration to add processed_at column...\n";
    
    // Check if the column already exists
    $checkColumn = $pdo->query("SHOW COLUMNS FROM post_leave_requests LIKE 'processed_at'");
    if ($checkColumn->rowCount() > 0) {
        echo "processed_at column already exists. Migration not needed.\n";
        exit;
    }
    
    // Add processed_at column to post_leave_requests table
    $sql = "ALTER TABLE post_leave_requests ADD COLUMN processed_at TIMESTAMP NULL DEFAULT NULL AFTER created_at";
    $pdo->exec($sql);
    echo "✓ Added processed_at column to post_leave_requests table\n";
    
    // Update existing records to set processed_at = created_at (since they were processed when moved)
    $sql = "UPDATE post_leave_requests SET processed_at = created_at WHERE processed_at IS NULL";
    $affectedRows = $pdo->exec($sql);
    echo "✓ Updated $affectedRows existing records with processed_at values\n";
    
    echo "\n🎉 Migration completed successfully!\n";
    echo "The processed_at column has been added and populated.\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Please check your database connection and try again.\n";
}
?>
