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
