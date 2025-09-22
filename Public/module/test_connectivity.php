<?php
session_start();
header('Content-Type: application/json');

// Basic connectivity test
try {
    // Test if session works
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'No user session found', 'debug' => 'session check failed']);
        exit;
    }
    
    // Test if database config can be loaded
    require_once '../config/db.php';
    
    // Test basic query
    $stmt = $pdo->prepare("SELECT 1 as test");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'All tests passed',
            'user_id' => $_SESSION['user_id'],
            'db_test' => $result['test']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database query failed']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>