<?php
/**
 * Test WhatsApp CallMeBot Configuration
 */

require_once 'CallMeBotWhatsApp.php';

echo "<h1>WhatsApp CallMeBot Configuration Test</h1>";

// Test the WhatsApp class
$whatsapp = new CallMeBotWhatsApp();

echo "<h2>✅ Configuration Details:</h2>";
echo "<ul>";
echo "<li><strong>Your Phone Number:</strong> +639762477146 (receives messages)</li>";
echo "<li><strong>CallMeBot Sender:</strong> +34684734044 (sends messages)</li>";
echo "<li><strong>API Key:</strong> 2833078</li>";
echo "<li><strong>Approval Link:</strong> Generated dynamically</li>";
echo "</ul>";

// Test permanent link generation
echo "<h2>🔗 Generated Permanent Link:</h2>";

// Use reflection to access private method for testing
$reflection = new ReflectionClass($whatsapp);
$method = $reflection->getMethod('generatePermanentApprovalLink');
$method->setAccessible(true);
$permanentLink = $method->invoke($whatsapp);

echo "<p style='word-break: break-all; background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
echo htmlspecialchars($permanentLink);
echo "</p>";

echo "<h2>📱 Test Message Format:</h2>";
$testData = [
    'id' => 123,
    'employee_name' => 'Test Employee',
    'position' => 'Staff',
    'date' => date('Y-m-d'),
    'start_time' => '18:00:00',
    'end_time' => '22:00:00',
    'duration_hours' => '4.0',
    'reason' => 'Test overtime request',
    'created_at' => date('Y-m-d H:i:s')
];

// Use reflection to test message creation
$messageMethod = $reflection->getMethod('createOTNotificationMessage');
$messageMethod->setAccessible(true);
$testMessage = $messageMethod->invoke($whatsapp, $testData);

echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px; white-space: pre-wrap;'>";
echo htmlspecialchars($testMessage);
echo "</pre>";

echo "<h2>🧪 Test Links:</h2>";
echo "<p><a href='?test=1' style='background: #25D366; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Send Test WhatsApp</a></p>";
echo "<p><a href='" . htmlspecialchars($permanentLink) . "' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Approval Link</a></p>";

echo "<p style='color: #666; font-size: 14px;'><strong>Note:</strong> The permanent link is now generated dynamically and will work on your current server.</p>";

echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 8px; border-left: 4px solid #4CAF50; margin: 20px 0;'>";
echo "<h3 style='color: #2e7d32; margin-top: 0;'>📱 How WhatsApp Delivery Works:</h3>";
echo "<ul style='color: #2e7d32; margin-bottom: 0;'>";
echo "<li><strong>Sender:</strong> +34684734044 (CallMeBot's official number)</li>";
echo "<li><strong>Receiver:</strong> +639762477146 (your phone)</li>";
echo "<li><strong>Authentication:</strong> API key 2833078 (linked to your phone)</li>";
echo "<li><strong>Process:</strong> System → CallMeBot API → WhatsApp message to your phone</li>";
echo "</ul>";
echo "</div>";
?>
