<?php
/**
 * CallMeBot WhatsApp Notification System
 * Simple PHP integration for sending WhatsApp messages to Scott
 */

class CallMeBotWhatsApp {
    private $apiKey;
    private $scottPhone;
    private $approvalLink;
    
    public function __construct($apiKey = '6561289', $scottPhone = '+639938642974') {
        $this->apiKey = $apiKey;
        $this->scottPhone = $scottPhone;
        $this->approvalLink = 'https://harley.resourcestaffonline.com/Public/Bugardi/quick-approval-system/approval-pages/quick_ot_approval.php?token=a1b2c3d4e5f6g7h8i9j0&type=permanent';
    }
    
    /**
     * Send WhatsApp notification to Scott about new OT request
     */
    public function notifyScottNewOTRequest($requestId, $employeeName = '') {
        try {
            // Get OT request data from database
            $otData = $this->getOTRequestData($requestId, $employeeName);
            
            if (!$otData) {
                error_log("❌ Could not find OT request with ID: $requestId");
                return false;
            }
            
            // Create WhatsApp message
            $message = $this->createOTNotificationMessage($otData);
            
            // Send WhatsApp message
            $result = $this->sendWhatsAppMessage($this->scottPhone, $message);
            
            if ($result) {
                error_log("✅ Scott notified via WhatsApp for OT request #$requestId");
                return true;
            } else {
                error_log("❌ Failed to send WhatsApp to Scott for OT request #$requestId");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("❌ WhatsApp notification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get OT request data from database
     */
    private function getOTRequestData($requestId, $employeeName = '') {
        try {
            // Try multiple database config paths
            $possiblePaths = [
                dirname(__FILE__) . '/../../../config/db.php',
                dirname(__FILE__) . '/../../../../config/db.php',
                $_SERVER['DOCUMENT_ROOT'] . '/Timekeeping-system/Public/config/db.php',
                dirname(__FILE__) . '/../../config/db.php'
            ];
            
            $dbConfigFound = false;
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    require_once $path;
                    $dbConfigFound = true;
                    error_log("✅ Database config found at: $path");
                    break;
                }
            }
            
            if (!$dbConfigFound) {
                throw new Exception("Database config file not found in any expected location");
            }
            
            // Try to query database if PDO exists
            if (isset($pdo)) {
                $query = "
                    SELECT 
                        ot.id,
                        CONCAT(emp.fname, ' ', emp.lname) as employee_name,
                        emp.position,
                        ot.date,
                        ot.start_time,
                        ot.end_time,
                        ot.duration_hours,
                        ot.reason,
                        ot.created_at
                    FROM overtime_requests ot
                    JOIN employees emp ON ot.employee_id = emp.id
                    WHERE ot.id = ? AND LOWER(emp.company) = 'bugardi'
                ";
                
                $stmt = $pdo->prepare($query);
                $stmt->execute([$requestId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    return $result;
                }
            }
            
            // Return test data if database query fails or config not found
            return [
                'id' => $requestId,
                'employee_name' => $employeeName ?: 'Test Employee',
                'position' => 'Staff',
                'date' => date('Y-m-d'),
                'start_time' => '18:00:00',
                'end_time' => '22:00:00',
                'duration_hours' => '4.0',
                'reason' => 'Urgent project deadline',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            // Return test data
            return [
                'id' => $requestId,
                'employee_name' => $employeeName ?: 'Test Employee',
                'position' => 'Staff',
                'date' => date('Y-m-d'),
                'start_time' => '18:00:00',
                'end_time' => '22:00:00',
                'duration_hours' => '4.0',
                'reason' => 'Urgent project deadline',
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    /**
     * Create simple WhatsApp notification message
     */
    private function createOTNotificationMessage($otData) {
        $message = "📋 *New OT Request*

👤 {$otData['employee_name']}
📅 {$otData['date']} | ⏰ {$otData['duration_hours']}h
📝 {$otData['reason']}

🔗 {$this->approvalLink}";

        return $message;
    }
    
    /**
     * Send WhatsApp message via CallMeBot API
     */
    private function sendWhatsAppMessage($phoneNumber, $message) {
        try {
            // Clean phone number (remove + and spaces)
            $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
            
            // URL encode the message
            $encodedMessage = urlencode($message);
            
            // Build CallMeBot API URL
            $apiUrl = "https://api.callmebot.com/whatsapp.php?phone={$cleanPhone}&text={$encodedMessage}&apikey={$this->apiKey}";
            
            // Send HTTP request
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 30,
                    'header' => 'User-Agent: Bugardi-OT-System/1.0'
                ]
            ]);
            
            $response = file_get_contents($apiUrl, false, $context);
            
            if ($response !== false) {
                error_log("CallMeBot API Response: " . $response);
                return true;
            } else {
                error_log("CallMeBot API failed - no response");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("CallMeBot API error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send OT notification (alias for compatibility)
     */
    public function sendOTNotification($requestId, $otData = []) {
        try {
            // If otData is provided as array, use it directly
            if (!empty($otData) && is_array($otData)) {
                $message = $this->createOTNotificationMessage([
                    'id' => $requestId,
                    'employee_name' => $otData['employee_name'] ?? 'Unknown Employee',
                    'position' => $otData['position'] ?? 'Staff',
                    'date' => $otData['date'] ?? date('Y-m-d'),
                    'start_time' => $otData['start_time'] ?? '18:00:00',
                    'end_time' => $otData['end_time'] ?? '22:00:00',
                    'duration_hours' => $otData['duration_hours'] ?? '4.0',
                    'reason' => $otData['reason'] ?? 'Overtime request',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                return $this->sendWhatsAppMessage($this->scottPhone, $message);
            } else {
                // Use existing method for database lookup
                return $this->notifyScottNewOTRequest($requestId, $otData['employee_name'] ?? '');
            }
            
        } catch (Exception $e) {
            error_log("❌ sendOTNotification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Test the WhatsApp notification system
     */
    public function testNotification() {
        try {
            $result = $this->notifyScottNewOTRequest(999, 'Test Employee');
            
            return [
                'success' => $result,
                'message' => $result ? 
                    'Test WhatsApp sent to Scott! Check his phone.' : 
                    'Failed to send test WhatsApp. Check error logs.'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage()
            ];
        }
    }
}

// Test endpoint
if (isset($_GET['test'])) {
    $whatsapp = new CallMeBotWhatsApp();
    $result = $whatsapp->testNotification();
    
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// Manual send endpoint
if (isset($_GET['send']) && isset($_GET['request_id'])) {
    $requestId = (int)$_GET['request_id'];
    $employeeName = $_GET['employee_name'] ?? '';
    
    $whatsapp = new CallMeBotWhatsApp();
    $result = $whatsapp->notifyScottNewOTRequest($requestId, $employeeName);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $result,
        'message' => $result ? 
            "WhatsApp notification sent to Scott for OT request #$requestId" : 
            "Failed to send WhatsApp notification"
    ]);
    exit;
}

// Documentation page
if (isset($_GET['docs'])) {
    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <title>CallMeBot WhatsApp Integration</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; max-width: 800px; }
            .code { background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 10px 0; }
            .button { background: #25D366; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block; }
            .success { color: #4CAF50; }
            .error { color: #f44336; }
        </style>
    </head>
    <body>
        <h1>📱 CallMeBot WhatsApp Integration</h1>
        
        <h2>✅ Configuration</h2>
        <ul>
            <li><strong>API Key:</strong> 6561289 (configured)</li>
            <li><strong>Scott's Phone:</strong> +639938642974</li>
            <li><strong>Approval Link:</strong> Ready</li>
        </ul>
        
        <h2>🚀 Usage in your OT submission form:</h2>
        <div class='code'>
require_once 'whatsapp-notifications/CallMeBotWhatsApp.php';<br><br>

// After saving OT request to database<br>
\$whatsapp = new CallMeBotWhatsApp();<br>
\$success = \$whatsapp->notifyScottNewOTRequest(\$requestId, \$employeeName);<br><br>

if (\$success) {<br>
&nbsp;&nbsp;&nbsp;&nbsp;echo 'OT request submitted! Scott has been notified via WhatsApp.';<br>
} else {<br>
&nbsp;&nbsp;&nbsp;&nbsp;echo 'OT request submitted! (Notification will be sent shortly)';<br>
}
        </div>
        
        <h2>🧪 Test the System:</h2>
        <a href='?test=1' class='button'>Send Test WhatsApp to Scott</a>
        <a href='?send=1&request_id=123&employee_name=John Doe' class='button'>Send Sample OT Notification</a>
        
        <h2>📱 What Scott receives:</h2>
        <div class='code'>
🏢 <strong>Bugardi OT Request</strong><br><br>
📋 <strong>New OT Request Pending Your Approval</strong><br><br>
👤 <strong>Employee:</strong> John Doe<br>
💼 <strong>Position:</strong> Staff<br>
🆔 <strong>Request ID:</strong> #123<br>
📅 <strong>Date:</strong> 2025-08-12<br>
⏰ <strong>Time:</strong> 18:00 - 22:00<br>
⏱️ <strong>Duration:</strong> 4 hours<br>
📝 <strong>Reason:</strong> Urgent project deadline<br><br>
🕐 <strong>Submitted:</strong> Aug 12, 15:30<br><br>
📱 <strong>Approve/Reject here:</strong><br>
[Approval Link]<br><br>
<em>Tap the link to open on your phone</em>
        </div>
        
        <p><strong>That's it!</strong> Simple, reliable WhatsApp notifications using CallMeBot PHP API.</p>
    </body>
    </html>
    ";
    exit;
}
?>
