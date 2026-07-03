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

// Normalize the requested path while preventing directory traversal.
$attachmentPath = trim(str_replace('\\', '/', $attachmentPath));
$attachmentPath = preg_replace('#(^|/)\.\.(?=/|$)#', '', $attachmentPath);
$attachmentPath = ltrim($attachmentPath, '/');
$fileNameOnly = basename($attachmentPath);

$uploadsRoot = realpath(__DIR__ . '/../uploads');
if (!$uploadsRoot || $fileNameOnly === '' || $fileNameOnly === '.' || $fileNameOnly === '..') {
    http_response_code(404);
    echo "Attachment file is missing or unavailable.";
    exit;
}

// Prefer the likely folder first, then fall back through all supported upload folders.
$candidateRelativePaths = [];
$addCandidate = function ($path) use (&$candidateRelativePaths) {
    $normalized = trim(str_replace('\\', '/', $path), '/');
    if ($normalized !== '' && !in_array($normalized, $candidateRelativePaths, true)) {
        $candidateRelativePaths[] = $normalized;
    }
};

if (preg_match('/^lr_/', $fileNameOnly)) {
    $addCandidate('leave_attachments/' . $fileNameOnly);
} elseif (preg_match('/^ot_/', $fileNameOnly)) {
    $addCandidate('overtime_attachments/' . $fileNameOnly);
} elseif (preg_match('/^attach_/', $fileNameOnly)) {
    $addCandidate('time_adjustment_attachments/' . $fileNameOnly);
    $addCandidate($fileNameOnly);
} elseif (preg_match('/^switch_/', $fileNameOnly)) {
    $addCandidate('schedule_switch/' . $fileNameOnly);
} elseif (preg_match('/^monthly_/', $fileNameOnly)) {
    $addCandidate('monthly_schedule/' . $fileNameOnly);
} else {
    $addCandidate('schedule_attachments/' . $fileNameOnly);
}

if (strpos($attachmentPath, 'uploads/') === 0) {
    $addCandidate(substr($attachmentPath, strlen('uploads/')));
} else {
    $addCandidate($attachmentPath);
}

$addCandidate($fileNameOnly);
$addCandidate('schedule_attachments/' . $fileNameOnly);
$addCandidate('schedule_requests/' . $fileNameOnly);
$addCandidate('schedule_switch/' . $fileNameOnly);
$addCandidate('monthly_schedule/' . $fileNameOnly);
$addCandidate('leave_attachments/' . $fileNameOnly);
$addCandidate('overtime_attachments/' . $fileNameOnly);
$addCandidate('time_adjustments/' . $fileNameOnly);
$addCandidate('time_adjustment_attachments/' . $fileNameOnly);

$fullPath = null;
foreach ($candidateRelativePaths as $relativePath) {
    $candidate = realpath($uploadsRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
    if ($candidate && strpos($candidate, $uploadsRoot . DIRECTORY_SEPARATOR) === 0 && is_file($candidate)) {
        $fullPath = $candidate;
        break;
    }
}

// Last resort: find the basename anywhere under Public/uploads for older records saved in a legacy folder.
if (!$fullPath) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uploadsRoot, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if ($file->isFile() && hash_equals($fileNameOnly, $file->getFilename())) {
            $candidate = $file->getRealPath();
            if ($candidate && strpos($candidate, $uploadsRoot . DIRECTORY_SEPARATOR) === 0) {
                $fullPath = $candidate;
                break;
            }
        }
    }
}

// Check if file exists
if (!$fullPath) {
    http_response_code(404);
    echo "Attachment file is missing or unavailable. It may have been deleted or not copied to this environment.";
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
