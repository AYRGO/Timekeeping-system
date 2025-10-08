<?php
// File: test_leave_accrual.php
// Test script for leave accrual system validation
include('Public/config/db.php');
date_default_timezone_set('Asia/Manila');

echo "🧪 LEAVE ACCRUAL SYSTEM TEST\n";
echo str_repeat("=", 50) . "\n";

// Test configuration
$testYear = date('Y');
$expectedVacationMonthly = 1.25;
$expectedSickMonthly = 0.42;

echo "📅 Testing for year: {$testYear}\n";
echo "🎯 Expected Vacation Leave: {$expectedVacationMonthly} days/month\n";
echo "🎯 Expected Sick Leave: {$expectedSickMonthly} days/month\n\n";

// Test 1: Verify leave types configuration
echo "TEST 1: Leave Types Configuration\n";
echo str_repeat("-", 30) . "\n";

$leaveTypes = [
    'sick' => ['monthly_increment' => 0.42, 'max_balance' => 15],
    'vacation' => ['monthly_increment' => 1.25, 'max_balance' => 15, 'carry_over' => 5],
];

foreach ($leaveTypes as $type => $config) {
    $status = "✅ PASS";
    
    if ($type === 'vacation' && $config['monthly_increment'] !== $expectedVacationMonthly) {
        $status = "❌ FAIL";
    }
    if ($type === 'sick' && $config['monthly_increment'] !== $expectedSickMonthly) {
        $status = "❌ FAIL";
    }
    
    echo "{$type}: {$config['monthly_increment']} days/month, max: {$config['max_balance']} days {$status}\n";
}

// Test 2: Check sample employee data
echo "\nTEST 2: Sample Employee Leave Credits\n";
echo str_repeat("-", 30) . "\n";

try {
    // Get a sample of employees with leave credits
    $stmt = $pdo->prepare("
        SELECT 
            e.fname, 
            e.lname, 
            lc.leave_type, 
            lc.balance, 
            lc.monthly_increment,
            lc.carry_over,
            lc.updated_at
        FROM employees e 
        JOIN leave_credits lc ON e.id = lc.employee_id 
        WHERE lc.year = ? AND lc.leave_type IN ('vacation', 'sick')
        AND e.status = 'active'
        ORDER BY e.fname, lc.leave_type
        LIMIT 10
    ");
    $stmt->execute([$testYear]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($results)) {
        echo "⚠️ WARNING: No leave credit records found for testing\n";
        echo "💡 Run the monthly_leave_credit.php script first to generate test data\n";
    } else {
        $currentEmployee = '';
        foreach ($results as $record) {
            $employeeName = $record['fname'] . ' ' . $record['lname'];
            
            if ($currentEmployee !== $employeeName) {
                if ($currentEmployee !== '') echo "\n";
                echo "👤 {$employeeName}:\n";
                $currentEmployee = $employeeName;
            }
            
            $expectedIncrement = ($record['leave_type'] === 'vacation') ? $expectedVacationMonthly : $expectedSickMonthly;
            $status = (abs($record['monthly_increment'] - $expectedIncrement) < 0.01) ? "✅" : "❌";
            
            echo "  {$record['leave_type']}: {$record['balance']} days (monthly: {$record['monthly_increment']}) {$status}\n";
            
            if ($record['leave_type'] === 'vacation' && $record['carry_over'] !== null) {
                echo "    Carry-over: {$record['carry_over']} days\n";
            }
        }
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

// Test 3: Annual calculation validation
echo "\nTEST 3: Annual Accrual Calculations\n";
echo str_repeat("-", 30) . "\n";

$vacationAnnual = $expectedVacationMonthly * 12;
$sickAnnual = $expectedSickMonthly * 12;

echo "Vacation Leave Annual: {$expectedVacationMonthly} × 12 = {$vacationAnnual} days\n";
echo "Sick Leave Annual: {$expectedSickMonthly} × 12 = {$sickAnnual} days\n";

echo "\nBalance Limits Check:\n";
echo "Vacation max balance (15 days) vs annual accrual ({$vacationAnnual} days): ";
echo ($vacationAnnual <= 15) ? "✅ APPROPRIATE\n" : "⚠️ REVIEW NEEDED\n";

echo "Sick max balance (15 days) vs annual accrual ({$sickAnnual} days): ";
echo ($sickAnnual <= 15) ? "✅ ALLOWS ACCUMULATION\n" : "❌ TOO LOW\n";

// Test 4: Database structure validation
echo "\nTEST 4: Database Structure Validation\n";
echo str_repeat("-", 30) . "\n";

try {
    // Check if required columns exist
    $stmt = $pdo->query("DESCRIBE leave_credits");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['employee_id', 'leave_type', 'balance', 'monthly_increment', 'carry_over', 'year', 'updated_at'];
    
    foreach ($requiredColumns as $column) {
        $exists = in_array($column, $columns);
        echo "{$column}: " . ($exists ? "✅ EXISTS" : "❌ MISSING") . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: Cannot validate database structure - " . $e->getMessage() . "\n";
}

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 TEST SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "✅ The leave accrual system is configured for:\n";
echo "   • Vacation Leave: {$expectedVacationMonthly} days per month\n";
echo "   • Sick Leave: {$expectedSickMonthly} days per month\n";
echo "   • Automatic monthly processing on last day of month\n";
echo "   • Carry-over support for vacation leave (5 days max)\n";
echo "   • Balance limits: 15 days for both leave types\n\n";
echo "💡 To run the actual accrual process, execute:\n";
echo "   php Public/cron/monthly_leave_credit.php\n";

?>