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
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Get form data
    $requestId = $_POST['request_id'] ?? null;
    $tableName = $_POST['table_name'] ?? null;
    
    // Validate required fields
    if (!$requestId || !$tableName) {
        throw new Exception('Missing required fields: request_id or table_name');
    }
    
    // Validate table name for security
    $allowedTables = ['schedule_change_requests'];
    if (!in_array($tableName, $allowedTables)) {
        throw new Exception('Invalid table name');
    }
    
    // Verify the request belongs to the current user and is pending
    $stmt = $pdo->prepare("SELECT employee_id, status FROM {$tableName} WHERE id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        throw new Exception('Request not found');
    }
    
    if ($request['employee_id'] != $_SESSION['employee']['id']) {
        throw new Exception('Access denied: This request does not belong to you');
    }
    
    if (strtolower($request['status']) !== 'pending') {
        throw new Exception('Can only modify pending requests');
    }
    
    // Validate file upload
    if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = 'No file uploaded';
        if (isset($_FILES['attachment']['error'])) {
            switch ($_FILES['attachment']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errorMsg = 'File is too large';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorMsg = 'File upload was interrupted';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $errorMsg = 'Temporary directory not found';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $errorMsg = 'Failed to write file';
                    break;
            }
        }
        throw new Exception($errorMsg);
    }
    
    $uploadedFile = $_FILES['attachment'];
    
    // Validate file size (10MB limit)
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($uploadedFile['size'] > $maxSize) {
        throw new Exception('File size must be less than 10MB');
    }
    
    // Validate file type
    $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
    $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, $allowedTypes)) {
        throw new Exception('Invalid file type. Allowed: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX');
    }
    
    // Validate MIME type for additional security
    $allowedMimes = [
        'application/pdf',
        'image/jpeg',
        'image/jpg', 
        'image/png',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
    
    if (!in_array($uploadedFile['type'], $allowedMimes)) {
        throw new Exception('Invalid file MIME type');
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = '../uploads/schedule_attachments/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }
    
    // Generate unique filename
    $timestamp = date('YmdHis');
    $randomString = bin2hex(random_bytes(8));
    $filename = "scr_{$timestamp}_{$randomString}.{$fileExtension}";
    $filePath = $uploadDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
        throw new Exception('Failed to save uploaded file');
    }
    
    // Update database with new attachment path (store relative path)
    $relativePath = 'schedule_attachments/' . $filename;
    $stmt = $pdo->prepare("UPDATE {$tableName} SET attachment_scr = ? WHERE id = ?");
    $success = $stmt->execute([$relativePath, $requestId]);
    
    if (!$success) {
        // Clean up uploaded file if database update fails
        unlink($filePath);
        throw new Exception('Failed to update database');
    }
    
    // Log the activity
    $stmt = $pdo->prepare("INSERT INTO notifications (employee_id, message, type, created_at, is_read) VALUES (?, ?, ?, NOW(), 0)");
    $message = "New attachment uploaded for schedule change request #{$requestId}";
    $stmt->execute([$_SESSION['employee']['id'], $message, 'system']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Attachment uploaded successfully',
        'filename' => $filename
    ]);
    
} catch (Exception $e) {
    error_log("Upload attachment error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Database error in upload attachment: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>