<?php
/**
 * WhatsApp Action Handler - Process Approve/Reject from WhatsApp buttons
 * This handles direct approve/reject actions from WhatsApp links
 */

require_once '../../../config/db.php';

$action = $_GET['action'] ?? '';
$requestId = (int)($_GET['id'] ?? 0);
$token = $_GET['token'] ?? '';

// Validate token for security
$expectedApproveToken = md5("approve_{$requestId}_bugardi");
$expectedRejectToken = md5("reject_{$requestId}_bugardi");

$isValidToken = ($action === 'approve' && $token === $expectedApproveToken) || 
                ($action === 'reject' && $token === $expectedRejectToken);

if (!$isValidToken || !in_array($action, ['approve', 'reject']) || $requestId <= 0) {
    http_response_code(403);
    die("❌ Invalid or expired link");
}

try {
    $pdo->beginTransaction();
    
    // Get the OT request details
    $stmt = $pdo->prepare("
        SELECT ot.*, CONCAT(e.fname, ' ', e.lname) as employee_name, e.position
        FROM overtime_requests ot 
        JOIN employees e ON ot.employee_id = e.id 
        WHERE ot.id = ? AND LOWER(e.company) = 'bugardi' AND ot.status = 'Pending'
    ");
    $stmt->execute([$requestId]);
    $otRequest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$otRequest) {
        throw new Exception("OT request not found or already processed");
    }
    
    // Process the action
    $status = $action === 'approve' ? 'approved' : 'declined';
    $explanation = $action === 'approve' ? 'Approved via WhatsApp' : 'Rejected via WhatsApp';
    
    // Move to post_overtime_requests table
    $stmt = $pdo->prepare("
        INSERT INTO post_overtime_requests 
        (employee_id, date, start_time, end_time, reason, status, attachment_ot, duration_hours, time_in, time_out, explanation, created_at)
        SELECT 
            o.employee_id, o.date, o.start_time, o.end_time, o.reason, ?, o.attachment_ot,
            o.duration_hours, o.time_in, o.time_out, ?, o.created_at
        FROM overtime_requests o
        WHERE o.id = ?
    ");
    $stmt->execute([$status, $explanation, $requestId]);
    
    // Delete from original table
    $stmt = $pdo->prepare("DELETE FROM overtime_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    
    $pdo->commit();
    
    // Send confirmation WhatsApp to Scott
    $confirmationMessage = "✅ *OT Request #{$requestId} " . strtoupper($action) . "D*

👤 *Employee:* {$otRequest['employee_name']}
📅 *Date:* {$otRequest['date']}
⏰ *Time:* {$otRequest['start_time']} - {$otRequest['end_time']}
⏱️ *Duration:* {$otRequest['duration_hours']} hours

🕐 *Action completed:* " . date('M d, H:i') . "

_Action processed successfully via WhatsApp_";

    // Send confirmation via CallMeBot
    $apiKey = "6561289";
    $phone = "639938642974";
    $encodedMessage = urlencode($confirmationMessage);
    $apiUrl = "https://api.callmebot.com/whatsapp.php?phone={$phone}&text={$encodedMessage}&apikey={$apiKey}";
    
    // Send confirmation (don't wait for response)
    file_get_contents($apiUrl);
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    die("❌ Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OT Request <?= ucfirst($action) ?>d</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: linear-gradient(135deg, #25D366, #128C7E);
            color: white;
            text-align: center;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: rgba(255,255,255,0.95);
            color: #333;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            max-width: 400px;
            width: 100%;
        }
        .icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        .success { color: #25D366; }
        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: left;
        }
        .button {
            background: #25D366;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 8px;
            display: inline-block;
            margin: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon success">
            <?= $action === 'approve' ? '✅' : '❌' ?>
        </div>
        
        <div class="title">
            OT Request <?= ucfirst($action) ?>d!
        </div>
        
        <div class="details">
            <strong>📋 Request ID:</strong> #<?= $requestId ?><br>
            <strong>👤 Employee:</strong> <?= htmlspecialchars($otRequest['employee_name']) ?><br>
            <strong>📅 Date:</strong> <?= $otRequest['date'] ?><br>
            <strong>⏰ Time:</strong> <?= $otRequest['start_time'] ?> - <?= $otRequest['end_time'] ?><br>
            <strong>⏱️ Duration:</strong> <?= $otRequest['duration_hours'] ?> hours<br>
            <strong>✅ Status:</strong> <?= ucfirst($status) ?>
        </div>
        
        <p>📱 <strong>Confirmation sent to WhatsApp</strong></p>
        
        <div style="margin-top: 30px;">
            <a href="../../../Admin_dashboard.php" class="button">
                📋 View Dashboard
            </a>
        </div>
        
        <div style="margin-top: 20px; font-size: 12px; color: #666;">
            ⚡ Action completed in <?= number_format((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000) ?>ms
        </div>
    </div>

    <script>
        // Auto-close after 5 seconds if opened in app
        setTimeout(() => {
            if (window.history.length <= 1) {
                window.close();
            }
        }, 5000);
    </script>
</body>
</html>
