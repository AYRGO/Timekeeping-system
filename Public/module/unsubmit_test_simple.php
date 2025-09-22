<?php
session_start();
header('Content-Type: application/json');

// Simple test response
error_log("Unsubmit test called - POST data: " . print_r($_POST, true));
error_log("Unsubmit test called - Session: " . print_r($_SESSION, true));

echo json_encode([
    'success' => true, 
    'message' => 'Test response working',
    'received_data' => $_POST,
    'session_user' => $_SESSION['user_id'] ?? 'not set'
]);
?>