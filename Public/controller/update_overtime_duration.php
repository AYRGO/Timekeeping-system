<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
try {
    include('../config/db.php');
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['employee']['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not authenticated. Session data: ' . json_encode($_SESSION)
    ]);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON data'
    ]);
    exit;
}

$request_id = $input['request_id'] ?? '';
$table_name = $input['table_name'] ?? '';
$ot_duration = $input['ot_duration'] ?? '';
$employee_id = $_SESSION['employee']['id'];

// Validate inputs
if (empty($request_id) || empty($table_name) || empty($ot_duration)) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
    exit;
}

// Validate duration is numeric and positive
if (!is_numeric($ot_duration) || floatval($ot_duration) <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid duration value'
    ]);
    exit;
}

try {
    // Log received data for debugging
    error_log("Update OT Duration - Received data: " . json_encode($input));
    error_log("Employee ID from session: " . $employee_id);
    
    // Determine the correct table based on table_name
    $update_table = '';
    if (strpos($table_name, 'post_') === 0) {
        $update_table = $table_name; // Already has post_ prefix
    } else {
        $update_table = 'post_' . $table_name; // Add post_ prefix
    }
    
    error_log("Determined table name: " . $update_table);
    
    // Make sure we're updating overtime requests table
    if (!in_array($update_table, ['post_ot_requests', 'ot_requests'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid table for overtime update. Table: ' . $update_table
        ]);
        exit;
    }
    
    // Check if the request belongs to the current user and is pending
    $check_sql = "SELECT id, employee_id, status, ot_duration FROM {$update_table} WHERE id = ? AND employee_id = ?";
    error_log("Check SQL: " . $check_sql);
    error_log("Check params: Request ID = " . $request_id . ", Employee ID = " . $employee_id);
    
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$request_id, $employee_id]);
    $existing_request = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Existing request found: " . json_encode($existing_request));
    
    if (!$existing_request) {
        echo json_encode([
            'success' => false,
            'message' => 'Request not found or access denied'
        ]);
        exit;
    }
    
    // Only allow updates for pending requests
    if (strtolower($existing_request['status']) !== 'pending') {
        echo json_encode([
            'success' => false,
            'message' => 'Only pending requests can be edited'
        ]);
        exit;
    }
    
    // Check if updated_at column exists before using it
    $column_check_sql = "SHOW COLUMNS FROM {$update_table} LIKE 'updated_at'";
    $column_stmt = $pdo->prepare($column_check_sql);
    $column_stmt->execute();
    $has_updated_at = $column_stmt->rowCount() > 0;
    
    // Update the overtime duration
    if ($has_updated_at) {
        $update_sql = "UPDATE {$update_table} SET ot_duration = ?, updated_at = NOW() WHERE id = ? AND employee_id = ?";
    } else {
        $update_sql = "UPDATE {$update_table} SET ot_duration = ? WHERE id = ? AND employee_id = ?";
    }
    
    error_log("Update SQL: " . $update_sql);
    error_log("Update params: Duration = " . floatval($ot_duration) . ", Request ID = " . $request_id . ", Employee ID = " . $employee_id);
    
    $update_stmt = $pdo->prepare($update_sql);
    $result = $update_stmt->execute([floatval($ot_duration), $request_id, $employee_id]);
    
    error_log("Update result: " . ($result ? 'true' : 'false'));
    error_log("Rows affected: " . $update_stmt->rowCount());
    
    if ($result && $update_stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Overtime duration updated successfully',
            'new_duration' => number_format(floatval($ot_duration), 2)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update overtime duration. Rows affected: ' . $update_stmt->rowCount()
        ]);
    }
    
} catch (Exception $e) {
    error_log("Update overtime duration error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred: ' . $e->getMessage()
    ]);
}
?>