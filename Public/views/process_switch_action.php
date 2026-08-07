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
                        
                        // Convert times to 12-hour format with AM/PM
                        $source_time_in = date('g:i A', strtotime($sourceSchedule['time_in']));
                        $source_time_out = date('g:i A', strtotime($sourceSchedule['time_out']));
                        $target_time_in = date('g:i A', strtotime($targetSchedule['time_in']));
                        $target_time_out = date('g:i A', strtotime($targetSchedule['time_out']));
                        
                        $body = "
                        <div style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif; max-width: 650px; margin: 0 auto; background: #f8fafc; padding: 0;'>
                            <!-- Header with RSS Branding -->
                            <div style='background: linear-gradient(135deg, #14b8a6 0%, #06b6d4 100%); padding: 40px 30px; text-align: center;'>
                                <div style='background: white; width: 100px; height: 100px; margin: 0 auto 20px; border-radius: 50%; display: inline-block; line-height: 100px; box-shadow: 0 4px 20px rgba(0,0,0,0.15);'>
                                    <span style='font-family: Arial, sans-serif; font-size: 36px; font-weight: bold; color: #14b8a6; letter-spacing: -2px;'>RSS</span>
                                </div>
                                <h1 style='color: white; margin: 0; font-size: 28px; font-weight: 600; letter-spacing: -0.5px;'>Resource Staff Solutions</h1>
                                <p style='color: rgba(255,255,255,0.9); margin: 8px 0 0 0; font-size: 14px;'>Timekeeping & HR Management System</p>
                            </div>
                            
                            <!-- Main Content -->
                            <div style='background: white; padding: 40px 30px;'>
                                <div style='text-align: center; margin-bottom: 30px;'>
                                    <div style='display: inline-block; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 12px 30px; border-radius: 50px; font-size: 18px; font-weight: 600; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);'>
                                        🔄 Schedule Swap Approved
                                    </div>
                                </div>
                                
                                <p style='color: #475569; font-size: 16px; line-height: 1.6; margin: 0 0 30px 0;'>Hi <strong>{$employee['fname']}</strong>,</p>
                                <p style='color: #475569; font-size: 16px; line-height: 1.6; margin: 0 0 30px 0;'>Your schedule swap request has been <strong>Approved</strong>. Your schedules have been successfully swapped!</p>
                                
                                <div style='background: #eff6ff; padding: 20px; border-radius: 12px; border-left: 5px solid #0ea5e9; margin-bottom: 25px;'>
                                    <h3 style='color: #075985; margin: 0 0 15px 0; font-size: 15px; font-weight: 600;'>📅 Schedule Swap</h3>
                                    <div style='margin: 15px 0;'>
                                        <p style='margin: 0 0 8px 0; color: #64748b; font-size: 14px; font-weight: 600;'>Date A: <span style='color: #0f172a;'>$source_formatted</span></p>
                                        <div style='background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); padding: 12px 15px; border-radius: 8px; color: #0c4a6e; font-weight: 600;'>{$sourceSchedule['schedule_name']}<br><span style='font-size: 13px;'>$source_time_in - $source_time_out</span></div>
                                    </div>
                                    <div style='text-align: center; margin: 20px 0;'>
                                        <span style='font-size: 28px; color: #14b8a6;'>⇅</span>
                                    </div>
                                    <div style='margin: 15px 0;'>
                                        <p style='margin: 0 0 8px 0; color: #64748b; font-size: 14px; font-weight: 600;'>Date B: <span style='color: #0f172a;'>$target_formatted</span></p>
                                        <div style='background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); padding: 12px 15px; border-radius: 8px; color: #0c4a6e; font-weight: 600;'>{$targetSchedule['schedule_name']}<br><span style='font-size: 13px;'>$target_time_in - $target_time_out</span></div>
                                    </div>
                                </div>
                                
                                <p style='color: #64748b; font-size: 14px; line-height: 1.6; margin: 25px 0 0 0; text-align: center;'>Thank you for using the Resource Staff Solutions Timekeeping System.</p>
                            </div>
                            
                            <!-- Footer -->
                            <div style='background: #0f172a; padding: 30px; text-align: center; color: white;'>
                                <p style='margin: 0 0 10px 0; font-size: 14px; font-weight: 600; color: #14b8a6;'>Resource Staff Solutions</p>
                                <p style='margin: 0 0 5px 0; font-size: 13px; color: #94a3b8;'>This is an automated notification from the Timekeeping System</p>
                                <p style='margin: 0; font-size: 12px; color: #64748b;'>Please do not reply to this email</p>
                                <div style='margin-top: 20px; padding-top: 20px; border-top: 1px solid #334155;'>
                                    <p style='margin: 0; font-size: 11px; color: #64748b;'>© " . date('Y') . " Resource Staff Solutions. All rights reserved.</p>
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
                    <div style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif; max-width: 650px; margin: 0 auto; background: #f8fafc; padding: 0;'>
                        <!-- Header with RSS Branding -->
                        <div style='background: linear-gradient(135deg, #14b8a6 0%, #06b6d4 100%); padding: 40px 30px; text-align: center;'>
                            <div style='background: white; width: 100px; height: 100px; margin: 0 auto 20px; border-radius: 50%; display: inline-block; line-height: 100px; box-shadow: 0 4px 20px rgba(0,0,0,0.15);'>
                                <span style='font-family: Arial, sans-serif; font-size: 36px; font-weight: bold; color: #14b8a6; letter-spacing: -2px;'>RSS</span>
                            </div>
                            <h1 style='color: white; margin: 0; font-size: 28px; font-weight: 600; letter-spacing: -0.5px;'>Resource Staff Solutions</h1>
                            <p style='color: rgba(255,255,255,0.9); margin: 8px 0 0 0; font-size: 14px;'>Timekeeping & HR Management System</p>
                        </div>
                        
                        <!-- Main Content -->
                        <div style='background: white; padding: 40px 30px;'>
                            <div style='text-align: center; margin-bottom: 30px;'>
                                <div style='display: inline-block; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 12px 30px; border-radius: 50px; font-size: 18px; font-weight: 600; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);'>
                                    🔄 Schedule Swap Declined
                                </div>
                            </div>
                            
                            <p style='color: #475569; font-size: 16px; line-height: 1.6; margin: 0 0 30px 0;'>Hi <strong>{$employee['fname']}</strong>,</p>
                            <p style='color: #475569; font-size: 16px; line-height: 1.6; margin: 0 0 30px 0;'>Your schedule swap request has been <strong>Declined</strong>. Please review the explanation below.</p>
                            
                            <div style='background: #eff6ff; padding: 20px; border-radius: 12px; border-left: 5px solid #0ea5e9; margin-bottom: 25px;'>
                                <h3 style='color: #075985; margin: 0 0 15px 0; font-size: 15px; font-weight: 600;'>📅 Requested Swap</h3>
                                <table style='width: 100%; border-collapse: collapse;'>
                                    <tr><td style='padding: 8px 0; color: #64748b; font-size: 14px;'>Date A:</td><td style='padding: 8px 0; color: #0f172a; font-weight: 600; font-size: 14px;'>$source_formatted</td></tr>
                                    <tr><td style='padding: 8px 0; color: #64748b; font-size: 14px;'>Date B:</td><td style='padding: 8px 0; color: #0f172a; font-weight: 600; font-size: 14px;'>$target_formatted</td></tr>
                                </table>
                            </div>
                            
                            <div style='background: #fef2f2; padding: 20px; border-radius: 12px; border-left: 5px solid #ef4444; margin-bottom: 25px;'>
                                <h3 style='color: #991b1b; margin: 0 0 12px 0; font-size: 15px; font-weight: 600;'>📝 Admin Explanation</h3>
                                <p style='color: #334155; line-height: 1.6; margin: 0; font-size: 14px;'>" . nl2br(htmlspecialchars($explanation)) . "</p>
                            </div>
                            
                            <p style='color: #64748b; font-size: 14px; line-height: 1.6; margin: 25px 0 0 0; text-align: center;'>Thank you for using the Resource Staff Solutions Timekeeping System.</p>
                        </div>
                        
                        <!-- Footer -->
                        <div style='background: #0f172a; padding: 30px; text-align: center; color: white;'>
                            <p style='margin: 0 0 10px 0; font-size: 14px; font-weight: 600; color: #14b8a6;'>Resource Staff Solutions</p>
                            <p style='margin: 0 0 5px 0; font-size: 13px; color: #94a3b8;'>This is an automated notification from the Timekeeping System</p>
                            <p style='margin: 0; font-size: 12px; color: #64748b;'>Please do not reply to this email</p>
                            <div style='margin-top: 20px; padding-top: 20px; border-top: 1px solid #334155;'>
                                <p style='margin: 0; font-size: 11px; color: #64748b;'>© " . date('Y') . " Resource Staff Solutions. All rights reserved.</p>
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
