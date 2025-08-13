<?php
/**
 * Manual WhatsApp Test for Bugardi OT System
 * Test the WhatsApp notification without waiting for an actual OT submission
 */

require_once 'CallMeBotWhatsApp.php';

echo "<h1>📱 WhatsApp Test for Bugardi OT System</h1>";

// Test 1: Basic WhatsApp notification
echo "<h2>🧪 Test 1: Send Test Notification</h2>";

try {
    $whatsapp = new CallMeBotWhatsApp();
    
    // Test data (simulating a real OT request)
    $testData = [
        'employee_name' => 'Test Employee',
        'position' => 'Staff',
        'date' => date('Y-m-d'),
        'start_time' => '18:00:00',
        'end_time' => '22:00:00',
        'duration_hours' => '4.0',
        'reason' => 'Urgent project deadline - testing WhatsApp notification system'
    ];
    
    echo "<p><strong>Testing with data:</strong></p>";
    echo "<ul>";
    echo "<li>Employee: " . htmlspecialchars($testData['employee_name']) . "</li>";
    echo "<li>Date: " . htmlspecialchars($testData['date']) . "</li>";
    echo "<li>Hours: " . htmlspecialchars($testData['duration_hours']) . "</li>";
    echo "<li>Reason: " . htmlspecialchars($testData['reason']) . "</li>";
    echo "</ul>";
    
    $result = $whatsapp->sendOTNotification(999, $testData);
    
    if ($result) {
        echo "<p style='color: green; font-weight: bold;'>✅ WhatsApp notification sent successfully!</p>";
        echo "<p>Check your WhatsApp at +639762477146 for the message from +34684734044</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ WhatsApp notification failed!</p>";
        echo "<p>Check error logs for details.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 2: Check configuration
echo "<h2>⚙️ Test 2: Configuration Check</h2>";

$reflection = new ReflectionClass('CallMeBotWhatsApp');
$constructor = $reflection->getConstructor();
$defaultParams = [];

// Get default parameter values
foreach ($constructor->getParameters() as $param) {
    if ($param->isDefaultValueAvailable()) {
        $defaultParams[$param->getName()] = $param->getDefaultValue();
    }
}

echo "<ul>";
echo "<li><strong>API Key:</strong> " . htmlspecialchars($defaultParams['apiKey'] ?? 'Not set') . "</li>";
echo "<li><strong>Phone Number:</strong> " . htmlspecialchars($defaultParams['scottPhone'] ?? 'Not set') . "</li>";
echo "<li><strong>Server:</strong> " . $_SERVER['HTTP_HOST'] . "</li>";
echo "<li><strong>Protocol:</strong> " . (isset($_SERVER['HTTPS']) ? 'https' : 'http') . "</li>";
echo "</ul>";

// Test 3: Generate approval link
echo "<h2>🔗 Test 3: Approval Link Generation</h2>";

try {
    $whatsapp = new CallMeBotWhatsApp();
    $reflection = new ReflectionClass($whatsapp);
    $method = $reflection->getMethod('generatePermanentApprovalLink');
    $method->setAccessible(true);
    $approvalLink = $method->invoke($whatsapp);
    
    echo "<p><strong>Generated Approval Link:</strong></p>";
    echo "<p style='word-break: break-all; background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
    echo "<a href='" . htmlspecialchars($approvalLink) . "' target='_blank'>" . htmlspecialchars($approvalLink) . "</a>";
    echo "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error generating approval link: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 4: Manual API call test
echo "<h2>📡 Test 4: Direct CallMeBot API Test</h2>";

if (isset($_GET['direct_test'])) {
    $phone = "639762477146";
    $apiKey = "2833078";
    $message = "🧪 Direct API Test from Bugardi System\n\nTime: " . date('M d, Y H:i A') . "\n\nThis is a direct test of the CallMeBot API.";
    
    $encodedMessage = urlencode($message);
    $apiUrl = "https://api.callmebot.com/whatsapp.php?phone={$phone}&text={$encodedMessage}&apikey={$apiKey}";
    
    echo "<p><strong>API URL:</strong></p>";
    echo "<p style='word-break: break-all; font-size: 12px; background: #f0f0f0; padding: 8px;'>" . htmlspecialchars($apiUrl) . "</p>";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 30,
            'header' => 'User-Agent: Bugardi-Test/1.0'
        ]
    ]);
    
    $response = @file_get_contents($apiUrl, false, $context);
    
    if ($response !== false) {
        echo "<p style='color: green; font-weight: bold;'>✅ Direct API call successful!</p>";
        echo "<p><strong>Response:</strong> " . htmlspecialchars($response) . "</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Direct API call failed!</p>";
        echo "<p>Check your internet connection and API key.</p>";
    }
} else {
    echo "<p><a href='?direct_test=1' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🧪 Run Direct API Test</a></p>";
}

echo "<hr style='margin: 30px 0;'>";
echo "<h2>📋 Next Steps:</h2>";
echo "<ol>";
echo "<li>If Test 1 works, your WhatsApp integration is ready!</li>";
echo "<li>Submit a real OT request to test the full workflow</li>";
echo "<li>Check your WhatsApp for notifications from +34684734044</li>";
echo "<li>Click the approval link in the message to test the approval flow</li>";
echo "</ol>";

echo "<p style='color: #666; font-size: 14px; margin-top: 20px;'>";
echo "<strong>Note:</strong> Messages come FROM +34684734044 (CallMeBot) TO +639762477146 (your phone) using API key 2833078.";
echo "</p>";
?>
