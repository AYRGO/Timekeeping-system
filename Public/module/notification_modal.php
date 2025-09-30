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

function sendEmail($to, $name, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        // Validate required SMTP configuration
        if (!EnvLoader::get('SMTP_HOST') || !EnvLoader::get('SMTP_USER') || !EnvLoader::get('SMTP_PASS')) {
            error_log("SMTP configuration missing in environment variables");
            return false;
        }
        
        $mail->CharSet    = 'UTF-8';
        $mail->isSMTP();
        $mail->Host       = EnvLoader::get('SMTP_HOST');
        $mail->SMTPAuth   = true;
        $mail->Username   = EnvLoader::get('SMTP_USER');

        // ✅ Use Gmail App Password here (NOT your real Gmail password)
        $mail->Password   = EnvLoader::get('SMTP_PASS');

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = EnvLoader::get('SMTP_PORT') ?: 587;

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]
        ];

        $mail->setFrom(
            EnvLoader::get('SMTP_FROM_EMAIL') ?: EnvLoader::get('SMTP_USER'), 
            EnvLoader::get('SMTP_FROM_NAME') ?: 'System Notification'
        );
        $mail->addAddress($to, $name);

        $mail->isHTML(true);
        $mail->Subject = htmlspecialchars($subject);
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. PHPMailer Error: {$mail->ErrorInfo}");
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
    SELECT id, leave_type, status, start_date, end_date, created_at, notified, explanation, reason, 'pending' as source_table
    FROM leave_requests 
    WHERE employee_id = ?
    UNION ALL
    SELECT id, leave_type, status, start_date, end_date, created_at, notified, explanation, reason, 'approved' as source_table
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
        'source_table' => $leave['source_table']
    ];

    // Only send email notifications for pending table entries that changed status
    if (in_array($raw_status, ['approved', 'rejected', 'declined']) && !$leave['notified'] && $leave['source_table'] === 'pending') {
        $subject = "Leave Request {$status}";
        $body = "<p>Hi {$employee['fname']},<br>Your leave request from <strong>$range</strong> for <strong>{$leave['leave_type']}</strong> was <strong>$status</strong>.</p>";

        if (($raw_status === 'declined' || $raw_status === 'rejected') && !empty($leave['explanation'])) {
            $body .= "<p><strong>Explanation:</strong> " . nl2br(htmlspecialchars($leave['explanation'])) . "</p>";
        }

        if (sendEmail($employee['personal_email'], "{$employee['fname']} {$employee['lname']}", $subject, $body)) {
            $update = $pdo->prepare("UPDATE leave_requests SET notified = 1 WHERE id = ?");
            $update->execute([$leave['id']]);
        }
    }
}

// --- Schedule Requests (from both tables) ---
$schedule_stmt = $pdo->prepare("
    SELECT scr.id, scr.work_schedule_id, scr.status, scr.start_date, scr.end_date, scr.created_at, scr.notified, scr.explanation, scr.reason,
           scr.current_work_schedule_id, 'pending' as source_table,
           current_ws.time_in as current_time_in, current_ws.time_out as current_time_out,
           new_ws.time_in as requested_time_in, new_ws.time_out as requested_time_out
    FROM schedule_change_requests scr
    LEFT JOIN work_schedules current_ws ON scr.current_work_schedule_id = current_ws.id
    LEFT JOIN work_schedules new_ws ON scr.work_schedule_id = new_ws.id
    WHERE scr.employee_id = ?
    UNION ALL
    SELECT pscr.id, pscr.work_schedule_id, pscr.status, pscr.start_date, pscr.end_date, pscr.created_at, pscr.notified, pscr.explanation, pscr.reason,
           pscr.current_work_schedule_id, 'approved' as source_table,
           current_ws2.time_in as current_time_in, current_ws2.time_out as current_time_out,
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
    $message = "Schedule change request for ($range) was <strong>$status</strong>.";

    if (strtolower($sched['status']) === 'declined' && !empty($sched['explanation'])) {
        $message .= "<br><span class='text-sm text-red-600'>Explanation: " . htmlspecialchars($sched['explanation']) . "</span>";
    }

    $notifications[] = [
        'message' => $message,
        'created_at' => $sched['created_at'],
        'status' => $sched['status'],
        'explanation' => $sched['explanation'] ?? '',
        'reason' => $sched['reason'] ?? '',
        'start_date' => $sched['start_date'],
        'end_date' => $sched['end_date'],
        'current_time_in' => $sched['current_time_in'],
        'current_time_out' => $sched['current_time_out'],
        'requested_time_in' => $sched['requested_time_in'],
        'requested_time_out' => $sched['requested_time_out'],
        'request_id' => $sched['id'],
        'table_name' => 'schedule_change_requests',
        'source_table' => $sched['source_table']
    ];

    if (in_array(strtolower($sched['status']), ['approved', 'declined']) && !$sched['notified'] && $sched['source_table'] === 'pending') {
        $subject = "Schedule Change Request {$status}";
        $body = "<p>Hi {$employee['fname']},<br>Your schedule change request for <strong>$range</strong> was <strong>$status</strong>.</p>";
        if (strtolower($sched['status']) === 'declined' && !empty($sched['explanation'])) {
            $body .= "<p><strong>Explanation:</strong> " . nl2br(htmlspecialchars($sched['explanation'])) . "</p>";
        }
        if (sendEmail($employee['personal_email'], "{$employee['fname']} {$employee['lname']}", $subject, $body)) {
            $update = $pdo->prepare("UPDATE schedule_change_requests SET notified = 1 WHERE id = ?");
            $update->execute([$sched['id']]);
        }
    }
}

// --- Time Adjustment Requests (from both tables) ---
$adjust_stmt = $pdo->prepare("
    SELECT id, log_date, current_time_in, current_time_out, requested_time_in, requested_time_out, status, reason, created_at, notified, 'pending' as source_table
    FROM time_adjustment_requests 
    WHERE employee_id = ?
    UNION ALL
    SELECT id, log_date, current_time_in, current_time_out, requested_time_in, requested_time_out, status, reason, created_at, notified, 'approved' as source_table
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
        'request_id' => $adjustment['id'],
        'table_name' => 'time_adjustment_requests',
        'source_table' => $adjustment['source_table']
    ];

    if (in_array($raw_status, ['approved', 'declined', 'rejected']) && !$adjustment['notified'] && $adjustment['source_table'] === 'pending') {
        $subject = "Time Adjustment Request {$status}";
        $body = "<p>Hi {$employee['fname']},<br>Your time adjustment request for <strong>$date</strong> was <strong>$status</strong>.</p>";

        if (($raw_status === 'declined' || $raw_status === 'rejected') && !empty($adjustment['reason'])) {
            $body .= "<p><strong>Explanation:</strong> " . nl2br(htmlspecialchars($adjustment['reason'])) . "</p>";
        }

        if (sendEmail($employee['personal_email'], "{$employee['fname']} {$employee['lname']}", $subject, $body)) {
            $update = $pdo->prepare("UPDATE time_adjustment_requests SET notified = 1 WHERE id = ?");
            $update->execute([$adjustment['id']]);
        }
    }
}

// --- Overtime Requests (from both tables) ---
// First get pending overtime requests
$pending_ot_stmt = $pdo->prepare("
    SELECT id, employee_id, date, start_time, end_time, reason, duration_hours, status, created_at, 'pending' as source_table
    FROM overtime_requests
    WHERE employee_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");
$pending_ot_stmt->execute([$current_user_id]);
$pending_ot_results = $pending_ot_stmt->fetchAll(PDO::FETCH_ASSOC);

// Then get processed overtime requests
$ot_stmt = $pdo->prepare("
    SELECT id, time_log_id, time_in, time_out, ot_duration, ot_type, reason, status, created_at, approved_at, approved_by, notified
    FROM post_ot_requests
    WHERE employee_id = ?
    ORDER BY COALESCE(approved_at, created_at) DESC
    LIMIT 10
");
$ot_stmt->execute([$current_user_id]);
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
        'request_id' => $ot['id'],
        'table_name' => 'overtime_requests',
        'source_table' => 'pending'
    ];
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
        'request_id' => $ot['id'],
        'table_name' => 'post_ot_requests',
        'source_table' => 'post'
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
