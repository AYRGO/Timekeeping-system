<?php
session_start();
include('../config/db.php');

if (!empty($_SESSION['demo_mode'])) {
    echo '<p class="text-gray-500">Comments are hidden in demo mode.</p>';
    exit;
}

// Force timezone to Asia/Manila (UTC+8)
date_default_timezone_set('Asia/Manila');

$announcementId = $_GET['announcement_id'] ?? null;

if (!$announcementId || !is_numeric($announcementId)) {
    http_response_code(400);
    echo '<p class="text-red-500">Invalid announcement ID.</p>';
    exit;
}

$current_user_id = $_SESSION['employee']['id'] ?? null;

$stmt = $pdo->prepare("
    SELECT c.content, c.created_at, e.fname, e.lname, c.employee_id
    FROM comments c
    JOIN employees e ON c.employee_id = e.id
    WHERE c.announcement_id = ?
    ORDER BY c.created_at ASC
");
$stmt->execute([$announcementId]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$comments) {
    echo '<p class="text-sm text-gray-400">No comments yet.</p>';
    exit;
}

foreach ($comments as $comment):
    $is_current_user = $comment['employee_id'] == $current_user_id;

    // Convert to Manila timezone (in case server saved in UTC)
    $datetime = new DateTime($comment['created_at'], new DateTimeZone('UTC'));
    $datetime->setTimezone(new DateTimeZone('Asia/Manila'));
    $formattedTime = $datetime->format('M j, Y · g:i A');
?>
    <div class="p-3 rounded border text-sm flex <?= $is_current_user ? 'justify-end bg-green-50 border-green-300' : 'bg-white' ?>">
        <div class="<?= $is_current_user ? 'text-right' : 'text-left' ?>">
            <p class="text-gray-800">
                <?= nl2br(htmlspecialchars($comment['content'])) ?>
            </p>
            <div class="text-xs text-gray-500 mt-1">
                <?= $formattedTime ?>
                <?= $is_current_user ? '- You' : '- ' . htmlspecialchars($comment['fname'] . ' ' . $comment['lname']) ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
