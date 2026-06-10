<?php
// File: smart_leave_accrual_scheduler.php
// Smart scheduler that checks mode from database and runs accordingly
include(__DIR__ . '/../config/db.php');
date_default_timezone_set('Asia/Manila');

// Set execution time limit
set_time_limit(0);

$startTime = date('Y-m-d H:i:s');
$currentYear = date('Y');

// Define leave accrual rates
$leaveRates = [
    'sick' => 0.42,        // 0.42 days per cycle
    'vacation' => 1.25     // 1.25 days per cycle
];

// Get current mode from database
$currentMode = 'testing'; // Default
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'accrual_mode'");
    $stmt->execute();
    $modeResult = $stmt->fetchColumn();
    if ($modeResult) {
        $currentMode = $modeResult;
    }
} catch (Exception $e) {
    echo "⚠️ Could not read mode from database, using default: testing\n";
}

echo "🗓️ SMART LEAVE ACCRUAL SCHEDULER\n";
echo str_repeat("=", 50) . "\n";
echo "📅 Start Time: {$startTime}\n";
echo "🔧 Current Mode: " . strtoupper($currentMode) . "\n";

if ($currentMode === 'production') {
    echo "⏰ Monitoring: End of month processing\n";
    echo "🎯 Sick Leave: {$leaveRates['sick']} days per month\n";
    echo "🎯 Vacation Leave: {$leaveRates['vacation']} days per month\n";
} else {
    echo "⏰ Monitoring: Every 10 seconds (TESTING)\n";
    echo "🎯 Sick Leave: {$leaveRates['sick']} days per cycle\n";
    echo "🎯 Vacation Leave: {$leaveRates['vacation']} days per cycle\n";
}

echo "❌ Press Ctrl+C to stop\n";
echo str_repeat("=", 50) . "\n\n";

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

// Function to process leave accrual
function processAccrual($pdo, $leaveRates, $currentYear, $mode) {
    try {
        $currentMonth = date('n');
        $currentTime = date('H:i:s');
        
        echo "🔄 PROCESSING LEAVE ACCRUAL - {$currentTime} ({$mode} mode)\n";
        echo str_repeat("-", 40) . "\n";
        
        // Only regular employment types are eligible for automatic accrual.
        $stmt = $pdo->query("SELECT id, CONCAT(fname, ' ', lname) as full_name FROM employees WHERE status = 'active' AND Emp_Type IN ('Regular', 'Old_Regular')");
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $processedCount = 0;
        $newEmployees = 0;
        $updatedEmployees = 0;
        
        foreach ($employees as $emp) {
            $employee_id = $emp['id'];
            $employeeProcessed = false;
            
            foreach ($leaveRates as $leaveType => $increment) {
                // Check if leave credit record exists
                $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND leave_type = ? AND year = ?");
                $stmt->execute([$employee_id, $leaveType, $currentYear]);
                $record = $stmt->fetch();
                
                if ($record) {
                    // Check if already processed based on mode
                    if ($mode === 'production') {
                        // Production mode - check if processed this month
                        $lastProcessed = $record['last_processed_month'] ?? 0;
                        if ($lastProcessed >= $currentMonth) {
                            continue;
                        }
                    } else {
                        // Testing mode - check if processed in last 30 seconds
                        $lastUpdated = new DateTime($record['updated_at']);
                        $now = new DateTime();
                        $timeDiff = $now->getTimestamp() - $lastUpdated->getTimestamp();
                        if ($timeDiff < 30) {
                            continue;
                        }
                    }
                    
                    // Update existing record
                    $currentBalance = floatval($record['balance']);
                    $currentCarryOver = ($leaveType === 'vacation') ? floatval($record['carry_over'] ?? 0) : 0;
                    
                    // Calculate how much can go to balance
                    $maxBalance = 15;
                    $spaceLeft = $maxBalance - $currentBalance;
                    $toBalance = min($increment, $spaceLeft);
                    $remaining = $increment - $toBalance;
                    
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
                    
                    echo "✅ {$emp['full_name']} - {$leaveType}: {$currentBalance} → {$newBalance}\n";
                    
                    if (!$employeeProcessed) {
                        $updatedEmployees++;
                        $employeeProcessed = true;
                    }
                    
                } else {
                    // Create new record for new employee
                    $balance = $increment;
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
                        $increment, 
                        $currentMonth
                    ]);
                    
                    echo "🆕 {$emp['full_name']} - {$leaveType}: {$balance}\n";
                    
                    if (!$employeeProcessed) {
                        $newEmployees++;
                        $employeeProcessed = true;
                    }
                }
                
                $processedCount++;
            }
        }
        
        echo "\n✅ Accrual cycle completed successfully!\n";
        echo "👥 Total employees: " . count($employees) . "\n";
        echo "📝 Records processed: {$processedCount}\n";
        echo "🆕 New employees: {$newEmployees}\n";
        echo "🔄 Updated employees: {$updatedEmployees}\n";
        echo "⏰ Processed at: {$currentTime}\n\n";
        
        return [
            'employees' => count($employees), 
            'records' => $processedCount,
            'new' => $newEmployees,
            'updated' => $updatedEmployees
        ];
        
    } catch (Exception $e) {
        throw new Exception("Accrual processing error: " . $e->getMessage());
    }
}

// Main processing logic based on mode
try {
    if ($currentMode === 'production') {
        // Production mode - run once and exit (for cron)
        if (isLastDayOfMonth() && !isAccrualProcessedThisMonth($pdo, $currentYear)) {
            echo "🚨 END OF MONTH DETECTED - PROCESSING ACCRUAL...\n";
            $result = processAccrual($pdo, $leaveRates, $currentYear, 'production');
            echo "✅ MONTHLY ACCRUAL COMPLETED!\n";
        } else {
            echo "ℹ️ Not end of month or already processed. No action needed.\n";
        }
        
    } else {
        // Testing mode - continuous loop
        $cycleCount = 0;
        
        while (true) {
            // Re-check mode in case it was changed
            try {
                $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'accrual_mode'");
                $stmt->execute();
                $newMode = $stmt->fetchColumn();
                if ($newMode && $newMode !== $currentMode) {
                    echo "🔄 Mode changed to {$newMode}. Exiting...\n";
                    break;
                }
            } catch (Exception $e) {
                // Continue if we can't check mode
            }
            
            echo "\033[2J\033[H"; // Clear screen
            echo "🗓️ TESTING MODE - CYCLE " . ($cycleCount + 1) . "\n";
            echo str_repeat("=", 40) . "\n";
            echo "⏰ Current Time: " . date('Y-m-d H:i:s') . "\n";
            echo "🔄 Cycles completed: {$cycleCount}\n";
            echo "⏳ Next cycle: " . date('H:i:s', time() + 10) . "\n";
            echo str_repeat("=", 40) . "\n\n";
            
            $result = processAccrual($pdo, $leaveRates, $currentYear, 'testing');
            
            echo "🌐 Check results at: https://harley.resourcestaffonline.com/Public/module/leave_credits.php\n";
            echo "⏰ Next cycle in 10 seconds...\n\n";
            
            $cycleCount++;
            sleep(10);
        }
    }
    
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "⏱️ Total runtime: " . gmdate("H:i:s", (time() - strtotime($startTime))) . "\n";
}
?>
