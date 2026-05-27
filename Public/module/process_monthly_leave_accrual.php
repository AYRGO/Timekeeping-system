<?php
session_start();
include('../config/db.php');
require_once __DIR__ . '/../config/demo_guard.php';
demo_block_mutation('Monthly leave accrual processing is disabled in demo mode.');

// Check if user is logged in - removed strict admin check since this page is admin-only anyway
if (!isset($_SESSION['employee'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access - Please log in']));
}

header('Content-Type: application/json');

try {
    $currentYear = date('Y');
    $currentMonth = date('n');
    $monthName = date('F');
    
    // Define monthly leave rates
    $leaveRates = [
        'sick' => 0.42,
        'vacation' => 1.25
    ];
    
    // Get all active employees
    $stmt = $pdo->query("SELECT id, CONCAT(fname, ' ', lname) as full_name FROM employees WHERE status = 'active'");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($employees)) {
        echo json_encode([
            'success' => false, 
            'message' => 'No active employees found'
        ]);
        exit;
    }
    
    $processedEmployees = 0;
    $totalRecords = 0;
    $errors = [];
    $details = [];
    
    // Process each employee
    foreach ($employees as $employee) {
        try {
            $employee_id = $employee['id'];
            $employeeRecords = 0;
            $employeeDetails = [
                'name' => $employee['full_name'],
                'updates' => []
            ];
            
            foreach ($leaveRates as $leaveType => $monthlyIncrement) {
                // Check existing record
                $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND leave_type = ? AND year = ?");
                $stmt->execute([$employee_id, $leaveType, $currentYear]);
                $record = $stmt->fetch();
                
                $previousBalance = 0;
                $newBalance = 0;
                
                if ($record) {
                    // Update existing record
                    $previousBalance = floatval($record['balance']);
                    $currentCarryOver = ($leaveType === 'vacation') ? floatval($record['carry_over'] ?? 0) : 0;
                    
                    // Apply balance limits
                    $maxBalance = 15;
                    $spaceLeft = $maxBalance - $previousBalance;
                    $toBalance = min($monthlyIncrement, $spaceLeft);
                    $remaining = $monthlyIncrement - $toBalance;
                    
                    // Handle vacation carry-over
                    $toCarryOver = 0;
                    if ($leaveType === 'vacation' && $remaining > 0) {
                        $maxCarryOver = 5;
                        $carrySpace = $maxCarryOver - $currentCarryOver;
                        $toCarryOver = min($remaining, $carrySpace);
                    }
                    
                    $newBalance = $previousBalance + $toBalance;
                    $newCarryOver = ($leaveType === 'vacation') ? $currentCarryOver + $toCarryOver : null;
                    
                    $stmt = $pdo->prepare("UPDATE leave_credits SET balance = ?, carry_over = ?, monthly_increment = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$newBalance, $newCarryOver, $monthlyIncrement, $record['id']]);
                    
                } else {
                    // Create new record
                    $newBalance = $monthlyIncrement;
                    $carryOver = ($leaveType === 'vacation') ? 0 : null;
                    
                    $stmt = $pdo->prepare("INSERT INTO leave_credits (employee_id, leave_type, balance, carry_over, year, monthly_increment, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$employee_id, $leaveType, $newBalance, $carryOver, $currentYear, $monthlyIncrement]);
                }
                
                $employeeDetails['updates'][] = [
                    'type' => ucfirst($leaveType),
                    'previous' => number_format($previousBalance, 2),
                    'added' => number_format($monthlyIncrement, 2),
                    'new' => number_format($newBalance, 2)
                ];
                
                $employeeRecords++;
                $totalRecords++;
            }
            
            if ($employeeRecords > 0) {
                $processedEmployees++;
                $details[] = $employeeDetails;
            }
            
        } catch (Exception $e) {
            $errors[] = "Error processing {$employee['full_name']}: " . $e->getMessage();
        }
    }
    
    // Return success response with details
    echo json_encode([
        'success' => true,
        'message' => "Successfully processed leave accrual for {$monthName} {$currentYear}",
        'summary' => [
            'total_employees' => count($employees),
            'processed_employees' => $processedEmployees,
            'total_records_updated' => $totalRecords,
            'errors_count' => count($errors)
        ],
        'details' => $details,
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
