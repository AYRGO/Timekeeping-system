<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require 'Public/config/db.php';

try {
    $mail = new PHPMailer(true);

    // SMTP Settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'it.resourcestaff@gmail.com';
    $mail->Password   = 'fqbr ocgu jcfh jwdy';  // App password
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('it.resourcestaff@gmail.com', 'MailBot - IT Support Specialist');
    $mail->isHTML(true);

    // Test recipient
    $mail->addAddress('it.resourcestaff@gmail.com', 'Test User');

    $mail->Subject = 'Test Email - ' . date('Y-m-d H:i:s');
    $mail->Body    = '
    <div style="font-family: Arial, sans-serif; padding: 20px;">
        <h2>Test Email</h2>
        <p>This is a test email to verify the email notification system is working.</p>
        <p>Sent at: ' . date('Y-m-d H:i:s') . '</p>
    </div>';

    $mail->send();
    echo "✅ Test email sent successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error sending test email: {$mail->ErrorInfo}\n";
}
?>