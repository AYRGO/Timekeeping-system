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

// Check if file was uploaded
if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

// Get form data
$request_id = $_POST['request_id'] ?? null;
$table_name = $_POST['table_name'] ?? null;
$attachment_column = $_POST['attachment_column'] ?? null;

if (!$request_id || !$table_name || !$attachment_column) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Validate file
$file = $_FILES['attachment'];
$allowed_types = [
    'application/pdf', 
    'image/jpeg', 
    'image/jpg',
    'image/png',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];

// Check file size (10MB limit)
if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File size too large. Maximum 10MB allowed.']);
    exit;
}

// Check file type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$file_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($file_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Unsupported file type']);
    exit;
}

try {
    // Determine upload directory based on table
    $upload_dirs = [
        'leave_requests' => '../uploads/leave_attachments/',
        'post_leave_requests' => '../uploads/leave_attachments/',
        'schedule_change_requests' => '../uploads/schedule_attachments/',
        'post_schedule_change_requests' => '../uploads/schedule_attachments/',
        'time_adjustment_requests' => '../uploads/',
        'post_time_adjustment_requests' => '../uploads/',
        'overtime_requests' => '../uploads/overtime_attachments/',
        'post_ot_requests' => '../uploads/overtime_attachments/',
        'new_ot_requests' => '../uploads/overtime_attachments/'
    ];
    
    $upload_dir = $upload_dirs[$table_name] ?? '../uploads/';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $prefixes = [
        'leave_requests' => 'lr_',
        'post_leave_requests' => 'lr_',
        'schedule_change_requests' => 'scr_',
        'post_schedule_change_requests' => 'scr_',
        'time_adjustment_requests' => 'attach_',
        'post_time_adjustment_requests' => 'attach_',
        'overtime_requests' => 'ot_',
        'post_ot_requests' => 'ot_',
        'new_ot_requests' => 'ot_'
    ];
    
    $prefix = $prefixes[$table_name] ?? 'file_';
    $new_filename = uniqid($prefix, true) . '.' . $ext;
    $target_path = $upload_dir . $new_filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
        exit;
    }
    
    // Update database record
    $sql = "UPDATE `{$table_name}` SET `{$attachment_column}` = :filename WHERE id = :request_id AND employee_id = :employee_id";
    $stmt = $pdo->prepare($sql);
    
    $result = $stmt->execute([
        'filename' => $new_filename,
        'request_id' => $request_id,
        'employee_id' => $employee_id
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Attachment uploaded successfully',
            'filename' => $new_filename
        ]);
    } else {
        // Delete uploaded file if database update failed
        unlink($target_path);
        echo json_encode(['success' => false, 'message' => 'Failed to update database record']);
    }
    
} catch (Exception $e) {
    // Clean up uploaded file if it exists
    if (isset($target_path) && file_exists($target_path)) {
        unlink($target_path);
    }
    
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>