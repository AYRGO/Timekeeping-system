<?php
// Simple test file to verify AJAX connection
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Connection test successful',
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'path' => __FILE__
]);
?>