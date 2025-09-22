<?php
session_start();
require_once '../../../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['table']) || !isset($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$table = $input['table'];
$request_id = $input['id'];

// Validate table name to prevent SQL injection
$allowed_tables = [
    'leave_requests',
    'post_ot_requests', 
    'schedule_change_requests',
    'time_adjustments_requests'
];

if (!in_array($table, $allowed_tables)) {
    echo json_encode(['success' => false, 'message' => 'Invalid table']);
    exit;
}

try {
    // Define table-specific columns and conditions
    $table_config = [
        'leave_requests' => ['id_column' => 'id', 'user_column' => 'emp_id', 'status_column' => 'status'],
        'post_ot_requests' => ['id_column' => 'id', 'user_column' => 'emp_id', 'status_column' => 'status'],
        'schedule_change_requests' => ['id_column' => 'id', 'user_column' => 'emp_id', 'status_column' => 'status'],
        'time_adjustments_requests' => ['id_column' => 'id', 'user_column' => 'emp_id', 'status_column' => 'status']
    ];
    
    $config = $table_config[$table];
    
    // First, verify the request belongs to the user and is pending
    $check_sql = "SELECT {$config['status_column']} FROM {$table} 
                  WHERE {$config['id_column']} = ? AND {$config['user_column']} = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$request_id, $user_id]);
    $request = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Request not found or access denied']);
        exit;
    }
    
    if (strtolower($request[$config['status_column']]) !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Only pending requests can be unsubmitted']);
        exit;
    }
    
    // Delete the request
    $delete_sql = "DELETE FROM {$table} WHERE {$config['id_column']} = ? AND {$config['user_column']} = ?";
    $delete_stmt = $pdo->prepare($delete_sql);
    $result = $delete_stmt->execute([$request_id, $user_id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Request successfully unsubmitted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to unsubmit request']);
    }
    
} catch (PDOException $e) {
    error_log("Unsubmit request error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>