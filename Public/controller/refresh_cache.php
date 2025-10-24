<?php
/**
 * Refresh Schedule Cache for Employee
 * 
 * Rebuilds the employee_daily_schedule_cache for a specific employee and date range
 */

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response

include('../config/db.php');

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    $employee_id = isset($input['employee_id']) ? (int)$input['employee_id'] : 0;
    $start_date = isset($input['start_date']) ? $input['start_date'] : null;
    $end_date = isset($input['end_date']) ? $input['end_date'] : null;
    
    // Validate inputs
    if (!$employee_id || !$start_date || !$end_date) {
        throw new Exception('Missing required parameters');
    }
    
    // Validate employee exists
    $empCheck = $pdo->prepare("SELECT id FROM employees WHERE id = ?");
    $empCheck->execute([$employee_id]);
    if (!$empCheck->fetch()) {
        throw new Exception('Employee not found');
    }
    
    // Delete existing cache entries for this date range
    $deleteStmt = $pdo->prepare("
        DELETE FROM employee_daily_schedule_cache 
        WHERE employee_id = ? AND schedule_date BETWEEN ? AND ?
    ");
    $deleteStmt->execute([$employee_id, $start_date, $end_date]);
    
    $rebuilt = 0;
    $currentDate = $start_date;
    
    // Rebuild cache day by day
    while ($currentDate <= $end_date) {
        $dayOfWeek = date('w', strtotime($currentDate)); // 0 (Sunday) to 6 (Saturday)
        
        // Check for approved schedule requests for this date
        $requestStmt = $pdo->prepare("
            SELECT psr.work_schedule_id, psr.is_rest_day, ws.name as schedule_name, ws.time_in, ws.time_out
            FROM post_schedule_change_requests psr
            LEFT JOIN work_schedules ws ON psr.work_schedule_id = ws.id
            WHERE psr.employee_id = ? 
              AND psr.status = 'Approved'
              AND ? BETWEEN psr.start_date AND psr.end_date
            ORDER BY psr.created_at DESC
            LIMIT 1
        ");
        $requestStmt->execute([$employee_id, $currentDate]);
        $approvedRequest = $requestStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($approvedRequest) {
            // Use approved request
            $cacheStmt = $pdo->prepare("
                INSERT INTO employee_daily_schedule_cache 
                (employee_id, schedule_date, work_schedule_id, is_rest_day, is_holiday, 
                 schedule_name, time_in, time_out, holiday_name, source, source_id, created_at)
                VALUES (?, ?, ?, ?, 0, ?, ?, ?, NULL, 'approved_request', NULL, NOW())
            ");
            $cacheStmt->execute([
                $employee_id,
                $currentDate,
                $approvedRequest['work_schedule_id'],
                $approvedRequest['is_rest_day'] ?? 0,
                $approvedRequest['schedule_name'],
                $approvedRequest['time_in'],
                $approvedRequest['time_out']
            ]);
            $rebuilt++;
        } else {
            // Get default schedule for this day of week
            $schedStmt = $pdo->prepare("
                SELECT eds.work_schedule_id, eds.is_rest_day, ws.name as schedule_name, ws.time_in, ws.time_out
                FROM employee_default_schedules eds
                LEFT JOIN work_schedules ws ON eds.work_schedule_id = ws.id
                WHERE eds.employee_id = ? AND eds.day_of_week = ?
            ");
            $schedStmt->execute([$employee_id, $dayOfWeek]);
            $defaultSched = $schedStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($defaultSched) {
                // Insert cache entry with default schedule
                $cacheStmt = $pdo->prepare("
                    INSERT INTO employee_daily_schedule_cache 
                    (employee_id, schedule_date, work_schedule_id, is_rest_day, is_holiday, 
                     schedule_name, time_in, time_out, holiday_name, source, source_id, created_at)
                    VALUES (?, ?, ?, ?, 0, ?, ?, ?, NULL, 'weekly_default', NULL, NOW())
                ");
                $cacheStmt->execute([
                    $employee_id,
                    $currentDate,
                    $defaultSched['work_schedule_id'],
                    $defaultSched['is_rest_day'],
                    $defaultSched['schedule_name'],
                    $defaultSched['time_in'],
                    $defaultSched['time_out']
                ]);
                $rebuilt++;
            }
        }
        
        // Move to next day
        $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
    }
    
    echo json_encode([
        'success' => true,
        'rebuilt' => $rebuilt,
        'message' => "Cache refreshed successfully for $rebuilt days"
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
