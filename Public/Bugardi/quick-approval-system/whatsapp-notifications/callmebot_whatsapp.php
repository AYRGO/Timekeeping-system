<?php
/**
 * Simple WhatsApp Messenger using CallMeBot API
 * No account upgrade required - just need API key from CallMeBot
 */

// Configuration
$phoneNumber = '+639762477146';  // Your phone number (international format)
$apiKey = '2833078';  // Your CallMeBot API key
$message = 'Hello from Bugardi OT System! This is a test message. Time: ' . date('M d, Y H:i A');

// Function to send WhatsApp message via CallMeBot
function sendWhatsAppMessage($phone, $message, $apiKey) {
    // Remove + from phone number for CallMeBot
    $cleanPhone = str_replace('+', '', $phone);
    
    // URL encode the message to handle spaces and special characters
    $encodedMessage = urlencode($message);
    
    // Build the API URL
    $apiUrl = "https://api.callmebot.com/whatsapp.php?phone={$cleanPhone}&text={$encodedMessage}&apikey={$apiKey}";
    
    // Send the request using file_get_contents
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'method' => 'GET',
            'header' => 'User-Agent: PHP WhatsApp Bot'
        ]
    ]);
    
    $response = @file_get_contents($apiUrl, false, $context);
    
    // Check if request was successful
    if ($response !== false) {
        return true;
    } else {
        return false;
    }
}

// Alternative function using cURL (more robust)
function sendWhatsAppMessageCurl($phone, $message, $apiKey) {
    // Remove + from phone number for CallMeBot
    $cleanPhone = str_replace('+', '', $phone);
    
    // URL encode the message
    $encodedMessage = urlencode($message);
    
    // Build the API URL
    $apiUrl = "https://api.callmebot.com/whatsapp.php?phone={$cleanPhone}&text={$encodedMessage}&apikey={$apiKey}";
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'PHP WhatsApp Bot');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Check for cURL errors
    if ($error) {
        echo "<p style='color: red;'>cURL Error: $error</p>";
        return false;
    }
    
    // Check HTTP response code
    if ($httpCode === 200 && $response !== false) {
        return true;
    } else {
        echo "<p style='color: red;'>HTTP Error: $httpCode</p>";
        echo "<p style='color: red;'>Response: $response</p>";
        return false;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../../../../../src/output.css?v=<?= time() ?>" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>CallMeBot WhatsApp Sender</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .container { background: #f8f9fa; padding: 30px; border-radius: 10px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .code { background: #f1f3f4; padding: 10px; border-radius: 5px; font-family: monospace; }
        button { background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; }
        button:hover { background: #0056b3; }
        input[type="text"] { width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ddd; border-radius: 4px; }
        textarea { width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ddd; border-radius: 4px; rows: 4; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📱 CallMeBot WhatsApp Sender</h1>
        <p>Simple WhatsApp messaging without Twilio account upgrades!</p>
        
        <div class="info">
            <h3>🔧 Setup Instructions:</h3>
            <ol>
                <li><strong>Get API Key:</strong> Add CallMeBot to your contacts: <code>+34684734044</code></li>
                <li><strong>Send message:</strong> "I allow callmebot to send me messages" to that number</li>
                <li><strong>Receive API key:</strong> CallMeBot will reply with your personal API key</li>
                <li><strong>Update code:</strong> Replace 'YOUR_CALLMEBOT_API_KEY_HERE' with your actual key</li>
            </ol>
            <p><strong>Note:</strong> Messages will come FROM +34684734044 TO your phone number.</p>
        </div>
        
        <form method="post">
            <h3>📝 Test Message</h3>
            
            <label><strong>Phone Number:</strong></label>
            <input type="text" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? $phoneNumber) ?>" placeholder="+639762477146">
            
            <label><strong>API Key:</strong></label>
            <input type="text" name="apikey" value="<?= htmlspecialchars($_POST['apikey'] ?? $apiKey) ?>" placeholder="Your CallMeBot API Key">
            
            <label><strong>Message:</strong></label>
            <textarea name="message" placeholder="Enter your message here..."><?= htmlspecialchars($_POST['message'] ?? $message) ?></textarea>
            
            <button type="submit" name="send_message">📱 Send WhatsApp Message</button>
        </form>
        
        <?php
        // Handle form submission
        if (isset($_POST['send_message'])) {
            $testPhone = $_POST['phone'];
            $testApiKey = $_POST['apikey'];
            $testMessage = $_POST['message'];
            
            echo "<h3>🚀 Sending Message...</h3>";
            echo "<p><strong>To:</strong> $testPhone</p>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($testMessage) . "</p>";
            
            if (empty($testApiKey) || $testApiKey === 'YOUR_CALLMEBOT_API_KEY_HERE') {
                echo "<p class='error'>❌ Please set up your CallMeBot API key first!</p>";
            } else {
                // Try to send the message
                $startTime = microtime(true);
                $success = sendWhatsAppMessageCurl($testPhone, $testMessage, $testApiKey);
                $endTime = microtime(true);
                
                echo "<p><strong>Response Time:</strong> " . round($endTime - $startTime, 2) . " seconds</p>";
                
                if ($success) {
                    echo "<p class='success'>✅ Message sent successfully!</p>";
                    echo "<p>Check WhatsApp on $testPhone for the message.</p>";
                } else {
                    echo "<p class='error'>❌ Message sending failed!</p>";
                    echo "<p>Please check your API key and phone number.</p>";
                }
            }
        }
        ?>
        
        <div class="info">
            <h3>💡 Integration with OT Approval System</h3>
            <p>Once this works, we can integrate it into your Bugardi OT approval system:</p>
            <ul>
                <li>✅ Replace Twilio with CallMeBot</li>
                <li>✅ No account upgrades needed</li>
                <li>✅ Works internationally</li>
                <li>✅ Simple and reliable</li>
            </ul>
        </div>
        
        <div class="code">
            <h4>📋 Sample API Request URL:</h4>
            <code>https://api.callmebot.com/whatsapp.php?phone=639762477146&text=Hello%20World&apikey=YOUR_API_KEY</code>
        </div>
    </div>
</body>
</html>
