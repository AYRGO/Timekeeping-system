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
    if (!isset($data['request_id']) || !isset($data['table_name']) || !isset($data['new_schedule'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $requestId = (int)$data['request_id'];
    $tableName = $data['table_name'];
    $newSchedule = $data['new_schedule'];
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
    
    // Connect to database
    require_once '../config/db.php';
    
    try {
        // First, verify that this request belongs to the logged-in user
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
        
        // Parse the new schedule to extract time_in and time_out
        // Expected format: "6:00 AM - 2:00 PM"
        if (preg_match('/(\d{1,2}):(\d{2}) (AM|PM) - (\d{1,2}):(\d{2}) (AM|PM)/', $newSchedule, $matches)) {
            // Convert times to 24-hour format
            $timeInHour = $matches[1];
            $timeInMinute = $matches[2];
            $timeInAMPM = $matches[3];
            
            $timeOutHour = $matches[4];
            $timeOutMinute = $matches[5];
            $timeOutAMPM = $matches[6];
            
            // Convert to 24-hour format
            if ($timeInAMPM === 'PM' && $timeInHour != 12) {
                $timeInHour += 12;
            } elseif ($timeInAMPM === 'AM' && $timeInHour == 12) {
                $timeInHour = 0;
            }
            
            if ($timeOutAMPM === 'PM' && $timeOutHour != 12) {
                $timeOutHour += 12;
            } elseif ($timeOutAMPM === 'AM' && $timeOutHour == 12) {
                $timeOutHour = 0;
            }
            
            $requestedTimeIn = sprintf('%02d:%02d:00', $timeInHour, $timeInMinute);
            $requestedTimeOut = sprintf('%02d:%02d:00', $timeOutHour, $timeOutMinute);
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid schedule format']);
            exit;
        }
        
        // Build update query
        $updateFields = [];
        $updateValues = [];
        
        $updateFields[] = 'requested_time_in = ?';
        $updateValues[] = $requestedTimeIn;
        
        $updateFields[] = 'requested_time_out = ?';
        $updateValues[] = $requestedTimeOut;
        
        // Handle date range if provided
        if ($newDateRange) {
            // Parse date range (expected format: YYYY-MM-DD to YYYY-MM-DD or single date)
            if (strpos($newDateRange, ' to ') !== false) {
                list($startDate, $endDate) = explode(' to ', $newDateRange);
                $updateFields[] = 'start_date = ?';
                $updateValues[] = $startDate;
                $updateFields[] = 'end_date = ?';
                $updateValues[] = $endDate;
            } else {
                // Single date
                $updateFields[] = 'start_date = ?';
                $updateValues[] = $newDateRange;
                $updateFields[] = 'end_date = ?';
                $updateValues[] = $newDateRange;
            }
        }
        
        // Note: updated_at column not available in this table
        
        // Add WHERE conditions
        $updateValues[] = $requestId;
        $updateValues[] = $employeeId;
        
        $updateQuery = "UPDATE `$tableName` SET " . implode(', ', $updateFields) . " WHERE id = ? AND employee_id = ? LIMIT 1";
        
        $updateStmt = $pdo->prepare($updateQuery);
        $result = $updateStmt->execute($updateValues);
        
        if ($result && $updateStmt->rowCount() > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Schedule change updated successfully',
                'new_schedule' => $newSchedule,
                'requested_time_in' => $requestedTimeIn,
                'requested_time_out' => $requestedTimeOut,
                'date_range' => $newDateRange,
                'affected_rows' => $updateStmt->rowCount()
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