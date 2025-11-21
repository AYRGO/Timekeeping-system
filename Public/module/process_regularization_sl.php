<?php
/**
 * One-Time Sick Leave (SL) Credit Processor for Regularization
 * Effective: January 1, 2026
 * 
 * Policy: When an employee is changed from Probationary to Regular,
 * they receive a one-time grant of 5.00 days Sick Leave immediately.
 * 
 * This is called when admin changes Emp_Type to 'Regular'.
 */

session_start();
include('../config/db.php');

// Only admins can trigger regularization
if (!isset($_SESSION['employee']) || $_SESSION['employee']['role'] !== 'internal') {
    die(json_encode(['success' => false, 'error' => 'Unauthorized - Admin only']));
}

header('Content-Type: application/json');

// Get the employee ID from POST
$data = json_decode(file_get_contents('php://input'), true);
$employee_id = isset($data['employee_id']) ? (int)$data['employee_id'] : 0;

if (!$employee_id) {
    die(json_encode(['success' => false, 'error' => 'Employee ID required']));
}

try {
    // 1. Verify employee exists and is Regular
    $stmt = $pdo->prepare("SELECT id, CONCAT(fname, ' ', lname) as full_name, Emp_Type FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        die(json_encode(['success' => false, 'error' => 'Employee not found']));
    }
    
    if ($employee['Emp_Type'] !== 'Regular') {
        die(json_encode(['success' => false, 'error' => 'Employee is not Regular status']));
    }
    
    $currentYear = (int)date('Y');
    $slCredit = 5.00; // Full 5-day credit
    
    // 2. Check if SL record already exists for this year
    $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND leave_type = 'sick' AND year = ?");
    $stmt->execute([$employee_id, $currentYear]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing record - ADD 5 days to current balance (in case they had some)
        $newBalance = floatval($existing['balance']) + $slCredit;
        
        // Cap at 15 days maximum
        $maxBalance = 15.00;
        if ($newBalance > $maxBalance) {
            $newBalance = $maxBalance;
        }
        
        $stmt = $pdo->prepare("
            UPDATE leave_credits 
            SET balance = ?, 
                monthly_increment = 0.42,
                updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$newBalance, $existing['id']]);
        
        $message = "Added {$slCredit} days SL to existing balance. New balance: {$newBalance} days";
        
    } else {
        // Create new SL record with 5-day balance
        $stmt = $pdo->prepare("
            INSERT INTO leave_credits 
            (employee_id, leave_type, balance, carry_over, year, monthly_increment, updated_at) 
            VALUES (?, 'sick', ?, NULL, ?, 0.42, NOW())
        ");
        $stmt->execute([$employee_id, $slCredit, $currentYear]);
        
        $message = "Created new SL record with {$slCredit} days balance";
    }
    
    // 3. Log this action
    $admin_id = $_SESSION['employee']['id'];
    $admin_name = $_SESSION['employee']['fname'] . ' ' . $_SESSION['employee']['lname'];
    
    // Optional: Create audit log entry (if you have an audit table)
    // For now, we'll just return success with details
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'employee_id' => $employee_id,
        'employee_name' => $employee['full_name'],
        'sl_granted' => $slCredit,
        'processed_by' => $admin_name,
        'timestamp' => date('Y-m-d H:i:s'),
        'policy_effective_date' => '2026-01-01'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
