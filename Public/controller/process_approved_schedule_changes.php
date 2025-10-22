<?php
/**
 * Process Approved Schedule Change Requests
 * 
 * This script automatically creates employee_daily_schedules entries
 * for all approved schedule change requests in post_schedule_change_requests
 * that haven't been processed yet.
 * 
 * Run this script:
 * 1. Via cron job (recommended for automatic processing)
 * 2. Manually after approving requests
 * 3. As part of the approval process
 */

require_once '../config/config.php';
require_once '../config/session_handler.php';

// Set execution time limit for large batch processing
set_time_limit(300); // 5 minutes

/**
 * Process a single approved schedule change request
 * Creates daily schedule entries from start_date to end_date
 */
function processScheduleChange($pdo, $request) {
    try {
        $employee_id = $request['employee_id'];
        $work_schedule_id = $request['work_schedule_id'];
        $start_date = $request['start_date'];
        $end_date = $request['end_date'];
        $request_id = $request['id'];
        
        echo "Processing request ID {$request_id} for employee {$employee_id}\n";
        echo "Date range: {$start_date} to {$end_date}\n";
        echo "New schedule ID: {$work_schedule_id}\n";
        
        // Validate dates
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        
        if ($start > $end) {
            echo "ERROR: Start date is after end date!\n";
            return false;
        }
        
        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($start, $interval, $end->modify('+1 day'));
        
        $insertCount = 0;
        $updateCount = 0;
        $skipCount = 0;
        
        // Begin transaction for data integrity
        $pdo->beginTransaction();
        
        foreach ($dateRange as $date) {
            $currentDate = $date->format('Y-m-d');
            
            // Check if a daily schedule already exists for this date
            $checkStmt = $pdo->prepare("
                SELECT id, actual_schedule_id 
                FROM employee_daily_schedules 
                WHERE employee_id = ? AND schedule_date = ?
            ");
            $checkStmt->execute([$employee_id, $currentDate]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Update existing entry - use work_schedule_id as the new schedule
                $updateStmt = $pdo->prepare("
                    UPDATE employee_daily_schedules 
                    SET actual_schedule_id = ?,
                        is_rest_day = 0,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $updateStmt->execute([$work_schedule_id, $existing['id']]);
                $updateCount++;
                echo "  Updated: {$currentDate}\n";
            } else {
                // Insert new entry - use work_schedule_id as the new schedule
                $insertStmt = $pdo->prepare("
                    INSERT INTO employee_daily_schedules 
                    (employee_id, schedule_date, actual_schedule_id, is_rest_day,
                     is_holiday, is_company_holiday, has_override, has_time_log,
                     scheduled_hours, actual_hours, overtime_hours, undertime_hours, late_minutes,
                     attendance_status, time_log_status, display_priority,
                     created_at, updated_at)
                    VALUES (?, ?, ?, 0, 0, 0, 0, 0, 0.00, 0.00, 0.00, 0.00, 0,
                            'scheduled', 'no_log', 1, NOW(), NOW())
                ");
                $insertStmt->execute([$employee_id, $currentDate, $work_schedule_id]);
                $insertCount++;
                echo "  Inserted: {$currentDate}\n";
            }
        }
        
        // Mark this request as processed in post_schedule_change_requests
        $markStmt = $pdo->prepare("
            UPDATE post_schedule_change_requests 
            SET processed_to_calendar = 1,
                processed_at = NOW()
            WHERE id = ?
        ");
        $markStmt->execute([$request_id]);
        
        // Commit transaction
        $pdo->commit();
        
        echo "✅ Successfully processed request ID {$request_id}\n";
        echo "   Inserted: {$insertCount} | Updated: {$updateCount} | Skipped: {$skipCount}\n\n";
        
        return true;
        
    } catch (Exception $e) {
        // Rollback on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "❌ Error processing request ID {$request_id}: " . $e->getMessage() . "\n\n";
        return false;
    }
}

// Main execution
try {
    echo "=== Processing Approved Schedule Change Requests ===\n";
    echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";
    
    // First, check if processed_to_calendar column exists, if not add it
    try {
        $checkColumn = $pdo->query("
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'post_schedule_change_requests' 
            AND COLUMN_NAME = 'processed_to_calendar'
        ")->fetch();
        
        if (!$checkColumn) {
            echo "Adding 'processed_to_calendar' column to post_schedule_change_requests table...\n";
            $pdo->exec("
                ALTER TABLE post_schedule_change_requests 
                ADD COLUMN processed_to_calendar TINYINT(1) DEFAULT 0 AFTER status,
                ADD COLUMN processed_at DATETIME NULL AFTER processed_to_calendar
            ");
            echo "✅ Column added successfully\n\n";
        }
    } catch (PDOException $e) {
        echo "Note: Column check/creation: " . $e->getMessage() . "\n\n";
    }
    
    // Get all approved schedule changes that haven't been processed yet
    // Note: Using LOWER() to handle both 'approved' and 'Approved' status values
    $stmt = $pdo->prepare("
        SELECT id, employee_id, work_schedule_id, start_date, end_date, status, created_at
        FROM post_schedule_change_requests 
        WHERE LOWER(status) = 'approved' 
        AND (processed_to_calendar IS NULL OR processed_to_calendar = 0)
        AND start_date IS NOT NULL 
        AND end_date IS NOT NULL
        AND work_schedule_id IS NOT NULL
        ORDER BY created_at ASC
    ");
    $stmt->execute();
    $approvedRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalRequests = count($approvedRequests);
    echo "Found {$totalRequests} approved schedule change request(s) to process\n\n";
    
    if ($totalRequests === 0) {
        echo "No requests to process. Exiting.\n";
        exit(0);
    }
    
    $successCount = 0;
    $failureCount = 0;
    
    foreach ($approvedRequests as $request) {
        if (processScheduleChange($pdo, $request)) {
            $successCount++;
        } else {
            $failureCount++;
        }
    }
    
    echo "\n=== Processing Complete ===\n";
    echo "Finished at: " . date('Y-m-d H:i:s') . "\n";
    echo "Total Requests: {$totalRequests}\n";
    echo "Successful: {$successCount}\n";
    echo "Failed: {$failureCount}\n";
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
