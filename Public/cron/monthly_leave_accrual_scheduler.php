<?php
// File: monthly_leave_accrual_scheduler.php
// Monthly leave accrua                    // Check if already processed this month by looking at updated_at
     // Function to display current monitoring status
function displayMonitoringStatus($startTime) {
    $currentTime = date('Y-m-d H:i:s');
    $runtime = (time() - strtotime($startTime));
    $today = date('j'); // Day of month
    $lastDay = date('t'); // Last day of current month
    $daysUntilEndOfMonth = $lastDay - $today;
    $nextAccrualDate = date('Y-m-t'); // Last day of current month
    
    echo "\033[2J\033[H"; // Clear screen and move cursor to top
    
    echo "🗓️ MONTHLY LEAVE ACCRUAL MONITOR\n";
    echo str_repeat("=", 60) . "\n";
    echo "⏰ Current Time: {$currentTime}\n";
    echo "🕐 Runtime: " . gmdate("H:i:s", $runtime) . "\n";
    echo "📅 Today: Day {$today} of {$lastDay}\n";
    echo "⏳ Days until month end: {$daysUntilEndOfMonth}\n";
    echo "📆 Next accrual date: {$nextAccrualDate}\n";
    
    if ($daysUntilEndOfMonth === 0) {
        echo "🎯 STATUS: Ready to process accrual!\n";
    } else {
        echo "⏱️ STATUS: Waiting for month end...\n";
    }
    
    echo "❌ Press Ctrl+C to stop\n";
    echo str_repeat("=", 60) . "\n\n";
}stUpdated = new DateTime($record['updated_at']);
                    $thisMonthStart = new DateTime(date('Y-m-01'));
                    if ($lastUpdated >= $thisMonthStart) {
                        continue; // Skip if already processed this month
                    }tem - runs at the end of every month
include(__DIR__ . '/../config/db.php');
date_default_timezone_set('Asia/Manila');

// Set execution time limit
set_time_limit(0);

$startTime = date('Y-m-d H:i:s');
$currentYear = date('Y');

// Define monthly leave accrual rates (realistic rates)
$monthlyLeaveRates = [
    'sick' => 0.42,        // 0.42 days per month (5 days per year)
    'vacation' => 1.25     // 1.25 days per month (15 days per year)
];

echo "🗓️ MONTHLY LEAVE ACCRUAL SCHEDULER\n";
echo str_repeat("=", 60) . "\n";
echo "📅 Start Time: {$startTime}\n";
echo "⏰ Monitoring: End of each month\n";
echo "🎯 Sick Leave: {$monthlyLeaveRates['sick']} days per month\n";
echo "🎯 Vacation Leave: {$monthlyLeaveRates['vacation']} days per month\n";
echo "❌ Press Ctrl+C to stop\n";
echo str_repeat("=", 60) . "\n\n";

// Function to check if it's the last day of the month
function isLastDayOfMonth() {
    $today = date('Y-m-d');
    $lastDayOfMonth = date('Y-m-t'); // Last day of current month
    return $today === $lastDayOfMonth;
}

// Function to check if accrual has already been processed this month
function isAccrualProcessedThisMonth($pdo, $currentYear) {
    $currentMonth = date('n'); // 1-12
    
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
        $currentMonth = date('n'); // 1-12
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
                        continue; // Skip if already processed this month
                    }
                    
                    // Update existing record
                    $currentBalance = floatval($record['balance']);
                    $currentCarryOver = ($leaveType === 'vacation') ? floatval($record['carry_over'] ?? 0) : 0;
                    
                    // Calculate how much can go to balance
                    $maxBalance = ($leaveType === 'vacation' || $leaveType === 'sick') ? 15 : 15;
                    $spaceLeft = $maxBalance - $currentBalance;
                    $toBalance = min($monthlyIncrement, $spaceLeft);
                    $remaining = $monthlyIncrement - $toBalance;
                    
                    // Handle vacation carry-over
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

// Function to display current monitoring status (testing mode)
function displayMonitoringStatus($startTime, $cycleCount = 0) {
    $currentTime = date('Y-m-d H:i:s');
    $runtime = (time() - strtotime($startTime));
    $nextCycle = date('H:i:s', time() + 10);
    
    echo "\033[2J\033[H"; // Clear screen and move cursor to top
    
    echo "🗓️ LEAVE ACCRUAL MONITOR - TESTING MODE\n";
    echo str_repeat("=", 60) . "\n";
    echo "⏰ Current Time: {$currentTime}\n";
    echo "🕐 Runtime: " . gmdate("H:i:s", $runtime) . "\n";
    echo "� Cycles completed: {$cycleCount}\n";
    echo "⏳ Next cycle: {$nextCycle}\n";
    echo "🎯 STATUS: Processing every 10 seconds\n";
    echo "❌ Press Ctrl+C to stop\n";
    echo str_repeat("=", 60) . "\n\n";
}

// Function to show testing schedule
function showTestingSchedule() {
    echo "📋 TESTING SCHEDULE (Every 10 seconds):\n";
    echo str_repeat("-", 40) . "\n";
    
    for ($i = 0; $i < 6; $i++) {
        $nextTime = date('H:i:s', time() + ($i * 10));
        $cycleNum = $i + 1;
        
        if ($i === 0) {
            echo "🎯 Cycle {$cycleNum}: {$nextTime} (Next)\n";
        } else {
            echo "📅 Cycle {$cycleNum}: {$nextTime}\n";
        }
    }
    echo "\n";
}

// Main monitoring loop - Testing Mode (10 seconds)
try {
    $cycleCount = 0;
    
    while (true) {
        // Display current status
        displayMonitoringStatus($startTime, $cycleCount);
        
        // Show testing schedule
        showTestingSchedule();
        
        // Process accrual every cycle (every 10 seconds)
        echo "🚨 PROCESSING ACCRUAL - CYCLE " . ($cycleCount + 1) . "...\n";
        echo str_repeat("=", 60) . "\n\n";
        
        // Process leave accrual
        $result = processLeaveAccrual($pdo, $monthlyLeaveRates, $currentYear);
        
        echo "✅ ACCRUAL CYCLE COMPLETED!\n";
        echo str_repeat("=", 60) . "\n\n";
        
        $cycleCount++;
        
        echo "💡 TIP: Check the leave credits page to see updated balances\n";
        echo "🌐 URL: https://harley.resourcestaffonline.com/Public/module/leave_credits.php\n\n";
        
        // Wait for 10 seconds before next cycle
        echo "⏰ Next cycle in 10 seconds...\n";
        sleep(10); // Process every 10 seconds
    }
    
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "\n🛑 Process stopped\n";
    echo "⏱️ Total runtime: " . gmdate("H:i:s", (time() - strtotime($startTime))) . "\n";
}
?>
