<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['employee']['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Database connection
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('No data provided');
    }
    
    $requestId = $input['request_id'] ?? null;
    $tableName = $input['table_name'] ?? null;
    $attachmentPath = $input['attachment_path'] ?? null;
    
    // Validate required fields
    if (!$requestId || !$tableName || !$attachmentPath) {
        throw new Exception('Missing required fields: request_id, table_name, or attachment_path');
    }
    
    // Validate table name for security
    $allowedTables = ['schedule_change_requests'];
    if (!in_array($tableName, $allowedTables)) {
        throw new Exception('Invalid table name');
    }
    
    // Verify the request belongs to the current user
    $stmt = $pdo->prepare("SELECT employee_id, attachment_scr FROM {$tableName} WHERE id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        throw new Exception('Request not found');
    }
    
    if ($request['employee_id'] != $_SESSION['employee']['id']) {
        throw new Exception('Access denied: This request does not belong to you');
    }
    
    // Verify the attachment matches
    if ($request['attachment_scr'] !== $attachmentPath) {
        throw new Exception('Attachment path mismatch');
    }
    
    // Delete the attachment file from filesystem
    // Try multiple possible paths
    $possiblePaths = [
        '../uploads/' . ltrim($attachmentPath, '/'),
        '../uploads/schedule_attachments/' . basename($attachmentPath),
        '../uploads/' . basename($attachmentPath)
    ];
    
    foreach ($possiblePaths as $fullPath) {
        if (file_exists($fullPath)) {
            if (!unlink($fullPath)) {
                error_log("Failed to delete file: {$fullPath}");
            } else {
                error_log("Successfully deleted file: {$fullPath}");
            }
            break;
        }
    }
    
    // Update database to remove attachment reference
    $stmt = $pdo->prepare("UPDATE {$tableName} SET attachment_scr = NULL WHERE id = ?");
    $success = $stmt->execute([$requestId]);
    
    if (!$success) {
        throw new Exception('Failed to update database');
    }
    
    // Log the activity
    $stmt = $pdo->prepare("INSERT INTO notifications (employee_id, message, type, created_at, is_read) VALUES (?, ?, ?, NOW(), 0)");
    $message = "Attachment deleted from schedule change request #{$requestId}";
    $stmt->execute([$_SESSION['employee']['id'], $message, 'system']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Attachment deleted successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Delete attachment error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Database error in delete attachment: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>