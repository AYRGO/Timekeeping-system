<?php
include('Public/config/db.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

function sendTestEmail($to, $name, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        // Enable debug logging
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str, $level) {
            echo "PHPMailer debug: [$level] $str\n";
        };
        
        $mail->CharSet    = 'UTF-8';
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'it.resourcestaff@gmail.com';
        $mail->Password   = 'plpe ycwj ztqb kxqk';  
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Ensure proper SSL verification
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false
            ]
        ];

        $mail->setFrom('it.resourcestaff@gmail.com', 'MailBot - IT Support Specialist');
        $mail->addAddress($to, $name);

        $mail->isHTML(true);
        $mail->Subject = htmlspecialchars($subject);
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Failed to send email to {$to} - Error: {$mail->ErrorInfo}\n";
        echo "Message that failed: {$subject}\n";
        echo "Error details: " . $e->getMessage() . "\n";
        return false;
    }
}

echo "=== TESTING EMAIL NOTIFICATION SYSTEM ===\n\n";

// Test 1: Send a direct test email
echo "Test 1: Sending direct test email...\n";
$result = sendTestEmail(
    'vincentvinz17@gmail.com', 
    'Vincent Kevin Santos', 
    'Test Notification System', 
    '<p>Hi Vincent,<br>This is a test email to verify the notification system is working.</p>'
);
echo "Test email result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n\n";

// Test 2: Create a fake leave request and simulate approval
echo "Test 2: Simulating leave request approval...\n";

// Insert a test leave request with notified = 0
$insert = $pdo->prepare("
    INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, reason, status, notified, created_at) 
    VALUES (1, 'vacation', '2025-10-10', '2025-10-11', 'Testing notification system', 'approved', 0, NOW())
");

if ($insert->execute()) {
    $test_request_id = $pdo->lastInsertId();
    echo "Created test leave request with ID: $test_request_id\n";
    
    // Now simulate the notification logic from notification_modal.php
    $emp_stmt = $pdo->prepare("SELECT fname, lname, personal_email FROM employees WHERE id = 1");
    $emp_stmt->execute();
    $employee = $emp_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($employee) {
        echo "Employee found: {$employee['fname']} {$employee['lname']} ({$employee['personal_email']})\n";
        
        // Simulate email sending
        $subject = "Leave Request Approved";
        $body = "<p>Hi {$employee['fname']},<br>Your leave request from <strong>October 10 to October 11</strong> for <strong>vacation</strong> was <strong>Approved</strong>.</p>";
        
        echo "Attempting to send notification email...\n";
        if (sendTestEmail($employee['personal_email'], "{$employee['fname']} {$employee['lname']}", $subject, $body)) {
            // Update the notified flag
            $update = $pdo->prepare("UPDATE leave_requests SET notified = 1 WHERE id = ?");
            $result = $update->execute([$test_request_id]);
            echo "Notification email sent successfully!\n";
            echo "Notified flag updated: " . ($result ? 'Success' : 'Failed') . "\n";
        } else {
            echo "Failed to send notification email\n";
        }
    } else {
        echo "Employee not found\n";
    }
    
    // Clean up - delete the test request
    echo "Cleaning up test data...\n";
    $delete = $pdo->prepare("DELETE FROM leave_requests WHERE id = ?");
    $delete->execute([$test_request_id]);
    echo "Test request deleted\n";
    
} else {
    echo "Failed to create test leave request\n";
}

echo "\n=== TEST COMPLETED ===\n";
?>