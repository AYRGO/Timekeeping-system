<?php
// File: realtime_leave_accrual.php
// Real-time leave accrual system for testing - runs every 10 seconds
include(__DIR__ . '/../config/db.php');
date_default_timezone_set('Asia/Manila');

// Set execution time limit to unlimited for continuous running
set_time_limit(0);

$executionCount = 0;
$startTime = date('Y-m-d H:i:s');
$currentYear = date('Y');

// Define leave accrual rates (per 10 seconds for testing)
$leaveRates = [
    'sick' => 0.42,        // 0.42 days per 10 seconds
    'vacation' => 1.25     // 1.25 days per 10 seconds
];

echo "🚀 REAL-TIME LEAVE ACCRUAL SYSTEM STARTED\n";
echo str_repeat("=", 60) . "\n";
echo "📅 Start Time: {$startTime}\n";
echo "⏱️ Accrual Interval: Every 10 seconds\n";
echo "🎯 Sick Leave: {$leaveRates['sick']} days per interval\n";
echo "🎯 Vacation Leave: {$leaveRates['vacation']} days per interval\n";
echo "❌ Press Ctrl+C to stop\n";
echo str_repeat("=", 60) . "\n\n";

// Function to process leave accrual
function processLeaveAccrual($pdo, $leaveRates, $currentYear) {
    try {
        // Get all active employees
        $employees = $pdo->query("SELECT id, CONCAT(fname, ' ', lname) as full_name FROM employees WHERE status = 'active'")->fetchAll(PDO::FETCH_ASSOC);
        
        $processedCount = 0;
        
        foreach ($employees as $emp) {
            $employee_id = $emp['id'];
            
            foreach ($leaveRates as $leaveType => $increment) {
                // Check if leave credit record exists
                $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND leave_type = ? AND year = ?");
                $stmt->execute([$employee_id, $leaveType, $currentYear]);
                $record = $stmt->fetch();
                
                if ($record) {
                    // Update existing record
                    $newBalance = floatval($record['balance']) + $increment;
                    
                    // Apply maximum balance limits
                    $maxBalance = ($leaveType === 'vacation' || $leaveType === 'sick') ? 50 : 15; // Increased for testing
                    if ($newBalance > $maxBalance) {
                        $newBalance = $maxBalance;
                    }
                    
                    $updateStmt = $pdo->prepare("
                        UPDATE leave_credits 
                        SET balance = ?, updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$newBalance, $record['id']]);
                } else {
                    // Create new record
                    $insertStmt = $pdo->prepare("
                        INSERT INTO leave_credits (employee_id, leave_type, balance, year, monthly_increment, updated_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $insertStmt->execute([$employee_id, $leaveType, $increment, $currentYear, $increment]);
                }
                
                $processedCount++;
            }
        }
        
        return ['employees' => count($employees), 'records' => $processedCount];
        
    } catch (Exception $e) {
        throw new Exception("Accrual processing error: " . $e->getMessage());
    }
}

// Function to display current status
function displayStatus($executionCount, $startTime, $employees, $records) {
    $currentTime = date('Y-m-d H:i:s');
    $runtime = (time() - strtotime($startTime));
    
    echo "\033[2J\033[H"; // Clear screen and move cursor to top
    
    echo "🔄 LEAVE ACCRUAL MONITOR - Execution #{$executionCount}\n";
    echo str_repeat("=", 60) . "\n";
    echo "⏰ Current Time: {$currentTime}\n";
    echo "🕐 Runtime: " . gmdate("H:i:s", $runtime) . "\n";
    echo "👥 Active Employees: {$employees}\n";
    echo "📝 Records Processed: {$records}\n";
    echo "⚡ Next accrual in: 10 seconds\n";
    echo "❌ Press Ctrl+C to stop\n";
    echo str_repeat("=", 60) . "\n\n";
}

// Function to show recent accruals
function showRecentAccruals($pdo, $limit = 5) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                e.fname, 
                e.lname, 
                lc.leave_type, 
                lc.balance, 
                lc.updated_at
            FROM leave_credits lc
            JOIN employees e ON lc.employee_id = e.id
            WHERE lc.leave_type IN ('sick', 'vacation')
            ORDER BY lc.updated_at DESC
            LIMIT " . intval($limit)
        );
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($results)) {
            echo "📊 RECENT ACCRUALS:\n";
            echo str_repeat("-", 40) . "\n";
            
            foreach ($results as $record) {
                $name = $record['fname'] . ' ' . $record['lname'];
                $type = ucfirst($record['leave_type']);
                $balance = number_format($record['balance'], 2);
                $time = date('H:i:s', strtotime($record['updated_at']));
                
                echo "👤 {$name} - {$type}: {$balance} days (at {$time})\n";
            }
            echo "\n";
        }
    } catch (Exception $e) {
        echo "⚠️ Cannot fetch recent accruals: " . $e->getMessage() . "\n\n";
    }
}

// Main execution loop
try {
    while (true) {
        $executionCount++;
        
        // Process leave accrual
        $result = processLeaveAccrual($pdo, $leaveRates, $currentYear);
        
        // Display current status
        displayStatus($executionCount, $startTime, $result['employees'], $result['records']);
        
        // Show recent accruals
        showRecentAccruals($pdo, 8);
        
        echo "💡 TIP: Check the leave credits page in another browser tab to see real-time updates!\n";
        echo "🌐 URL: http://localhost/Timekeeping-system/Public/employee/leave_credits.php\n\n";
        
        // Wait for 10 seconds
        echo "⏳ Waiting 10 seconds for next accrual...\n";
        sleep(10);
    }
    
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "\n🛑 Process stopped\n";
    echo "📊 Total executions: {$executionCount}\n";
    echo "⏱️ Total runtime: " . gmdate("H:i:s", (time() - strtotime($startTime))) . "\n";
}
?>