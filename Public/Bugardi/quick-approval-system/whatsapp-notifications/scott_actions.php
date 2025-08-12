<?php
/**
 * Simple Reply Handler for A123 / R123 format
 * Processes Scott's replies like "A123" (approve) or "R123" (reject)
 */

require_once '../../../config/db.php';

// Handle direct reply processing
if (isset($_GET['reply'])) {
    $reply = strtoupper(trim($_GET['reply']));
    
    // Parse reply format: A123 or R123
    if (preg_match('/^([AR])(\d+)$/', $reply, $matches)) {
        $action = $matches[1] === 'A' ? 'approve' : 'reject';
        $requestId = (int)$matches[2];
        
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
                throw new Exception("OT request #{$requestId} not found or already processed");
            }
            
            // Process the action
            $status = $action === 'approve' ? 'approved' : 'declined';
            $explanation = $action === 'approve' ? 'Approved via WhatsApp (A' . $requestId . ')' : 'Rejected via WhatsApp (R' . $requestId . ')';
            
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
            $actionEmoji = $action === 'approve' ? '✅' : '❌';
            $actionText = strtoupper($action) . 'D';
            
            $confirmationMessage = "{$actionEmoji} *OT #{$requestId} {$actionText}*

👤 {$otRequest['employee_name']}
📅 {$otRequest['date']} | ⏰ {$otRequest['duration_hours']}h

✅ *Processed via:* {$reply}
🕐 *Time:* " . date('H:i') . "

_Action completed successfully!_";

            // Send confirmation via CallMeBot
            $apiKey = "6561289";
            $phone = "639938642974";
            $encodedMessage = urlencode($confirmationMessage);
            $apiUrl = "https://api.callmebot.com/whatsapp.php?phone={$phone}&text={$encodedMessage}&apikey={$apiKey}";
            
            file_get_contents($apiUrl);
            
            // Return success page
            echo "<!DOCTYPE html>
            <html><head><title>OT {$actionText}</title>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { font-family: Arial; text-align: center; padding: 50px; background: #25D366; color: white; }
                .container { background: white; color: #333; padding: 30px; border-radius: 15px; max-width: 300px; margin: 0 auto; }
                .icon { font-size: 60px; margin: 20px 0; }
            </style></head><body>
            <div class='container'>
                <div class='icon'>{$actionEmoji}</div>
                <h2>OT #{$requestId} {$actionText}!</h2>
                <p><strong>{$otRequest['employee_name']}</strong></p>
                <p>{$otRequest['date']} | {$otRequest['duration_hours']}h</p>
                <p>✅ Confirmation sent to WhatsApp</p>
                <script>setTimeout(() => window.close(), 3000);</script>
            </div></body></html>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<!DOCTYPE html><html><head><title>Error</title></head><body style='text-align: center; padding: 50px; font-family: Arial;'>";
            echo "<h2>❌ Error</h2><p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</body></html>";
        }
    } else {
        echo "<!DOCTYPE html><html><head><title>Invalid Reply</title></head><body style='text-align: center; padding: 50px; font-family: Arial;'>";
        echo "<h2>❌ Invalid Reply Format</h2><p>Use format: A123 (approve) or R123 (reject)</p>";
        echo "</body></html>";
    }
    exit;
}

// Show pending requests interface
$stmt = $pdo->query("
    SELECT ot.id, ot.date, ot.duration_hours, ot.reason, CONCAT(e.fname, ' ', e.lname) as employee_name
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
    <title>Scott's OT Quick Actions</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 15px; 
            background: linear-gradient(135deg, #25D366, #128C7E);
            color: white;
            min-height: 100vh;
        }
        .container {
            max-width: 400px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        .request {
            background: rgba(255,255,255,0.95);
            color: #333;
            padding: 20px;
            border-radius: 15px;
            margin: 15px 0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 15px;
        }
        .btn {
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            text-align: center;
            display: block;
        }
        .approve { background: #25D366; color: white; }
        .reject { background: #dc3545; color: white; }
        .copy-btn {
            background: #17a2b8;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            font-size: 12px;
            cursor: pointer;
            margin: 5px;
        }
        .reply-codes {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 10px;
            margin: 10px 0;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📱 Scott's OT Actions</h1>
            <p>Quick approval interface for WhatsApp</p>
        </div>
        
        <?php if (empty($pendingRequests)): ?>
        <div class="request" style="text-align: center;">
            <h3>✅ All caught up!</h3>
            <p>No pending OT requests at the moment.</p>
        </div>
        <?php else: ?>
        
        <div style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center;">
            <strong>💡 WhatsApp Reply Codes:</strong><br>
            <small>Copy and paste these as WhatsApp replies</small>
        </div>
        
        <?php foreach ($pendingRequests as $request): ?>
        <div class="request">
            <h3>📋 OT Request #<?= $request['id'] ?></h3>
            <div style="margin: 10px 0;">
                <strong>👤 Employee:</strong> <?= htmlspecialchars($request['employee_name']) ?><br>
                <strong>📅 Date:</strong> <?= $request['date'] ?><br>
                <strong>⏱️ Duration:</strong> <?= $request['duration_hours'] ?> hours<br>
                <strong>📝 Reason:</strong> <?= htmlspecialchars(substr($request['reason'], 0, 50)) ?>...
            </div>
            
            <div class="reply-codes">
                <strong>WhatsApp Reply Codes:</strong><br>
                Approve: <code>A<?= $request['id'] ?></code> 
                <button class="copy-btn" onclick="copyText('A<?= $request['id'] ?>')">Copy</button><br>
                Reject: <code>R<?= $request['id'] ?></code> 
                <button class="copy-btn" onclick="copyText('R<?= $request['id'] ?>')">Copy</button>
            </div>
            
            <div class="actions">
                <a href="?reply=A<?= $request['id'] ?>" class="btn approve">
                    ✅ Approve (A<?= $request['id'] ?>)
                </a>
                <a href="?reply=R<?= $request['id'] ?>" class="btn reject">
                    ❌ Reject (R<?= $request['id'] ?>)
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 30px; font-size: 12px; opacity: 0.8;">
            💡 Scott can reply to WhatsApp with codes like "A123" or "R123"<br>
            Or use this page for quick testing
        </div>
    </div>

    <script>
        function copyText(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert(`Copied: ${text}\nPaste this as WhatsApp reply`);
            });
        }
    </script>
</body>
</html>
