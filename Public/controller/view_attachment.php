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

// Determine full path - check multiple possible locations
$possiblePaths = [
    '../uploads/' . $attachmentPath,  // Direct in uploads
    '../uploads/schedule_attachments/' . basename($attachmentPath),  // In schedule_attachments folder
    '../uploads/' . basename($attachmentPath)  // Just filename in uploads root
];

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