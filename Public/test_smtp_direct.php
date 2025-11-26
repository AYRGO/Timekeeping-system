<?php
// Direct email test without using the processor file
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "<h2>Direct SMTP Test to Gmail</h2>";
echo "<p>Testing connection to smtp.gmail.com:587...</p>";
echo "<hr>";

$mail = new PHPMailer(true);

try {
    // Enable verbose debug output
    $mail->SMTPDebug = 3;
    $mail->Debugoutput = 'html';
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'it.resourcestaff@gmail.com';
    $mail->Password = 'plpe ycwj ztqb kxqk';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->Timeout = 30;
    
    // Disable SSL verification (for testing)
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];
    
    // Recipients
    $mail->setFrom('it.resourcestaff@gmail.com', 'Timekeeping System');
    $mail->addAddress('rjmanago@gmail.com', 'Resty Nazareno');
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email - Monthly Schedule System';
    $mail->Body = '
    <div style="font-family: Arial, sans-serif; padding: 20px; background: #f0f0f0;">
        <div style="background: white; padding: 30px; border-radius: 10px;">
            <h2 style="color: #10b981;">Email System Test</h2>
            <p>This is a test email to verify the monthly schedule email notification system is working.</p>
            <p><strong>Timestamp:</strong> ' . date('F j, Y g:i:s A') . '</p>
            <hr>
            <p style="color: #6b7280; font-size: 14px;">If you receive this email, the system is configured correctly!</p>
        </div>
    </div>';
    
    echo "<div style='background: #fffbeb; padding: 15px; border-left: 4px solid #f59e0b; margin: 20px 0;'>";
    echo "<strong>Attempting to send email...</strong>";
    echo "</div>";
    
    $mail->send();
    
    echo "<div style='background: #ecfdf5; padding: 20px; border-left: 4px solid #10b981; margin: 20px 0;'>";
    echo "<h3 style='color: #065f46; margin: 0 0 10px 0;'>✅ SUCCESS!</h3>";
    echo "<p>Email has been sent successfully to <strong>rjmanago@gmail.com</strong></p>";
    echo "<p>Please check your inbox (and spam folder) for the test email.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #fef2f2; padding: 20px; border-left: 4px solid #ef4444; margin: 20px 0;'>";
    echo "<h3 style='color: #991b1b; margin: 0 0 10px 0;'>❌ FAILED</h3>";
    echo "<p><strong>Error:</strong> {$mail->ErrorInfo}</p>";
    echo "<p><strong>Exception:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>System Information:</h3>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . phpversion() . "</li>";
echo "<li><strong>OpenSSL:</strong> " . (extension_loaded('openssl') ? '✅ Enabled' : '❌ Disabled') . "</li>";
echo "<li><strong>cURL:</strong> " . (extension_loaded('curl') ? '✅ Enabled' : '❌ Disabled') . "</li>";
echo "<li><strong>PHPMailer:</strong> " . (class_exists('PHPMailer\\PHPMailer\\PHPMailer') ? '✅ Loaded' : '❌ Not found') . "</li>";
echo "</ul>";
