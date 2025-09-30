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
        exit;
    }
    
    // Validate required fields
    if (!isset($data['request_id']) || !isset($data['table_name']) || !isset($data['field_name']) || !isset($data['new_value'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $requestId = (int)$data['request_id'];
    $tableName = $data['table_name'];
    $fieldName = $data['field_name'];
    $newValue = $data['new_value'];
    $employeeId = $_SESSION['employee']['id'];
    
    // Security: Validate table name and field name to prevent SQL injection
    $allowedTables = [
        'post_ot_requests',
        'ot_requests',
        'post_time_adjustment_requests',
        'time_adjustment_requests',
        'post_leave_requests',
        'leave_requests',
        'post_schedule_change_requests',
        'schedule_change_requests'
    ];
    
    $allowedFields = [
        'reason',
        'ot_reason',
        'explanation'
    ];
    
    if (!in_array($tableName, $allowedTables)) {
        echo json_encode(['success' => false, 'message' => 'Invalid table name']);
        exit;
    }
    
    if (!in_array($fieldName, $allowedFields)) {
        echo json_encode(['success' => false, 'message' => 'Invalid field name']);
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
        
        // Update the field
        $updateQuery = "UPDATE `$tableName` SET `$fieldName` = ? WHERE id = ? AND employee_id = ? LIMIT 1";
        $updateStmt = $pdo->prepare($updateQuery);
        $result = $updateStmt->execute([$newValue, $requestId, $employeeId]);
        
        if ($result && $updateStmt->rowCount() > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Field updated successfully',
                'updated_field' => $fieldName,
                'new_value' => $newValue,
                'affected_rows' => $updateStmt->rowCount()
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No rows updated. Request may not exist or you may not have permission.']);
        }
        
    } catch (PDOException $e) {
        error_log("Database error in update_request_field.php: " . $e->getMessage());
        error_log("SQL Query: " . ($updateQuery ?? 'N/A'));
        error_log("Parameters: " . json_encode([$newValue ?? 'N/A', $requestId ?? 'N/A', $employeeId ?? 'N/A']));
        echo json_encode([
            'success' => false, 
            'message' => 'Database error occurred: ' . $e->getMessage(),
            'error_code' => $e->getCode(),
            'debug_info' => [
                'table' => $tableName ?? 'N/A',
                'field' => $fieldName ?? 'N/A',
                'request_id' => $requestId ?? 'N/A'
            ]
        ]);
    }
    
} catch (Exception $e) {
    error_log("General error in update_request_field.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred']);
}
?>
