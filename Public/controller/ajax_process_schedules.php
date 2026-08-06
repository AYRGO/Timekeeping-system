<?php
/**
 * AJAX Endpoint: Process Approved Schedule Changes
 * 
 * This endpoint can be called via AJAX to process approved schedule changes
 * and create daily calendar entries automatically.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (empty($_SESSION['employee']['id']) || !is_numeric($_SESSION['employee']['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$currentEmployeeId = (int)($_SESSION['employee']['id'] ?? 0);
session_write_close();

try {
    // Get approved requests that need processing
    // The status column uses a case-insensitive collation, so the direct comparison
    // remains indexable while accepting the existing status casing.
    $stmt = $pdo->prepare("
        SELECT id, employee_id, work_schedule_id, start_date, end_date
        FROM post_schedule_change_requests 
        WHERE employee_id = ?
        AND status = 'approved'
        AND (processed_to_calendar IS NULL OR processed_to_calendar = 0)
        AND start_date IS NOT NULL 
        AND end_date IS NOT NULL
        AND work_schedule_id IS NOT NULL
        ORDER BY created_at ASC
        LIMIT 50
    ");
    $stmt->execute([$currentEmployeeId]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $processed = 0;
    $errors = [];
    
    foreach ($requests as $request) {
        try {
            $pdo->beginTransaction();
            
            $start = new DateTime($request['start_date']);
            $end = new DateTime($request['end_date']);
            $interval = new DateInterval('P1D');
            $dateRange = new DatePeriod($start, $interval, $end->modify('+1 day'));
            
            foreach ($dateRange as $date) {
                $currentDate = $date->format('Y-m-d');
                
                // Check if entry exists
                $checkStmt = $pdo->prepare("
                    SELECT id FROM employee_daily_schedules 
                    WHERE employee_id = ? AND schedule_date = ?
                ");
                $checkStmt->execute([$request['employee_id'], $currentDate]);
                $existing = $checkStmt->fetch();
                
                if ($existing) {
                    // Update existing - only update the schedule-related columns
                    $updateStmt = $pdo->prepare("
                        UPDATE employee_daily_schedules 
                        SET actual_schedule_id = ?,
                            is_rest_day = 0,
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    $updateStmt->execute([
                        $request['work_schedule_id'],  // This is the NEW schedule from approved request
                        $existing['id']
                    ]);
                } else {
                    // Insert new - include required fields only
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
                    $insertStmt->execute([
                        $request['employee_id'],
                        $currentDate,
                        $request['work_schedule_id']  // This is the NEW schedule from approved request
                    ]);
                }
            }
            
            // Mark as processed
            $markStmt = $pdo->prepare("
                UPDATE post_schedule_change_requests 
                SET processed_to_calendar = 1, processed_at = NOW()
                WHERE id = ?
            ");
            $markStmt->execute([$request['id']]);
            
            $pdo->commit();
            $processed++;
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "Request ID {$request['id']}: " . $e->getMessage();
        }
    }
    
    echo json_encode([
        'success' => true,
        'processed' => $processed,
        'total_found' => count($requests),
        'errors' => $errors,
        'message' => "Processed {$processed} schedule change request(s)"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
