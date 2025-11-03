<?php
session_start();
include('../config/db.php');

// Check if user is logged in
if (!isset($_SESSION['employee'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'enable') {
    // Enable auto-accrual
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_key, setting_value, updated_at) 
        VALUES ('auto_accrual_enabled', '1', NOW())
        ON DUPLICATE KEY UPDATE setting_value = '1', updated_at = NOW()
    ");
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Auto-accrual enabled successfully',
        'status' => 'enabled'
    ]);
    
} elseif ($action === 'disable') {
    // Disable auto-accrual
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_key, setting_value, updated_at) 
        VALUES ('auto_accrual_enabled', '0', NOW())
        ON DUPLICATE KEY UPDATE setting_value = '0', updated_at = NOW()
    ");
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Auto-accrual disabled successfully',
        'status' => 'disabled'
    ]);
    
} elseif ($action === 'status') {
    // Get current status
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'auto_accrual_enabled'");
    $stmt->execute();
    $status = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'enabled' => ($status === '1' || $status === 1),
        'status' => ($status === '1' || $status === 1) ? 'enabled' : 'disabled'
    ]);
    
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action'
    ]);
}
?>
