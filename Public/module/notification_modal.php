<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../../vendor/autoload.php';
include('../config/db.php');

$current_user_id = $_SESSION['employee']['id'] ?? null;
$notifications = [];

// Helper function to get ACTUAL current schedule from calendar (matches schedule_content.php logic)
function getActualCurrentScheduleFromCalendar($pdo, $employee_id, $date = null) {
    if (!$date) $date = date('Y-m-d');
    
    // PRIORITY 1: Check employee_daily_schedule_cache (what the calendar actually displays)
    try {
        $stmt = $pdo->prepare("
            SELECT work_schedule_id, is_rest_day, schedule_name, time_in, time_out
            FROM employee_daily_schedule_cache 
            WHERE employee_id = ? AND schedule_date = ?
            LIMIT 1
        ");
        $stmt->execute([$employee_id, $date]);
        $cache = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cache) {
            if ($cache['is_rest_day'] == 1) {
                return ['time_in' => '—', 'time_out' => '—', 'is_rest_day' => true];
            }
            if ($cache['time_in'] && $cache['time_out']) {
                return [
                    'time_in' => date('g:i A', strtotime($cache['time_in'])),
                    'time_out' => date('g:i A', strtotime($cache['time_out'])),
                    'is_rest_day' => false
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Cache lookup failed: " . $e->getMessage());
    }
    
    // PRIORITY 2: Check employee_default_schedules
    try {
        $dayOfWeek = date('w', strtotime($date));
        $stmt = $pdo->prepare("
            SELECT edd.work_schedule_id, edd.is_rest_day, ws.time_in, ws.time_out
            FROM employee_default_schedules edd
            LEFT JOIN work_schedules ws ON edd.work_schedule_id = ws.id
            WHERE edd.employee_id = ? 
              AND edd.day_of_week = ? 
              AND edd.effective_from <= ? 
              AND (edd.effective_until IS NULL OR edd.effective_until >= ?)
            LIMIT 1
        ");
        $stmt->execute([$employee_id, $dayOfWeek, $date, $date]);
        $weekly = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($weekly) {
            if ($weekly['is_rest_day'] == 1 || !$weekly['work_schedule_id']) {
                return ['time_in' => '—', 'time_out' => '—', 'is_rest_day' => true];
            }
            if ($weekly['time_in'] && $weekly['time_out']) {
                return [
                    'time_in' => date('g:i A', strtotime($weekly['time_in'])),
                    'time_out' => date('g:i A', strtotime($weekly['time_out'])),
                    'is_rest_day' => false
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Weekly schedule lookup failed: " . $e->getMessage());
    }
    
    // PRIORITY 3: Check if weekend (Saturday or Sunday)
    $dayOfWeek = date('w', strtotime($date));
    if ($dayOfWeek == 0 || $dayOfWeek == 6) {
        return ['time_in' => '—', 'time_out' => '—', 'is_rest_day' => true];
    }
    
    // PRIORITY 4: Fall back to employee's official schedule
    try {
        $stmt = $pdo->prepare("
            SELECT e.official_sched, ws.time_in, ws.time_out
            FROM employees e
            LEFT JOIN work_schedules ws ON e.official_sched = ws.id
            WHERE e.id = ?
        ");
        $stmt->execute([$employee_id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($employee && $employee['time_in'] && $employee['time_out']) {
            return [
                'time_in' => date('g:i A', strtotime($employee['time_in'])),
                'time_out' => date('g:i A', strtotime($employee['time_out'])),
                'is_rest_day' => false
            ];
        }
    } catch (Exception $e) {
        error_log("Official schedule lookup failed: " . $e->getMessage());
    }
    
    return ['time_in' => '—', 'time_out' => '—', 'is_rest_day' => false];
}

function sendEmail($to, $name, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        // Enable debug logging
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer debug: [$level] $str");
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
        error_log("Failed to send email to {$to} - Error: {$mail->ErrorInfo}");
        error_log("Message that failed: {$subject}");
        error_log("Error details: " . $e->getMessage());
        return false;
    }
}

function getScheduleOutForDate(PDO $pdo, int $employeeId, string $date): string {
    // First check for approved schedule changes from post_schedule_change_requests
    $stmt = $pdo->prepare("
        SELECT ws.time_out
        FROM post_schedule_change_requests pscr
        LEFT JOIN work_schedules ws ON pscr.work_schedule_id = ws.id
        WHERE pscr.employee_id = ?
          AND pscr.status = 'approved'
          AND ? BETWEEN pscr.start_date AND pscr.end_date
        ORDER BY pscr.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$employeeId, $date]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['time_out']) {
        return $result['time_out'];
    }
    
    // Fallback to employee's official schedule
    $stmt = $pdo->prepare("
        SELECT ws.time_out
        FROM employees e
        LEFT JOIN work_schedules ws ON e.official_sched = ws.id
        WHERE e.id = ?
    ");
    $stmt->execute([$employeeId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['time_out'] ?? '17:00:00'; // Default fallback
}

if ($current_user_id) {
    // Get employee info
    $emp_stmt = $pdo->prepare("SELECT fname, lname, personal_email FROM employees WHERE id = ?");
    $emp_stmt->execute([$current_user_id]);
    $employee = $emp_stmt->fetch(PDO::FETCH_ASSOC);

// --- Leave Requests (from both tables) ---
// Pending leave requests
$leave_stmt = $pdo->prepare("
    SELECT id, leave_type, status, start_date, end_date, created_at, notified, explanation, reason, attachment_lr, 'pending' as source_table
    FROM leave_requests 
    WHERE employee_id = ?
    UNION ALL
    SELECT id, leave_type, status, start_date, end_date, created_at, notified, explanation, reason, attachment_lr, 'approved' as source_table
    FROM post_leave_requests
    WHERE employee_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");
$leave_stmt->execute([$current_user_id, $current_user_id]);
$leave_results = $leave_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($leave_results as $leave) {
    $raw_status = strtolower($leave['status']);
    // Treat both 'declined' and 'rejected' as 'Declined'
    if ($raw_status === 'approved') {
        $status = 'Approved';
    } elseif ($raw_status === 'declined' || $raw_status === 'rejected') {
        $status = 'Declined';
    } else {
        $status = 'Pending';
    }
    $range = date('F j', strtotime($leave['start_date'])) . ' to ' . date('F j', strtotime($leave['end_date']));
    $message = "Leave request for <strong>{$leave['leave_type']}</strong> ($range) was <strong>$status</strong>.";

    if (($raw_status === 'declined' || $raw_status === 'rejected') && !empty($leave['explanation'])) {
        $message .= "<br><span class='text-sm text-red-600'>Explanation: " . htmlspecialchars($leave['explanation']) . "</span>";
    }

    $notifications[] = [
        'message' => $message,
        'created_at' => $leave['created_at'],
        'leave_status' => $status,
        'explanation' => $leave['explanation'] ?? '',
        'leave_type' => $leave['leave_type'] ?? 'Leave Request',
        'request_id' => $leave['id'],
        'table_name' => 'leave_requests',
        'start_date' => $leave['start_date'],
        'end_date' => $leave['end_date'],
        'reason' => $leave['reason'] ?? '',
        'attachment_lr' => $leave['attachment_lr'] ?? '',
        'source_table' => $leave['source_table']
    ];

    // Send email notifications for both pending and post table entries that haven't been notified
    if (in_array($raw_status, ['approved', 'rejected', 'declined']) && !$leave['notified']) {
        $subject = "Leave Request {$status}";
        $body = "<p>Hi {$employee['fname']},<br>Your leave request from <strong>$range</strong> for <strong>{$leave['leave_type']}</strong> was <strong>$status</strong>.</p>";

        if (($raw_status === 'declined' || $raw_status === 'rejected') && !empty($leave['explanation'])) {
            $body .= "<p><strong>Explanation:</strong> " . nl2br(htmlspecialchars($leave['explanation'])) . "</p>";
        }

        // Enhanced email body with complete information
        $enhanced_body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f9fafb; padding: 20px;'>
            <div style='background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <h2 style='color: #1f2937; margin-bottom: 20px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;'>Leave Request {$status}</h2>
                
                <div style='background: #f3f4f6; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
                    <h3 style='color: #374151; margin: 0 0 10px 0;'>📋 Request Details</h3>
                    <p><strong>Employee:</strong> {$employee['fname']} {$employee['lname']}</p>
                    <p><strong>Leave Type:</strong> " . ucfirst($leave['leave_type']) . "</p>
                    <p><strong>Date Range:</strong> $range</p>
                    <p><strong>Request ID:</strong> #{$leave['id']}</p>
                    <p><strong>Submitted:</strong> " . date('F j, Y g:i A', strtotime($leave['created_at'])) . "</p>
                </div>
                
                <div style='background: " . ($raw_status === 'approved' ? '#ecfdf5' : '#fef2f2') . "; padding: 15px; border-radius: 8px; border-left: 4px solid " . ($raw_status === 'approved' ? '#10b981' : '#ef4444') . "; margin-bottom: 20px;'>
                    <h3 style='color: " . ($raw_status === 'approved' ? '#065f46' : '#991b1b') . "; margin: 0 0 10px 0;'>" . ($raw_status === 'approved' ? '✅' : '❌') . " Status: {$status}</h3>
                </div>";
        
        if (!empty($leave['reason'])) {
            $enhanced_body .= "
                <div style='background: #eff6ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
                    <h3 style='color: #1e40af; margin: 0 0 10px 0;'>💬 Your Reason</h3>
                    <p style='font-style: italic; color: #374151;'>" . nl2br(htmlspecialchars($leave['reason'])) . "</p>
                </div>";
        }
        
        if (($raw_status === 'declined' || $raw_status === 'rejected') && !empty($leave['explanation'])) {
            $enhanced_body .= "
                <div style='background: #fef2f2; padding: 15px; border-radius: 8px; border-left: 4px solid #ef4444; margin-bottom: 20px;'>
                    <h3 style='color: #991b1b; margin: 0 0 10px 0;'>📝 Admin Explanation</h3>
                    <p style='color: #374151;'>" . nl2br(htmlspecialchars($leave['explanation'])) . "</p>
                </div>";
        }
        
        $enhanced_body .= "
                <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;'>
                    <p style='color: #6b7280; font-size: 14px;'>This is an automated notification from the Timekeeping System</p>
                    <p style='color: #6b7280; font-size: 12px;'>Please do not reply to this email</p>
                </div>
            </div>
        </div>";

        if (sendEmail($employee['personal_email'], "{$employee['fname']} {$employee['lname']}", $subject, $enhanced_body)) {
            // Update the correct table based on source
            if ($leave['source_table'] === 'pending') {
                $update = $pdo->prepare("UPDATE leave_requests SET notified = 1 WHERE id = ?");
            } else {
                $update = $pdo->prepare("UPDATE post_leave_requests SET notified = 1 WHERE id = ?");
            }
            $result = $update->execute([$leave['id']]);
            error_log("Leave notification update for ID {$leave['id']} in {$leave['source_table']} table: " . ($result ? 'Success' : 'Failed'));
        }
    }
}

// --- Schedule Requests (from both tables) ---
$schedule_stmt = $pdo->prepare("
    SELECT scr.id, scr.work_schedule_id, scr.status, scr.start_date, scr.end_date, scr.created_at, scr.notified, scr.explanation, scr.reason,
           scr.current_work_schedule_id, scr.attachment_scr, scr.is_rest_day, 'pending' as source_table, scr.employee_id,
           current_ws.time_in as stored_current_time_in, current_ws.time_out as stored_current_time_out,
           new_ws.time_in as requested_time_in, new_ws.time_out as requested_time_out
    FROM schedule_change_requests scr
    LEFT JOIN work_schedules current_ws ON scr.current_work_schedule_id = current_ws.id
    LEFT JOIN work_schedules new_ws ON scr.work_schedule_id = new_ws.id
    WHERE scr.employee_id = ?
    UNION ALL
    SELECT pscr.id, pscr.work_schedule_id, pscr.status, pscr.start_date, pscr.end_date, pscr.created_at, pscr.notified, pscr.explanation, pscr.reason,
           pscr.current_work_schedule_id, pscr.attachment_scr, pscr.is_rest_day, 'approved' as source_table, pscr.employee_id,
           current_ws2.time_in as stored_current_time_in, current_ws2.time_out as stored_current_time_out,
           new_ws2.time_in as requested_time_in, new_ws2.time_out as requested_time_out
    FROM post_schedule_change_requests pscr
    LEFT JOIN work_schedules current_ws2 ON pscr.current_work_schedule_id = current_ws2.id
    LEFT JOIN work_schedules new_ws2 ON pscr.work_schedule_id = new_ws2.id
    WHERE pscr.employee_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");
$schedule_stmt->execute([$current_user_id, $current_user_id]);
$schedule_results = $schedule_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($schedule_results as $sched) {
    $status = ucfirst($sched['status']);
    $range = date('F j', strtotime($sched['start_date'])) . ' to ' . date('F j', strtotime($sched['end_date']));
    
    // Check if this is a rest day request
    $isRestDay = (empty($sched['work_schedule_id']) || ($sched['is_rest_day'] ?? 0) == 1);
    $requestType = $isRestDay ? 'Day off request' : 'Schedule change request';
    
    $message = "{$requestType} for ($range) was <strong>$status</strong>.";

    if (strtolower($sched['status']) === 'declined' && !empty($sched['explanation'])) {
        $message .= "<br><span class='text-sm text-red-600'>Explanation: " . htmlspecialchars($sched['explanation']) . "</span>";
    }

    // IMPORTANT: For APPROVED/DECLINED requests (history), ALWAYS use stored current_work_schedule_id
    // This preserves historical reference of what the schedule was BEFORE the change
    // Only fetch live schedule for PENDING requests
    $currentTimeIn = $sched['stored_current_time_in'];
    $currentTimeOut = $sched['stored_current_time_out'];
    
    if (strtolower($sched['status']) === 'pending') {
        // For pending requests, fetch the ACTUAL current schedule from calendar
        $actualCurrentSchedule = getActualCurrentScheduleFromCalendar($pdo, $sched['employee_id'], $sched['start_date']);
        
        // Use actual current schedule if available, otherwise fall back to stored values
        $currentTimeIn = $actualCurrentSchedule['time_in'] ?? $currentTimeIn;
        $currentTimeOut = $actualCurrentSchedule['time_out'] ?? $currentTimeOut;
    }

    $notifications[] = [
        'message' => $message,
        'created_at' => $sched['created_at'],
        'status' => $sched['status'],
        'explanation' => $sched['explanation'] ?? '',
        'reason' => $sched['reason'] ?? '',
        'start_date' => $sched['start_date'],
        'end_date' => $sched['end_date'],
        'current_time_in' => $currentTimeIn,
        'current_time_out' => $currentTimeOut,
        'requested_time_in' => $sched['requested_time_in'],
        'requested_time_out' => $sched['requested_time_out'],
        'attachment_scr' => $sched['attachment_scr'] ?? '',
        'work_schedule_id' => $sched['work_schedule_id'],
        'is_rest_day' => $sched['is_rest_day'] ?? 0,
        'request_id' => $sched['id'],
        'table_name' => 'schedule_change_requests',
        'source_table' => $sched['source_table']
    ];

    // Send email notifications for both pending and post table entries that haven't been notified
    if (in_array(strtolower($sched['status']), ['approved', 'declined']) && !$sched['notified']) {
        $emailSubject = $isRestDay ? "Day Off Request {$status}" : "Schedule Change Request {$status}";
        $subject = $emailSubject;
        
        $requestTypeText = $isRestDay ? 'day off request' : 'schedule change request';
        $body = "<p>Hi {$employee['fname']},<br>Your {$requestTypeText} for <strong>$range</strong> was <strong>$status</strong>.</p>";
        if (strtolower($sched['status']) === 'declined' && !empty($sched['explanation'])) {
            $body .= "<p><strong>Explanation:</strong> " . nl2br(htmlspecialchars($sched['explanation'])) . "</p>";
        }
        // Enhanced email body with complete schedule information
        $current_schedule = ($currentTimeIn && $currentTimeOut) ? 
            date('g:i A', strtotime($currentTimeIn)) . ' - ' . date('g:i A', strtotime($currentTimeOut)) : 'Not specified';
        
        // For rest days, show "Day Off" instead of schedule times
        if ($isRestDay) {
            $requested_schedule = 'Day Off';
        } else {
            $requested_schedule = ($sched['requested_time_in'] && $sched['requested_time_out']) ? 
                date('g:i A', strtotime($sched['requested_time_in'])) . ' - ' . date('g:i A', strtotime($sched['requested_time_out'])) : 'Not specified';
        }
            
        $emailIcon = $isRestDay ? '🛏️' : '🕒';
        $emailTitle = $isRestDay ? 'Day Off Request' : 'Schedule Change Request';
        
        $enhanced_body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f9fafb; padding: 20px;'>
            <div style='background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <h2 style='color: #1f2937; margin-bottom: 20px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;'>{$emailIcon} {$emailTitle} {$status}</h2>
                
                <div style='background: #f3f4f6; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
                    <h3 style='color: #374151; margin: 0 0 10px 0;'>📋 Request Details</h3>
                    <p><strong>Employee:</strong> {$employee['fname']} {$employee['lname']}</p>
                    <p><strong>Date Range:</strong> $range</p>
                    <p><strong>Request ID:</strong> #{$sched['id']}</p>
                    <p><strong>Submitted:</strong> " . date('F j, Y g:i A', strtotime($sched['created_at'])) . "</p>
                </div>";
        
        // Only show schedule details if it's NOT a rest day
        if (!$isRestDay) {
            $enhanced_body .= "
                <div style='background: #eff6ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
                    <h3 style='color: #1e40af; margin: 0 0 15px 0;'>📅 Schedule Details</h3>
                    <div style='display: flex; justify-content: space-between; margin-bottom: 10px;'>
                        <div style='flex: 1; margin-right: 10px;'>
                            <strong>Current Schedule:</strong><br>
                            <span style='background: #fee2e2; padding: 5px 10px; border-radius: 5px; color: #991b1b;'>$current_schedule</span>
                        </div>
                        <div style='flex: 1; margin-left: 10px;'>
                            <strong>Requested Schedule:</strong><br>
                            <span style='background: #dcfce7; padding: 5px 10px; border-radius: 5px; color: #166534;'>$requested_schedule</span>
                        </div>
                    </div>
                </div>";
        } else {
            // For rest days, show a special message
            $enhanced_body .= "
                <div style='background: #fef2f2; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #ef4444;'>
                    <h3 style='color: #991b1b; margin: 0 0 10px 0;'>🛏️ Day Off Request</h3>
                    <p style='color: #374151;'>You requested a day off from <strong>$range</strong>.</p>
                </div>";
        }
        
        $enhanced_body .= "
                
                <div style='background: " . (strtolower($sched['status']) === 'approved' ? '#ecfdf5' : '#fef2f2') . "; padding: 15px; border-radius: 8px; border-left: 4px solid " . (strtolower($sched['status']) === 'approved' ? '#10b981' : '#ef4444') . "; margin-bottom: 20px;'>
                    <h3 style='color: " . (strtolower($sched['status']) === 'approved' ? '#065f46' : '#991b1b') . "; margin: 0 0 10px 0;'>" . (strtolower($sched['status']) === 'approved' ? '✅' : '❌') . " Status: {$status}</h3>
                </div>";
        
        if (!empty($sched['reason'])) {
            $enhanced_body .= "
                <div style='background: #eff6ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
                    <h3 style='color: #1e40af; margin: 0 0 10px 0;'>💬 Your Reason</h3>
                    <p style='font-style: italic; color: #374151;'>" . nl2br(htmlspecialchars($sched['reason'])) . "</p>
                </div>";
        }
        
        if (strtolower($sched['status']) === 'declined' && !empty($sched['explanation'])) {
            $enhanced_body .= "
                <div style='background: #fef2f2; padding: 15px; border-radius: 8px; border-left: 4px solid #ef4444; margin-bottom: 20px;'>
                    <h3 style='color: #991b1b; margin: 0 0 10px 0;'>📝 Admin Explanation</h3>
                    <p style='color: #374151;'>" . nl2br(htmlspecialchars($sched['explanation'])) . "</p>
                </div>";
        }
        
        $enhanced_body .= "
                <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;'>
                    <p style='color: #6b7280; font-size: 14px;'>This is an automated notification from the Timekeeping System</p>
                    <p style='color: #6b7280; font-size: 12px;'>Please do not reply to this email</p>
                </div>
            </div>
        </div>";

        if (sendEmail($employee['personal_email'], "{$employee['fname']} {$employee['lname']}", $subject, $enhanced_body)) {
            // Update the correct table based on source
            if ($sched['source_table'] === 'pending') {
                $update = $pdo->prepare("UPDATE schedule_change_requests SET notified = 1 WHERE id = ?");
            } else {
                $update = $pdo->prepare("UPDATE post_schedule_change_requests SET notified = 1 WHERE id = ?");
            }
            $result = $update->execute([$sched['id']]);
            error_log("Schedule change notification update for ID {$sched['id']} in {$sched['source_table']} table: " . ($result ? 'Success' : 'Failed'));
        }
    }
}

// --- Time Adjustment Requests (from both tables) ---
$adjust_stmt = $pdo->prepare("
    SELECT id, log_date, current_time_in, current_time_out, requested_time_in, requested_time_out, status, reason, created_at, notified, attachment, 'pending' as source_table
    FROM time_adjustment_requests 
    WHERE employee_id = ?
    UNION ALL
    SELECT id, log_date, current_time_in, current_time_out, requested_time_in, requested_time_out, status, reason, created_at, notified, attachment, 'approved' as source_table
    FROM post_time_adjustment_requests
    WHERE employee_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");
$adjust_stmt->execute([$current_user_id, $current_user_id]);
$adjust_results = $adjust_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($adjust_results as $adjustment) {
    $raw_status = strtolower($adjustment['status']);
    // Always show Declined if status is declined or rejected, even for post_time_adjustment_requests
    if ($raw_status === 'approved') {
        $status = 'Approved';
    } elseif ($raw_status === 'declined' || $raw_status === 'rejected') {
        $status = 'Declined';
    } else {
        $status = 'Pending';
    }
    $date = date('F j', strtotime($adjustment['log_date']));
    $message = "Time adjustment request for <strong>$date</strong> was <strong>$status</strong>.";

    if (($raw_status === 'declined' || $raw_status === 'rejected') && !empty($adjustment['reason'])) {
        $message .= "<br><span class='text-sm text-red-600'>Explanation: " . htmlspecialchars($adjustment['reason']) . "</span>";
    }

    $notifications[] = [
        'message' => $message,
        'created_at' => $adjustment['created_at'],
        'time_adjust_status' => $status,
        'reason' => $adjustment['reason'] ?? '',
        'log_date' => $adjustment['log_date'],
        'current_time_in' => $adjustment['current_time_in'],
        'current_time_out' => $adjustment['current_time_out'],
        'requested_time_in' => $adjustment['requested_time_in'],
        'requested_time_out' => $adjustment['requested_time_out'],
        'attachment' => $adjustment['attachment'] ?? '',
        'request_id' => $adjustment['id'],
        'table_name' => 'time_adjustment_requests',
        'source_table' => $adjustment['source_table']
    ];

    // Send email notifications for both pending and post table entries that haven't been notified
    if (in_array($raw_status, ['approved', 'declined', 'rejected']) && !$adjustment['notified']) {
        $subject = "Time Adjustment Request {$status}";
        $body = "<p>Hi {$employee['fname']},<br>Your time adjustment request for <strong>$date</strong> was <strong>$status</strong>.</p>";

        if (($raw_status === 'declined' || $raw_status === 'rejected') && !empty($adjustment['reason'])) {
            $body .= "<p><strong>Explanation:</strong> " . nl2br(htmlspecialchars($adjustment['reason'])) . "</p>";
        }

        // Enhanced email body with complete time adjustment information
        $current_time_in = $adjustment['current_time_in'] ? date('g:i A', strtotime($adjustment['current_time_in'])) : 'Not recorded';
        $current_time_out = $adjustment['current_time_out'] ? date('g:i A', strtotime($adjustment['current_time_out'])) : 'Not recorded';
        $requested_time_in = $adjustment['requested_time_in'] ? date('g:i A', strtotime($adjustment['requested_time_in'])) : 'Not specified';
        $requested_time_out = $adjustment['requested_time_out'] ? date('g:i A', strtotime($adjustment['requested_time_out'])) : 'Not specified';
        
        $enhanced_body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f9fafb; padding: 20px;'>
            <div style='background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <h2 style='color: #1f2937; margin-bottom: 20px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;'>⏰ Time Adjustment Request {$status}</h2>
                
                <div style='background: #f3f4f6; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
                    <h3 style='color: #374151; margin: 0 0 10px 0;'>📋 Request Details</h3>
                    <p><strong>Employee:</strong> {$employee['fname']} {$employee['lname']}</p>
                    <p><strong>Date:</strong> " . date('F j, Y', strtotime($adjustment['log_date'])) . "</p>
                    <p><strong>Request ID:</strong> #{$adjustment['id']}</p>
                    <p><strong>Submitted:</strong> " . date('F j, Y g:i A', strtotime($adjustment['created_at'])) . "</p>
                </div>
                
                <div style='background: #eff6ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
                    <h3 style='color: #1e40af; margin: 0 0 15px 0;'>🕐 Time Details</h3>
                    <div style='margin-bottom: 15px;'>
                        <strong>Current Times:</strong><br>
                        <div style='background: #fee2e2; padding: 10px; border-radius: 5px; margin: 5px 0;'>
                            <span style='color: #991b1b;'>Time In: $current_time_in | Time Out: $current_time_out</span>
                        </div>
                    </div>
                    <div>
                        <strong>Requested Times:</strong><br>
                        <div style='background: #dcfce7; padding: 10px; border-radius: 5px; margin: 5px 0;'>
                            <span style='color: #166534;'>Time In: $requested_time_in | Time Out: $requested_time_out</span>
                        </div>
                    </div>
                </div>
                
                <div style='background: " . ($raw_status === 'approved' ? '#ecfdf5' : '#fef2f2') . "; padding: 15px; border-radius: 8px; border-left: 4px solid " . ($raw_status === 'approved' ? '#10b981' : '#ef4444') . "; margin-bottom: 20px;'>
                    <h3 style='color: " . ($raw_status === 'approved' ? '#065f46' : '#991b1b') . "; margin: 0 0 10px 0;'>" . ($raw_status === 'approved' ? '✅' : '❌') . " Status: {$status}</h3>
                </div>";
        
        if (!empty($adjustment['reason'])) {
            $enhanced_body .= "
                <div style='background: #eff6ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
                    <h3 style='color: #1e40af; margin: 0 0 10px 0;'>💬 Your Reason</h3>
                    <p style='font-style: italic; color: #374151;'>" . nl2br(htmlspecialchars($adjustment['reason'])) . "</p>
                </div>";
        }
        
        if (($raw_status === 'declined' || $raw_status === 'rejected') && !empty($adjustment['reason'])) {
            $enhanced_body .= "
                <div style='background: #fef2f2; padding: 15px; border-radius: 8px; border-left: 4px solid #ef4444; margin-bottom: 20px;'>
                    <h3 style='color: #991b1b; margin: 0 0 10px 0;'>📝 Admin Explanation</h3>
                    <p style='color: #374151;'>" . nl2br(htmlspecialchars($adjustment['reason'])) . "</p>
                </div>";
        }
        
        $enhanced_body .= "
                <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;'>
                    <p style='color: #6b7280; font-size: 14px;'>This is an automated notification from the Timekeeping System</p>
                    <p style='color: #6b7280; font-size: 12px;'>Please do not reply to this email</p>
                </div>
            </div>
        </div>";

        if (sendEmail($employee['personal_email'], "{$employee['fname']} {$employee['lname']}", $subject, $enhanced_body)) {
            // Update the correct table based on source
            if ($adjustment['source_table'] === 'pending') {
                $update = $pdo->prepare("UPDATE time_adjustment_requests SET notified = 1 WHERE id = ?");
            } else {
                $update = $pdo->prepare("UPDATE post_time_adjustment_requests SET notified = 1 WHERE id = ?");
            }
            $result = $update->execute([$adjustment['id']]);
            error_log("Time adjustment notification update for ID {$adjustment['id']} in {$adjustment['source_table']} table: " . ($result ? 'Success' : 'Failed'));
        }
    }
}

// --- Overtime Requests (from both tables) ---
// First get pending overtime requests
$pending_ot_stmt = $pdo->prepare("
    SELECT id, employee_id, date, start_time, end_time, reason, duration_hours, status, created_at, attachment_ot as attachment, 'pending' as source_table
    FROM overtime_requests
    WHERE employee_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");
$pending_ot_stmt->execute([$current_user_id]);
$pending_ot_results = $pending_ot_stmt->fetchAll(PDO::FETCH_ASSOC);

// Then get processed overtime requests from both post tables
$ot_stmt = $pdo->prepare("
    SELECT id, time_log_id, time_in, time_out, ot_duration, ot_type, reason, status, created_at, approved_at, approved_by, notified, attachment, 'post_ot_requests' as source_table
    FROM post_ot_requests
    WHERE employee_id = ?
    UNION ALL
    SELECT id, time_log_id, time_in, time_out, ot_duration, ot_type, reason, status, created_at, approved_at, approved_by, notified, attachment, 'post2_overtime_requests' as source_table
    FROM post2_overtime_requests
    WHERE employee_id = ?
    ORDER BY COALESCE(approved_at, created_at) DESC
    LIMIT 20
");
$ot_stmt->execute([$current_user_id, $current_user_id]);
$ot_results = $ot_stmt->fetchAll(PDO::FETCH_ASSOC);

// Process pending overtime requests
foreach ($pending_ot_results as $ot) {
    $raw_status = strtolower($ot['status']);
    if ($raw_status === 'approved') {
        $status = 'Approved';
    } elseif ($raw_status === 'declined' || $raw_status === 'rejected') {
        $status = 'Declined';
    } else {
        $status = 'Pending';
    }
    $duration = number_format((float)$ot['duration_hours'], 2);
    $ot_type = 'Regular OT'; // Default for pending requests
    $created_at = $ot['created_at'];

    $notifications[] = [
        'message' => "Overtime request for <strong>{$ot_type}</strong> ({$duration} hours) was <strong>{$status}</strong>.",
        'created_at' => $created_at,
        'ot_status' => $status,
        'ot_reason' => $ot['reason'],
        'time_in' => $ot['start_time'] ?? null,
        'time_out' => $ot['end_time'] ?? null,
        'start_ot' => $ot['start_time'] ?? null,
        'end_ot' => $ot['end_time'] ?? null,
        'ot_duration' => $ot['duration_hours'],
        'ot_type' => $ot_type,
        'ot_date' => $ot['date'],
        'attachment' => $ot['attachment'] ?? '',
        'request_id' => $ot['id'],
        'table_name' => 'overtime_requests',
        'source_table' => 'pending'
    ];

    // Send email notifications for overtime requests that haven't been notified
    // Note: overtime_requests table doesn't have a 'notified' column, so we'll check if it exists first
    if (in_array($raw_status, ['approved', 'declined', 'rejected'])) {
        $subject = "Overtime Request {$status}";
        $date_str = date('F j, Y', strtotime($ot['date']));
        $body = "<p>Hi {$employee['fname']},<br>Your overtime request for <strong>{$date_str}</strong> ({$duration} hours) was <strong>{$status}</strong>.</p>";

        if (($raw_status === 'declined' || $raw_status === 'rejected') && !empty($ot['reason'])) {
            $body .= "<p><strong>Reason:</strong> " . nl2br(htmlspecialchars($ot['reason'])) . "</p>";
        }

        // Enhanced email body with complete overtime information
        $start_time = $ot['start_time'] ? date('g:i A', strtotime($ot['start_time'])) : 'Not specified';
        $end_time = $ot['end_time'] ? date('g:i A', strtotime($ot['end_time'])) : 'Not specified';
        
        $enhanced_body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f9fafb; padding: 20px;'>
            <div style='background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <h2 style='color: #1f2937; margin-bottom: 20px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;'>⏰ Overtime Request {$status}</h2>
                
                <div style='background: #f3f4f6; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
                    <h3 style='color: #374151; margin: 0 0 10px 0;'>📋 Request Details</h3>
                    <p><strong>Employee:</strong> {$employee['fname']} {$employee['lname']}</p>
                    <p><strong>Date:</strong> {$date_str}</p>
                    <p><strong>Overtime Type:</strong> {$ot_type}</p>
                    <p><strong>Duration:</strong> {$duration} hours</p>
                    <p><strong>Request ID:</strong> #{$ot['id']}</p>
                    <p><strong>Submitted:</strong> " . date('F j, Y g:i A', strtotime($ot['created_at'])) . "</p>
                </div>
                
                <div style='background: #eff6ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
                    <h3 style='color: #1e40af; margin: 0 0 10px 0;'>🕐 Time Schedule</h3>
                    <p><strong>Start Time:</strong> $start_time</p>
                    <p><strong>End Time:</strong> $end_time</p>
                </div>
                
                <div style='background: " . ($raw_status === 'approved' ? '#ecfdf5' : '#fef2f2') . "; padding: 15px; border-radius: 8px; border-left: 4px solid " . ($raw_status === 'approved' ? '#10b981' : '#ef4444') . "; margin-bottom: 20px;'>
                    <h3 style='color: " . ($raw_status === 'approved' ? '#065f46' : '#991b1b') . "; margin: 0 0 10px 0;'>" . ($raw_status === 'approved' ? '✅' : '❌') . " Status: {$status}</h3>
                </div>";
        
        if (!empty($ot['reason'])) {
            $enhanced_body .= "
                <div style='background: #eff6ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
                    <h3 style='color: #1e40af; margin: 0 0 10px 0;'>💬 Your Reason</h3>
                    <p style='font-style: italic; color: #374151;'>" . nl2br(htmlspecialchars($ot['reason'])) . "</p>
                </div>";
        }
        
        if (($raw_status === 'declined' || $raw_status === 'rejected') && !empty($ot['reason'])) {
            $enhanced_body .= "
                <div style='background: #fef2f2; padding: 15px; border-radius: 8px; border-left: 4px solid #ef4444; margin-bottom: 20px;'>
                    <h3 style='color: #991b1b; margin: 0 0 10px 0;'>📝 Admin Explanation</h3>
                    <p style='color: #374151;'>" . nl2br(htmlspecialchars($ot['reason'])) . "</p>
                </div>";
        }
        
        $enhanced_body .= "
                <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;'>
                    <p style='color: #6b7280; font-size: 14px;'>This is an automated notification from the Timekeeping System</p>
                    <p style='color: #6b7280; font-size: 12px;'>Please do not reply to this email</p>
                </div>
            </div>
        </div>";

        if (sendEmail($employee['personal_email'], "{$employee['fname']} {$employee['lname']}", $subject, $enhanced_body)) {
            error_log("Overtime notification sent for ID {$ot['id']} - Status: {$status}");
        }
    }
}

// Process post overtime requests
foreach ($ot_results as $ot) {
    $raw_status = strtolower($ot['status']);
    if ($raw_status === 'approved') {
        $status = 'Approved';
    } elseif ($raw_status === 'declined' || $raw_status === 'rejected') {
        $status = 'Declined';
    } else {
        $status = 'Pending';
    }
    $duration = number_format($ot['ot_duration'], 2);
    $ot_type = $ot['ot_type'] ?? 'Regular OT';
    $created_at = $ot['approved_at'] ?? $ot['created_at'];

    // Compute Start OT and End OT using schedule logic (similar to new_overtime)
    $actual_time_in = $ot['time_in'] ?? null;
    $actual_time_out = $ot['time_out'] ?? null;
    $log_date = null;
    $start_ot = null;
    $end_ot = null;

    // Get log_date from time_logs table if time_log_id exists
    if (!empty($ot['time_log_id'])) {
        $log_stmt = $pdo->prepare("SELECT log_date FROM time_logs WHERE id = ?");
        $log_stmt->execute([$ot['time_log_id']]);
        $log_result = $log_stmt->fetch(PDO::FETCH_ASSOC);
        $log_date = $log_result['log_date'] ?? null;
    }
    
    // Fallback: derive log_date from time_in if available
    if (!$log_date && $actual_time_in) {
        $log_date = date('Y-m-d', strtotime($actual_time_in));
    }

    if ($log_date) {
        if (strtolower($ot_type) === 'restday ot') {
            // Restday OT uses actual in/out
            $start_ot = $actual_time_in;
            $end_ot = $actual_time_out;
        } else {
            // Regular OT: start at scheduled time_out, end at actual time_out
            $sched_out = getScheduleOutForDate($pdo, (int)$current_user_id, $log_date); // HH:MM:SS
            $sched_out_dt = DateTime::createFromFormat('Y-m-d H:i:s', $log_date . ' ' . $sched_out);
            $actual_out_dt = $actual_time_out ? new DateTime($actual_time_out) : null;
            
            if ($actual_out_dt && $sched_out_dt) {
                $actual_out_date = $actual_out_dt->format('Y-m-d');
                if ($actual_out_date !== $log_date) {
                    // Cross-midnight: shift schedule to the actual out date
                    $sched_out_dt = DateTime::createFromFormat('Y-m-d H:i:s', $actual_out_date . ' ' . $sched_out);
                }
            }
            $start_ot = $sched_out_dt ? $sched_out_dt->format('Y-m-d H:i:s') : null;
            $end_ot = $actual_time_out;
        }
    }

    $notifications[] = [
        'message' => "Overtime request for <strong>{$ot_type}</strong> ({$duration} hours) was <strong>{$status}</strong>.",
        'created_at' => $created_at,
        'ot_status' => $status,
        'ot_reason' => $ot['reason'],
        'time_in' => $actual_time_in,
        'time_out' => $actual_time_out,
        'start_ot' => $start_ot,
        'end_ot' => $end_ot,
        'ot_duration' => $ot['ot_duration'],
        'ot_type' => $ot_type,
        'ot_date' => $log_date,
        'attachment' => $ot['attachment'] ?? '',
        'request_id' => $ot['id'],
        'table_name' => $ot['source_table'],
        'source_table' => 'post'
    ];

    // Send email notifications for post overtime requests that haven't been notified
    if (in_array($raw_status, ['approved', 'declined', 'rejected']) && !$ot['notified']) {
        $subject = "Overtime Request {$status}";
        $date_str = $log_date ? date('F j, Y', strtotime($log_date)) : 'Unknown Date';
        $body = "<p>Hi {$employee['fname']},<br>Your overtime request for <strong>{$ot_type}</strong> on <strong>{$date_str}</strong> ({$duration} hours) was <strong>{$status}</strong>.</p>";

        if (($raw_status === 'declined' || $raw_status === 'rejected') && !empty($ot['reason'])) {
            $body .= "<p><strong>Reason:</strong> " . nl2br(htmlspecialchars($ot['reason'])) . "</p>";
        }

        // Enhanced email body with complete overtime information
        $actual_time_in_display = $actual_time_in ? date('g:i A', strtotime($actual_time_in)) : 'Not recorded';
        $actual_time_out_display = $actual_time_out ? date('g:i A', strtotime($actual_time_out)) : 'Not recorded';
        $start_ot_display = $start_ot ? date('g:i A', strtotime($start_ot)) : 'Not calculated';
        $end_ot_display = $end_ot ? date('g:i A', strtotime($end_ot)) : 'Not calculated';
        
        $enhanced_body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f9fafb; padding: 20px;'>
            <div style='background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <h2 style='color: #1f2937; margin-bottom: 20px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;'>⏰ Overtime Request {$status}</h2>
                
                <div style='background: #f3f4f6; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
                    <h3 style='color: #374151; margin: 0 0 10px 0;'>📋 Request Details</h3>
                    <p><strong>Employee:</strong> {$employee['fname']} {$employee['lname']}</p>
                    <p><strong>Date:</strong> {$date_str}</p>
                    <p><strong>Overtime Type:</strong> {$ot_type}</p>
                    <p><strong>Duration:</strong> {$duration} hours</p>
                    <p><strong>Request ID:</strong> #{$ot['id']}</p>
                    <p><strong>Processed:</strong> " . date('F j, Y g:i A', strtotime($created_at)) . "</p>
                </div>
                
                <div style='background: #eff6ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
                    <h3 style='color: #1e40af; margin: 0 0 15px 0;'>🕐 Time Details</h3>
                    <div style='margin-bottom: 10px;'>
                        <strong>Work Schedule:</strong><br>
                        <span style='background: #e0e7ff; padding: 5px 10px; border-radius: 5px; color: #3730a3;'>In: $actual_time_in_display | Out: $actual_time_out_display</span>
                    </div>
                    <div>
                        <strong>Overtime Period:</strong><br>
                        <span style='background: #fbbf24; padding: 5px 10px; border-radius: 5px; color: #92400e;'>Start: $start_ot_display | End: $end_ot_display</span>
                    </div>
                </div>
                
                <div style='background: " . ($raw_status === 'approved' ? '#ecfdf5' : '#fef2f2') . "; padding: 15px; border-radius: 8px; border-left: 4px solid " . ($raw_status === 'approved' ? '#10b981' : '#ef4444') . "; margin-bottom: 20px;'>
                    <h3 style='color: " . ($raw_status === 'approved' ? '#065f46' : '#991b1b') . "; margin: 0 0 10px 0;'>" . ($raw_status === 'approved' ? '✅' : '❌') . " Status: {$status}</h3>
                </div>";
        
        if (!empty($ot['reason'])) {
            $enhanced_body .= "
                <div style='background: #eff6ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
                    <h3 style='color: #1e40af; margin: 0 0 10px 0;'>💬 Your Reason</h3>
                    <p style='font-style: italic; color: #374151;'>" . nl2br(htmlspecialchars($ot['reason'])) . "</p>
                </div>";
        }
        
        if (($raw_status === 'declined' || $raw_status === 'rejected') && !empty($ot['reason'])) {
            $enhanced_body .= "
                <div style='background: #fef2f2; padding: 15px; border-radius: 8px; border-left: 4px solid #ef4444; margin-bottom: 20px;'>
                    <h3 style='color: #991b1b; margin: 0 0 10px 0;'>📝 Admin Explanation</h3>
                    <p style='color: #374151;'>" . nl2br(htmlspecialchars($ot['reason'])) . "</p>
                </div>";
        }
        
        $enhanced_body .= "
                <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;'>
                    <p style='color: #6b7280; font-size: 14px;'>This is an automated notification from the Timekeeping System</p>
                    <p style='color: #6b7280; font-size: 12px;'>Please do not reply to this email</p>
                </div>
            </div>
        </div>";

        if (sendEmail($employee['personal_email'], "{$employee['fname']} {$employee['lname']}", $subject, $enhanced_body)) {
            // Update the correct table based on source_table
            if ($ot['source_table'] === 'post_ot_requests') {
                $update = $pdo->prepare("UPDATE post_ot_requests SET notified = 1 WHERE id = ?");
            } else {
                $update = $pdo->prepare("UPDATE post2_overtime_requests SET notified = 1 WHERE id = ?");
            }
            $result = $update->execute([$ot['id']]);
            error_log("Overtime notification update for ID {$ot['id']} in {$ot['source_table']} table: " . ($result ? 'Success' : 'Failed'));
        }
    }
}

// --- Schedule Swap Requests ---
$switch_stmt = $pdo->prepare("
    SELECT id, source_date, target_date, reason, attachment_path, status, created_at, processed_at, admin_notes
    FROM schedule_switch_requests 
    WHERE employee_id = ?
    ORDER BY created_at DESC
");
$switch_stmt->execute([$current_user_id]);
$switch_requests = $switch_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($switch_requests as $switch) {
    $status = ucfirst(strtolower($switch['status']));
    $source_date = date('F j, Y', strtotime($switch['source_date']));
    $target_date = date('F j, Y', strtotime($switch['target_date']));
    
    // Get schedule details for both dates
    $source_schedule = getActualCurrentScheduleFromCalendar($pdo, $current_user_id, $switch['source_date']);
    $target_schedule = getActualCurrentScheduleFromCalendar($pdo, $current_user_id, $switch['target_date']);
    
    $notifications[] = [
        'message' => "Schedule swap request from <strong>{$source_date}</strong> to <strong>{$target_date}</strong> was <strong>{$status}</strong>.",
        'created_at' => $switch['created_at'],
        'type' => 'Schedule Swap Request',
        'status' => $switch['status'],
        'source_date' => $switch['source_date'],
        'target_date' => $switch['target_date'],
        'source_schedule_in' => $source_schedule['time_in'],
        'source_schedule_out' => $source_schedule['time_out'],
        'target_schedule_in' => $target_schedule['time_in'],
        'target_schedule_out' => $target_schedule['time_out'],
        'reason' => $switch['reason'],
        'attachment_scr' => $switch['attachment_path'] ?? '',
        'explanation' => $switch['admin_notes'] ?? '',
        'processed_at' => $switch['processed_at'],
        'request_id' => $switch['id'],
        'table_name' => 'schedule_switch_requests',
        'source_table' => 'schedule_switch_requests'
    ];
}

// --- Monthly Schedule Requests ---
$monthly_stmt = $pdo->prepare("
    SELECT id, year, month, 
           sunday_schedule_id, sunday_is_rest_day,
           monday_schedule_id, monday_is_rest_day,
           tuesday_schedule_id, tuesday_is_rest_day,
           wednesday_schedule_id, wednesday_is_rest_day,
           thursday_schedule_id, thursday_is_rest_day,
           friday_schedule_id, friday_is_rest_day,
           saturday_schedule_id, saturday_is_rest_day,
           reason, attachment_path, status, created_at, processed_at, admin_notes
    FROM month_weekly_schedule 
    WHERE employee_id = ?
    ORDER BY created_at DESC
");
$monthly_stmt->execute([$current_user_id]);
$monthly_requests = $monthly_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($monthly_requests as $monthly) {
    $status = ucfirst(strtolower($monthly['status']));
    $month_name = date('F Y', strtotime("{$monthly['year']}-{$monthly['month']}-01"));
    
    // Get schedule details for each day
    $weekly_schedules = [];
    $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    
    foreach ($days as $day) {
        $schedule_id = $monthly["{$day}_schedule_id"];
        $is_rest_day = $monthly["{$day}_is_rest_day"];
        
        if ($is_rest_day == 1 || empty($schedule_id)) {
            $weekly_schedules[$day] = ['time_in' => '—', 'time_out' => '—', 'is_rest_day' => true];
        } else {
            // Get schedule from work_schedules table
            $sched_stmt = $pdo->prepare("SELECT time_in, time_out FROM work_schedules WHERE id = ?");
            $sched_stmt->execute([$schedule_id]);
            $sched = $sched_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($sched) {
                $weekly_schedules[$day] = [
                    'time_in' => date('g:i A', strtotime($sched['time_in'])),
                    'time_out' => date('g:i A', strtotime($sched['time_out'])),
                    'is_rest_day' => false
                ];
            } else {
                $weekly_schedules[$day] = ['time_in' => '—', 'time_out' => '—', 'is_rest_day' => true];
            }
        }
    }
    
    $notifications[] = [
        'message' => "Monthly schedule request for <strong>{$month_name}</strong> was <strong>{$status}</strong>.",
        'created_at' => $monthly['created_at'],
        'type' => 'Monthly Schedule Request',
        'status' => $monthly['status'],
        'year' => $monthly['year'],
        'month' => $monthly['month'],
        'month_name' => $month_name,
        'weekly_schedules' => $weekly_schedules,
        'reason' => $monthly['reason'],
        'attachment_scr' => $monthly['attachment_path'] ?? '',
        'explanation' => $monthly['admin_notes'] ?? '',
        'processed_at' => $monthly['processed_at'],
        'request_id' => $monthly['id'],
        'table_name' => 'month_weekly_schedule',
        'source_table' => 'month_weekly_schedule'
    ];
}


    // Sort all notifications by newest first
    usort($notifications, function ($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
}
?>

<!-- Notification Modal -->
<div id="notificationModal" class="fixed top-16 right-8 w-80 bg-white shadow-lg rounded-xl border border-gray-200 hidden z-50">
    <div class="px-4 py-3 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-700">Notifications</h2>
        <button onclick="toggleModal()" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <ul class="max-h-60 overflow-y-auto divide-y divide-gray-100">
        <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $note): ?>
                <li class="px-4 py-3 hover:bg-gray-50">
                    <p class="text-sm text-gray-600"><?= $note['message'] ?></p>
                    <span class="text-xs text-gray-400"><?= date('M j, Y H:i', strtotime($note['created_at'])) ?></span>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li class="px-4 py-3 text-sm text-gray-500">No notifications found.</li>
        <?php endif; ?>
    </ul>
</div>

<script>
function toggleModal() {
    const modal = document.getElementById('notificationModal');
    if (modal) {
        modal.classList.toggle('hidden');
    }
}

// Optional: Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('notificationModal');
    const button = document.querySelector('.notification-button');
    // Only close if modal is visible and click is outside modal and button
    if (modal && !modal.classList.contains('hidden') && !modal.contains(event.target) && !button?.contains(event.target)) {
        modal.classList.add('hidden');
    }
});


    function toggleModal() {
        const modal = document.getElementById('notificationModal');
        if (modal) {
            modal.classList.toggle('hidden');
        }
    }

    // Optional: Close modal when clicking outside
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('notificationModal');
        const button = document.querySelector('.notification-button');

        if (modal && !modal.contains(event.target) && !button?.contains(event.target)) {
            modal.classList.add('hidden');
        }
    });

</script>
