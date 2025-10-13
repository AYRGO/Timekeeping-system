<?php
// File: test_hostinger_connection.php
// Test database connection and leave credits table on Hostinger
include(__DIR__ . '/../config/db.php');

echo "🔍 HOSTINGER DATABASE CONNECTION TEST\n";
echo str_repeat("=", 50) . "\n";
echo "⏰ Test Time: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Test database connection
    echo "1. Testing database connection...\n";
    $stmt = $pdo->query("SELECT 1");
    echo "   ✅ Database connection successful!\n\n";
    
    // Test employees table
    echo "2. Testing employees table...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM employees WHERE status = 'active'");
    $empCount = $stmt->fetchColumn();
    echo "   ✅ Found {$empCount} active employees\n\n";
    
    // Test leave_credits table structure
    echo "3. Testing leave_credits table structure...\n";
    $stmt = $pdo->query("DESCRIBE leave_credits");
    $columns = $stmt->fetchAll();
    echo "   ✅ Leave credits table structure:\n";
    foreach ($columns as $col) {
        echo "      - {$col['Field']} ({$col['Type']})\n";
    }
    echo "\n";
    
    // Check existing leave credits
    echo "4. Checking existing leave credits...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM leave_credits WHERE year = " . date('Y'));
    $creditsCount = $stmt->fetchColumn();
    echo "   ✅ Found {$creditsCount} leave credit records for " . date('Y') . "\n\n";
    
    // Show sample leave credits
    echo "5. Sample leave credits (first 5 records):\n";
    $stmt = $pdo->query("
        SELECT lc.*, CONCAT(e.fname, ' ', e.lname) as employee_name 
        FROM leave_credits lc 
        JOIN employees e ON lc.employee_id = e.id 
        WHERE lc.year = " . date('Y') . " 
        ORDER BY lc.updated_at DESC 
        LIMIT 5
    ");
    $samples = $stmt->fetchAll();
    
    if (count($samples) > 0) {
        foreach ($samples as $sample) {
            echo "   📋 {$sample['employee_name']}: {$sample['leave_type']} = {$sample['balance']} days (Updated: {$sample['updated_at']})\n";
        }
    } else {
        echo "   ⚠️ No leave credit records found!\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ ALL TESTS PASSED!\n";
    echo "🚀 Ready to run leave accrual processing.\n\n";
    
    echo "🔗 Next steps:\n";
    echo "   1. Run: php hostinger_leave_accrual.php\n";
    echo "   2. Set up cron job for regular processing\n";
    echo "   3. Monitor results on website\n\n";
    
} catch (Exception $e) {
    echo "❌ TEST FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n\n";
    
    echo "🔧 Troubleshooting:\n";
    echo "   1. Check database credentials in config/db.php\n";
    echo "   2. Verify database server is accessible\n";
    echo "   3. Ensure leave_credits table exists\n";
    echo "   4. Check file permissions\n\n";
}
?>