<?php
// File: process_monthly_accrual.php
// Manual monthly accrual processing for production system

// Prevent any output before JSON header
ob_start();

// Set JSON header immediately
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Clear any previous output
ob_clean();

try {
    include(__DIR__ . '/../config/db.php');
    date_default_timezone_set('Asia/Manila');

    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Handle empty input
    if ($input === null) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }

if ($input['action'] !== 'process_monthly') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

$currentYear = date('Y');
$currentMonth = date('n'); // 1-12

// Define monthly leave accrual rates (production rates)
$monthlyLeaveRates = [
    'sick' => 0.42,        // 0.42 days per month (5 days per year)
    'vacation' => 1.25     // 1.25 days per month (15 days per year)
];

try {
    // Function to process monthly leave accrual (production version)
    function processMonthlyAccrual($pdo, $leaveRates, $currentYear) {
        $currentMonth = date('n'); // 1-12
        $monthName = date('F');
        
        // Get all active employees
        $stmt = $pdo->query("SELECT id, CONCAT(fname, ' ', lname) as full_name FROM employees WHERE status = 'active'");
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $processedCount = 0;
        $newEmployees = 0;
        $updatedEmployees = 0;
        $skippedEmployees = 0;
        
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
                    $lastProcessedMonth = intval($record['last_processed_month'] ?? 0);
                    if ($lastProcessedMonth >= $currentMonth) {
                        if (!$employeeProcessed) {
                            $skippedEmployees++;
                            $employeeProcessed = true;
                        }
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
        
        return [
            'employees' => count($employees), 
            'records' => $processedCount,
            'new' => $newEmployees,
            'updated' => $updatedEmployees,
            'skipped' => $skippedEmployees,
            'month' => $monthName
        ];
    }
    
    // Process the monthly accrual
    $result = processMonthlyAccrual($pdo, $monthlyLeaveRates, $currentYear);
    
    // Log the processing
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => 'manual_monthly_accrual',
        'month' => $currentMonth,
        'year' => $currentYear,
        'result' => $result,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ];
    
    // You can add logging to a file or database here if needed
    // file_put_contents(__DIR__ . '/accrual_log.txt', json_encode($logEntry) . "\n", FILE_APPEND);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => "Monthly accrual processed successfully for {$result['month']} {$currentYear}",
        'employees' => $result['employees'],
        'records' => $result['records'],
        'new' => $result['new'],
        'updated' => $result['updated'],
        'skipped' => $result['skipped'],
        'month' => $result['month'],
        'year' => $currentYear,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

} catch (Exception $globalError) {
    // Catch any errors from the include or initial setup
    echo json_encode([
        'success' => false, 
        'message' => 'System error: ' . $globalError->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

// Ensure clean output
ob_end_flush();
?>