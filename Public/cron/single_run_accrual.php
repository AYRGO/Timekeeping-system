<?php
// File: single_run_accrual.php
// Single execution version for web access or cron jobs
include(__DIR__ . '/../config/db.php');
date_default_timezone_set('Asia/Manila');

$startTime = date('Y-m-d H:i:s');
$currentYear = date('Y');

// Define monthly leave accrual rates
$monthlyLeaveRates = [
    'sick' => 0.42,        // 0.42 days per month (5 days per year)
    'vacation' => 1.25     // 1.25 days per month (15 days per year)
];

echo "🗓️ MONTHLY LEAVE ACCRUAL - SINGLE RUN\n";
echo str_repeat("=", 60) . "\n";
echo "📅 Execution Time: {$startTime}\n";
echo "🎯 Sick Leave: {$monthlyLeaveRates['sick']} days per month\n";
echo "🎯 Vacation Leave: {$monthlyLeaveRates['vacation']} days per month\n";
echo str_repeat("=", 60) . "\n\n";

// Function to check if it's the last day of the month
function isLastDayOfMonth() {
    $today = date('Y-m-d');
    $lastDayOfMonth = date('Y-m-t');
    return $today === $lastDayOfMonth;
}

// Function to check if accrual has already been processed this month
function isAccrualProcessedThisMonth($pdo, $currentYear) {
    $currentMonth = date('n');
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM leave_credits 
        WHERE year = ? AND last_processed_month = ?
        LIMIT 1
    ");
    $stmt->execute([$currentYear, $currentMonth]);
    
    return $stmt->fetchColumn() > 0;
}

// Function to process monthly leave accrual
function processMonthlyAccrual($pdo, $leaveRates, $currentYear) {
    try {
        $currentMonth = date('n');
        $monthName = date('F');
        
        echo "🔄 PROCESSING MONTHLY ACCRUAL FOR {$monthName} {$currentYear}\n";
        echo str_repeat("-", 50) . "\n";
        
        // Get all active employees
        $employees = $pdo->query("SELECT id, CONCAT(fname, ' ', lname) as full_name FROM employees WHERE status = 'active'")->fetchAll(PDO::FETCH_ASSOC);
        
        $processedCount = 0;
        $newEmployees = 0;
        $updatedEmployees = 0;
        
        foreach ($employees as $emp) {
            $employee_id = $emp['id'];
            $employeeProcessed = false;
            
            foreach ($leaveRates as $leaveType => $monthlyIncrement) {
                // Check if leave credit record exists
                $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND leave_type = ? AND year = ?");
                $stmt->execute([$employee_id, $leaveType, $currentYear]);
                $record = $stmt->fetch();
                
                if ($record) {
                    // Check if already processed this month
                    $lastProcessed = $record['last_processed_month'] ?? 0;
                    if ($lastProcessed >= $currentMonth) {
                        continue;
                    }
                    
                    $currentBalance = floatval($record['balance']);
                    $currentCarryOver = ($leaveType === 'vacation') ? floatval($record['carry_over'] ?? 0) : 0;
                    
                    $maxBalance = 15;
                    $spaceLeft = $maxBalance - $currentBalance;
                    $toBalance = min($monthlyIncrement, $spaceLeft);
                    $remaining = $monthlyIncrement - $toBalance;
                    
                    $toCarryOver = 0;
                    if ($leaveType === 'vacation' && $remaining > 0) {
                        $maxCarryOver = 5;
                        $carrySpace = $maxCarryOver - $currentCarryOver;
                        $toCarryOver = min($remaining, $carrySpace);
                    }
                    
                    $newBalance = $currentBalance + $toBalance;
                    $newCarryOver = ($leaveType === 'vacation') ? $currentCarryOver + $toCarryOver : null;
                    
                    $updateStmt = $pdo->prepare("
                        UPDATE leave_credits 
                        SET balance = ?, carry_over = ?, last_processed_month = ?, updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$newBalance, $newCarryOver, $currentMonth, $record['id']]);
                    
                    if (!$employeeProcessed) {
                        $updatedEmployees++;
                        $employeeProcessed = true;
                    }
                    
                } else {
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
                    
                    if (!$employeeProcessed) {
                        $newEmployees++;
                        $employeeProcessed = true;
                    }
                }
                
                $processedCount++;
            }
        }
        
        echo "✅ Monthly accrual completed successfully!\n";
        echo "👥 Total employees: " . count($employees) . "\n";
        echo "📝 Records processed: {$processedCount}\n";
        echo "🆕 New employees: {$newEmployees}\n";
        echo "🔄 Updated employees: {$updatedEmployees}\n";
        echo "📅 Month processed: {$monthName} {$currentYear}\n\n";
        
        return [
            'employees' => count($employees), 
            'records' => $processedCount,
            'new' => $newEmployees,
            'updated' => $updatedEmployees
        ];
        
    } catch (Exception $e) {
        throw new Exception("Monthly accrual processing error: " . $e->getMessage());
    }
}

// Main execution
try {
    $today = date('j');
    $lastDay = date('t');
    $daysUntilEndOfMonth = $lastDay - $today;
    
    echo "📅 Today: Day {$today} of {$lastDay}\n";
    echo "⏳ Days until month end: {$daysUntilEndOfMonth}\n\n";
    
    // Check if it's the last day of the month and accrual hasn't been processed
    if (isLastDayOfMonth() && !isAccrualProcessedThisMonth($pdo, $currentYear)) {
        echo "🚨 END OF MONTH DETECTED - PROCESSING ACCRUAL...\n";
        echo str_repeat("=", 60) . "\n\n";
        
        $result = processMonthlyAccrual($pdo, $monthlyLeaveRates, $currentYear);
        
        echo "✅ ACCRUAL PROCESSING COMPLETED!\n";
        echo str_repeat("=", 60) . "\n\n";
        
    } else if (isAccrualProcessedThisMonth($pdo, $currentYear)) {
        echo "ℹ️ Accrual already processed for this month.\n\n";
    } else {
        echo "⏳ Waiting for end of month (will process on day {$lastDay})...\n\n";
    }
    
    $endTime = date('Y-m-d H:i:s');
    echo "🏁 Execution completed at: {$endTime}\n";
    
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
?>