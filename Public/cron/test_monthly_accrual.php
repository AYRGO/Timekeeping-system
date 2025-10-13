<?php
// File: test_monthly_accrual.php
// Test version - simulates end of month every 30 seconds for testing
include(__DIR__ . '/../config/db.php');
date_default_timezone_set('Asia/Manila');

// Set execution time limit
set_time_limit(0);

$executionCount = 0;
$startTime = date('Y-m-d H:i:s');
$currentYear = date('Y');

// Define monthly leave accrual rates
$monthlyLeaveRates = [
    'sick' => 0.42,        // 0.42 days per month
    'vacation' => 1.25     // 1.25 days per month
];

echo "🧪 MONTHLY ACCRUAL TESTER - SIMULATING END OF MONTH\n";
echo str_repeat("=", 60) . "\n";
echo "📅 Start Time: {$startTime}\n";
echo "⏰ Simulating: End of month every 30 seconds\n";
echo "🎯 Sick Leave: {$monthlyLeaveRates['sick']} days per month\n";
echo "🎯 Vacation Leave: {$monthlyLeaveRates['vacation']} days per month\n";
echo "❌ Press Ctrl+C to stop\n";
echo str_repeat("=", 60) . "\n\n";

// Function to simulate monthly accrual (ignores actual date)
function simulateMonthlyAccrual($pdo, $leaveRates, $currentYear, $simulatedMonth) {
    try {
        echo "🔄 SIMULATING ACCRUAL FOR MONTH {$simulatedMonth}\n";
        echo str_repeat("-", 50) . "\n";
        
        // Get all active employees
        $employees = $pdo->query("SELECT id, CONCAT(fname, ' ', lname) as full_name FROM employees WHERE status = 'active'")->fetchAll(PDO::FETCH_ASSOC);
        
        $processedCount = 0;
        $employeeUpdates = [];
        
        foreach ($employees as $emp) {
            $employee_id = $emp['id'];
            $empUpdates = [];
            
            foreach ($leaveRates as $leaveType => $monthlyIncrement) {
                // Check if leave credit record exists
                $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND leave_type = ? AND year = ?");
                $stmt->execute([$employee_id, $leaveType, $currentYear]);
                $record = $stmt->fetch();
                
                if ($record) {
                    // Update existing record
                    $currentBalance = floatval($record['balance']);
                    $currentCarryOver = ($leaveType === 'vacation') ? floatval($record['carry_over'] ?? 0) : 0;
                    
                    // Calculate how much can go to balance
                    $maxBalance = 15;
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
                        SET balance = ?, carry_over = ?, updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$newBalance, $newCarryOver, $record['id']]);
                    
                    $empUpdates[] = sprintf("%s: %.2f → %.2f (+%.2f)", 
                        ucfirst($leaveType), 
                        $currentBalance, 
                        $newBalance, 
                        $toBalance + $toCarryOver
                    );
                    
                } else {
                    // Create new record for new employee
                    $balance = $monthlyIncrement;
                    $carryOver = ($leaveType === 'vacation') ? 0 : null;
                    
                    $insertStmt = $pdo->prepare("
                        INSERT INTO leave_credits (employee_id, leave_type, balance, carry_over, year, monthly_increment, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $insertStmt->execute([
                        $employee_id, 
                        $leaveType, 
                        $balance, 
                        $carryOver, 
                        $currentYear, 
                        $monthlyIncrement
                    ]);
                    
                    $empUpdates[] = sprintf("%s: NEW → %.2f", ucfirst($leaveType), $balance);
                }
                
                $processedCount++;
            }
            
            if (!empty($empUpdates)) {
                $employeeUpdates[] = "👤 " . $emp['full_name'] . ": " . implode(", ", $empUpdates);
            }
        }
        
        return [
            'employees' => count($employees), 
            'records' => $processedCount,
            'updates' => $employeeUpdates
        ];
        
    } catch (Exception $e) {
        throw new Exception("Simulated accrual error: " . $e->getMessage());
    }
}

// Function to display status
function displayTestStatus($executionCount, $startTime, $simulatedMonth, $result) {
    $currentTime = date('Y-m-d H:i:s');
    $runtime = (time() - strtotime($startTime));
    
    echo "\033[2J\033[H"; // Clear screen and move cursor to top
    
    echo "🧪 MONTHLY ACCRUAL TEST - Execution #{$executionCount}\n";
    echo str_repeat("=", 60) . "\n";
    echo "⏰ Current Time: {$currentTime}\n";
    echo "🕐 Runtime: " . gmdate("H:i:s", $runtime) . "\n";
    echo "📅 Simulated Month: {$simulatedMonth}\n";
    echo "👥 Active Employees: {$result['employees']}\n";
    echo "📝 Records Processed: {$result['records']}\n";
    echo "⏳ Next accrual in: 30 seconds\n";
    echo "❌ Press Ctrl+C to stop\n";
    echo str_repeat("=", 60) . "\n\n";
}

// Function to show sample updates
function showSampleUpdates($updates, $limit = 8) {
    if (!empty($updates)) {
        echo "📊 SAMPLE EMPLOYEE UPDATES:\n";
        echo str_repeat("-", 40) . "\n";
        
        $sampleUpdates = array_slice($updates, 0, $limit);
        foreach ($sampleUpdates as $update) {
            echo $update . "\n";
        }
        
        if (count($updates) > $limit) {
            echo "... and " . (count($updates) - $limit) . " more employees\n";
        }
        echo "\n";
    }
}

// Main execution loop
try {
    $simulatedMonth = date('n'); // Start with current month
    
    while (true) {
        $executionCount++;
        $simulatedMonth++; // Increment month for each simulation
        
        if ($simulatedMonth > 12) {
            $simulatedMonth = 1; // Reset to January
        }
        
        // Process simulated monthly accrual
        $result = simulateMonthlyAccrual($pdo, $monthlyLeaveRates, $currentYear, $simulatedMonth);
        
        // Display current status
        displayTestStatus($executionCount, $startTime, $simulatedMonth, $result);
        
        // Show sample updates
        showSampleUpdates($result['updates'], 8);
        
        echo "💡 TIP: Check the leave credits page to see updated balances\n";
        echo "🌐 URL: http://localhost/Timekeeping-system/Public/module/live_leave_credits.php\n\n";
        
        // Wait for 30 seconds before next "month"
        echo "⏳ Waiting 30 seconds for next month simulation...\n";
        sleep(30);
    }
    
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "\n🛑 Process stopped\n";
    echo "📊 Total executions: {$executionCount}\n";
    echo "⏱️ Total runtime: " . gmdate("H:i:s", (time() - strtotime($startTime))) . "\n";
}
?>