<?php
/**
 * Complete OT Workflow Test with Reply-Based WhatsApp Integration
 * Test the full flow: Submit OT → WhatsApp Notification → Reply Processing
 */

require_once '../../../config/db.php';
require_once '../whatsapp-notifications/CallMeBotWhatsApp.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get a test employee from Bugardi
        $stmt = $pdo->prepare("SELECT id, fname, lname FROM employees WHERE LOWER(company) = 'bugardi' LIMIT 1");
        $stmt->execute();
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$employee) {
            throw new Exception("No Bugardi employees found in database");
        }
        
        // Insert test OT request
        $testDate = date('Y-m-d');
        $startTime = $_POST['start_time'] ?? '18:00';
        $endTime = $_POST['end_time'] ?? '22:00';
        $duration = $_POST['duration'] ?? 4;
        $reason = $_POST['reason'] ?? 'Project deadline completion';
        
        $stmt = $pdo->prepare("
            INSERT INTO overtime_requests 
            (employee_id, date, start_time, end_time, duration_hours, reason, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW())
        ");
        
        $stmt->execute([
            $employee['id'],
            $testDate,
            $startTime,
            $endTime,
            $duration,
            $reason
        ]);
        
        $otRequestId = $pdo->lastInsertId();
        
        // Send WhatsApp notification
        $whatsapp = new CallMeBotWhatsApp();
        $result = $whatsapp->sendOTNotification($otRequestId, [
            'employee_name' => $employee['fname'] . ' ' . $employee['lname'],
            'date' => $testDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_hours' => $duration,
            'reason' => $reason
        ]);
        
        if ($result) {
            $message = "✅ **COMPLETE WORKFLOW TEST SUCCESS!**

📋 **OT Request Created:** #{$otRequestId}
👤 **Employee:** {$employee['fname']} {$employee['lname']}
📅 **Date:** {$testDate}
⏰ **Duration:** {$duration} hours
📝 **Reason:** {$reason}

📱 **WhatsApp Notification:** Sent from +34684734044 to +639762477146

🔧 **Scott's Reply Options:**
• **Approve:** Text `A{$otRequestId}` to WhatsApp
• **Reject:** Text `R{$otRequestId}` to WhatsApp
• **Quick Page:** [scott_actions.php](scott_actions.php)

💡 **Test the reply system:**
[Test A{$otRequestId} (Approve)](scott_actions.php?reply=A{$otRequestId}) | [Test R{$otRequestId} (Reject)](scott_actions.php?reply=R{$otRequestId})";
        } else {
            $message = "❌ OT request created but WhatsApp notification failed";
        }
        
    } catch (Exception $e) {
        $message = "❌ Error: " . $e->getMessage();
    }
}

// Get pending requests count
$stmt = $pdo->query("
    SELECT COUNT(*) as count 
    FROM overtime_requests ot 
    JOIN employees e ON ot.employee_id = e.id 
    WHERE LOWER(e.company) = 'bugardi' AND ot.status = 'Pending'
");
$pendingCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../../../../../src/output.css?v=<?= time() ?>" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Complete OT Workflow Test</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: linear-gradient(135deg, #007bff, #17a2b8);
            color: white;
            min-height: 100vh;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        .card {
            background: rgba(255,255,255,0.95);
            color: #333;
            padding: 25px;
            border-radius: 15px;
            margin: 20px 0;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        .header {
            text-align: center;
            background: rgba(255,255,255,0.1);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        .form-group {
            margin: 15px 0;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, textarea, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .btn {
            background: #25D366;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            margin: 15px 0;
        }
        .btn:hover { background: #128C7E; }
        .result {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            white-space: pre-line;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }
        .links {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 20px 0;
        }
        .link-btn {
            background: #17a2b8;
            color: white;
            padding: 12px;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Complete OT Workflow Test</h1>
            <p>Test the full flow: Submit OT → WhatsApp → Reply Processing</p>
        </div>
        
        <div class="stats">
            <div class="stat">
                <h3><?= $pendingCount ?></h3>
                <p>Pending OT Requests</p>
            </div>
            <div class="stat">
                <h3>A123/R123</h3>
                <p>Reply Format</p>
            </div>
            <div class="stat">
                <h3>+639762477146</h3>
                <p>Scott's WhatsApp</p>
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="result <?= strpos($message, '❌') !== false ? 'error' : '' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>📝 Submit Test OT Request</h2>
            <form method="POST">
                <div class="form-group">
                    <label>📅 Date:</label>
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>⏰ Start Time:</label>
                        <input type="time" name="start_time" value="18:00" required>
                    </div>
                    <div class="form-group">
                        <label>🏁 End Time:</label>
                        <input type="time" name="end_time" value="22:00" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>⏱️ Duration (hours):</label>
                    <select name="duration" required>
                        <option value="1">1 hour</option>
                        <option value="2">2 hours</option>
                        <option value="3">3 hours</option>
                        <option value="4" selected>4 hours</option>
                        <option value="6">6 hours</option>
                        <option value="8">8 hours</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>📝 Reason:</label>
                    <textarea name="reason" rows="3" placeholder="Enter reason for overtime request..." required>Project deadline completion - urgent client deliverable due tomorrow morning</textarea>
                </div>
                
                <button type="submit" class="btn">
                    🚀 Submit OT & Send WhatsApp Notification
                </button>
            </form>
        </div>
        
        <div class="card">
            <h2>🔧 Testing Tools</h2>
            <div class="links">
                <a href="scott_actions.php" class="link-btn">📱 Scott's Actions Page</a>
                <a href="simple-whatsapp-test.php" class="link-btn">📞 Simple WhatsApp Test</a>
                <a href="debug-whatsapp.php" class="link-btn">🐛 Debug WhatsApp</a>
                <a href="../quick_ot_approval.php" class="link-btn">📋 OT Approval Interface</a>
            </div>
        </div>
        
        <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px; margin-top: 20px; text-align: center;">
            <h3>💡 How This Works</h3>
            <p><strong>1.</strong> Employee submits OT request via this form<br>
            <strong>2.</strong> System automatically sends WhatsApp to Scott<br>
            <strong>3.</strong> Scott replies with "A123" (approve) or "R123" (reject)<br>
            <strong>4.</strong> System processes reply and sends confirmation</p>
        </div>
    </div>
</body>
</html>
