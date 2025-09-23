<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['employee']['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['employee']['id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['table']) || !isset($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$table = $input['table'];
$request_id = $input['id'];

// Log the incoming request for debugging
error_log("Unsubmit request received - Table: '$table', ID: '$request_id', User: '$user_id'");

// Validate table name to prevent SQL injection
$allowed_tables = [
    'leave_requests',
    'post_leave_requests',
    'post_ot_requests', 
    'overtime_requests',
    'schedule_change_requests',
    'post_schedule_change_requests',
    'time_adjustment_requests',
    'post_time_adjustment_requests'
];

if (!in_array($table, $allowed_tables)) {
    error_log("Invalid table '$table' attempted. Allowed tables: " . implode(', ', $allowed_tables));
    echo json_encode(['success' => false, 'message' => "Invalid table: $table"]);
    exit;
}

try {
    // Define table-specific columns and conditions
    $table_config = [
        'leave_requests' => ['id_column' => 'id', 'user_column' => 'employee_id', 'status_column' => 'status'],
        'post_leave_requests' => ['id_column' => 'id', 'user_column' => 'employee_id', 'status_column' => 'status'],
        'post_ot_requests' => ['id_column' => 'id', 'user_column' => 'employee_id', 'status_column' => 'status'],
        'overtime_requests' => ['id_column' => 'id', 'user_column' => 'employee_id', 'status_column' => 'status'],
        'schedule_change_requests' => ['id_column' => 'id', 'user_column' => 'employee_id', 'status_column' => 'status'],
        'post_schedule_change_requests' => ['id_column' => 'id', 'user_column' => 'employee_id', 'status_column' => 'status'],
        'time_adjustment_requests' => ['id_column' => 'id', 'user_column' => 'employee_id', 'status_column' => 'status'],
        'post_time_adjustment_requests' => ['id_column' => 'id', 'user_column' => 'employee_id', 'status_column' => 'status']
    ];
    
    $config = $table_config[$table];
    
    // Log the table configuration being used
    error_log("Using table: $table with config: " . json_encode($config));
    
    // First, verify the request belongs to the user and is pending
    $check_sql = "SELECT {$config['status_column']} FROM {$table} 
                  WHERE {$config['id_column']} = ? AND {$config['user_column']} = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$request_id, $user_id]);
    $request = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Log for debugging
    error_log("Unsubmit attempt - User: $user_id, Table: $table, ID: $request_id");
    if ($request) {
        error_log("Request found with status: " . $request[$config['status_column']]);
    } else {
        error_log("Request not found");
    }
    
    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Request not found or access denied']);
        exit;
    }
    
    if (strtolower($request[$config['status_column']]) !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Only pending requests can be unsubmitted. Current status: ' . $request[$config['status_column']]]);
        exit;
    }
    
    // Delete the request
    $delete_sql = "DELETE FROM {$table} WHERE {$config['id_column']} = ? AND {$config['user_column']} = ?";
    $delete_stmt = $pdo->prepare($delete_sql);
    $result = $delete_stmt->execute([$request_id, $user_id]);
    
    // Check how many rows were affected
    $rows_affected = $delete_stmt->rowCount();
    error_log("Delete result - Rows affected: $rows_affected");
    
    if ($result && $rows_affected > 0) {
        error_log("Successfully deleted request ID $request_id from table $table");
        echo json_encode(['success' => true, 'message' => 'Request successfully unsubmitted']);
    } else if ($result && $rows_affected === 0) {
        error_log("No rows affected - request may have already been deleted");
        echo json_encode(['success' => false, 'message' => 'Request not found or already deleted']);
    } else {
        error_log("Delete operation failed");
        echo json_encode(['success' => false, 'message' => 'Failed to unsubmit request']);
    }
    
} catch (PDOException $e) {
    error_log("Unsubmit request error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Unsubmit request unexpected error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred']);
}
?>
