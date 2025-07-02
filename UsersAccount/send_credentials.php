<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // PHPMailer autoload
require '../Public/config/db.php';

try {
    // Fetch all employees with a valid email
    $query = $pdo->query("SELECT fname, lname, personal_email, username, password FROM employees WHERE personal_email IS NOT NULL AND personal_email != ''");
    $employees = $query->fetchAll(PDO::FETCH_ASSOC);

    if (!$employees) {
        die("No employees found with valid personal emails.");
    }

    foreach ($employees as $emp) {
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

        // Send to the employee's own email
        $mail->addAddress($emp['personal_email'], $emp['fname'] . ' ' . $emp['lname']);

        // Email body (same content — do not edit)
        $body = <<<HTML
<div style="font-family: Arial, sans-serif; color: #333; padding: 20px; line-height: 1.6;">
  <img src="https://timekeeping-system.resourcestaffonline.com/asset/RSS-logo-colour.png" alt="Company Logo" style="max-width: 150px; margin-bottom: 20px;" />

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
    <a href="https://timekeeping-system.resourcestaffonline.com/employee/login" style="color: #1e40af;">https://timekeeping-system.resourcestaffonline.com/employee/login</a>
  </p>

   <p style="margin-top: 20px;">
  To help you get started, please watch the tutorial video below:<br>
  <a href="https://www.dropbox.com/scl/fi/wdyyl97noof3qd0nzxp6m/Timekeeping-System-Tutorial.mp4?rlkey=xnq5kg6urn9qv5kqc3l5o41j7&st=n5uij8ze&dl=0" style="color: #1e40af;" target="_blank">
    📽️ Watch Tutorial Video
  </a>
</p>

  <p>If you have any issues accessing the platform, please do not hesitate to reach out me.</p>

  <p style="margin-top: 10px;">Cheers,</p>

  <table style="margin-top: 30px; font-family: Arial, sans-serif; color: #333;">
    <tr>
      <td style="vertical-align: top; padding-right: 10px;">
        <img src="https://timekeeping-system.resourcestaffonline.com/asset/RSS-logo-colour.png" alt="RSS Logo" style="height: 60px;" />
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

        $mail->Subject = 'Welcome to Resourcestaff Time-keeping System';
        $mail->Body    = $body;

        try {
            $mail->send();
            echo "✅ Email sent to: {$emp['personal_email']} ({$emp['fname']} {$emp['lname']})\n";
        } catch (Exception $e) {
            echo "❌ Failed to send to {$emp['personal_email']}: {$mail->ErrorInfo}\n";
        }
    }
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}