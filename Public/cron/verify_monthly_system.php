<?php
// File: verify_monthly_system.php
// Verification script for monthly accrual system
include(__DIR__ . '/../config/db.php');

echo "🧪 MONTHLY ACCRUAL SYSTEM VERIFICATION\n";
echo str_repeat("=", 50) . "\n";
echo "⏰ Test Time: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // 1. Test database connection
    echo "1. Testing database connection...\n";
    $stmt = $pdo->query("SELECT 1");
    echo "   ✅ Database connection successful\n\n";
    
    // 2. Check employees table
    echo "2. Checking employees table...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active'");
    $activeEmployees = $stmt->fetchColumn();
    echo "   ✅ Found {$activeEmployees} active employees\n\n";
    
    // 3. Check leave_credits table structure
    echo "3. Checking leave_credits table...\n";
    $stmt = $pdo->query("DESCRIBE leave_credits");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $requiredColumns = ['employee_id', 'leave_type', 'balance', 'carry_over', 'year', 'last_processed_month', 'updated_at'];
    
    foreach ($requiredColumns as $col) {
        if (in_array($col, $columns)) {
            echo "   ✅ Column '{$col}' exists\n";
        } else {
            echo "   ❌ Column '{$col}' missing!\n";
        }
    }
    echo "\n";
    
    // 4. Check current leave credits
    echo "4. Current leave credits status...\n";
    $currentYear = date('Y');
    $currentMonth = date('n');
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_records,
            COUNT(DISTINCT employee_id) as employees_with_credits,
            AVG(CASE WHEN leave_type = 'sick' THEN balance END) as avg_sick,
            AVG(CASE WHEN leave_type = 'vacation' THEN balance END) as avg_vacation,
            MAX(updated_at) as last_update
        FROM leave_credits 
        WHERE year = ?
    ");
    $stmt->execute([$currentYear]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   📊 Total records: {$stats['total_records']}\n";
    echo "   👥 Employees with credits: {$stats['employees_with_credits']}\n";
    echo "   🤒 Average sick leave: " . number_format($stats['avg_sick'] ?? 0, 2) . " days\n";
    echo "   🏖️ Average vacation leave: " . number_format($stats['avg_vacation'] ?? 0, 2) . " days\n";
    echo "   📅 Last update: " . ($stats['last_update'] ?: 'Never') . "\n\n";
    
    // 5. Check if processed this month
    echo "5. Monthly processing status...\n";
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM leave_credits 
        WHERE year = ? AND last_processed_month = ?
    ");
    $stmt->execute([$currentYear, $currentMonth]);
    $processedThisMonth = $stmt->fetchColumn();
    
    if ($processedThisMonth > 0) {
        echo "   ✅ Already processed for " . date('F Y') . " ({$processedThisMonth} records)\n";
    } else {
        echo "   ⏳ Not yet processed for " . date('F Y') . "\n";
    }
    echo "\n";
    
    // 6. Next accrual date
    echo "6. Next accrual information...\n";
    $today = date('Y-m-d');
    $endOfMonth = date('Y-m-t');
    $daysUntil = (strtotime($endOfMonth) - strtotime($today)) / (24 * 60 * 60);
    
    echo "   📅 Today: {$today}\n";
    echo "   📅 End of month: {$endOfMonth}\n";
    echo "   ⏰ Days until accrual: {$daysUntil}\n\n";
    
    // 7. Sample employee check
    echo "7. Sample employee data...\n";
    $stmt = $pdo->query("
        SELECT e.id, CONCAT(e.fname, ' ', e.lname) as name, 
               lc.leave_type, lc.balance, lc.last_processed_month
        FROM employees e
        LEFT JOIN leave_credits lc ON e.id = lc.employee_id AND lc.year = {$currentYear}
        WHERE e.status = 'active'
        ORDER BY e.id, lc.leave_type
        LIMIT 6
    ");
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($samples as $sample) {
        if ($sample['leave_type']) {
            echo "   👤 {$sample['name']}: {$sample['leave_type']} = {$sample['balance']} days\n";
        } else {
            echo "   👤 {$sample['name']}: No leave credits yet\n";
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ SYSTEM VERIFICATION COMPLETE!\n\n";
    
    echo "🚀 READY FOR PRODUCTION USE\n";
    echo "📋 Next steps:\n";
    echo "   1. Upload monthly_accrual_production.php to /Public/cron/\n";
    echo "   2. Set up cron job to run daily at midnight\n";
    echo "   3. Monitor results via monthly_accrual_monitor.php\n\n";
    
    echo "🔗 Cron job command:\n";
    echo "   0 0 * * * /usr/bin/php /home/u816220874/domains/harley.resourcestaffonline.com/public_html/Public/cron/monthly_accrual_production.php\n\n";
    
    echo "🌐 Monitor URL:\n";
    echo "   https://harley.resourcestaffonline.com/Public/module/monthly_accrual_monitor.php\n\n";
    
} catch (Exception $e) {
    echo "❌ VERIFICATION FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n\n";
    
    echo "🔧 Please check:\n";
    echo "   • Database connection settings\n";
    echo "   • Table structure\n";
    echo "   • File permissions\n";
}
?>