<?php
session_start();
include('config/db.php');

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$employee_id = $_SESSION['user_id'];
$leave_type = $_GET['type'] ?? '';

if (empty($leave_type)) {
    echo json_encode(['success' => false, 'message' => 'Leave type not specified']);
    exit;
}

try {
    // Fetch the leave balance for the specific employee and leave type
    $stmt = $pdo->prepare("SELECT balance FROM leave_credits WHERE employee_id = ? AND leave_type = ? AND year = ?");
    $stmt->execute([$employee_id, $leave_type, date('Y')]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'balance' => floatval($result['balance']),
            'leave_type' => $leave_type
        ]);
    } else {
        // If no record found, return 0 balance
        echo json_encode([
            'success' => true, 
            'balance' => 0,
            'leave_type' => $leave_type
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
