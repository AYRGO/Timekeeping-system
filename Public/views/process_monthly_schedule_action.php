<?php
// process_monthly_schedule_action.php
// Handle approve/decline actions for monthly schedule requests

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include('../config/db.php');
require_once '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Email sending function
function sendEmail($to, $name, $subject, $body) {
    $mail = new PHPMailer(true);
    
    try {
        // Debug mode - set to 0 for production
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer debug: [$level] $str");
        };
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'it.resourcestaff@gmail.com';
        $mail->Password = 'plpe ycwj ztqb kxqk';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->Timeout = 10; // 10 second timeout
        
        // SSL options
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        // Recipients
        $mail->setFrom('it.resourcestaff@gmail.com', 'Timekeeping System');
        $mail->addAddress($to, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        $mail->send();
        error_log("Email sent successfully to: $to");
        return true;
    } catch (Exception $e) {
        error_log("Email send failed: {$mail->ErrorInfo}");
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: schedule_request.php?view=monthly&error=" . urlencode("Invalid request method"));
    exit;
}

$request_id = $_POST['request_id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$request_id || !$action) {
    header("Location: schedule_request.php?view=monthly&error=" . urlencode("Missing required parameters"));
    exit;
}

try {
    if ($action === 'approve') {
        // Get the approved request FIRST to determine dates
        $requestStmt = $pdo->prepare("SELECT * FROM month_weekly_schedule WHERE id = ?");
        $requestStmt->execute([$request_id]);
        $request = $requestStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            header("Location: schedule_request.php?view=monthly&error=" . urlencode("Request not found"));
            exit;
        }
        
        $employee_id = $request['employee_id'];
        $year = $request['year'];
        $month = $request['month'];
        
        // Calculate date range
        $today = date('Y-m-d');
        $todayTimestamp = strtotime($today);
        $firstDayOfMonth = date('Y-m-d', strtotime("$year-$month-01"));
        $lastDayOfMonth = date('Y-m-t', strtotime("$year-$month-01"));
        
        // Determine start date: For FUTURE months, start from day 1. For CURRENT month, start from today.
        if (strtotime($firstDayOfMonth) > $todayTimestamp) {
            // Future month (e.g., December when we're in November)
            $startDate = $firstDayOfMonth;
            error_log("MONTHLY APPROVAL: Future month detected. Start date: $startDate");
        } elseif (strtotime($lastDayOfMonth) >= $todayTimestamp) {
            // Current month - start from today
            $startDate = $today;
            error_log("MONTHLY APPROVAL: Current month detected. Start date: $startDate");
        } else {
            // Past month - cannot approve
            header("Location: schedule_request.php?view=monthly&error=" . urlencode("Cannot approve schedule for past months"));
            exit;
        }
        
        error_log("MONTHLY APPROVAL: Processing request ID $request_id for employee $employee_id, month $year-$month");
        error_log("MONTHLY APPROVAL: Date range: $startDate to $lastDayOfMonth");
        
        // STEP 1: UPDATE employee_default_schedules (Weekly Pattern)
        // End the current weekly schedule by setting effective_until
        $endPreviousStmt = $pdo->prepare("
            UPDATE employee_default_schedules 
            SET effective_until = ?
            WHERE employee_id = ? 
              AND (effective_until IS NULL OR effective_until >= ?)
        ");
        $previousEndDate = date('Y-m-d', strtotime($startDate . ' -1 day'));
        $endPreviousStmt->execute([$previousEndDate, $employee_id, $startDate]);
        error_log("MONTHLY APPROVAL: Ended previous schedules with effective_until = $previousEndDate");
        
        // Insert new weekly schedule pattern (7 days)
        $weeklySchedule = [
            0 => ['schedule_id' => $request['sunday_schedule_id'], 'is_rest_day' => $request['sunday_is_rest_day']],
            1 => ['schedule_id' => $request['monday_schedule_id'], 'is_rest_day' => $request['monday_is_rest_day']],
            2 => ['schedule_id' => $request['tuesday_schedule_id'], 'is_rest_day' => $request['tuesday_is_rest_day']],
            3 => ['schedule_id' => $request['wednesday_schedule_id'], 'is_rest_day' => $request['wednesday_is_rest_day']],
            4 => ['schedule_id' => $request['thursday_schedule_id'], 'is_rest_day' => $request['thursday_is_rest_day']],
            5 => ['schedule_id' => $request['friday_schedule_id'], 'is_rest_day' => $request['friday_is_rest_day']],
            6 => ['schedule_id' => $request['saturday_schedule_id'], 'is_rest_day' => $request['saturday_is_rest_day']]
        ];
        
        $insertedCount = 0;
        foreach ($weeklySchedule as $dayOfWeek => $daySchedule) {
            $insertStmt = $pdo->prepare("
                INSERT INTO employee_default_schedules 
                (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from, effective_until, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $insertStmt->execute([
                $employee_id,
                $dayOfWeek,
                $daySchedule['schedule_id'],
                $daySchedule['is_rest_day'],
                $startDate,
                $lastDayOfMonth  // Set to end of month so it only applies for this month
            ]);
            $insertedCount++;
            error_log("MONTHLY APPROVAL: Inserted schedule for day $dayOfWeek (schedule_id: {$daySchedule['schedule_id']}, is_rest: {$daySchedule['is_rest_day']})");
        }
        error_log("MONTHLY APPROVAL: Inserted $insertedCount weekly schedule rows");
        
        // STEP 2: REBUILD employee_daily_schedule_cache for the affected date range
        // Clear existing cache entries for this date range
        $deleteStmt = $pdo->prepare("
            DELETE FROM employee_daily_schedule_cache 
            WHERE employee_id = ? 
              AND schedule_date BETWEEN ? AND ?
        ");
        $deleteStmt->execute([$employee_id, $startDate, $lastDayOfMonth]);
        $deletedRows = $deleteStmt->rowCount();
        error_log("MONTHLY APPROVAL: Deleted $deletedRows cache rows");
        
        // Rebuild cache with new schedule
        $currentDate = strtotime($startDate);
        $endDateTimestamp = strtotime($lastDayOfMonth);
        $cacheInsertCount = 0;
        
        while ($currentDate <= $endDateTimestamp) {
            $dateStr = date('Y-m-d', $currentDate);
            $dayOfWeek = (int)date('w', $currentDate);
            $daySchedule = $weeklySchedule[$dayOfWeek];
            
            if ($daySchedule['is_rest_day']) {
                // Insert rest day
                $pdo->prepare("
                    INSERT INTO employee_daily_schedule_cache 
                    (employee_id, schedule_date, work_schedule_id, is_rest_day, is_holiday, 
                     schedule_name, time_in, time_out, holiday_name, source, created_at, updated_at)
                    VALUES (?, ?, NULL, 1, 0, NULL, NULL, NULL, NULL, 'approved_monthly_request', NOW(), NOW())
                ")->execute([$employee_id, $dateStr]);
                $cacheInsertCount++;
            } else {
                // Insert work schedule
                $schedStmt = $pdo->prepare("SELECT name, time_in, time_out FROM work_schedules WHERE id = ?");
                $schedStmt->execute([$daySchedule['schedule_id']]);
                $schedInfo = $schedStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($schedInfo) {
                    $pdo->prepare("
                        INSERT INTO employee_daily_schedule_cache 
                        (employee_id, schedule_date, work_schedule_id, is_rest_day, is_holiday, 
                         schedule_name, time_in, time_out, holiday_name, source, created_at, updated_at)
                        VALUES (?, ?, ?, 0, 0, ?, ?, ?, NULL, 'approved_monthly_request', NOW(), NOW())
                    ")->execute([
                        $employee_id, $dateStr, $daySchedule['schedule_id'],
                        $schedInfo['name'], $schedInfo['time_in'], $schedInfo['time_out']
                    ]);
                    $cacheInsertCount++;
                }
            }
            
            $currentDate = strtotime('+1 day', $currentDate);
        }
        error_log("MONTHLY APPROVAL: Inserted $cacheInsertCount cache rows");
        
        // Update request status to approved and mark as processed
        $updateStmt = $pdo->prepare("
            UPDATE month_weekly_schedule 
            SET status = 'approved', 
                processed_by = ?,
                processed_at = NOW()
            WHERE id = ?
        ");
        $admin_id = $_SESSION['user_id'] ?? $_SESSION['employee']['id'] ?? null;
        $updateStmt->execute([$admin_id, $request_id]);
        error_log("MONTHLY APPROVAL: Request marked as approved and processed");
        
        // Send email notification
        error_log("MONTHLY APPROVAL: Attempting to send email notification for request ID $request_id");
        try {
            $empStmt = $pdo->prepare("SELECT fname, lname, personal_email FROM employees WHERE id = ?");
            $empStmt->execute([$employee_id]);
            $employee = $empStmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("MONTHLY APPROVAL: Employee data: " . json_encode($employee));
            
            if ($employee && !empty($employee['personal_email'])) {
                error_log("MONTHLY APPROVAL: Email address found: {$employee['personal_email']}");
                $subject = "Monthly Schedule Request Approved";
                $month_name = date('F Y', strtotime("$year-$month-01"));
                
                // Build schedule summary
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
                            <p><strong>Employee:</strong> {$employee['fname']} {$employee['lname']}</p>
                            <p><strong>Month:</strong> $month_name</p>
                            <p><strong>Request ID:</strong> #{$request_id}</p>
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
                
                sendEmail($employee['personal_email'], "{$employee['fname']} {$employee['lname']}", $subject, $body);
                error_log("MONTHLY APPROVAL: Email sent successfully to {$employee['personal_email']}");
            } else {
                error_log("MONTHLY APPROVAL: No email address found for employee ID $employee_id");
            }
        } catch (Exception $e) {
            error_log("MONTHLY APPROVAL: Failed to send email: " . $e->getMessage());
            error_log("MONTHLY APPROVAL: Stack trace: " . $e->getTraceAsString());
        }
        
        header("Location: schedule_request.php?view=monthly&message=" . urlencode("Monthly schedule approved! Weekly pattern updated for $year-$month."));
        
    } else if ($action === 'decline') {
        $explanation = $_POST['explanation'] ?? '';
        
        if (empty($explanation)) {
            header("Location: schedule_request.php?view=monthly&error=" . urlencode("Explanation is required for declining"));
            exit;
        }
        
        // Get request details for email
        $requestStmt = $pdo->prepare("SELECT employee_id, year, month FROM month_weekly_schedule WHERE id = ?");
        $requestStmt->execute([$request_id]);
        $request = $requestStmt->fetch(PDO::FETCH_ASSOC);
        
        // Update status to rejected
        $stmt = $pdo->prepare("
            UPDATE month_weekly_schedule 
            SET status = 'rejected',
                admin_notes = ?,
                processed_by = ?,
                processed_at = NOW()
            WHERE id = ?
        ");
        
        $admin_id = $_SESSION['user_id'] ?? $_SESSION['employee']['id'] ?? null;
        $stmt->execute([$explanation, $admin_id, $request_id]);
        
        // Send email notification
        error_log("MONTHLY DECLINE: Attempting to send email notification for request ID $request_id");
        if ($request) {
            try {
                $empStmt = $pdo->prepare("SELECT fname, lname, personal_email FROM employees WHERE id = ?");
                $empStmt->execute([$request['employee_id']]);
                $employee = $empStmt->fetch(PDO::FETCH_ASSOC);
                
                error_log("MONTHLY DECLINE: Employee data: " . json_encode($employee));
                
                if ($employee && !empty($employee['personal_email'])) {
                    error_log("MONTHLY DECLINE: Email address found: {$employee['personal_email']}");
                    $subject = "Monthly Schedule Request Declined";
                    $month_name = date('F Y', strtotime("{$request['year']}-{$request['month']}-01"));
                    
                    $body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f9fafb; padding: 20px;'>
                        <div style='background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                            <h2 style='color: #1f2937; margin-bottom: 20px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;'>Monthly Schedule Request Declined</h2>
                            
                            <div style='background: #f3f4f6; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
                                <h3 style='color: #374151; margin: 0 0 10px 0;'>📋 Request Details</h3>
                                <p><strong>Employee:</strong> {$employee['fname']} {$employee['lname']}</p>
                                <p><strong>Month:</strong> $month_name</p>
                                <p><strong>Request ID:</strong> #{$request_id}</p>
                            </div>
                            
                            <div style='background: #fef2f2; padding: 15px; border-radius: 8px; border-left: 4px solid #ef4444; margin-bottom: 20px;'>
                                <h3 style='color: #991b1b; margin: 0 0 10px 0;'>❌ Status: Declined</h3>
                            </div>
                            
                            <div style='background: #fef2f2; padding: 15px; border-radius: 8px; border-left: 4px solid #ef4444; margin-bottom: 20px;'>
                                <h3 style='color: #991b1b; margin: 0 0 10px 0;'>📝 Admin Explanation</h3>
                                <p style='color: #374151;'>" . nl2br(htmlspecialchars($explanation)) . "</p>
                            </div>
                            
                            <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;'>
                                <p style='color: #6b7280; font-size: 14px;'>This is an automated notification from the Timekeeping System</p>
                                <p style='color: #6b7280; font-size: 12px;'>Please do not reply to this email</p>
                            </div>
                        </div>
                    </div>";
                    
                    sendEmail($employee['personal_email'], "{$employee['fname']} {$employee['lname']}", $subject, $body);
                    error_log("MONTHLY DECLINE: Email sent successfully to {$employee['personal_email']}");
                } else {
                    error_log("MONTHLY DECLINE: No email address found for employee ID {$request['employee_id']}");
                }
            } catch (Exception $e) {
                error_log("MONTHLY DECLINE: Failed to send email: " . $e->getMessage());
                error_log("MONTHLY DECLINE: Stack trace: " . $e->getTraceAsString());
            }
        }
        
        header("Location: schedule_request.php?view=monthly&message=" . urlencode("Monthly schedule request declined"));
        
    } else {
        header("Location: schedule_request.php?view=monthly&error=" . urlencode("Invalid action"));
    }
    
} catch (PDOException $e) {
    error_log("Monthly schedule action error: " . $e->getMessage());
    header("Location: schedule_request.php?view=monthly&error=" . urlencode("Database error: " . $e->getMessage()));
}

exit;
