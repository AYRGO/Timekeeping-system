<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // PHPMailer autoload
require '../Public/config/db.php';

// Fetch employees with valid emails
$query = $pdo->query("SELECT fname, lname, email, username, password FROM employees WHERE email IS NOT NULL AND email != ''");
$employees = $query->fetchAll(PDO::FETCH_ASSOC);

if (!$employees) {
    die("No employees found with valid email.");
}

$mail = new PHPMailer(true);

try {
    // SMTP Settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.yourmailserver.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'your_email@example.com';
    $mail->Password   = 'your_password';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('your_email@example.com', 'ResourceStaff Management');
    $mail->isHTML(true); // Enable HTML

foreach ($employees as $emp) {
    // Build email body content
   $body = <<<HTML
<div style="font-family: Arial, sans-serif; color: #333; padding: 20px; line-height: 1.6;">
  <img src="../Public/asset/RSS-logo-colour.png" alt="Company Logo" style="max-width: 150px; margin-bottom: 20px;" />

  <p>Hi {$emp['fname']} {$emp['lname']},</p>

  <p>We are pleased to welcome you to <strong>Resourcestaff Time-keeping System</strong>. Your account has been successfully created, and you may now access the system using the login credentials provided below:</p>

  <table style="border-collapse: collapse; margin-top: 15px;">
    <tr>
      <td style="padding: 8px 12px; font-weight: bold;">Username:</td>
      <td style="padding: 8px 12px;">{$emp['username']}</td>
    </tr>
    <tr>
      <td style="padding: 8px 12px; font-weight: bold;">Password:</td>
      <td style="padding: 8px 12px;">{$emp['password']}</td>
    </tr>
  </table>

  <p style="margin-top: 20px;">
    You can log in via the following link:<br>
    <a href="https://your-domain.com/login" style="color: #1e40af;">https://your-domain.com/login</a>
  </p>

    <!-- QR Code Section
  <div style="margin: 20px 0;">
    <p style="margin-bottom: 5px;">Scan the QR code to access the login page:</p>
    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://your-domain.com/login" alt="QR Code" />
  </div> -->

  <p>If you have any issues accessing the platform, please do not hesitate to reach out to our IT support team.</p>

  <p style="margin-top: 10px;">Cheers,</p>

  <!-- Signature Block -->
  <table style="margin-top: 30px; font-family: Arial, sans-serif; color: #333;">
    <tr>
      <td style="vertical-align: top; padding-right: 10px;">
        <img src="../Public/asset/RSS-logo-colour.png" alt="RSS Logo" style="height: 60px;" />
      </td>
      <td style="border-left: 2px solid #00bcd4; padding-left: 10px;">
        <strong>Cedrick Arnigo</strong><br>
        IT Support Specialist<br>
        +63 993 864 2974<br>
        <a href="mailto:IT@resourcestaff.com.ph" style="color: #1e40af;">IT@resourcestaff.com.ph</a>
      </td>
    </tr>
  </table>

  <hr style="margin-top: 30px; border: none; border-top: 1px solid #ccc;" />
  <p style="font-size: 12px; color: #888;">
    This is an automated message. Please do not reply directly to this email.
  </p>
</div>
HTML;


    // Output the email to browser instead of sending
    echo $body;
    break; // Remove this line if you want to preview all employee emails
}
    echo "All credentials have been sent successfully.";
} catch (Exception $e) {
    echo "Failed to send: {$mail->ErrorInfo}";
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}
