<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['employee']['id'])) {
    header('Location: ../login.php');
    exit;
}

// Get the attachment path from URL parameter
$attachmentPath = $_GET['file'] ?? '';

if (empty($attachmentPath)) {
    http_response_code(404);
    echo "File not found.";
    exit;
}

// Security: Sanitize the file path to prevent directory traversal
// Allow schedule_attachments subdirectory but prevent traversal attacks
$attachmentPath = str_replace(['../', '..\\'], '', $attachmentPath);

// Determine full path - check multiple possible locations based on file naming pattern
$possiblePaths = [];

// Check if it's a leave attachment (starts with lr_)
if (preg_match('/^lr_/', basename($attachmentPath))) {
    $possiblePaths[] = '../uploads/leave_attachments/' . basename($attachmentPath);
    $possiblePaths[] = '../uploads/' . $attachmentPath;  // In case path includes directory
}
// Check if it's an overtime attachment (starts with ot_)
elseif (preg_match('/^ot_/', basename($attachmentPath))) {
    $possiblePaths[] = '../uploads/overtime_attachments/' . basename($attachmentPath);
    $possiblePaths[] = '../uploads/' . $attachmentPath;
}
// Check if it's a time adjustment attachment (starts with attach_)
elseif (preg_match('/^attach_/', basename($attachmentPath)) || preg_match('/uploads\/attach_/', $attachmentPath)) {
    $possiblePaths[] = '../' . $attachmentPath;  // For paths like uploads/attach_680e21cd91505.JPG
    $possiblePaths[] = '../uploads/' . basename($attachmentPath);  // Just filename in uploads root
    $possiblePaths[] = '../uploads/time_adjustment_attachments/' . basename($attachmentPath);
}
// Check if it's a schedule switch attachment (starts with switch_)
elseif (preg_match('/^switch_/', basename($attachmentPath)) || preg_match('/schedule_switch/', $attachmentPath)) {
    $possiblePaths[] = '../uploads/schedule_switch/' . basename($attachmentPath);
    $possiblePaths[] = '../' . $attachmentPath;  // For paths like uploads/schedule_switch/switch_...
    $possiblePaths[] = '../uploads/' . basename($attachmentPath);  // Just filename in uploads root
}
// Check if it's a monthly schedule attachment (starts with monthly_)
elseif (preg_match('/^monthly_/', basename($attachmentPath)) || preg_match('/monthly_schedule/', $attachmentPath)) {
    $possiblePaths[] = '../uploads/monthly_schedule/' . basename($attachmentPath);
    $possiblePaths[] = '../' . $attachmentPath;  // For paths like uploads/monthly_schedule/monthly_...
    $possiblePaths[] = '../uploads/' . basename($attachmentPath);  // Just filename in uploads root
}
// Schedule attachments or default
else {
    $possiblePaths[] = '../uploads/schedule_attachments/' . basename($attachmentPath);
    $possiblePaths[] = '../uploads/' . $attachmentPath;  // Direct in uploads
    $possiblePaths[] = '../uploads/' . basename($attachmentPath);  // Just filename in uploads root
}

$fullPath = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $fullPath = $path;
        break;
    }
}

// Check if file exists
if (!$fullPath) {
    http_response_code(404);
    echo "File not found. Searched paths: " . implode(', ', $possiblePaths);
    exit;
}

// Get file info
$fileExtension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$fileName = pathinfo($fullPath, PATHINFO_BASENAME);

// Set appropriate content type based on file extension
$contentTypes = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
];

$contentType = $contentTypes[$fileExtension] ?? 'application/octet-stream';

// Set headers
header('Content-Type: ' . $contentType);
header('Content-Length: ' . filesize($fullPath));
header('Content-Disposition: inline; filename="' . $fileName . '"');

// For images and PDFs, display inline. For others, force download
if (!in_array($fileExtension, ['pdf', 'jpg', 'jpeg', 'png', 'gif'])) {
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
}

// Output the file
readfile($fullPath);
exit;
?>