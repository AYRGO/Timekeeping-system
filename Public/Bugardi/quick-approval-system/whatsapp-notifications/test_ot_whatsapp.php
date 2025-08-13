<?php
/**
 * Direct WhatsApp Test for OT Request Notifications
 * This will simulate sending a WhatsApp notification as if an OT request was just submitted
 */

require_once 'CallMeBotWhatsApp.php';

echo "<h1>📱 Direct WhatsApp OT Request Test</h1>";

// Test sending a WhatsApp notification for a new OT request
echo "<h2>🧪 Testing WhatsApp OT Notification</h2>";

try {
    $whatsapp = new CallMeBotWhatsApp();
    
    // Simulate OT request data
    $testRequestId = 999;
    $testOTData = [
        'employee_name' => 'Test Employee (Bugardi)',
        'position' => 'Staff',
        'date' => date('Y-m-d'),
        'start_time' => '18:00:00',
        'end_time' => '22:00:00',
        'duration_hours' => '4.0',
        'reason' => 'Urgent project completion required - overtime needed to meet client deadline'
    ];
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
    echo "<h3>📋 Test OT Request Details:</h3>";
    echo "<ul>";
    echo "<li><strong>Employee:</strong> " . htmlspecialchars($testOTData['employee_name']) . "</li>";
    echo "<li><strong>Date:</strong> " . htmlspecialchars($testOTData['date']) . "</li>";
    echo "<li><strong>Time:</strong> " . htmlspecialchars($testOTData['start_time']) . " - " . htmlspecialchars($testOTData['end_time']) . "</li>";
    echo "<li><strong>Duration:</strong> " . htmlspecialchars($testOTData['duration_hours']) . " hours</li>";
    echo "<li><strong>Reason:</strong> " . htmlspecialchars($testOTData['reason']) . "</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<p><strong>🚀 Sending WhatsApp notification...</strong></p>";
    
    $result = $whatsapp->sendOTNotification($testRequestId, $testOTData);
    
    if ($result) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; border: 1px solid #c3e6cb; margin: 15px 0;'>";
        echo "<h3>✅ SUCCESS!</h3>";
        echo "<p><strong>WhatsApp notification sent successfully!</strong></p>";
        echo "<p>📱 Check your WhatsApp at <strong>+639762477146</strong></p>";
        echo "<p>📨 Message should come from <strong>+34684734044</strong> (CallMeBot)</p>";
        echo "<p>🔗 The message should contain the permanent approval link</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; border: 1px solid #f5c6cb; margin: 15px 0;'>";
        echo "<h3>❌ FAILED</h3>";
        echo "<p><strong>WhatsApp notification failed to send</strong></p>";
        echo "<p>Check the error logs for details</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; border: 1px solid #f5c6cb; margin: 15px 0;'>";
    echo "<h3>❌ ERROR</h3>";
    echo "<p><strong>Exception:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

// Test 2: Direct API call
echo "<h2>🔧 Direct CallMeBot API Test</h2>";

if (isset($_GET['api_test'])) {
    $phone = "639762477146";
    $apiKey = "2833078";
    $message = "🧪 DIRECT API TEST\n\n📋 New OT Request Test\n👤 Test Employee\n📅 " . date('M d, Y') . "\n⏰ 4 hours\n📝 Test overtime request\n\n🔗 Approval Link Test\n\nSent at: " . date('H:i A');
    
    $encodedMessage = urlencode($message);
    $apiUrl = "https://api.callmebot.com/whatsapp.php?phone={$phone}&text={$encodedMessage}&apikey={$apiKey}";
    
    echo "<p><strong>Calling CallMeBot API directly...</strong></p>";
    echo "<p style='font-size: 12px; word-break: break-all; background: #f1f1f1; padding: 8px; border-radius: 4px;'>";
    echo "URL: " . htmlspecialchars($apiUrl);
    echo "</p>";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 30,
            'header' => 'User-Agent: Bugardi-Test/1.0'
        ]
    ]);
    
    $response = @file_get_contents($apiUrl, false, $context);
    
    if ($response !== false) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
        echo "<h3>✅ Direct API Call Successful!</h3>";
        echo "<p><strong>Response:</strong> " . htmlspecialchars($response) . "</p>";
        echo "<p>Check your WhatsApp for the test message!</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
        echo "<h3>❌ Direct API Call Failed</h3>";
        echo "<p>Could not reach CallMeBot API. Check your internet connection.</p>";
        echo "</div>";
    }
} else {
    echo "<p><a href='?api_test=1' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🧪 Run Direct API Test</a></p>";
}

echo "<hr style='margin: 30px 0;'>";

echo "<h2>📋 How to Test the Complete Workflow:</h2>";
echo "<ol>";
echo "<li><strong>Submit Real OT Request:</strong> Have a Bugardi employee submit an overtime request through the system</li>";
echo "<li><strong>Check WhatsApp:</strong> You should receive a notification on +639762477146 from +34684734044</li>";
echo "<li><strong>Click Approval Link:</strong> Tap the link in the WhatsApp message</li>";
echo "<li><strong>Approve/Reject:</strong> Use the mobile-friendly approval page</li>";
echo "<li><strong>Get Confirmation:</strong> Receive WhatsApp confirmation of your action</li>";
echo "</ol>";

echo "<h2>🔧 Troubleshooting:</h2>";
echo "<ul>";
echo "<li><strong>No WhatsApp received:</strong> Check if your API key 2833078 is still valid</li>";
echo "<li><strong>Link doesn't work:</strong> Make sure the permanent token is generated correctly</li>";
echo "<li><strong>Employee not from Bugardi:</strong> Only Bugardi company employees trigger WhatsApp notifications</li>";
echo "</ul>";

echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 8px; border-left: 4px solid #4CAF50; margin: 20px 0;'>";
echo "<h3>📱 WhatsApp Configuration Summary:</h3>";
echo "<ul style='margin: 0;'>";
echo "<li><strong>Your Phone:</strong> +639762477146</li>";
echo "<li><strong>CallMeBot Sender:</strong> +34684734044</li>";
echo "<li><strong>API Key:</strong> 2833078</li>";
echo "<li><strong>Message Trigger:</strong> When Bugardi employee submits OT request</li>";
echo "<li><strong>Approval Link:</strong> Permanent link included in each message</li>";
echo "</ul>";
echo "</div>";
?>
