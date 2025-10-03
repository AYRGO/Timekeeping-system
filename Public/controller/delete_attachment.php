<?php
session_start();
include('../config/db.php');

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['employee']['id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$employee_id = $_SESSION['employee']['id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$request_id = $input['request_id'] ?? null;
$table_name = $input['table_name'] ?? null;
$attachment_column = $input['attachment_column'] ?? null;

if (!$request_id || !$table_name || !$attachment_column) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    // First, get the current attachment filename
    $sql = "SELECT `{$attachment_column}` FROM `{$table_name}` WHERE id = :request_id AND employee_id = :employee_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'request_id' => $request_id,
        'employee_id' => $employee_id
    ]);
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Request not found or access denied']);
        exit;
    }
    
    $current_filename = $row[$attachment_column];
    
    if (!$current_filename) {
        echo json_encode(['success' => false, 'message' => 'No attachment to delete']);
        exit;
    }
    
    // Determine file path based on table
    $upload_dirs = [
        'leave_requests' => '../uploads/leave_attachments/',
        'post_leave_requests' => '../uploads/leave_attachments/',
        'schedule_change_requests' => '../uploads/schedule_attachments/',
        'post_schedule_change_requests' => '../uploads/schedule_attachments/',
        'time_adjustment_requests' => '../uploads/time_adjustments/',
        'post_time_adjustment_requests' => '../uploads/time_adjustments/',
        'overtime_requests' => '../uploads/overtime_attachments/',
        'post_ot_requests' => '../uploads/overtime_attachments/',
        'new_ot_requests' => '../uploads/overtime_attachments/'
    ];
    
    $upload_dir = $upload_dirs[$table_name] ?? '../uploads/';
    $file_path = $upload_dir . $current_filename;
    
    // Update database to remove attachment reference
    $update_sql = "UPDATE `{$table_name}` SET `{$attachment_column}` = NULL WHERE id = :request_id AND employee_id = :employee_id";
    $update_stmt = $pdo->prepare($update_sql);
    
    $result = $update_stmt->execute([
        'request_id' => $request_id,
        'employee_id' => $employee_id
    ]);
    
    if ($result) {
        // Delete physical file if it exists
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Attachment deleted successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update database record']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>