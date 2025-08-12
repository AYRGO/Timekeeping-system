<?php
/**
 * Send Quick Notification Endpoint
 * Handles AJAX requests to send notifications to Quick
 */

require_once '../config/db.php';
require_once 'quick_notifications.php';

// Set content type for JSON response
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action !== 'send_reminder') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

try {
    // Get pending requests count
    $stmt = $pdo->query("
        SELECT COUNT(*) as pending_count
        FROM overtime_requests ot
        JOIN employees e ON ot.employee_id = e.id
        WHERE ot.status = 'pending' AND LOWER(e.company) = 'bugardi'
    ");
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $pendingCount = $result['pending_count'];
    
    if ($pendingCount > 0) {
        $notifier = new QuickNotificationSystem($pdo);
        $success = $notifier->sendWeeklyReminder();
        
        if ($success) {
            echo json_encode([
                'success' => true, 
                'message' => "Notification sent to Quick about $pendingCount pending request(s)"
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to send notification email'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'No pending requests to notify about'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Quick notification error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred while sending notification'
    ]);
}
?>
