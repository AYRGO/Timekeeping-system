<?php
// Hostinger Cron Job - Monthly Leave Accrual
// Path: /public_html/cron/hostinger_monthly_accrual.php
// Cron Schedule: 0 23 28-31 * * (Run at 11 PM on days 28-31 of every month)

// Set PHP limits for cron job
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

// Include database configuration
require_once(__DIR__ . '/../config/db_production.php');

// Logging function
function logMessage($message, $type = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logFile = __DIR__ . '/../logs/monthly_accrual.log';
    
    // Create logs directory if it doesn't exist
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    $logMessage = "[{$timestamp}] [{$type}] {$message}" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    
    // Also output for cron job monitoring
    echo $logMessage;
}

// Check if it's the last day of the month
function isLastDayOfMonth() {
    $today = date('j');
    $lastDay = date('t');
    return $today == $lastDay;
}

// Main execution
try {
    logMessage("=== MONTHLY LEAVE ACCRUAL CRON JOB STARTED ===");
    logMessage("Server: " . ($_SERVER['HTTP_HOST'] ?? 'CLI'));
    logMessage("PHP Version: " . phpversion());
    logMessage("Memory Limit: " . ini_get('memory_limit'));
    
    // Only run on the last day of the month
    if (!isLastDayOfMonth()) {
        logMessage("Not the last day of the month. Exiting.");
        exit(0);
    }
    
    $currentYear = date('Y');
    $currentMonth = date('n');
    $monthName = date('F');
    
    logMessage("Processing accrual for {$monthName} {$currentYear}");
    
    // Check database connection
    if (!isset($pdo)) {
        throw new Exception("Database connection not available");
    }
    
    // Define monthly leave rates
    $leaveRates = [
        'sick' => 0.42,
        'vacation' => 1.25
    ];
    
    logMessage("Leave rates: Sick=" . $leaveRates['sick'] . ", Vacation=" . $leaveRates['vacation']);
    
    // Only regular employment types are eligible for automatic accrual.
    $stmt = $pdo->query("SELECT id, CONCAT(fname, ' ', lname) as full_name FROM employees WHERE status = 'active' AND Emp_Type IN ('Regular', 'Old_Regular')");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    logMessage("Found " . count($employees) . " active employees");
    
    if (empty($employees)) {
        logMessage("No active employees found. Exiting.", 'WARNING');
        exit(0);
    }
    
    $processedEmployees = 0;
    $totalRecords = 0;
    $errors = 0;
    
    // Process each employee
    foreach ($employees as $employee) {
        try {
            $employee_id = $employee['id'];
            $employeeRecords = 0;
            
            foreach ($leaveRates as $leaveType => $monthlyIncrement) {
                // Check existing record
                $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND leave_type = ? AND year = ?");
                $stmt->execute([$employee_id, $leaveType, $currentYear]);
                $record = $stmt->fetch();
                
                if ($record) {
                    // Check if already processed this month
                    $lastUpdated = new DateTime($record['updated_at']);
                    $thisMonthStart = new DateTime(date('Y-m-01'));
                    
                    if ($lastUpdated >= $thisMonthStart) {
                        continue; // Already processed this month
                    }
                    
                    // Update existing record
                    $currentBalance = floatval($record['balance']);
                    $currentCarryOver = ($leaveType === 'vacation') ? floatval($record['carry_over'] ?? 0) : 0;
                    
                    // Apply balance limits
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
                    
                    $stmt = $pdo->prepare("UPDATE leave_credits SET balance = ?, carry_over = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$newBalance, $newCarryOver, $record['id']]);
                    
                } else {
                    // Create new record
                    $balance = $monthlyIncrement;
                    $carryOver = ($leaveType === 'vacation') ? 0 : null;
                    
                    $stmt = $pdo->prepare("INSERT INTO leave_credits (employee_id, leave_type, balance, carry_over, year, monthly_increment, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$employee_id, $leaveType, $balance, $carryOver, $currentYear, $monthlyIncrement]);
                }
                
                $employeeRecords++;
                $totalRecords++;
            }
            
            if ($employeeRecords > 0) {
                $processedEmployees++;
            }
            
        } catch (Exception $e) {
            $errors++;
            logMessage("Error processing employee {$employee['full_name']}: " . $e->getMessage(), 'ERROR');
        }
    }
    
    // Log summary
    logMessage("=== PROCESSING SUMMARY ===");
    logMessage("Employees processed: {$processedEmployees}/" . count($employees));
    logMessage("Total records updated: {$totalRecords}");
    logMessage("Errors encountered: {$errors}");
    logMessage("Memory used: " . memory_get_peak_usage(true) . " bytes");
    logMessage("Execution time: " . (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) . " seconds");
    
    if ($errors > 0) {
        logMessage("Completed with errors", 'WARNING');
        exit(1);
    } else {
        logMessage("=== MONTHLY LEAVE ACCRUAL COMPLETED SUCCESSFULLY ===");
        exit(0);
    }
    
} catch (Exception $e) {
    logMessage("FATAL ERROR: " . $e->getMessage(), 'ERROR');
    logMessage("Stack trace: " . $e->getTraceAsString(), 'ERROR');
    exit(1);
}
?>
