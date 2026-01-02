<?php
/**
 * Auto-Accrual Processor - Called automatically by JavaScript
 * This processes leave accruals based on mode:
 * - Testing mode: Runs every time (10 seconds)
 * - Production mode: Runs only on last day of month (once per month)
 */

session_start();
include('../config/db.php');

// Allow any logged-in user to trigger auto-accrual (it runs in background)
if (!isset($_SESSION['employee']['id'])) {
    die(json_encode(['success' => false, 'error' => 'Not logged in']));
}

header('Content-Type: application/json');

try {
    // 1. Check if auto-accrual is enabled
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'auto_accrual_enabled'");
    $stmt->execute();
    $autoAccrualEnabled = $stmt->fetchColumn();
    
    if ($autoAccrualEnabled !== '1' && $autoAccrualEnabled !== 1) {
        echo json_encode([
            'success' => true,
            'message' => 'Auto-accrual is disabled',
            'processed' => 0,
            'status' => 'disabled'
        ]);
        exit;
    }
    
    // 2. Check accrual mode
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'accrual_mode'");
    $stmt->execute();
    $accrualMode = $stmt->fetchColumn() ?: 'production';
    
    $shouldProcess = false;
    $reason = '';
    
    if ($accrualMode === 'production') {
        // Production mode - check if last day of month
        $currentDay = (int)date('j');
        $lastDayOfMonth = (int)date('t');
        
        if ($currentDay !== $lastDayOfMonth) {
            echo json_encode([
                'success' => true,
                'message' => 'Not the last day of month',
                'processed' => 0,
                'status' => 'waiting',
                'next_check' => 'Tomorrow'
            ]);
            exit;
        }
        
        // Check if already processed this month
        $currentMonth = (int)date('n');
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'last_auto_accrual_month'");
        $stmt->execute();
        $lastProcessedMonth = (int)$stmt->fetchColumn();
        
        if ($lastProcessedMonth === $currentMonth) {
            echo json_encode([
                'success' => true,
                'message' => 'Already processed for this month',
                'processed' => 0,
                'status' => 'completed',
                'month' => date('F Y')
            ]);
            exit;
        }
        
        $shouldProcess = true;
        $reason = 'Last day of month - ' . date('F j, Y');
        
    } else {
        // Testing mode - always process
        $shouldProcess = true;
        $reason = 'Testing mode - immediate processing';
    }
    
    if (!$shouldProcess) {
        echo json_encode([
            'success' => true,
            'message' => 'Conditions not met',
            'processed' => 0,
            'status' => 'skipped'
        ]);
        exit;
    }
    
    // 3. Process the accrual
    $currentYear = (int)date('Y');
    
    // ACCRUAL POLICY (Updated - No Sick Leave Accrual):
    // - OLD_REGULAR (hired before Jan 1, 2026): Gets ONLY VL (1.25/month) - sick leave accrual removed
    // - REGULAR (regularized after Jan 1, 2026): Gets ONLY VL (1.25/month) - already got 5-day SL upon regularization
    // - PROBATIONARY: No automatic accrual
    
    // Get all active employees with Regular or Old_Regular employment type
    $stmt = $pdo->query("SELECT id, CONCAT(fname, ' ', lname) as full_name, Emp_Type FROM employees WHERE status = 'active' AND Emp_Type IN ('Regular', 'Old_Regular')");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $processedCount = 0;
    $errors = [];
    
    foreach ($employees as $employee) {
        try {
            $employee_id = $employee['id'];
            $empType = $employee['Emp_Type'];
            
            // Determine leave rates based on employment type
            $leaveRates = [];
            
            if ($empType === 'Old_Regular') {
                // Old employees now get ONLY VL monthly (sick leave accrual removed)
                $leaveRates = [
                    'vacation' => 1.25   // 15 days / 12 months
                ];
            } elseif ($empType === 'Regular') {
                // New employees get ONLY VL monthly (SL was one-time upon regularization)
                $leaveRates = [
                    'vacation' => 1.25
                ];
            }
            
            foreach ($leaveRates as $leaveType => $monthlyIncrement) {
                // Check existing record
                $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND leave_type = ? AND year = ?");
                $stmt->execute([$employee_id, $leaveType, $currentYear]);
                $record = $stmt->fetch();
                
                if ($record) {
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
            }
            
            $processedCount++;
            
        } catch (Exception $e) {
            $errors[] = "Employee {$employee['full_name']}: " . $e->getMessage();
        }
    }
    
    // 4. Update last processed month (only in production mode)
    if ($accrualMode === 'production') {
        $currentMonth = (int)date('n');
        $stmt = $pdo->prepare("
            INSERT INTO system_settings (setting_key, setting_value, updated_at) 
            VALUES ('last_auto_accrual_month', ?, NOW())
            ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
        ");
        $stmt->execute([$currentMonth, $currentMonth]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Accrual processed successfully",
        'processed' => $processedCount,
        'total_employees' => count($employees),
        'mode' => $accrualMode,
        'reason' => $reason,
        'errors' => $errors,
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => 'completed'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'status' => 'error'
    ]);
}
?>
