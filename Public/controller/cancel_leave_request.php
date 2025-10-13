<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

// Enable error logging for debugging
ini_set('log_errors', 1);
error_log("=== CANCEL LEAVE REQUEST DEBUG START ===");

// Check if user is logged in
if (!isset($_SESSION['employee']['id'])) {
    error_log("User not authenticated");
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['employee']['id'];
error_log("User ID: " . $user_id);

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
error_log("Raw input: " . file_get_contents('php://input'));
error_log("Parsed input: " . json_encode($input));

if (!isset($input['table']) || !isset($input['id'])) {
    error_log("Missing required parameters - table: " . ($input['table'] ?? 'missing') . ", id: " . ($input['id'] ?? 'missing'));
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$table = $input['table'];
$request_id = $input['id'];
error_log("Processing table: '$table', request_id: '$request_id'");

// Log the incoming request for debugging
error_log("Cancel leave request received - Table: '$table', ID: '$request_id', User: '$user_id'");

// Validate table name for leave requests only - be more flexible with naming
$allowed_tables = [
    'leave_requests',
    'post_leave_requests'
];

// Also allow if table name contains 'leave'
$is_leave_table = in_array($table, $allowed_tables) || strpos($table, 'leave') !== false;

if (!$is_leave_table) {
    error_log("Invalid table '$table' attempted for leave cancellation. Allowed tables: " . implode(', ', $allowed_tables) . " or any table containing 'leave'");
    echo json_encode(['success' => false, 'message' => "Invalid table for leave cancellation: $table"]);
    exit;
}

// Normalize table name - if it's a leave table but not in our standard list, default to post_leave_requests
if (!in_array($table, $allowed_tables)) {
    error_log("Non-standard leave table '$table' detected, defaulting to 'post_leave_requests'");
    $table = 'post_leave_requests';
}

try {
    // First, verify the request belongs to the user and is pending
    $check_sql = "SELECT status, leave_type, start_date, end_date FROM {$table} 
                  WHERE id = ? AND employee_id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$request_id, $user_id]);
    $request = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Log for debugging
    error_log("Cancel leave attempt - User: $user_id, Table: $table, ID: $request_id");
    if ($request) {
        error_log("Leave request found - Type: " . $request['leave_type'] . ", Status: " . $request['status'] . ", Dates: " . $request['start_date'] . " to " . $request['end_date']);
    } else {
        error_log("Leave request not found");
    }
    
    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Leave request not found or access denied']);
        exit;
    }
    
    if (strtolower($request['status']) !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Only pending leave requests can be cancelled. Current status: ' . $request['status']]);
        exit;
    }
    
    // Delete the leave request
    $delete_sql = "DELETE FROM {$table} WHERE id = ? AND employee_id = ?";
    $delete_stmt = $pdo->prepare($delete_sql);
    $result = $delete_stmt->execute([$request_id, $user_id]);
    
    // Check how many rows were affected
    $rows_affected = $delete_stmt->rowCount();
    error_log("Delete leave request result - Rows affected: $rows_affected");
    
    if ($result && $rows_affected > 0) {
        error_log("Successfully cancelled leave request ID $request_id from table $table");
        
        // Create a more descriptive success message
        $leave_type = ucfirst($request['leave_type']);
        if (strpos(strtolower($request['leave_type']), 'half') !== false) {
            $leave_type = "Half Day " . str_replace(['half day ', 'halfday '], '', $leave_type);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => $leave_type . ' request cancelled successfully',
            'leave_type' => $request['leave_type'],
            'dates' => $request['start_date'] . ($request['start_date'] !== $request['end_date'] ? ' to ' . $request['end_date'] : '')
        ]);
    } else if ($result && $rows_affected === 0) {
        error_log("No rows affected - leave request may have already been deleted");
        echo json_encode(['success' => false, 'message' => 'Leave request not found or already cancelled']);
    } else {
        error_log("Delete leave operation failed");
        echo json_encode(['success' => false, 'message' => 'Failed to cancel leave request']);
    }
    
} catch (PDOException $e) {
    error_log("Cancel leave request error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred while cancelling leave request']);
} catch (Exception $e) {
    error_log("Cancel leave request unexpected error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred while cancelling leave request']);
}

error_log("=== CANCEL LEAVE REQUEST DEBUG END ===");
?>
