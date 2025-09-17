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


if ($current_user_id) {
    // Get employee info
    $emp_stmt = $pdo->prepare("SELECT fname, lname, personal_email FROM employees WHERE id = ?");
    $emp_stmt->execute([$current_user_id]);
    $employee = $emp_stmt->fetch(PDO::FETCH_ASSOC);

// --- Leave Requests (from both tables) ---
// Get pending leave requests first
$leave_stmt = $pdo->prepare("
    SELECT id, leave_type, status, start_date, end_date, created_at, notified, explanation, 'pending' as source_table
    FROM leave_requests 
    WHERE employee_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");
$leave_stmt->execute([$current_user_id]);
$leave_results = $leave_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get approved leave requests, excluding those already in pending
$approved_leave_stmt = $pdo->prepare("
    SELECT id, leave_type, status, start_date, end_date, created_at, notified, explanation, 'approved' as source_table
    FROM post_leave_requests
    WHERE employee_id = ? AND id NOT IN (
        SELECT id FROM leave_requests WHERE employee_id = ?
    )
    ORDER BY created_at DESC
    LIMIT 10
");
$approved_leave_stmt->execute([$current_user_id, $current_user_id]);
$approved_leave_results = $approved_leave_stmt->fetchAll(PDO::FETCH_ASSOC);

// Combine results
$leave_results = array_merge($leave_results, $approved_leave_results);

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
        'explanation' => $leave['explanation'] ?? ''
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
// Get pending schedule requests first
$schedule_stmt = $pdo->prepare("
    SELECT id, work_schedule_id, status, start_date, end_date, created_at, notified, explanation, 'pending' as source_table
    FROM schedule_change_requests 
    WHERE employee_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");
$schedule_stmt->execute([$current_user_id]);
$schedule_results = $schedule_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get approved schedule requests, excluding those already in pending
$approved_schedule_stmt = $pdo->prepare("
    SELECT id, work_schedule_id, status, start_date, end_date, created_at, notified, explanation, 'approved' as source_table
    FROM post_schedule_change_requests
    WHERE employee_id = ? AND id NOT IN (
        SELECT id FROM schedule_change_requests WHERE employee_id = ?
    )
    ORDER BY created_at DESC
    LIMIT 10
");
$approved_schedule_stmt->execute([$current_user_id, $current_user_id]);
$approved_schedule_results = $approved_schedule_stmt->fetchAll(PDO::FETCH_ASSOC);

// Combine results
$schedule_results = array_merge($schedule_results, $approved_schedule_results);

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
    'explanation' => $sched['explanation'] ?? ''
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
// Get pending time adjustment requests first
$adjust_stmt = $pdo->prepare("
    SELECT id, log_date, status, reason, created_at, notified, 'pending' as source_table
    FROM time_adjustment_requests 
    WHERE employee_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");
$adjust_stmt->execute([$current_user_id]);
$adjust_results = $adjust_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get approved time adjustment requests, excluding those already in pending
$approved_adjust_stmt = $pdo->prepare("
    SELECT id, log_date, status, '' as reason, created_at, notified, 'approved' as source_table
    FROM post_time_adjustment_requests
    WHERE employee_id = ? AND id NOT IN (
        SELECT id FROM time_adjustment_requests WHERE employee_id = ?
    )
    ORDER BY created_at DESC
    LIMIT 10
");
$approved_adjust_stmt->execute([$current_user_id, $current_user_id]);
$approved_adjust_results = $approved_adjust_stmt->fetchAll(PDO::FETCH_ASSOC);

// Combine results
$adjust_results = array_merge($adjust_results, $approved_adjust_results);

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
        'reason' => $adjustment['reason'] ?? ''
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

// --- Overtime Requests (from post_ot_requests table only) ---
$ot_stmt = $pdo->prepare("
    SELECT id, time_log_id, ot_duration, ot_type, reason, status, created_at, approved_at, approved_by, notified
    FROM post_ot_requests
    WHERE employee_id = ?
    ORDER BY COALESCE(approved_at, created_at) DESC
    LIMIT 10
");
$ot_stmt->execute([$current_user_id]);
$ot_results = $ot_stmt->fetchAll(PDO::FETCH_ASSOC);

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

    $notifications[] = [
        'message' => "Overtime request for <strong>{$ot_type}</strong> ({$duration} hours) was <strong>{$status}</strong>.",
        'created_at' => $created_at,
        'ot_status' => $status,
        'ot_reason' => $ot['reason'],
    ];
}


    // Remove duplicates based on message content and date
    $unique_notifications = [];
    $seen_messages = [];
    
    foreach ($notifications as $notification) {
        $message_key = md5($notification['message'] . $notification['created_at']);
        if (!in_array($message_key, $seen_messages)) {
            $seen_messages[] = $message_key;
            $unique_notifications[] = $notification;
        }
    }
    
    $notifications = $unique_notifications;

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
