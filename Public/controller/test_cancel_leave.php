<?php
// Simple test version for debugging
header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['employee']['id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit();
}

$employee_id = $_SESSION['employee']['id'];
require_once('../config/db.php');

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit();
    }
    
    $request_id = $data['id'] ?? null;
    
    if (!$request_id || !is_numeric($request_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
        exit();
    }
    
    // Just try to fetch the leave request
    $fetchStmt = $pdo->prepare("SELECT * FROM leave_requests WHERE id = ? AND employee_id = ?");
    $fetchStmt->execute([$request_id, $employee_id]);
    $leaveRequest = $fetchStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$leaveRequest) {
        echo json_encode(['success' => false, 'message' => 'Leave request not found']);
        exit();
    }
    
    // Check if post_leave_requests table exists
    $checkStmt = $pdo->prepare("SHOW TABLES LIKE 'post_leave_requests'");
    $checkStmt->execute();
    $tableExists = $checkStmt->fetch();
    
    if (!$tableExists) {
        echo json_encode(['success' => false, 'message' => 'post_leave_requests table does not exist']);
        exit();
    }
    
    // Return success with debug info
    echo json_encode([
        'success' => true, 
        'message' => 'Debug successful',
        'leave_request' => $leaveRequest,
        'table_exists' => true
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>