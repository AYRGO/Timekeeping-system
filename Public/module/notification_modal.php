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
        $mail->CharSet    = 'UTF-8';
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'it.resourcestaff@gmail.com';

        // ✅ Use Gmail App Password here (NOT your real Gmail password)
        $mail->Password   = 'fqbr ocgu jcfh jwdy';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
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
        error_log("Email could not be sent. PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
}


if ($current_user_id) {
    // Get employee info
    $emp_stmt = $pdo->prepare("SELECT fname, lname, personal_email FROM employees WHERE id = ?");
    $emp_stmt->execute([$current_user_id]);
    $employee = $emp_stmt->fetch(PDO::FETCH_ASSOC);

// --- Leave Requests ---
$leave_stmt = $pdo->prepare("
    SELECT id, leave_type, status, start_date, end_date, created_at, notified, explanation
    FROM leave_requests 
    WHERE employee_id = ?
    ORDER BY created_at DESC
    LIMIT 5
");
$leave_stmt->execute([$current_user_id]);
$leave_results = $leave_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($leave_results as $leave) {
    $raw_status = strtolower($leave['status']);
    $status = ucfirst($raw_status); // Directly capitalize: approved, rejected, etc.

    $range = date('F j', strtotime($leave['start_date'])) . ' to ' . date('F j', strtotime($leave['end_date']));
    $message = "Leave request for <strong>{$leave['leave_type']}</strong> ($range) was <strong>$status</strong>.";

    if ($raw_status === 'rejected' && !empty($leave['explanation'])) {
        $message .= "<br><span class='text-sm text-red-600'>Explanation: " . htmlspecialchars($leave['explanation']) . "</span>";
    }

    $notifications[] = ['message' => $message, 'created_at' => $leave['created_at']];

    if (in_array($raw_status, ['approved', 'rejected']) && !$leave['notified']) {
        $subject = "Leave Request {$status}";
        $body = "<p>Hi {$employee['fname']},<br>Your leave request from <strong>$range</strong> for <strong>{$leave['leave_type']}</strong> was <strong>$status</strong>.</p>";

        if ($raw_status === 'rejected' && !empty($leave['explanation'])) {
            $body .= "<p><strong>Explanation:</strong> " . nl2br(htmlspecialchars($leave['explanation'])) . "</p>";
        }

        if (sendEmail($employee['personal_email'], "{$employee['fname']} {$employee['lname']}", $subject, $body)) {
            $update = $pdo->prepare("UPDATE leave_requests SET notified = 1 WHERE id = ?");
            $update->execute([$leave['id']]);
        }
    }
}

    // --- Schedule Requests ---
    $schedule_stmt = $pdo->prepare("
        SELECT id, work_schedule_id, status, start_date, end_date, created_at, notified, explanation
        FROM schedule_change_requests 
        WHERE employee_id = ?
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $schedule_stmt->execute([$current_user_id]);
    $schedule_results = $schedule_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($schedule_results as $sched) {
        $status = ucfirst($sched['status']);
        $range = date('F j', strtotime($sched['start_date'])) . ' to ' . date('F j', strtotime($sched['end_date']));
        $message = "Schedule change request for ($range) was <strong>$status</strong>.";

        if (strtolower($sched['status']) === 'declined' && !empty($sched['explanation'])) {
            $message .= "<br><span class='text-sm text-red-600'>Explanation: " . htmlspecialchars($sched['explanation']) . "</span>";
        }

        $notifications[] = ['message' => $message, 'created_at' => $sched['created_at']];

        if (in_array(strtolower($sched['status']), ['approved', 'declined']) && !$sched['notified']) {
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


    // --- Time Adjustment Requests ---
$adjust_stmt = $pdo->prepare("
    SELECT id, log_date, status, reason, created_at, notified
    FROM time_adjustment_requests 
    WHERE employee_id = ?
    ORDER BY created_at DESC
    LIMIT 5
");
$adjust_stmt->execute([$current_user_id]);
$adjust_results = $adjust_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($adjust_results as $adjustment) {
    $status = ucfirst(strtolower($adjustment['status']));
    $date = date('F j', strtotime($adjustment['log_date']));
    $message = "Time adjustment request for <strong>$date</strong> was <strong>$status</strong>.";

    if (strtolower($adjustment['status']) === 'declined' && !empty($adjustment['reason'])) {
        $message .= "<br><span class='text-sm text-red-600'>Explanation: " . htmlspecialchars($adjustment['reason']) . "</span>";
    }

    $notifications[] = ['message' => $message, 'created_at' => $adjustment['created_at']];

    if (in_array(strtolower($adjustment['status']), ['approved', 'declined']) && !$adjustment['notified']) {
        $subject = "Time Adjustment Request {$status}";
        $body = "<p>Hi {$employee['fname']},<br>Your time adjustment request for <strong>$date</strong> was <strong>$status</strong>.</p>";

        if (strtolower($adjustment['status']) === 'declined' && !empty($adjustment['reason'])) {
            $body .= "<p><strong>Explanation:</strong> " . nl2br(htmlspecialchars($adjustment['reason'])) . "</p>";
        }

        if (sendEmail($employee['personal_email'], "{$employee['fname']} {$employee['lname']}", $subject, $body)) {
            $update = $pdo->prepare("UPDATE time_adjustment_requests SET notified = 1 WHERE id = ?");
            $update->execute([$adjustment['id']]);
        }
    }
}


// --- Overtime Requests ---
$ot_stmt = $pdo->prepare("
    SELECT id, date, start_time, end_time, status, explanation, created_at, notified
    FROM overtime_requests
    WHERE employee_id = ?
    ORDER BY created_at DESC
    LIMIT 5
");
$ot_stmt->execute([$current_user_id]);
$ot_results = $ot_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($ot_results as $ot) {
    $status = ucfirst($ot['status']);
    $date = date('F j, Y', strtotime($ot['date']));
    $message = "Overtime request on <strong>{$date}</strong> was <strong>{$status}</strong>.";

    if (strtolower($ot['status']) === 'rejected' && !empty($ot['explanation'])) {
        $message .= "<br><span class='text-sm text-red-600'>Explanation: " . htmlspecialchars($ot['explanation']) . "</span>";
    }

    $notifications[] = ['message' => $message, 'created_at' => $ot['created_at']];

    if (in_array(strtolower($ot['status']), ['approved', 'rejected']) && !$ot['notified']) {
        $subject = "Overtime Request $status";
        $body = "<p>Hi {$employee['fname']},<br>Your overtime request on <strong>$date</strong> was <strong>$status</strong>.</p>";

        if (!empty($ot['explanation'])) {
            $body .= "<p><strong>Explanation:</strong> " . nl2br(htmlspecialchars($ot['explanation'])) . "</p>";
        }

        if (sendEmail($employee['personal_email'], "{$employee['fname']} {$employee['lname']}", $subject, $body)) {
            $update = $pdo->prepare("UPDATE overtime_requests SET notified = 1 WHERE id = ?");
            $update->execute([$ot['id']]);
        }
    }
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

        if (modal && !modal.contains(event.target) && !button?.contains(event.target)) {
            modal.classList.add('hidden');
        }
    });
</script>
