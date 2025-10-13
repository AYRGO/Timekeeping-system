<?php
// File: live_leave_credits_ajax.php
// AJAX endpoint for real-time leave credits updates
header('Content-Type: application/json');
include('../config/db.php');

$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'User ID required']);
    exit;
}

try {
    // Fetch current leave credits
    $stmt = $pdo->prepare("
        SELECT leave_type, balance, updated_at 
        FROM leave_credits 
        WHERE employee_id = ? AND year = ? AND leave_type IN ('sick', 'vacation')
        ORDER BY leave_type
    ");
    $stmt->execute([$user_id, date('Y')]);
    $credits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get employee name for logging
    $nameStmt = $pdo->prepare("SELECT CONCAT(fname, ' ', lname) as full_name FROM employees WHERE id = ?");
    $nameStmt->execute([$user_id]);
    $employeeName = $nameStmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'credits' => $credits,
        'employee_name' => $employeeName,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>