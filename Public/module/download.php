<?php
$filename = basename($_GET['file'] ?? '');
$filepath = __DIR__ . "/Public/views/" . $filename;

if (!file_exists($filepath)) {
    http_response_code(404);
    echo "❌ File not found.";
    exit;
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filepath));
readfile($filepath);
exit;
