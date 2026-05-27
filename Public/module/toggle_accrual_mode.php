<?php
// File: toggle_accrual_mode.php
// Toggle between testing and production accrual modes
include('../config/db.php');
require_once __DIR__ . '/../config/demo_guard.php';
demo_block_mutation('Accrual mode settings are disabled in demo mode.');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$mode = $input['mode'] ?? '';

if (!in_array($mode, ['testing', 'production'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid mode. Must be "testing" or "production"']);
    exit;
}

try {
    // Create or update system settings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS system_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(255) UNIQUE NOT NULL,
            setting_value TEXT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // Update the accrual mode setting
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_key, setting_value) 
        VALUES ('accrual_mode', ?) 
        ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
    ");
    $stmt->execute([$mode, $mode]);

    // Calculate next accrual date based on mode
    $nextAccrualDate = '';
    $daysUntil = 0;
    
    if ($mode === 'production') {
        // Calculate end of month
        $currentDate = new DateTime();
        $lastDayOfMonth = new DateTime($currentDate->format('Y-m-t'));
        $daysUntil = $currentDate->diff($lastDayOfMonth)->days;
        $nextAccrualDate = $lastDayOfMonth->format('F j, Y');
    } else {
        // Testing mode - next cycle in 10 seconds
        $nextAccrualTime = new DateTime();
        $nextAccrualTime->add(new DateInterval('PT10S'));
        $daysUntil = 10; // seconds
        $nextAccrualDate = $nextAccrualTime->format('H:i:s');
    }

    echo json_encode([
        'success' => true,
        'mode' => $mode,
        'next_accrual_date' => $nextAccrualDate,
        'time_until' => $daysUntil,
        'message' => ucfirst($mode) . ' mode activated successfully!'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
