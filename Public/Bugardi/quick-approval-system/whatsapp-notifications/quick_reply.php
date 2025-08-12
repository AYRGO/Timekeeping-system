<?php
/**
 * WhatsApp Reply Handler - Process YES/NO replies from Scott
 * This handles when Scott replies "YES" or "NO" to WhatsApp messages
 */

require_once '../../../config/db.php';

// This would typically be called by a WhatsApp webhook
// For now, we'll create a simple interface for Scott to use

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reply = strtoupper(trim($_POST['reply'] ?? ''));
    $requestId = (int)($_POST['request_id'] ?? 0);
    
    if (!in_array($reply, ['YES', 'NO']) || $requestId <= 0) {
        die(json_encode(['success' => false, 'message' => 'Invalid reply or request ID']));
    }
    
    try {
        $pdo->beginTransaction();
        
        // Get the OT request details
        $stmt = $pdo->prepare("
            SELECT ot.*, CONCAT(e.fname, ' ', e.lname) as employee_name 
            FROM overtime_requests ot 
            JOIN employees e ON ot.employee_id = e.id 
            WHERE ot.id = ? AND LOWER(e.company) = 'bugardi' AND ot.status = 'Pending'
        ");
        $stmt->execute([$requestId]);
        $otRequest = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$otRequest) {
            throw new Exception("OT request not found or already processed");
        }
        
        // Process the reply
        $action = $reply === 'YES' ? 'approve' : 'reject';
        $status = $reply === 'YES' ? 'approved' : 'declined';
        $explanation = $reply === 'YES' ? 'Approved via WhatsApp reply' : 'Rejected via WhatsApp reply';
        
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
        
        // Send confirmation WhatsApp
        $actionText = $reply === 'YES' ? 'APPROVED ✅' : 'REJECTED ❌';
        $confirmationMessage = "🎉 *OT Request #{$requestId} {$actionText}*

👤 *Employee:* {$otRequest['employee_name']}
📅 *Date:* {$otRequest['date']}
⏰ *Duration:* {$otRequest['duration_hours']} hours

🕐 *Action completed:* " . date('M d, H:i') . "

_Reply processed successfully!_";

        // Send confirmation via CallMeBot
        $apiKey = "6561289";
        $phone = "639938642974";
        $encodedMessage = urlencode($confirmationMessage);
        $apiUrl = "https://api.callmebot.com/whatsapp.php?phone={$phone}&text={$encodedMessage}&apikey={$apiKey}";
        
        file_get_contents($apiUrl);
        
        echo json_encode(['success' => true, 'message' => "OT Request #{$requestId} {$actionText}"]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get pending OT requests for Scott
$stmt = $pdo->query("
    SELECT ot.id, ot.date, ot.duration_hours, CONCAT(e.fname, ' ', e.lname) as employee_name
    FROM overtime_requests ot 
    JOIN employees e ON ot.employee_id = e.id 
    WHERE LOWER(e.company) = 'bugardi' AND ot.status = 'Pending'
    ORDER BY ot.created_at DESC
");
$pendingRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Reply Interface - Scott</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: #25D366;
            color: white;
        }
        .container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            color: #333;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .request {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin: 10px 0;
            border-left: 4px solid #25D366;
        }
        .buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
        }
        .approve { background: #25D366; color: white; }
        .reject { background: #dc3545; color: white; }
        .result {
            margin: 15px 0;
            padding: 15px;
            border-radius: 8px;
            display: none;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        h1 { color: #25D366; text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📱 Quick Reply Interface</h1>
        <p style="text-align: center; color: #666; font-size: 14px;">
            Simulate WhatsApp YES/NO replies for testing
        </p>
        
        <div id="result" class="result"></div>
        
        <?php if (empty($pendingRequests)): ?>
        <div style="text-align: center; padding: 30px; color: #666;">
            ✅ No pending OT requests
        </div>
        <?php else: ?>
        
        <?php foreach ($pendingRequests as $request): ?>
        <div class="request">
            <strong>📋 Request #<?= $request['id'] ?></strong><br>
            <strong>👤 Employee:</strong> <?= htmlspecialchars($request['employee_name']) ?><br>
            <strong>📅 Date:</strong> <?= $request['date'] ?><br>
            <strong>⏱️ Duration:</strong> <?= $request['duration_hours'] ?> hours
            
            <div class="buttons">
                <button class="btn approve" onclick="processReply(<?= $request['id'] ?>, 'YES')">
                    ✅ YES (Approve)
                </button>
                <button class="btn reject" onclick="processReply(<?= $request['id'] ?>, 'NO')">
                    ❌ NO (Reject)
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php endif; ?>
        
        <div style="margin-top: 30px; text-align: center; font-size: 12px; color: #666;">
            💡 In real WhatsApp, Scott just replies "YES" or "NO" to the message
        </div>
    </div>

    <script>
        function processReply(requestId, reply) {
            const resultDiv = document.getElementById('result');
            resultDiv.style.display = 'block';
            resultDiv.className = 'result';
            resultDiv.innerHTML = '⏳ Processing...';
            
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `request_id=${requestId}&reply=${reply}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.className = 'result success';
                    resultDiv.innerHTML = '✅ ' + data.message;
                    // Reload page after 2 seconds
                    setTimeout(() => location.reload(), 2000);
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = '❌ ' + data.message;
                }
            })
            .catch(error => {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = '❌ Error: ' + error.message;
            });
        }
    </script>
</body>
</html>
