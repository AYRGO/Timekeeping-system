<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['employee']['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Set content type
header('Content-Type: application/json');

try {
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }
    
    // Validate required fields
    if (!isset($data['request_id']) || !isset($data['table_name']) || !isset($data['work_schedule_id'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $requestId = (int)$data['request_id'];
    $tableName = $data['table_name'];
    $workScheduleId = (int)$data['work_schedule_id'];
    $newDateRange = $data['new_date_range'] ?? null;
    $employeeId = $_SESSION['employee']['id'];
    
    // Security: Validate table name to prevent SQL injection
    $allowedTables = [
        'schedule_change_requests',
        'post_schedule_change_requests'
    ];
    
    if (!in_array($tableName, $allowedTables)) {
        echo json_encode(['success' => false, 'message' => 'Invalid table name']);
        exit;
    }
    
    // Validate that the work_schedule_id exists
    require_once '../config/db.php';
    
    $scheduleCheckStmt = $pdo->prepare("SELECT id, time_in, time_out FROM work_schedules WHERE id = ?");
    $scheduleCheckStmt->execute([$workScheduleId]);
    $schedule = $scheduleCheckStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        echo json_encode(['success' => false, 'message' => 'Invalid schedule ID']);
        exit;
    }
    
    try {
        // First, let's check what columns exist in the table
        $columnsQuery = "DESCRIBE `$tableName`";
        $columnsStmt = $pdo->prepare($columnsQuery);
        $columnsStmt->execute();
        $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
        error_log("Available columns in $tableName: " . json_encode($columns));
        
        // Verify that this request belongs to the logged-in user
        $verifyQuery = "SELECT employee_id, status FROM `$tableName` WHERE id = ? LIMIT 1";
        $verifyStmt = $pdo->prepare($verifyQuery);
        $verifyStmt->execute([$requestId]);
        $request = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'Request not found']);
            exit;
        }
        
        if ($request['employee_id'] != $employeeId) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized: Request does not belong to you']);
            exit;
        }
        
        // Only allow editing pending requests
        if (isset($request['status']) && strtolower($request['status']) !== 'pending') {
            echo json_encode(['success' => false, 'message' => 'Cannot edit non-pending requests']);
            exit;
        }
        
        // Build update query
        $updateFields = [];
        $updateValues = [];
        
        // Update the work_schedule_id (this is the field that stores the requested schedule)
        if (isset($data['work_schedule_id'])) {
            $updateFields[] = 'work_schedule_id = ?';
            $updateValues[] = (int)$data['work_schedule_id'];
        }
        
        // Handle date range if provided
        if ($newDateRange) {
            // Parse date range (expected format: "Oct 10, 2025 to Oct 10, 2025" or "2025-10-10 to 2025-10-10")
            if (strpos($newDateRange, ' to ') !== false) {
                list($startDateRaw, $endDateRaw) = explode(' to ', $newDateRange);
                
                // Convert to YYYY-MM-DD format for database
                $startDate = date('Y-m-d', strtotime(trim($startDateRaw)));
                $endDate = date('Y-m-d', strtotime(trim($endDateRaw)));
                
                if ($startDate && $startDate !== '1970-01-01' && $endDate && $endDate !== '1970-01-01') {
                    $updateFields[] = 'start_date = ?';
                    $updateValues[] = $startDate;
                    $updateFields[] = 'end_date = ?';
                    $updateValues[] = $endDate;
                } else {
                    error_log("Date conversion failed for: " . $newDateRange);
                }
            } else {
                // Single date
                $singleDate = date('Y-m-d', strtotime(trim($newDateRange)));
                if ($singleDate && $singleDate !== '1970-01-01') {
                    $updateFields[] = 'start_date = ?';
                    $updateValues[] = $singleDate;
                    $updateFields[] = 'end_date = ?';
                    $updateValues[] = $singleDate;
                } else {
                    error_log("Single date conversion failed for: " . $newDateRange);
                }
            }
        }
        
        // Note: updated_at column not available in this table
        
        // Add WHERE conditions
        $updateValues[] = $requestId;
        $updateValues[] = $employeeId;
        
        $updateQuery = "UPDATE `$tableName` SET " . implode(', ', $updateFields) . " WHERE id = ? AND employee_id = ? LIMIT 1";
        
        // Log the query for debugging
        error_log("Schedule Update Query: " . $updateQuery);
        error_log("Schedule Update Values: " . json_encode($updateValues));
        
        $updateStmt = $pdo->prepare($updateQuery);
        $result = $updateStmt->execute($updateValues);
        
        // Log the result
        error_log("Update result: " . ($result ? 'true' : 'false'));
        error_log("Rows affected: " . $updateStmt->rowCount());
        
        if ($result && $updateStmt->rowCount() > 0) {
            // Format schedule display for response
            $timeInDisplay = date("g:i A", strtotime($schedule['time_in']));
            $timeOutDisplay = date("g:i A", strtotime($schedule['time_out']));
            $scheduleDisplay = "$timeInDisplay - $timeOutDisplay";
            
            echo json_encode([
                'success' => true, 
                'message' => 'Schedule change updated successfully',
                'new_schedule_id' => $workScheduleId,
                'new_schedule_display' => $scheduleDisplay,
                'date_range' => $newDateRange,
                'affected_rows' => $updateStmt->rowCount(),
                'debug_info' => [
                    'query' => $updateQuery,
                    'values' => $updateValues,
                    'request_id' => $requestId,
                    'employee_id' => $employeeId
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No rows updated. Request may not exist or you may not have permission.']);
        }
        
    } catch (PDOException $e) {
        error_log("Database error in update_schedule_change.php: " . $e->getMessage());
        error_log("SQL Query: " . ($updateQuery ?? 'N/A'));
        error_log("Parameters: " . json_encode($updateValues ?? []));
        echo json_encode([
            'success' => false, 
            'message' => 'Database error occurred: ' . $e->getMessage(),
            'error_code' => $e->getCode(),
            'debug_info' => [
                'table' => $tableName ?? 'N/A',
                'request_id' => $requestId ?? 'N/A',
                'schedule' => $newSchedule ?? 'N/A'
            ]
        ]);
    }
    
} catch (Exception $e) {
    error_log("General error in update_schedule_change.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred']);
}
?>