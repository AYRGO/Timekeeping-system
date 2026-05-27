<?php
session_start();
include('../config/db.php');
require_once __DIR__ . '/../config/demo_guard.php';
demo_block_mutation('Auto accrual settings are disabled in demo mode.');

// Check if user is logged in and is admin
if (!isset($_SESSION['employee']) || $_SESSION['employee']['role'] !== 'internal') {
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$enable = $input['enable'] ?? null;

if ($enable === null) {
    // Legacy support for POST action parameter
    $action = $_POST['action'] ?? '';
    
    if ($action === 'enable') {
        $enable = true;
    } elseif ($action === 'disable') {
        $enable = false;
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid request format'
        ]);
        exit;
    }
}

try {
    if ($enable === true) {
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
            'status' => 'enabled',
            'enabled' => true
        ]);
        
    } elseif ($enable === false) {
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
            'status' => 'disabled',
            'enabled' => false
        ]);
        
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid enable value'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
