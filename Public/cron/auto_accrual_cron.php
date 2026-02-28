<?php
/**
 * Auto-Accrual Cron Job (Daily Check)
 * This script runs daily and checks if:
 * 1. Auto-accrual is enabled
 * 2. It's the last day of the month
 * 3. Current month hasn't been processed yet
 * 
 * FIXED (Feb 28, 2026):
 * - Uses UPDATE instead of INSERT...ON DUPLICATE KEY to prevent duplicate system_settings rows
 * - Uses SELECT FOR UPDATE lock to prevent concurrent processing
 * - Only accrues VL (no SL per new policy)
 * - Added ORDER BY id DESC LIMIT 1 to always get the latest setting value
 * - Added Old_Regular to employee type filter
 * 
 * Setup: Run this daily via cron OR Windows Task Scheduler
 * Cron: 0 23 * * * /usr/bin/php /path/to/auto_accrual_cron.php
 */

// Set time limit
set_time_limit(300);

// Include database
require_once(__DIR__ . '/../config/db.php');

// Log file
$logFile = __DIR__ . '/../logs/auto_accrual.log';
if (!file_exists(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$timestamp}] {$message}\n", FILE_APPEND);
    echo "[{$timestamp}] {$message}\n";
}

try {
    logMessage("=== AUTO-ACCRUAL CRON CHECK STARTED ===");
    
    // 1. Check if auto-accrual is enabled (ORDER BY id DESC to get latest value)
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'auto_accrual_enabled' ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $autoAccrualEnabled = $stmt->fetchColumn();
    
    if ($autoAccrualEnabled !== '1' && $autoAccrualEnabled !== 1) {
        logMessage("Auto-accrual is DISABLED. Exiting.");
        exit(0);
    }
    
    logMessage("Auto-accrual is ENABLED. Checking mode...");
    
    // 1.5 Check accrual mode (testing or production)
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'accrual_mode' ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $accrualMode = $stmt->fetchColumn() ?: 'production';
    
    logMessage("Accrual mode: " . strtoupper($accrualMode));
    
    // 2. Check conditions based on mode
    $currentMonth = (int)date('n');
    $currentYear = (int)date('Y');
    
    if ($accrualMode === 'production') {
        // Production mode - check if last day of month
        $currentDay = (int)date('j');
        $lastDayOfMonth = (int)date('t');
        
        if ($currentDay !== $lastDayOfMonth) {
            logMessage("Not the last day of the month (Day $currentDay of $lastDayOfMonth). Exiting.");
            exit(0);
        }
        
        logMessage("Today IS the last day of the month!");
        
        // Use transaction + FOR UPDATE lock to prevent concurrent runs
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'last_auto_accrual_month' ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $stmt->execute();
        $lastProcessedMonth = (int)$stmt->fetchColumn();
        
        if ($lastProcessedMonth === $currentMonth) {
            $pdo->rollBack();
            logMessage("Already processed for this month ($currentMonth). Exiting.");
            exit(0);
        }
        
        // Mark as processed IMMEDIATELY to prevent concurrent runs
        $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = 'last_auto_accrual_month'");
        $stmt->execute([$currentMonth]);
        
        logMessage("Month $currentMonth marked as processing. Starting accrual...");
        
    } else {
        // Testing mode - run every time
        logMessage("TESTING MODE - Running accrual cycle...");
        $pdo->beginTransaction();
    }
    
    // 3. Process the accrual
    // ACCRUAL POLICY (Updated - No Sick Leave Accrual):
    // - Regular & Old_Regular: ONLY VL (1.25/month = 15/year)
    // - Probationary: No automatic accrual
    $leaveType = 'vacation';
    $monthlyIncrement = 1.25;
    
    // Get all active employees with Regular or Old_Regular employment type
    $stmt = $pdo->query("SELECT id, CONCAT(fname, ' ', lname) as full_name, Emp_Type FROM employees WHERE status = 'active' AND Emp_Type IN ('Regular', 'Old_Regular')");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    logMessage("Found " . count($employees) . " active Regular/Old_Regular employees");
    
    $processedCount = 0;
    $errorCount = 0;
    
    foreach ($employees as $employee) {
        try {
            $employee_id = $employee['id'];
            
            // Only VL accrual — no SL accrual per policy
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
                
                logMessage("  {$employee['full_name']}: VL {$currentBalance} -> {$newBalance} (carry: {$currentCarryOver} -> {$newCarryOver})");
                
            } else {
                // Create new record
                $stmt = $pdo->prepare("INSERT INTO leave_credits (employee_id, leave_type, balance, carry_over, year, monthly_increment, updated_at) VALUES (?, ?, ?, 0, ?, ?, NOW())");
                $stmt->execute([$employee_id, $leaveType, $monthlyIncrement, $currentYear, $monthlyIncrement]);
                
                logMessage("  {$employee['full_name']}: NEW VL record = {$monthlyIncrement}");
            }
            
            $processedCount++;
            
        } catch (Exception $e) {
            $errorCount++;
            logMessage("ERROR processing employee {$employee['full_name']}: " . $e->getMessage());
        }
    }
    
    // Commit the transaction
    $pdo->commit();
    
    logMessage("=== AUTO-ACCRUAL COMPLETED ===");
    logMessage("Mode: " . strtoupper($accrualMode));
    logMessage("Employees processed: $processedCount");
    logMessage("Errors: $errorCount");
    
    exit(0);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    logMessage("FATAL ERROR: " . $e->getMessage());
    exit(1);
}
?>
