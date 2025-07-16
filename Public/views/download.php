<?php
// download.php
$baseDir = __DIR__ . '/uploads';

if (!isset($_GET['file']) || !isset($_GET['name'])) {
    http_response_code(400);
    exit('Missing file or name parameter.');
}

$stored = basename($_GET['file']); // Sanitize input
$original = $_GET['name'];

$fullPath = $baseDir . '/' . $stored;

if (!file_exists($fullPath)) {
    http_response_code(404);
    exit('File not found.');
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($original) . '"');
header('Content-Length: ' . filesize($fullPath));
readfile($fullPath);
exit;
