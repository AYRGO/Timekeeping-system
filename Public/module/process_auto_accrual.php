<?php
/**
 * Auto-Accrual Processor - Called automatically by JavaScript
 * This processes leave accruals based on mode:
 * - Testing mode: Runs every time (10 seconds)
 * - Production mode: Runs only on last day of month (once per month)
 * 
 * FIXED (Feb 28, 2026):
 * - Uses UPDATE instead of INSERT...ON DUPLICATE KEY to prevent duplicate system_settings rows
 * - Uses SELECT FOR UPDATE lock to prevent concurrent processing
 * - Only accrues VL (no SL per new policy)
 * - Added ORDER BY id DESC LIMIT 1 to always get the latest setting value
 */

session_start();
include('../config/db.php');
require_once __DIR__ . '/../config/demo_guard.php';
demo_block_mutation('Auto accrual processing is disabled in demo mode.');

// Allow any logged-in user to trigger auto-accrual (it runs in background)
if (!isset($_SESSION['employee']['id'])) {
    die(json_encode(['success' => false, 'error' => 'Not logged in']));
}

header('Content-Type: application/json');

try {
    // 1. Check if auto-accrual is enabled (ORDER BY id DESC to get latest value)
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'auto_accrual_enabled' ORDER BY id DESC LIMIT 1");
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
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'accrual_mode' ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $accrualMode = $stmt->fetchColumn() ?: 'production';
    
    $shouldProcess = false;
    $reason = '';
    $currentYear = (int)date('Y');
    $currentMonth = (int)date('n');
    
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
        
        // Use transaction + FOR UPDATE lock to prevent race conditions
        $pdo->beginTransaction();
        
        // Lock the setting row to prevent concurrent accrual runs
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'last_auto_accrual_month' ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $stmt->execute();
        $lastProcessedMonth = (int)$stmt->fetchColumn();
        
        if ($lastProcessedMonth === $currentMonth) {
            $pdo->rollBack();
            echo json_encode([
                'success' => true,
                'message' => 'Already processed for this month',
                'processed' => 0,
                'status' => 'completed',
                'month' => date('F Y')
            ]);
            exit;
        }
        
        // Mark as processed IMMEDIATELY (before doing work) to prevent concurrent runs
        $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = 'last_auto_accrual_month'");
        $stmt->execute([$currentMonth]);
        
        $shouldProcess = true;
        $reason = 'Last day of month - ' . date('F j, Y');
        
    } else {
        // Testing mode - always process
        $shouldProcess = true;
        $reason = 'Testing mode - immediate processing';
        $pdo->beginTransaction();
    }
    
    if (!$shouldProcess) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode([
            'success' => true,
            'message' => 'Conditions not met',
            'processed' => 0,
            'status' => 'skipped'
        ]);
        exit;
    }
    
    // 3. Process the accrual
    // ACCRUAL POLICY:
    // - Regular & Old_Regular: Gets ONLY VL (1.25/month) — NO sick leave accrual
    // - PROBATIONARY & FLOATING: No automatic accrual
    
    $stmt = $pdo->query("SELECT id, CONCAT(fname, ' ', lname) as full_name, Emp_Type FROM employees WHERE status = 'active' AND Emp_Type IN ('Regular', 'Old_Regular')");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $processedCount = 0;
    $errors = [];
    
    foreach ($employees as $employee) {
        try {
            $employee_id = $employee['id'];
            
            // Only VL accrual — no SL accrual per policy
            $leaveType = 'vacation';
            $monthlyIncrement = 1.25;
            
            // Check existing record
            $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND leave_type = ? AND year = ?");
            $stmt->execute([$employee_id, $leaveType, $currentYear]);
            $record = $stmt->fetch();
            
            if ($record) {
                $currentBalance = floatval($record['balance']);
                $currentCarryOver = floatval($record['carry_over'] ?? 0);
                
                // Apply balance limits
                $maxBalance = 15;
                $spaceLeft = max(0, $maxBalance - $currentBalance);
                $toBalance = min($monthlyIncrement, $spaceLeft);
                $remaining = $monthlyIncrement - $toBalance;
                
                // Handle vacation carry-over
                $toCarryOver = 0;
                if ($remaining > 0) {
                    $maxCarryOver = 5;
                    $carrySpace = max(0, $maxCarryOver - $currentCarryOver);
                    $toCarryOver = min($remaining, $carrySpace);
                }
                
                $newBalance = $currentBalance + $toBalance;
                $newCarryOver = $currentCarryOver + $toCarryOver;
                
                $stmt = $pdo->prepare("UPDATE leave_credits SET balance = ?, carry_over = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$newBalance, $newCarryOver, $record['id']]);
                
            } else {
                // Create new record
                $stmt = $pdo->prepare("INSERT INTO leave_credits (employee_id, leave_type, balance, carry_over, year, monthly_increment, updated_at) VALUES (?, ?, ?, 0, ?, ?, NOW())");
                $stmt->execute([$employee_id, $leaveType, $monthlyIncrement, $currentYear, $monthlyIncrement]);
            }
            
            $processedCount++;
            
        } catch (Exception $e) {
            $errors[] = "Employee {$employee['full_name']}: " . $e->getMessage();
        }
    }
    
    // Commit the entire transaction (month already marked as processed)
    $pdo->commit();
    
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
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'status' => 'error'
    ]);
}
?>
