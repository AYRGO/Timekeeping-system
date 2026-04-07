<?php
/**
 * Add holiday_type column to employee_daily_schedule_cache
 * and update existing records
 */

// Use the application's database configuration
require_once 'Public/config/db.php';

// Check if database connection is available
if (!isset($pdo)) {
    die("Database connection not available. Please check your database configuration.");
}

echo "<pre>";
echo "========================================\n";
echo "Adding holiday_type to cache table...\n";
echo "========================================\n\n";

try {
    // Add holiday_type column
    $pdo->exec("
        ALTER TABLE employee_daily_schedule_cache 
        ADD COLUMN holiday_type ENUM('regular', 'special_non_working', 'special_working') DEFAULT NULL 
        AFTER holiday_name
    ");
    echo "✓ Added holiday_type column to employee_daily_schedule_cache\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "✓ holiday_type column already exists\n";
    } else {
        echo "❌ Error adding column: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Update existing records with holiday types
try {
    $stmt = $pdo->exec("
        UPDATE employee_daily_schedule_cache edc
        INNER JOIN company_holidays ch ON edc.holiday_name = ch.holiday_name
        SET edc.holiday_type = ch.holiday_type
        WHERE edc.is_holiday = 1 AND edc.holiday_name IS NOT NULL
    ");
    echo "✓ Updated $stmt existing cache records with holiday types\n";
} catch (PDOException $e) {
    echo "❌ Error updating records: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n========================================\n";
echo "✓ Migration complete!\n";
echo "========================================\n";
echo "</pre>";

?>
