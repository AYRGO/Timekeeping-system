<?php
session_start();
error_log("=== PROCESS SWITCH ACTION CALLED ===");
error_log("POST data: " . json_encode($_POST));
error_log("Session admin_id: " . ($_SESSION['admin_id'] ?? 'NOT SET'));
error_log("Session employee: " . json_encode($_SESSION['employee'] ?? 'NOT SET'));
error_log("Session view_mode: " . ($_SESSION['view_mode'] ?? 'NOT SET'));

include('../config/db.php');
require_once '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Email sending function
function sendEmail($to, $name, $subject, $body) {
    $mail = new PHPMailer(true);
    
    try {
        // Debug
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

// Check if admin is logged in (either admin_id or internal employee in admin view mode)
$is_admin = isset($_SESSION['admin_id']) || 
            (isset($_SESSION['employee']['role']) && $_SESSION['employee']['role'] === 'internal' && $_SESSION['view_mode'] === 'admin');

error_log("Is admin check result: " . ($is_admin ? 'TRUE' : 'FALSE'));

if (!$is_admin) {
    error_log("NOT ADMIN - Redirecting to admin_homepage.php");
    header('Location: admin_homepage.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log("=== POST REQUEST DETECTED ===");
    $request_id = $_POST['request_id'] ?? null;
    $action = $_POST['action'] ?? null;
    $explanation = $_POST['explanation'] ?? null;
    
    error_log("Request ID from POST: " . ($request_id ?? 'NULL'));
    error_log("Action from POST: " . ($action ?? 'NULL'));
    
    // Get admin ID (either from admin_id or employee id)
    $admin_id = $_SESSION['admin_id'] ?? $_SESSION['employee']['id'] ?? null;
    error_log("Admin ID resolved to: " . ($admin_id ?? 'NULL'));

    if (!$request_id || !$action) {
        error_log("ERROR: Missing request_id or action - Redirecting");
        header('Location: schedule_request.php?view=swap&error=' . urlencode('Invalid request.'));
        exit();
    }

    error_log("=== ATTEMPTING TO FETCH REQUEST FROM DATABASE ===");
    
    try {
        // Get the swap request details
        $stmt = $pdo->prepare("
            SELECT employee_id, source_date, target_date, status 
            FROM schedule_switch_requests 
            WHERE id = ?
        ");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        error_log("Database query result: " . json_encode($request));

        if (!$request) {
            error_log("ERROR: Request not found in database");
            header('Location: schedule_request.php?view=swap&error=' . urlencode('Request not found.'));
            exit();
        }

        error_log("Request status from DB: " . $request['status']);
        
        if (strtolower($request['status']) !== 'pending') {
            error_log("ERROR: Request status is not pending, it is: " . $request['status']);
            header('Location: schedule_request.php?view=swap&error=' . urlencode('Request has already been processed.'));
            exit();
        }

        $employee_id = $request['employee_id'];
        $source_date = $request['source_date'];
        $target_date = $request['target_date'];

        if ($action === 'approve') {
            // Log approval attempt
            error_log("=== SWAP APPROVAL START ===");
            error_log("Request ID: $request_id");
            error_log("Employee ID: $employee_id");
            error_log("Source Date: $source_date");
            error_log("Target Date: $target_date");
            
            // Get schedules for both dates
            $stmt = $pdo->prepare("
                SELECT work_schedule_id, is_rest_day, schedule_name, time_in, time_out
                FROM employee_daily_schedule_cache
                WHERE employee_id = ? AND schedule_date = ?
            ");
            
            // Get source date schedule
            $stmt->execute([$employee_id, $source_date]);
            $sourceSchedule = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Source schedule: " . json_encode($sourceSchedule));
            
            // Get target date schedule
            $stmt->execute([$employee_id, $target_date]);
            $targetSchedule = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Target schedule: " . json_encode($targetSchedule));

            if (!$sourceSchedule || !$targetSchedule) {
                error_log("ERROR: Schedule not found - Source: " . ($sourceSchedule ? 'found' : 'NOT FOUND') . ", Target: " . ($targetSchedule ? 'found' : 'NOT FOUND'));
                header('Location: schedule_request.php?view=swap&error=' . urlencode('One or both schedules not found in cache.'));
                exit();
            }

            // Begin transaction to swap schedules atomically
            $pdo->beginTransaction();

            try {
                // Update source date with target schedule
                $updateStmt = $pdo->prepare("
                    UPDATE employee_daily_schedule_cache 
                    SET work_schedule_id = ?, 
                        is_rest_day = ?, 
                        schedule_name = ?, 
                        time_in = ?, 
                        time_out = ?
                    WHERE employee_id = ? AND schedule_date = ?
                ");
                $result1 = $updateStmt->execute([
                    $targetSchedule['work_schedule_id'],
                    $targetSchedule['is_rest_day'],
                    $targetSchedule['schedule_name'],
                    $targetSchedule['time_in'],
                    $targetSchedule['time_out'],
                    $employee_id,
                    $source_date
                ]);
                error_log("Updated source date ($source_date): " . ($result1 ? "SUCCESS" : "FAILED") . " - Rows affected: " . $updateStmt->rowCount());

                // Update target date with source schedule
                $result2 = $updateStmt->execute([
                    $sourceSchedule['work_schedule_id'],
                    $sourceSchedule['is_rest_day'],
                    $sourceSchedule['schedule_name'],
                    $sourceSchedule['time_in'],
                    $sourceSchedule['time_out'],
                    $employee_id,
                    $target_date
                ]);
                error_log("Updated target date ($target_date): " . ($result2 ? "SUCCESS" : "FAILED") . " - Rows affected: " . $updateStmt->rowCount());

                // Update request status to approved
                $statusStmt = $pdo->prepare("
                    UPDATE schedule_switch_requests 
                    SET status = 'Approved', 
                        processed_at = NOW(), 
                        processed_by = ?,
                        admin_notes = 'Schedules successfully swapped'
                    WHERE id = ?
                ");
                $result3 = $statusStmt->execute([$admin_id, $request_id]);
                error_log("Updated request status: " . ($result3 ? "SUCCESS" : "FAILED") . " - Rows affected: " . $statusStmt->rowCount());

                $pdo->commit();
                error_log("=== SWAP APPROVAL COMPLETE - TRANSACTION COMMITTED ===");

                // Send email notification
                try {
                    $empStmt = $pdo->prepare("SELECT fname, lname, personal_email FROM employees WHERE id = ?");
                    $empStmt->execute([$employee_id]);
                    $employee = $empStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($employee && !empty($employee['personal_email'])) {
                        $subject = "Schedule Swap Request Approved";
                        $source_formatted = date('F j, Y', strtotime($source_date));
                        $target_formatted = date('F j, Y', strtotime($target_date));
                        
                        $body = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f9fafb; padding: 20px;'>
                            <div style='background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                                <h2 style='color: #1f2937; margin-bottom: 20px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;'>Schedule Swap Request Approved</h2>
                                
                                <div style='background: #f3f4f6; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
                                    <h3 style='color: #374151; margin: 0 0 10px 0;'>🔄 Swap Details</h3>
                                    <p><strong>Employee:</strong> {$employee['fname']} {$employee['lname']}</p>
                                    <p><strong>Request ID:</strong> #{$request_id}</p>
                                </div>
                                
                                <div style='background: #eff6ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
                                    <h3 style='color: #1e40af; margin: 0 0 10px 0;'>📅 Schedule Swap</h3>
                                    <div style='margin: 10px 0;'>
                                        <strong>Date A:</strong> $source_formatted<br>
                                        <span style='background: #dbeafe; padding: 5px 10px; border-radius: 5px; margin-top: 5px; display: inline-block;'>{$sourceSchedule['schedule_name']} ({$sourceSchedule['time_in']} - {$sourceSchedule['time_out']})</span>
                                    </div>
                                    <div style='text-align: center; margin: 15px 0;'>
                                        <span style='font-size: 24px;'>⇅</span>
                                    </div>
                                    <div style='margin: 10px 0;'>
                                        <strong>Date B:</strong> $target_formatted<br>
                                        <span style='background: #dbeafe; padding: 5px 10px; border-radius: 5px; margin-top: 5px; display: inline-block;'>{$targetSchedule['schedule_name']} ({$targetSchedule['time_in']} - {$targetSchedule['time_out']})</span>
                                    </div>
                                </div>
                                
                                <div style='background: #ecfdf5; padding: 15px; border-radius: 8px; border-left: 4px solid #10b981; margin-bottom: 20px;'>
                                    <h3 style='color: #065f46; margin: 0 0 10px 0;'>✅ Status: Approved</h3>
                                    <p style='color: #374151; margin: 0;'>Your schedules have been successfully swapped!</p>
                                </div>
                                
                                <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;'>
                                    <p style='color: #6b7280; font-size: 14px;'>This is an automated notification from the Timekeeping System</p>
                                    <p style='color: #6b7280; font-size: 12px;'>Please do not reply to this email</p>
                                </div>
                            </div>
                        </div>";
                        
                        sendEmail($employee['personal_email'], "{$employee['fname']} {$employee['lname']}", $subject, $body);
                    }
                } catch (Exception $e) {
                    error_log("Failed to send email: " . $e->getMessage());
                }

                header('Location: schedule_request.php?view=swap&message=' . urlencode('Schedule swap approved and applied successfully!'));
                exit();

            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("ERROR during swap: " . $e->getMessage());
                error_log("=== SWAP APPROVAL FAILED - TRANSACTION ROLLED BACK ===");
                header('Location: schedule_request.php?view=swap&error=' . urlencode('Error swapping schedules: ' . $e->getMessage()));
                exit();
            }

        } elseif ($action === 'decline') {
            if (!$explanation) {
                header('Location: schedule_request.php?view=swap&error=' . urlencode('Explanation is required for declining.'));
                exit();
            }

            // Update request status to rejected
            $stmt = $pdo->prepare("
                UPDATE schedule_switch_requests 
                SET status = 'Rejected', 
                    processed_at = NOW(), 
                    processed_by = ?,
                    admin_notes = ?
                WHERE id = ?
            ");
            $stmt->execute([$admin_id, $explanation, $request_id]);

            // Send email notification
            try {
                $empStmt = $pdo->prepare("SELECT fname, lname, personal_email FROM employees WHERE id = ?");
                $empStmt->execute([$employee_id]);
                $employee = $empStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($employee && !empty($employee['personal_email'])) {
                    $subject = "Schedule Swap Request Declined";
                    $source_formatted = date('F j, Y', strtotime($source_date));
                    $target_formatted = date('F j, Y', strtotime($target_date));
                    
                    $body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f9fafb; padding: 20px;'>
                        <div style='background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                            <h2 style='color: #1f2937; margin-bottom: 20px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;'>Schedule Swap Request Declined</h2>
                            
                            <div style='background: #f3f4f6; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
                                <h3 style='color: #374151; margin: 0 0 10px 0;'>🔄 Swap Details</h3>
                                <p><strong>Employee:</strong> {$employee['fname']} {$employee['lname']}</p>
                                <p><strong>Request ID:</strong> #{$request_id}</p>
                            </div>
                            
                            <div style='background: #eff6ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
                                <h3 style='color: #1e40af; margin: 0 0 10px 0;'>📅 Requested Swap</h3>
                                <p><strong>Date A:</strong> $source_formatted</p>
                                <p><strong>Date B:</strong> $target_formatted</p>
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
                }
            } catch (Exception $e) {
                error_log("Failed to send email: " . $e->getMessage());
            }

            header('Location: schedule_request.php?view=swap&message=' . urlencode('Schedule swap request declined.'));
            exit();

        } else {
            header('Location: schedule_request.php?view=swap&error=' . urlencode('Invalid action.'));
            exit();
        }

    } catch (PDOException $e) {
        header('Location: schedule_request.php?view=swap&error=' . urlencode('Database error: ' . $e->getMessage()));
        exit();
    }

} else {
    header('Location: schedule_request.php?view=swap');
    exit();
}
?>
