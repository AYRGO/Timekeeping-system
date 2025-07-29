<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../config/db.php');

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_id'], $_POST['action'])) {
    $leave_id = intval($_POST['leave_id']);
    $action = $_POST['action'];

    if (!in_array($action, ['approve', 'decline'])) {
        $message = "Invalid action specified.";
    } else {
        try {
            $pdo->beginTransaction();

            // Get the complete leave request data
            $stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE id = :id");
            $stmt->execute(['id' => $leave_id]);
            $leave_request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$leave_request) {
                throw new Exception("Leave request not found.");
            }

            if ($action === 'approve') {
                $employee_id = $leave_request['employee_id'];
                $leave_type = $leave_request['leave_type'];

                // Update status
                $leave_request['status'] = 'approved';

                // Decrease the corresponding leave credit by 1
                $stmt = $pdo->prepare("UPDATE leave_credits SET balance = balance - 1 WHERE employee_id = :employee_id AND leave_type = :leave_type");
                $stmt->execute([
                    'employee_id' => $employee_id,
                    'leave_type' => $leave_type
                ]);

            } elseif ($action === 'decline') {
                $explanation = trim($_POST['explanation'] ?? '');
                if (empty($explanation)) {
                    throw new Exception("Explanation is required for declining.");
                }
                $leave_request['explanation'] = $explanation;
                $leave_request['status'] = 'rejected';
            }

            // Insert into post_leave_requests table
            $stmt = $pdo->prepare("
                INSERT INTO post_leave_requests 
                (employee_id, leave_type, start_date, end_date, reason, status, 
                 attachment_lr, explanation, created_at, notified) 
                VALUES 
                (:employee_id, :leave_type, :start_date, :end_date, :reason, :status, 
                 :attachment_lr, :explanation, :created_at, :notified)
            ");
            
            $stmt->execute([
                'employee_id' => $leave_request['employee_id'],
                'leave_type' => $leave_request['leave_type'],
                'start_date' => $leave_request['start_date'],
                'end_date' => $leave_request['end_date'],
                'reason' => $leave_request['reason'],
                'status' => $leave_request['status'],
                'attachment_lr' => $leave_request['attachment_lr'],
                'explanation' => $leave_request['explanation'] ?? null,
                'created_at' => $leave_request['created_at'],
                'notified' => $leave_request['notified'] ?? 0
            ]);

            // Delete from leave_requests table
            $stmt = $pdo->prepare("DELETE FROM leave_requests WHERE id = :id");
            $stmt->execute(['id' => $leave_id]);

            // Create notification for the employee
            $notification_message = $action === 'approve' 
                ? "Your leave request has been approved by admin."
                : "Your leave request has been declined by admin.";
            
            $stmt = $pdo->prepare("
                INSERT INTO notifications (employee_id, message, type, created_at) 
                VALUES (:employee_id, :message, 'leave_response', NOW())
            ");
            $stmt->execute([
                'employee_id' => $leave_request['employee_id'],
                'message' => $notification_message
            ]);

            $pdo->commit();

            $action_text = $action === 'approve' ? 'approved' : 'declined';
            $message = "Leave request #$leave_id has been $action_text.";

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error processing request: " . $e->getMessage();
        }
    }
} else {
    $message = "Invalid form submission.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave Action Result</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="max-w-lg mx-auto mt-20 bg-white p-6 rounded shadow text-center">
        <h1 class="text-xl font-bold mb-4 text-gray-800">Action Result</h1>
        <p class="text-gray-700 mb-6"><?= htmlspecialchars($message) ?></p>
        <a href="leave_request_list.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
            ← Back to Leave Requests
        </a>
    </div>
</body>
</html>
