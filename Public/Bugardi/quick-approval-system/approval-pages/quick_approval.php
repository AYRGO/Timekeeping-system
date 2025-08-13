<?php
/**
 * Quick Approval Handler for Quick's Email Links
 * Handles one-click approve/reject from email notifications
 */

require_once '../config/db.php';

// Verify token and process action
$requestId = $_GET['id'] ?? 0;
$action = $_GET['action'] ?? '';
$token = $_GET['token'] ?? '';

// Validate inputs
if (!$requestId || !in_array($action, ['approve', 'reject']) || !$token) {
    showError('Invalid request parameters.');
    exit;
}

// Verify token
$secret = 'quick-ot-quick-approval-2025';
$expectedToken = hash('sha256', $requestId . $action . $secret . date('Y-m-d'));

if (!hash_equals($expectedToken, $token)) {
    showError('Invalid or expired approval link.');
    exit;
}

// Get request details
$stmt = $pdo->prepare("
    SELECT 
        ot.id, ot.status, ot.date, ot.reason, ot.duration_hours,
        CONCAT(e.fname, ' ', e.lname) as employee_name,
        e.company
    FROM overtime_requests ot
    JOIN employees e ON ot.employee_id = e.id
    WHERE ot.id = ? AND LOWER(e.company) = 'bugardi'
");

$stmt->execute([$requestId]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    showError('Request not found or not accessible.');
    exit;
}

if (strtolower($request['status']) !== 'pending') {
    showError('This request has already been processed. Current status: ' . $request['status']);
    exit;
}

// Process the action
$newStatus = $action === 'approve' ? 'Approved' : 'Rejected';

$updateStmt = $pdo->prepare("
    UPDATE overtime_requests 
    SET status = ? 
    WHERE id = ? AND status = 'Pending'
");

$success = $updateStmt->execute([$newStatus, $requestId]);

if ($success && $updateStmt->rowCount() > 0) {
    showSuccess($action, $request);
} else {
    showError('Failed to process the request. It may have already been processed.');
}

function showSuccess($action, $request) {
    $actionText = $action === 'approve' ? 'Approved' : 'Rejected';
    $iconColor = $action === 'approve' ? 'text-green-600' : 'text-red-600';
    $bgColor = $action === 'approve' ? 'bg-green-50' : 'bg-red-50';
    $borderColor = $action === 'approve' ? 'border-green-200' : 'border-red-200';
    
    // Generate link back to main dashboard
    $secret = 'quick-ot-approval-bugardi-temporary-2025';
    $token = hash('sha256', 'quick-temporary' . $secret . date('Y-m-d'));
    $dashboardLink = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/Timekeeping-system/Public/Bugardi/quick_ot_approval.php?token=' . $token . '&type=temporary';
    
    echo "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <link href='../../../../src/output.css?v=" . time() . "' rel='stylesheet'>
        <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
        <title>Request $actionText</title>
    </head>
    <body class='bg-gray-100 min-h-screen flex items-center justify-center'>
        <div class='max-w-md mx-auto p-6'>
            <div class='$bgColor border $borderColor rounded-lg p-8 text-center shadow-lg'>
                <div class='w-16 h-16 mx-auto mb-4 bg-white rounded-full flex items-center justify-center'>
                    <i class='fas " . ($action === 'approve' ? 'fa-check' : 'fa-times') . " text-3xl $iconColor'></i>
                </div>
                
                <h1 class='text-2xl font-bold text-gray-900 mb-2'>Request $actionText</h1>
                
                <div class='bg-white rounded-lg p-4 mb-6 text-left'>
                    <h3 class='font-semibold text-gray-800 mb-2'>Request Details:</h3>
                    <p class='text-sm text-gray-600'><strong>Employee:</strong> " . htmlspecialchars($request['employee_name']) . "</p>
                    <p class='text-sm text-gray-600'><strong>Date:</strong> " . date('M d, Y', strtotime($request['date'])) . "</p>
                    <p class='text-sm text-gray-600'><strong>Duration:</strong> " . $request['duration_hours'] . " hours</p>
                    <p class='text-sm text-gray-600'><strong>Status:</strong> <span class='font-semibold $iconColor'>$actionText</span></p>
                </div>
                
                <div class='space-y-3'>
                    <a href='$dashboardLink' class='block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors'>
                        <i class='fas fa-tachometer-alt mr-2'></i>View All Requests
                    </a>
                    
                    <p class='text-sm text-gray-500'>
                        ✅ Action completed successfully!<br>
                        The employee and admin have been notified.
                    </p>
                </div>
            </div>
            
            <div class='text-center mt-6'>
                <p class='text-sm text-gray-500'>
                    © " . date('Y') . " ResourceStaffing Solutions
                </p>
            </div>
        </div>
    </body>
    </html>";
}

function showError($message) {
    echo "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Error</title>
        <link href='../../../../src/output.css' rel='stylesheet'>
        <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
    </head>
    <body class='bg-gray-100 min-h-screen flex items-center justify-center'>
        <div class='max-w-md mx-auto p-6 text-center'>
            <div class='bg-red-50 border border-red-200 rounded-lg p-8 shadow-lg'>
                <div class='w-16 h-16 mx-auto mb-4 bg-white rounded-full flex items-center justify-center'>
                    <i class='fas fa-exclamation-triangle text-3xl text-red-600'></i>
                </div>
                
                <h1 class='text-2xl font-bold text-gray-900 mb-2'>Error</h1>
                <p class='text-gray-600 mb-6'>" . htmlspecialchars($message) . "</p>
                
                <a href='javascript:history.back()' class='bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg'>
                    <i class='fas fa-arrow-left mr-2'></i>Go Back
                </a>
            </div>
        </div>
    </body>
    </html>";
}
?>
