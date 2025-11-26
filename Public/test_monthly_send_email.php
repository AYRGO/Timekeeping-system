<?php
// Manual test to send a monthly schedule email
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../vendor/autoload.php';
include('config/db.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Email sending function (same as in process_monthly_schedule_action.php)
function sendEmail($to, $name, $subject, $body) {
    $mail = new PHPMailer(true);
    
    try {
        // Debug
        $mail->SMTPDebug = 2; // Enable verbose debug output
        $mail->Debugoutput = function($str, $level) {
            echo "<pre style='background: #f0f0f0; padding: 5px;'>PHPMailer debug: [$level] $str</pre>";
        };
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'it.resourcestaff@gmail.com';
        $mail->Password = 'plpe ycwj ztqb kxqk';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('it.resourcestaff@gmail.com', 'Timekeeping System');
        $mail->addAddress($to, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        $mail->send();
        echo "<p style='color: green; font-weight: bold;'>✅ Email sent successfully to: $to</p>";
        return true;
    } catch (Exception $e) {
        echo "<p style='color: red; font-weight: bold;'>❌ Email send failed: {$mail->ErrorInfo}</p>";
        echo "<p style='color: red;'>Exception: {$e->getMessage()}</p>";
        echo "<pre style='color: red;'>Stack trace: {$e->getTraceAsString()}</pre>";
        return false;
    }
}

echo "<h2>Manual Email Test for Monthly Schedule Request #8</h2>";

// Get request #8 details
$stmt = $pdo->prepare("
    SELECT mws.*, e.fname, e.lname, e.personal_email
    FROM month_weekly_schedule mws
    JOIN employees e ON mws.employee_id = e.id
    WHERE mws.id = 8
");
$stmt->execute();
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    die("Request #8 not found!");
}

echo "<h3>Request Details:</h3>";
echo "<ul>";
echo "<li><strong>Employee:</strong> {$request['fname']} {$request['lname']}</li>";
echo "<li><strong>Email:</strong> {$request['personal_email']}</li>";
echo "<li><strong>Month:</strong> " . date('F Y', strtotime("{$request['year']}-{$request['month']}-01")) . "</li>";
echo "<li><strong>Status:</strong> {$request['status']}</li>";
echo "</ul>";

// Build email exactly as done in process_monthly_schedule_action.php
$subject = "Monthly Schedule Request Approved";
$month_name = date('F Y', strtotime("{$request['year']}-{$request['month']}-01"));

$weeklySchedule = [
    0 => ['schedule_id' => $request['sunday_schedule_id'], 'is_rest_day' => $request['sunday_is_rest_day']],
    1 => ['schedule_id' => $request['monday_schedule_id'], 'is_rest_day' => $request['monday_is_rest_day']],
    2 => ['schedule_id' => $request['tuesday_schedule_id'], 'is_rest_day' => $request['tuesday_is_rest_day']],
    3 => ['schedule_id' => $request['wednesday_schedule_id'], 'is_rest_day' => $request['wednesday_is_rest_day']],
    4 => ['schedule_id' => $request['thursday_schedule_id'], 'is_rest_day' => $request['thursday_is_rest_day']],
    5 => ['schedule_id' => $request['friday_schedule_id'], 'is_rest_day' => $request['friday_is_rest_day']],
    6 => ['schedule_id' => $request['saturday_schedule_id'], 'is_rest_day' => $request['saturday_is_rest_day']]
];

$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$schedule_summary = '';

for ($i = 0; $i < 7; $i++) {
    $daySchedule = $weeklySchedule[$i];
    if ($daySchedule['is_rest_day']) {
        $schedule_summary .= "<tr><td style='padding: 8px; border: 1px solid #e5e7eb;'><strong>{$days[$i]}</strong></td><td style='padding: 8px; border: 1px solid #e5e7eb; background: #fef2f2; color: #991b1b;'>Rest Day</td></tr>";
    } else if ($daySchedule['schedule_id']) {
        $schedStmt = $pdo->prepare("SELECT name, time_in, time_out FROM work_schedules WHERE id = ?");
        $schedStmt->execute([$daySchedule['schedule_id']]);
        $schedInfo = $schedStmt->fetch(PDO::FETCH_ASSOC);
        if ($schedInfo) {
            $schedule_summary .= "<tr><td style='padding: 8px; border: 1px solid #e5e7eb;'><strong>{$days[$i]}</strong></td><td style='padding: 8px; border: 1px solid #e5e7eb; background: #ecfdf5; color: #065f46;'>{$schedInfo['name']} ({$schedInfo['time_in']} - {$schedInfo['time_out']})</td></tr>";
        }
    }
}

$body = "
<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f9fafb; padding: 20px;'>
    <div style='background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
        <h2 style='color: #1f2937; margin-bottom: 20px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;'>Monthly Schedule Request Approved</h2>
        
        <div style='background: #f3f4f6; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
            <h3 style='color: #374151; margin: 0 0 10px 0;'>Request Details</h3>
            <p><strong>Employee:</strong> {$request['fname']} {$request['lname']}</p>
            <p><strong>Month:</strong> $month_name</p>
            <p><strong>Request ID:</strong> #{$request['id']}</p>
        </div>
        
        <div style='background: #eff6ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
            <h3 style='color: #1e40af; margin: 0 0 10px 0;'>Weekly Schedule Pattern</h3>
            <table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>
                $schedule_summary
            </table>
        </div>
        
        <div style='background: #ecfdf5; padding: 15px; border-radius: 8px; border-left: 4px solid #10b981; margin-bottom: 20px;'>
            <h3 style='color: #065f46; margin: 0 0 10px 0;'>Status: Approved</h3>
            <p style='color: #374151; margin: 0;'>Your monthly schedule has been applied for $month_name!</p>
        </div>
        
        <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;'>
            <p style='color: #6b7280; font-size: 14px;'>This is an automated notification from the Timekeeping System</p>
            <p style='color: #6b7280; font-size: 12px;'>Please do not reply to this email</p>
        </div>
    </div>
</div>";

echo "<hr><h3>Sending Email...</h3>";
$result = sendEmail($request['personal_email'], "{$request['fname']} {$request['lname']}", $subject, $body);

if ($result) {
    echo "<h3 style='color: green;'>✅ SUCCESS! Check your inbox at {$request['personal_email']}</h3>";
} else {
    echo "<h3 style='color: red;'>❌ FAILED! Check the error messages above</h3>";
}
