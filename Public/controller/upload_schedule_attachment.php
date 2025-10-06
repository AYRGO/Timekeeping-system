<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header early
header('Content-Type: application/json');

// Log the upload attempt
error_log('Upload schedule attachment started - Session: ' . print_r($_SESSION, true));
error_log('POST data: ' . print_r($_POST, true));
error_log('FILES data: ' . print_r($_FILES, true));

// Check if user is logged in
if (!isset($_SESSION['employee']['id'])) {
    error_log('Upload failed: User not logged in');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Database connection
require_once '../config/db.php';

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
    error_log("About to update database - Table: {$tableName}, ID: {$requestId}, Path: {$relativePath}");
    
    $stmt = $pdo->prepare("UPDATE {$tableName} SET attachment_scr = ? WHERE id = ?");
    $success = $stmt->execute([$relativePath, $requestId]);
    
    error_log("Database update result: " . ($success ? 'SUCCESS' : 'FAILED'));
    error_log("Rows affected: " . $stmt->rowCount());
    
    if (!$success) {
        // Clean up uploaded file if database update fails
        unlink($filePath);
        throw new Exception('Failed to update database');
    }
    
    if ($stmt->rowCount() === 0) {
        error_log("Warning: No rows were updated. Request ID might not exist or conditions not met.");
    }
    
    // Log the activity
    $stmt = $pdo->prepare("INSERT INTO notifications (employee_id, message, type, created_at, is_read) VALUES (?, ?, ?, NOW(), 0)");
    $message = "New attachment uploaded for schedule change request #{$requestId}";
    $stmt->execute([$_SESSION['employee']['id'], $message, 'system']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Attachment uploaded successfully',
        'filename' => $filename,
        'new_filename' => $filename,  // Also include for compatibility
        'file_path' => $relativePath
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