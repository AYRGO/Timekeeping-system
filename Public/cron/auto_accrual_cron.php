<?php
/**
 * Auto-Accrual Cron Job (Daily Check)
 * This script runs daily and checks if:
 * 1. Auto-accrual is enabled
 * 2. It's the last day of the month
 * 3. Current month hasn't been processed yet
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
    
    // 1. Check if auto-accrual is enabled
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'auto_accrual_enabled'");
    $stmt->execute();
    $autoAccrualEnabled = $stmt->fetchColumn();
    
    if ($autoAccrualEnabled !== '1' && $autoAccrualEnabled !== 1) {
        logMessage("Auto-accrual is DISABLED. Exiting.");
        exit(0);
    }
    
    logMessage("Auto-accrual is ENABLED. Checking mode...");
    
    // 1.5 Check accrual mode (testing or production)
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'accrual_mode'");
    $stmt->execute();
    $accrualMode = $stmt->fetchColumn() ?: 'production';
    
    logMessage("Accrual mode: " . strtoupper($accrualMode));
    
    // 2. Check conditions based on mode
    if ($accrualMode === 'production') {
        // Production mode - check if last day of month
        $currentDay = (int)date('j');
        $lastDayOfMonth = (int)date('t');
        
        if ($currentDay !== $lastDayOfMonth) {
            logMessage("Not the last day of the month (Day $currentDay of $lastDayOfMonth). Exiting.");
            exit(0);
        }
        
        logMessage("Today IS the last day of the month!");
        
        // Check if already processed this month
        $currentMonth = (int)date('n');
        $currentYear = (int)date('Y');
        
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'last_auto_accrual_month'");
        $stmt->execute();
        $lastProcessedMonth = (int)$stmt->fetchColumn();
        
        if ($lastProcessedMonth === $currentMonth) {
            logMessage("Already processed for this month ($currentMonth). Exiting.");
            exit(0);
        }
        
        logMessage("Month $currentMonth has NOT been processed yet. Starting accrual...");
        
    } else {
        // Testing mode - run every time (every 10 seconds)
        logMessage("TESTING MODE - Running accrual cycle...");
        $currentYear = (int)date('Y');
    }
    
    // 3. Process the accrual
    $leaveRates = [
        'sick' => 0.42,
        'vacation' => 1.25
    ];
    
    // Get all active employees with Regular employment type ONLY
    $stmt = $pdo->query("SELECT id, CONCAT(fname, ' ', lname) as full_name, Emp_Type FROM employees WHERE status = 'active' AND Emp_Type = 'Regular'");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    logMessage("Found " . count($employees) . " active Regular employees");
    
    $processedCount = 0;
    $errorCount = 0;
    
    foreach ($employees as $employee) {
        try {
            $employee_id = $employee['id'];
            
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
            $errorCount++;
            logMessage("ERROR processing employee {$employee['full_name']}: " . $e->getMessage());
        }
    }
    
    // 5. Update last processed month (only in production mode)
    if ($accrualMode === 'production') {
        $currentMonth = (int)date('n');
        $stmt = $pdo->prepare("
            INSERT INTO system_settings (setting_key, setting_value, updated_at) 
            VALUES ('last_auto_accrual_month', ?, NOW())
            ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
        ");
        $stmt->execute([$currentMonth, $currentMonth]);
        logMessage("Month marked as processed: " . date('F Y'));
    }
    
    logMessage("=== AUTO-ACCRUAL COMPLETED ===");
    logMessage("Mode: " . strtoupper($accrualMode));
    logMessage("Employees processed: $processedCount");
    logMessage("Errors: $errorCount");
    
    exit(0);
    
} catch (Exception $e) {
    logMessage("FATAL ERROR: " . $e->getMessage());
    exit(1);
}
?>
