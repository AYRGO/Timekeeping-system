<?php
// File: hostinger_leave_accrual.php
// Single execution leave accrual for Hostinger cron (runs once per call)
include(__DIR__ . '/../config/db.php');
date_default_timezone_set('Asia/Manila');

$currentTime = date('Y-m-d H:i:s');
$currentYear = date('Y');

// Define leave accrual rates (for testing - same as before)
$leaveRates = [
    'sick' => 0.42,        // 0.42 days per cycle
    'vacation' => 1.25     // 1.25 days per cycle
];

echo "🗓️ HOSTINGER LEAVE ACCRUAL - SINGLE EXECUTION\n";
echo str_repeat("=", 50) . "\n";
echo "⏰ Execution Time: {$currentTime}\n";
echo "🎯 Sick Leave: {$leaveRates['sick']} days per run\n";
echo "🎯 Vacation Leave: {$leaveRates['vacation']} days per run\n";
echo str_repeat("=", 50) . "\n\n";

// Function to process leave accrual (single execution)
function processSingleAccrual($pdo, $leaveRates, $currentYear) {
    try {
        $currentMonth = date('n'); // 1-12
        $currentTime = date('H:i:s');
        
        echo "🔄 PROCESSING LEAVE ACCRUAL - {$currentTime}\n";
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
                    // For testing - check if processed in last 30 seconds to avoid spam
                    $lastUpdated = new DateTime($record['updated_at']);
                    $now = new DateTime();
                    $timeDiff = $now->getTimestamp() - $lastUpdated->getTimestamp();
                    
                    // Skip if updated within last 30 seconds (for rapid cron testing)
                    if ($timeDiff < 30) {
                        echo "⏭️ Skipping {$emp['full_name']} - {$leaveType} (updated {$timeDiff}s ago)\n";
                        continue;
                    }
                    
                    // Update existing record
                    $currentBalance = floatval($record['balance']);
                    $currentCarryOver = ($leaveType === 'vacation') ? floatval($record['carry_over'] ?? 0) : 0;
                    
                    // Calculate how much can go to balance
                    $maxBalance = 15; // Max balance for both sick and vacation
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
                    
                    // Update the record
                    $updateStmt = $pdo->prepare("
                        UPDATE leave_credits 
                        SET balance = ?, carry_over = ?, last_processed_month = ?, updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$newBalance, $newCarryOver, $currentMonth, $record['id']]);
                    
                    echo "✅ Updated {$emp['full_name']} - {$leaveType}: {$currentBalance} → {$newBalance}\n";
                    
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
                    
                    echo "🆕 Created {$emp['full_name']} - {$leaveType}: {$balance}\n";
                    
                    if (!$employeeProcessed) {
                        $newEmployees++;
                        $employeeProcessed = true;
                    }
                }
                
                $processedCount++;
            }
        }
        
        echo "\n" . str_repeat("-", 40) . "\n";
        echo "✅ ACCRUAL PROCESSING COMPLETED!\n";
        echo "👥 Total employees: " . count($employees) . "\n";
        echo "📝 Records processed: {$processedCount}\n";
        echo "🆕 New employees: {$newEmployees}\n";
        echo "🔄 Updated employees: {$updatedEmployees}\n";
        echo "⏰ Completed at: " . date('H:i:s') . "\n\n";
        
        return [
            'employees' => count($employees), 
            'records' => $processedCount,
            'new' => $newEmployees,
            'updated' => $updatedEmployees
        ];
        
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
        throw $e;
    }
}

// Execute single accrual run
try {
    $result = processSingleAccrual($pdo, $leaveRates, $currentYear);
    
    echo "🎯 SUCCESS! Accrual completed for " . $result['employees'] . " employees.\n";
    echo "🌐 Check results at: https://harley.resourcestaffonline.com/Public/module/leave_credits.php\n";
    echo "📱 Monitor page: https://harley.resourcestaffonline.com/Public/module/monthly_leave_monitor.php\n\n";
    
    // Log completion
    echo "📋 EXECUTION LOG:\n";
    echo "   Start: {$currentTime}\n";
    echo "   End: " . date('Y-m-d H:i:s') . "\n";
    echo "   Records: {$result['records']}\n";
    echo "   Status: SUCCESS\n";
    
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "⏰ Failed at: " . date('Y-m-d H:i:s') . "\n";
    exit(1);
}
?>
