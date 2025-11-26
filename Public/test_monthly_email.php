<?php
// Test email sending for monthly schedule requests
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../vendor/autoload.php';
include('config/db.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "<h2>Testing Monthly Schedule Email Notifications</h2>";

// Email sending function (same as in process_monthly_schedule_action.php)
function sendEmail($to, $name, $subject, $body) {
    $mail = new PHPMailer(true);
    
    try {
        // Debug
        $mail->SMTPDebug = 2; // Enable verbose debug output
        $mail->Debugoutput = function($str, $level) {
            echo "<pre>PHPMailer debug: [$level] $str</pre>";
        };
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'aljon.bernal13@gmail.com';
        $mail->Password = 'lwam kkru zprj upjl';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('aljon.bernal13@gmail.com', 'Timekeeping System');
        $mail->addAddress($to, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        $mail->send();
        echo "<p style='color: green;'>✅ Email sent successfully to: $to</p>";
        return true;
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Email send failed: {$mail->ErrorInfo}</p>";
        echo "<p style='color: red;'>Exception: {$e->getMessage()}</p>";
        return false;
    }
}

// Check for recent monthly schedule requests
$stmt = $pdo->prepare("
    SELECT mws.id, mws.employee_id, mws.status, mws.year, mws.month, mws.created_at,
           e.fname, e.lname, e.personal_email,
           mws.sunday_schedule_id, mws.sunday_is_rest_day,
           mws.monday_schedule_id, mws.monday_is_rest_day,
           mws.tuesday_schedule_id, mws.tuesday_is_rest_day,
           mws.wednesday_schedule_id, mws.wednesday_is_rest_day,
           mws.thursday_schedule_id, mws.thursday_is_rest_day,
           mws.friday_schedule_id, mws.friday_is_rest_day,
           mws.saturday_schedule_id, mws.saturday_is_rest_day
    FROM month_weekly_schedule mws
    JOIN employees e ON mws.employee_id = e.id
    ORDER BY mws.created_at DESC
    LIMIT 5
");
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Recent Monthly Schedule Requests:</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Employee</th><th>Email</th><th>Month</th><th>Status</th><th>Created</th><th>Test Email</th></tr>";

foreach ($requests as $request) {
    $month_name = date('F Y', strtotime("{$request['year']}-{$request['month']}-01"));
    echo "<tr>";
    echo "<td>{$request['id']}</td>";
    echo "<td>{$request['fname']} {$request['lname']}</td>";
    echo "<td>{$request['personal_email']}</td>";
    echo "<td>$month_name</td>";
    echo "<td>{$request['status']}</td>";
    echo "<td>" . date('M j, Y H:i', strtotime($request['created_at'])) . "</td>";
    echo "<td>";
    
    if (!empty($request['personal_email'])) {
        // Build schedule summary for approved email
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $schedule_summary = '';
        
        for ($i = 0; $i < 7; $i++) {
            $day_key = strtolower($days[$i]);
            $schedule_id = $request["{$day_key}_schedule_id"];
            $is_rest_day = $request["{$day_key}_is_rest_day"];
            
            if ($is_rest_day) {
                $schedule_summary .= "<tr><td style='padding: 8px; border: 1px solid #e5e7eb;'><strong>{$days[$i]}</strong></td><td style='padding: 8px; border: 1px solid #e5e7eb; background: #fef2f2; color: #991b1b;'>Rest Day</td></tr>";
            } else if ($schedule_id) {
                $schedStmt = $pdo->prepare("SELECT name, time_in, time_out FROM work_schedules WHERE id = ?");
                $schedStmt->execute([$schedule_id]);
                $schedInfo = $schedStmt->fetch(PDO::FETCH_ASSOC);
                if ($schedInfo) {
                    $schedule_summary .= "<tr><td style='padding: 8px; border: 1px solid #e5e7eb;'><strong>{$days[$i]}</strong></td><td style='padding: 8px; border: 1px solid #e5e7eb; background: #ecfdf5; color: #065f46;'>{$schedInfo['name']} (" . date('g:i A', strtotime($schedInfo['time_in'])) . " - " . date('g:i A', strtotime($schedInfo['time_out'])) . ")</td></tr>";
                }
            }
        }
        
        $subject = "Monthly Schedule Request Approved - TEST";
        $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f9fafb; padding: 20px;'>
            <div style='background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <h2 style='color: #1f2937; margin-bottom: 20px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;'>Monthly Schedule Request Approved (TEST)</h2>
                
                <div style='background: #f3f4f6; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
                    <h3 style='color: #374151; margin: 0 0 10px 0;'>📋 Request Details</h3>
                    <p><strong>Employee:</strong> {$request['fname']} {$request['lname']}</p>
                    <p><strong>Month:</strong> $month_name</p>
                    <p><strong>Request ID:</strong> #{$request['id']}</p>
                </div>
                
                <div style='background: #eff6ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
                    <h3 style='color: #1e40af; margin: 0 0 10px 0;'>📅 Weekly Schedule Pattern</h3>
                    <table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>
                        $schedule_summary
                    </table>
                </div>
                
                <div style='background: #ecfdf5; padding: 15px; border-radius: 8px; border-left: 4px solid #10b981; margin-bottom: 20px;'>
                    <h3 style='color: #065f46; margin: 0 0 10px 0;'>✅ Status: Approved (TEST)</h3>
                    <p style='color: #374151; margin: 0;'>This is a test email for monthly schedule approval!</p>
                </div>
                
                <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;'>
                    <p style='color: #6b7280; font-size: 14px;'>This is an automated notification from the Timekeeping System</p>
                    <p style='color: #6b7280; font-size: 12px;'>Please do not reply to this email</p>
                </div>
            </div>
        </div>";
        
        echo "<form method='post' style='display: inline;'>";
        echo "<input type='hidden' name='test_email' value='1'>";
        echo "<input type='hidden' name='email' value='{$request['personal_email']}'>";
        echo "<input type='hidden' name='name' value='{$request['fname']} {$request['lname']}'>";
        echo "<input type='hidden' name='subject' value='$subject'>";
        echo "<input type='hidden' name='body' value='" . htmlspecialchars($body) . "'>";
        echo "<button type='submit'>Send Test Email</button>";
        echo "</form>";
    } else {
        echo "No email on file";
    }
    
    echo "</td>";
    echo "</tr>";
}

echo "</table>";

// Process test email if form submitted
if (isset($_POST['test_email'])) {
    echo "<hr><h3>Sending Test Email...</h3>";
    $result = sendEmail(
        $_POST['email'],
        $_POST['name'],
        $_POST['subject'],
        $_POST['body']
    );
}

// Check error logs
echo "<hr><h3>Recent Error Logs (last 50 lines):</h3>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    $lines = file($error_log);
    $recent = array_slice($lines, -50);
    echo "<pre style='background: #f3f4f6; padding: 10px; border-radius: 5px; overflow-x: auto;'>";
    foreach ($recent as $line) {
        if (stripos($line, 'email') !== false || stripos($line, 'phpmailer') !== false || stripos($line, 'monthly') !== false) {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>";
} else {
    echo "<p>Error log location: " . ($error_log ? $error_log : "Not configured") . "</p>";
}

echo "<hr><h3>Check PHPMailer Installation:</h3>";
echo "<p>PHPMailer class exists: " . (class_exists('PHPMailer\PHPMailer\PHPMailer') ? '✅ Yes' : '❌ No') . "</p>";
echo "<p>Vendor autoload exists: " . (file_exists('../vendor/autoload.php') ? '✅ Yes' : '❌ No') . "</p>";
