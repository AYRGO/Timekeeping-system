<?php
// File: monthly_accrual_production.php
// Production monthly leave accrual system - runs at end of each month
include(__DIR__ . '/../config/db.php');
date_default_timezone_set('Asia/Manila');

$executionTime = date('Y-m-d H:i:s');
$currentYear = date('Y');
$currentMonth = date('n'); // 1-12
$monthName = date('F');

// Monthly leave accrual rates
$monthlyRates = [
    'sick' => 0.42,        // 0.42 days per month (5 days per year)
    'vacation' => 1.25     // 1.25 days per month (15 days per year)
];

echo "🗓️ MONTHLY LEAVE ACCRUAL - PRODUCTION SYSTEM\n";
echo str_repeat("=", 60) . "\n";
echo "📅 Execution: {$executionTime}\n";
echo "📆 Processing: {$monthName} {$currentYear}\n";
echo "🎯 Sick Leave: +{$monthlyRates['sick']} days per month\n";
echo "🎯 Vacation Leave: +{$monthlyRates['vacation']} days per month\n";
echo str_repeat("=", 60) . "\n\n";

// Function to check if it's the last day of the month
function isLastDayOfMonth() {
    $today = date('Y-m-d');
    $lastDayOfMonth = date('Y-m-t'); // Last day of current month
    return $today === $lastDayOfMonth;
}

// Function to check if accrual has already been processed this month
function isAlreadyProcessedThisMonth($pdo, $currentYear, $currentMonth) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM leave_credits 
            WHERE year = ? AND last_processed_month = ?
            LIMIT 1
        ");
        $stmt->execute([$currentYear, $currentMonth]);
        
        return $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        // If we can't check, assume not processed to be safe
        return false;
    }
}

// Function to process monthly leave accrual
function processMonthlyAccrual($pdo, $monthlyRates, $currentYear, $currentMonth) {
    try {
        echo "🔄 PROCESSING MONTHLY ACCRUAL...\n";
        echo str_repeat("-", 50) . "\n";
        
        // Only regular employment types are eligible for automatic accrual.
        $stmt = $pdo->query("SELECT id, CONCAT(fname, ' ', lname) as full_name FROM employees WHERE status = 'active' AND Emp_Type IN ('Regular', 'Old_Regular')");
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($employees)) {
            echo "⚠️ No active employees found!\n";
            return ['employees' => 0, 'records' => 0, 'new' => 0, 'updated' => 0];
        }
        
        $processedCount = 0;
        $newEmployees = 0;
        $updatedEmployees = 0;
        $skippedCount = 0;
        
        foreach ($employees as $emp) {
            $employee_id = $emp['id'];
            $employeeName = $emp['full_name'];
            $employeeProcessed = false;
            
            foreach ($monthlyRates as $leaveType => $monthlyIncrement) {
                // Check if leave credit record exists
                $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND leave_type = ? AND year = ?");
                $stmt->execute([$employee_id, $leaveType, $currentYear]);
                $record = $stmt->fetch();
                
                if ($record) {
                    // Check if already processed this month
                    $lastProcessed = intval($record['last_processed_month'] ?? 0);
                    if ($lastProcessed >= $currentMonth) {
                        $skippedCount++;
                        echo "⏭️ {$employeeName} - {$leaveType}: Already processed for month {$currentMonth}\n";
                        continue;
                    }
                    
                    // Update existing record
                    $currentBalance = floatval($record['balance']);
                    $currentCarryOver = ($leaveType === 'vacation') ? floatval($record['carry_over'] ?? 0) : 0;
                    
                    // Calculate balance allocation (max 15 days)
                    $maxBalance = 15;
                    $spaceInBalance = $maxBalance - $currentBalance;
                    $toBalance = min($monthlyIncrement, $spaceInBalance);
                    $remaining = $monthlyIncrement - $toBalance;
                    
                    // Handle vacation carry-over (max 5 days)
                    $toCarryOver = 0;
                    if ($leaveType === 'vacation' && $remaining > 0) {
                        $maxCarryOver = 5;
                        $spaceInCarryOver = $maxCarryOver - $currentCarryOver;
                        $toCarryOver = min($remaining, $spaceInCarryOver);
                    }
                    
                    $newBalance = $currentBalance + $toBalance;
                    $newCarryOver = ($leaveType === 'vacation') ? $currentCarryOver + $toCarryOver : null;
                    
                    // Update the record
                    $updateStmt = $pdo->prepare("
                        UPDATE leave_credits 
                        SET balance = ?, carry_over = ?, last_processed_month = ?, updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$newBalance, $newCarryOver, $currentMonth, $record['id']]);
                    
                    echo "✅ {$employeeName} - {$leaveType}: {$currentBalance} → {$newBalance}";
                    if ($leaveType === 'vacation' && $toCarryOver > 0) {
                        echo " (Carry-over: +{$toCarryOver})";
                    }
                    echo "\n";
                    
                    if (!$employeeProcessed) {
                        $updatedEmployees++;
                        $employeeProcessed = true;
                    }
                    
                } else {
                    // Create new record for new employee
                    $balance = $monthlyIncrement;
                    $carryOver = ($leaveType === 'vacation') ? 0 : null;
                    
                    $insertStmt = $pdo->prepare("
                        INSERT INTO leave_credits (employee_id, leave_type, balance, carry_over, year, monthly_increment, last_processed_month, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $insertStmt->execute([
                        $employee_id, 
                        $leaveType, 
                        $balance, 
                        $carryOver, 
                        $currentYear, 
                        $monthlyIncrement, 
                        $currentMonth
                    ]);
                    
                    echo "🆕 {$employeeName} - {$leaveType}: Created with {$balance} days\n";
                    
                    if (!$employeeProcessed) {
                        $newEmployees++;
                        $employeeProcessed = true;
                    }
                }
                
                $processedCount++;
            }
        }
        
        echo "\n" . str_repeat("-", 50) . "\n";
        echo "✅ MONTHLY ACCRUAL COMPLETED SUCCESSFULLY!\n";
        echo "👥 Total employees: " . count($employees) . "\n";
        echo "📝 Records processed: {$processedCount}\n";
        echo "🆕 New employees: {$newEmployees}\n";
        echo "🔄 Updated employees: {$updatedEmployees}\n";
        echo "⏭️ Skipped (already processed): {$skippedCount}\n";
        echo "📅 Month processed: " . date('F Y') . "\n";
        echo "⏰ Completed at: " . date('H:i:s') . "\n\n";
        
        return [
            'employees' => count($employees), 
            'records' => $processedCount,
            'new' => $newEmployees,
            'updated' => $updatedEmployees,
            'skipped' => $skippedCount
        ];
        
    } catch (Exception $e) {
        throw new Exception("Monthly accrual processing error: " . $e->getMessage());
    }
}

// Main execution logic
try {
    // Check if it's the last day of the month
    if (!isLastDayOfMonth()) {
        echo "ℹ️ NOT END OF MONTH - No processing needed\n";
        echo "📅 Today: " . date('Y-m-d') . "\n";
        echo "📅 End of month: " . date('Y-m-t') . "\n";
        echo "⏰ Next check: Tomorrow\n\n";
        exit(0);
    }
    
    // Check if already processed this month
    if (isAlreadyProcessedThisMonth($pdo, $currentYear, $currentMonth)) {
        echo "ℹ️ ALREADY PROCESSED - Accrual completed for {$monthName} {$currentYear}\n";
        echo "⏰ Next processing: " . date('F Y', strtotime('+1 month')) . "\n\n";
        exit(0);
    }
    
    // Process monthly accrual
    echo "🚨 END OF MONTH DETECTED - PROCESSING ACCRUAL...\n\n";
    $result = processMonthlyAccrual($pdo, $monthlyRates, $currentYear, $currentMonth);
    
    echo "🎉 SUCCESS! Monthly accrual completed for {$result['employees']} employees\n";
    echo "🌐 View results: https://harley.resourcestaffonline.com/Public/module/leave_credits.php\n";
    echo "📊 Summary: {$result['updated']} updated, {$result['new']} new, {$result['skipped']} skipped\n\n";
    
    // Log success
    echo "📋 EXECUTION LOG:\n";
    echo "   Status: SUCCESS\n";
    echo "   Start: {$executionTime}\n";
    echo "   End: " . date('Y-m-d H:i:s') . "\n";
    echo "   Month: {$monthName} {$currentYear}\n";
    echo "   Records: {$result['records']}\n\n";
    
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "⏰ Failed at: " . date('Y-m-d H:i:s') . "\n";
    echo "📅 Month: {$monthName} {$currentYear}\n\n";
    
    echo "🔧 TROUBLESHOOTING:\n";
    echo "   1. Check database connection\n";
    echo "   2. Verify employees table has active employees\n";
    echo "   3. Ensure leave_credits table exists\n";
    echo "   4. Check file permissions\n\n";
    
    exit(1);
}
?>
