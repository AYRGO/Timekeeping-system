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
    if (!isset($data['request_id']) || !isset($data['table_name']) || !isset($data['requested_time_in']) || !isset($data['requested_time_out'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $requestId = (int)$data['request_id'];
    $tableName = $data['table_name'];
    $requestedTimeIn = $data['requested_time_in'];
    $requestedTimeOut = $data['requested_time_out'];
    $employeeId = $_SESSION['employee']['id'];
    
    // Security: Validate table name to prevent SQL injection
    $allowedTables = [
        'time_adjustment_requests',
        'post_time_adjustment_requests'
    ];
    
    if (!in_array($tableName, $allowedTables)) {
        echo json_encode(['success' => false, 'message' => 'Invalid table name']);
        exit;
    }
    
    // Convert 12-hour format to 24-hour format for database storage
    function convertTo24Hour($time12h) {
        if (empty($time12h)) return null;
        
        $time12h = trim($time12h);
        if (preg_match('/(\d{1,2}):(\d{2}) (AM|PM)/i', $time12h, $matches)) {
            $hour = (int)$matches[1];
            $minute = $matches[2];
            $ampm = strtoupper($matches[3]);
            
            if ($ampm === 'PM' && $hour != 12) {
                $hour += 12;
            } elseif ($ampm === 'AM' && $hour == 12) {
                $hour = 0;
            }
            
            return sprintf('%02d:%02d:00', $hour, $minute);
        }
        
        return null;
    }
    
    $requestedTimeIn24 = convertTo24Hour($requestedTimeIn);
    $requestedTimeOut24 = convertTo24Hour($requestedTimeOut);
    
    if (!$requestedTimeIn24 || !$requestedTimeOut24) {
        echo json_encode(['success' => false, 'message' => 'Invalid time format']);
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
        
        // Update the time adjustment (no updated_at column available)
        $updateQuery = "UPDATE `$tableName` SET requested_time_in = ?, requested_time_out = ? WHERE id = ? AND employee_id = ? LIMIT 1";
        
        $updateStmt = $pdo->prepare($updateQuery);
        $result = $updateStmt->execute([$requestedTimeIn24, $requestedTimeOut24, $requestId, $employeeId]);
        
        if ($result && $updateStmt->rowCount() > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Time adjustment updated successfully',
                'requested_time_in' => $requestedTimeIn,
                'requested_time_out' => $requestedTimeOut,
                'requested_time_in_24h' => $requestedTimeIn24,
                'requested_time_out_24h' => $requestedTimeOut24,
                'affected_rows' => $updateStmt->rowCount()
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No rows updated. Request may not exist or you may not have permission.']);
        }
        
    } catch (PDOException $e) {
        error_log("Database error in update_time_adjustment.php: " . $e->getMessage());
        error_log("SQL Query: " . ($updateQuery ?? 'N/A'));
        error_log("Parameters: " . json_encode([$requestedTimeIn24 ?? 'N/A', $requestedTimeOut24 ?? 'N/A', $requestId ?? 'N/A', $employeeId ?? 'N/A']));
        echo json_encode([
            'success' => false, 
            'message' => 'Database error occurred: ' . $e->getMessage(),
            'error_code' => $e->getCode(),
            'debug_info' => [
                'table' => $tableName ?? 'N/A',
                'request_id' => $requestId ?? 'N/A',
                'time_in' => $requestedTimeIn ?? 'N/A',
                'time_out' => $requestedTimeOut ?? 'N/A'
            ]
        ]);
    }
    
} catch (Exception $e) {
    error_log("General error in update_time_adjustment.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred']);
}
?>