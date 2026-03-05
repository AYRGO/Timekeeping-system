<?php
/**
 * Auto-Forfeit Expired Pending Requests
 * 
 * This script automatically marks pending requests as "Forfeited" when they pass the present day.
 * Should be run daily via cron job or triggered on page load.
 * 
 * Tables affected:
 * - schedule_change_requests (end_date < today)
 * - schedule_switch_requests (target_date < today)
 * - month_weekly_schedule (last day of month < today)
 */

// Set SERVER_NAME for CLI execution to use local database
if (php_sapi_name() === 'cli') {
    $_SERVER['SERVER_NAME'] = 'localhost';
    $_SERVER['SERVER_ADDR'] = '127.0.0.1';
}

require_once(__DIR__ . '/../config/db.php');

try {
    $today = date('Y-m-d');
    $totalForfeited = 0;
    
    // ===== SCHEMA MIGRATION: Ensure status columns support 'Forfeited' and 'Cancelled' =====
    // These ALTER TABLEs are idempotent — safe to run repeatedly
    try {
        // Check if schedule_change_requests status column needs updating
        $colInfo = $pdo->query("SHOW COLUMNS FROM schedule_change_requests LIKE 'status'")->fetch(PDO::FETCH_ASSOC);
        if ($colInfo && strpos($colInfo['Type'], 'Forfeited') === false) {
            $pdo->exec("ALTER TABLE schedule_change_requests MODIFY COLUMN status VARCHAR(50) DEFAULT 'Pending'");
            // Fix any records that were corrupted by previous enum constraint (empty strings from failed 'Forfeited' writes)
            $pdo->exec("UPDATE schedule_change_requests SET status = 'Pending' WHERE status = '' OR status IS NULL");
            $logMigration = "[" . date('Y-m-d H:i:s') . "] MIGRATION: schedule_change_requests.status changed from ENUM to VARCHAR(50), fixed empty status records\n";
            $logFile = __DIR__ . '/../../logs/forfeit_log.txt';
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) mkdir($logDir, 0755, true);
            file_put_contents($logFile, $logMigration, FILE_APPEND);
        }
        
        // Check if schedule_switch_requests status column needs updating
        $colInfo = $pdo->query("SHOW COLUMNS FROM schedule_switch_requests LIKE 'status'")->fetch(PDO::FETCH_ASSOC);
        if ($colInfo && strpos($colInfo['Type'], 'forfeited') === false) {
            $pdo->exec("ALTER TABLE schedule_switch_requests MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending'");
            $pdo->exec("UPDATE schedule_switch_requests SET status = 'pending' WHERE status = '' OR status IS NULL");
        }
        
        // Check if month_weekly_schedule status column needs updating
        $colInfo = $pdo->query("SHOW COLUMNS FROM month_weekly_schedule LIKE 'status'")->fetch(PDO::FETCH_ASSOC);
        if ($colInfo && strpos($colInfo['Type'], 'forfeited') === false) {
            $pdo->exec("ALTER TABLE month_weekly_schedule MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending'");
            $pdo->exec("UPDATE month_weekly_schedule SET status = 'pending' WHERE status = '' OR status IS NULL");
        }
    } catch (Exception $migrationError) {
        // Log migration error but continue with forfeit logic
        $logFile = __DIR__ . '/../../logs/forfeit_log.txt';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) mkdir($logDir, 0755, true);
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] MIGRATION ERROR: " . $migrationError->getMessage() . "\n", FILE_APPEND);
    }
    // ===== END SCHEMA MIGRATION =====
    
    // 1. Forfeit expired schedule_change_requests
    $stmt = $pdo->prepare("
        UPDATE schedule_change_requests 
        SET status = 'Forfeited',
            explanation = CONCAT(
                COALESCE(explanation, ''), 
                IF(explanation IS NOT NULL AND explanation != '', '\n\n', ''),
                'Auto-forfeited: Request expired on ', end_date, ' without admin action.'
            )
        WHERE status = 'Pending' 
        AND end_date < ?
    ");
    $stmt->execute([$today]);
    $scheduleChangeForfeited = $stmt->rowCount();
    $totalForfeited += $scheduleChangeForfeited;
    
    // 2. Forfeit expired schedule_switch_requests
    $stmt = $pdo->prepare("
        UPDATE schedule_switch_requests 
        SET status = 'forfeited',
            admin_notes = CONCAT(
                COALESCE(admin_notes, ''), 
                IF(admin_notes IS NOT NULL AND admin_notes != '', '\n\n', ''),
                'Auto-forfeited: Swap date (', target_date, ') has passed without admin action.'
            )
        WHERE status = 'pending' 
        AND target_date < ?
    ");
    $stmt->execute([$today]);
    $swapForfeited = $stmt->rowCount();
    $totalForfeited += $swapForfeited;
    
    // 3. Forfeit expired month_weekly_schedule requests
    // Calculate last day of the month for each pending request
    $stmt = $pdo->prepare("
        UPDATE month_weekly_schedule 
        SET status = 'forfeited',
            admin_notes = CONCAT(
                COALESCE(admin_notes, ''), 
                IF(admin_notes IS NOT NULL AND admin_notes != '', '\n\n', ''),
                'Auto-forfeited: Month (', year, '-', LPAD(month, 2, '0'), ') has ended without admin action.'
            )
        WHERE status = 'pending' 
        AND LAST_DAY(CONCAT(year, '-', LPAD(month, 2, '0'), '-01')) < ?
    ");
    $stmt->execute([$today]);
    $monthlyForfeited = $stmt->rowCount();
    $totalForfeited += $monthlyForfeited;
    
    // Log the results
    $logMessage = sprintf(
        "[%s] Auto-forfeit executed: %d schedule changes, %d swaps, %d monthly schedules. Total: %d requests forfeited.\n",
        date('Y-m-d H:i:s'),
        $scheduleChangeForfeited,
        $swapForfeited,
        $monthlyForfeited,
        $totalForfeited
    );
    
    // Write to log file
    $logFile = __DIR__ . '/../../logs/forfeit_log.txt';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    // Return results (useful when called programmatically)
    return [
        'success' => true,
        'total_forfeited' => $totalForfeited,
        'schedule_change_forfeited' => $scheduleChangeForfeited,
        'swap_forfeited' => $swapForfeited,
        'monthly_forfeited' => $monthlyForfeited,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
} catch (Exception $e) {
    $errorMessage = sprintf(
        "[%s] ERROR in auto-forfeit: %s\n",
        date('Y-m-d H:i:s'),
        $e->getMessage()
    );
    
    $logFile = __DIR__ . '/../../logs/forfeit_log.txt';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents($logFile, $errorMessage, FILE_APPEND);
    
    return [
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
}
